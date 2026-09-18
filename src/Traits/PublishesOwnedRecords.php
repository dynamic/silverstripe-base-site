<?php

namespace Dynamic\Base\Traits;

use Psr\Log\LoggerInterface;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;

/**
 * Publishes a saved record's owned ($owns) children to Live.
 *
 * Extracted from TemplateDataExtension, where this behaviour shipped for
 * SocialLinks/UtilityLinks in dynamic/silverstripe-base-site#174, so the unversioned owners
 * in this module share one implementation: an unversioned owner's $owns declaration never
 * cascades a publish on its own, which is the bug #174 (SiteConfig) and #185
 * (NavigationGroup) both exist to fix.
 *
 * A consumer supplies the record to publish from via getOwnedRecordsOwner(), and must wire
 * BOTH entry points, because DataObject::write() reaches only one per save:
 *  - onAfterSkippedWrite(), provided here, for the branch where no column of the owner
 *    changed - which is what a real "add a link, click Save" CMS edit produces, since
 *    MultiLinkField persists the Link record directly.
 *  - onAfterWrite(), declared by the consumer, NOT here. A DataObject consumer must call
 *    parent::onAfterWrite() (it fetches generated columns and dispatches
 *    extend('onAfterWrite')); SilverStripe\Core\Extension declares no such parent method, so
 *    one shared body cannot serve both consumer kinds.
 *
 * Scope of the reuse: one level of $owns, each owned child a leaf. That limit is stated in
 * publishOwnedRecord() and is the reason a consumer that owns non-leaf records must revisit
 * it.
 *
 * @see \Dynamic\Base\Extension\TemplateDataExtension
 * @see \Dynamic\Base\Model\NavigationGroup
 */
trait PublishesOwnedRecords
{
    /**
     * The record whose $owns declarations drive publishOwnedRecords(): the class's own
     * instance for a DataObject consumer, $this->getOwner() for an Extension consumer.
     *
     * Typed deliberately - it is the only place a consumer's notion of "the owner" enters the
     * trait, and everything downstream calls findOwned(), validate() and reads
     * ObsoleteClassName/ID off it. SilverStripe\Core\Extension::getOwner() is untyped, so an
     * Extension consumer narrows it here.
     *
     * @return DataObject
     */
    abstract protected function getOwnedRecordsOwner(): DataObject;

    /**
     * The second of the two required entry points: write() takes this branch when no column
     * of the owner changed, which is what a real "add a link, click Save" edit produces.
     *
     * The trap: DataObject::preWrite() fires this SAME hook when validateWrite() *rejects*
     * the write, immediately before re-throwing that ValidationException. Nothing was
     * persisted on that path, so publishing here would take records live from a save the
     * editor was just told failed. shouldPublishOwnedRecordsOnSkippedWrite() is the gate
     * that tells the two call sites apart; consumers override it where they can answer
     * exactly.
     *
     * Public, and necessarily so: Extensible::invokeWithExtensions() reaches this hook through
     * Extension::invokeExtension(), i.e. from outside the object, and a consumer overriding it
     * must stay public too.
     *
     * Return type deliberately omitted, as on NavigationGroup's other write hooks: PHP rejects
     * a subclass that drops a return type its parent declared, so leaving these untyped is
     * what keeps a downstream project's conventional untyped override loadable.
     *
     * @see shouldPublishOwnedRecordsOnSkippedWrite()
     *
     * @return void
     */
    public function onAfterSkippedWrite()
    {
        if (!$this->shouldPublishOwnedRecordsOnSkippedWrite()) {
            return;
        }

        $this->publishOwnedRecords();
    }

    /**
     * Whether a write that changed no column of the owner should still publish its owned
     * records - i.e. whether this onAfterSkippedWrite() call came from the genuine no-changes
     * branch of DataObject::write() or from the validate-and-reject branch of preWrite().
     *
     * The default re-checks validate()/ObsoleteClassName. It is a heuristic, not the check
     * validateWrite() itself makes, and it exists only because an Extension consumer has
     * nothing better: extensions are Injector singletons shared across every owner record for
     * the life of the process, so a flag set from onBeforeWrite() would leak between
     * unrelated records. A DataObject consumer is one object per record and can answer
     * exactly - NavigationGroup overrides this to do just that.
     *
     * Known false negatives of this default, all failing safe (records stay in draft and are
     * re-attempted on the next save):
     *  - write($skipValidation: true) accepts a record validate() would reject; the write
     *    succeeds but this reads validate() as failing and skips publishing.
     *  - config validation_enabled = false makes validateWrite() skip validate() entirely
     *    (FixtureBlueprint sets it while loading fixtures), so nothing rejects the write, yet
     *    this still consults validate() and can skip publishing.
     *
     * @return bool
     */
    protected function shouldPublishOwnedRecordsOnSkippedWrite(): bool
    {
        $owner = $this->getOwnedRecordsOwner();

        if ($owner->ObsoleteClassName || !$owner->validate()->isValid()) {
            return false;
        }

        return true;
    }

    /**
     * Publishes every record currently owned (via $owns) by the owner record.
     *
     * Uses findOwned() - the framework's own $owns-driven traversal - rather than hardcoded
     * per-relation loops, so this stays correct if $owns ever gains another relation.
     *
     * Pinned to the draft reading stage regardless of ambient mode: outside a CMS request the
     * default reading mode is Stage.Live (Versioned::DEFAULT_MODE), under which findOwned()
     * would not return a draft-only owned record at all and it would be silently skipped.
     *
     * Records are attempted independently (see publishOwnedRecord()); one failure neither
     * stops this loop nor fails the owner's save. Nothing here is ever re-thrown:
     * DataObject::write() calls these hooks *before* resetting its isChanged() bookkeeping
     * and flushing its cache, so an exception escaping would leave the owner row committed
     * while the in-memory object still reported itself dirty, with stale cached lookups
     * elsewhere in the request - worse than a link staying in draft beside a logged error.
     *
     * @return void
     */
    protected function publishOwnedRecords(): void
    {
        Versioned::withVersionedMode(function (): void {
            Versioned::set_stage(Versioned::DRAFT);

            foreach ($this->getOwnedRecordsOwner()->findOwned(false) as $owned) {
                $this->publishOwnedRecord($owned);
            }
        });
    }

    /**
     * Publishes a single record owned (via $owns) by the owner record.
     *
     * The modification check is shallow, deliberately, and that is this trait's reuse
     * boundary. Versioned::isModifiedOnDraft() is isOnDraft() && stagesDiffer(), and
     * stagesDiffer() compares only this record's own draft and live version numbers - so an
     * owned record whose own version is unchanged but which owns a modified draft child is
     * skipped, silently. Accepted because no owned child here reaches it
     * (ExternalLink/EmailLink/PhoneLink/FileLink declare no $owns and carry no FileTracking,
     * so every record this visits is a leaf), and because the recursive alternative publishes
     * parents because a descendant moved - a bigger change than #174/#185 asked for, and
     * publishing too much is the harder failure to undo. A consumer that owns records which
     * themselves declare $owns MUST revisit this line.
     *
     * canPublish() is checked here because nothing downstream does: publishRecursive() hands
     * an inferred ChangeSet to ChangeSet::publish(), whose own docblock puts the canPublish()
     * call on the caller, and these owners aren't versioned - this trait is the only thing
     * that publishes these records at all. Permission resolves differently per owned type
     * (Social_CRUD for a SocialLink, FILE_EDIT_ALL for the logos, SiteConfig::canEdit() for
     * utility links, Link's own rules for a footer link), so a denial is per record.
     *
     * Exempt: a CLI process with no logged-in member - CLI dev/build, `dev/tasks/*`, test
     * fixtures - where there is no identity to check and gating them would stop those
     * contexts publishing anything, regressing #174. Anything with a current member is
     * checked, CLI or not. A web request is not exempt even when anonymous, because
     * SiteConfig::write() (and NavigationGroup::write()) enforce no permission of their own -
     * the CMS controller does, when it renders the form - and these hooks fire on every write
     * from any caller, framework code included. docs/SocialLinks.md covers what browser
     * dev/build actually needs.
     *
     * A denial logs a warning, not an error: nothing failed, the publish was not attempted.
     * The record keeps isModifiedOnDraft() true and stays owned, so it is reconsidered - and,
     * while the answer is unchanged, re-logged - on each later save. This delays a publish it
     * disapproves of rather than blocking it outright.
     *
     * @param DataObject $object
     * @return void
     */
    protected function publishOwnedRecord(DataObject $object): void
    {
        if (!$object->hasExtension(Versioned::class)) {
            return;
        }

        if (!$object->isModifiedOnDraft()) {
            return;
        }

        $member = Security::getCurrentUser();

        // Inside the try on purpose. canPublish() is not a read of a field: it dispatches into
        // project permission hooks and, for a File, into folder-permission lookups, so it can
        // throw the way the publish can. Outside, that throw would escape the write hook and
        // leave the committed row reporting itself dirty for the rest of the request - the
        // failure mode publishOwnedRecords() exists to prevent.
        try {
            if ((!Director::is_cli() || $member !== null) && !$object->canPublish($member)) {
                // Facts only - the reasoning belongs in the docblock above, not in a line this
                // method re-emits on every later save of the same record.
                Injector::inst()->get(LoggerInterface::class)->warning(sprintf(
                    'Skipped publishing %s #%d owned by %s #%d: canPublish() denied for %s, '
                    . 'left in draft.',
                    get_class($object),
                    $object->ID,
                    get_class($this->getOwnedRecordsOwner()),
                    $this->getOwnedRecordsOwner()->ID,
                    $member ? sprintf('member #%d', $member->ID) : 'no logged-in member'
                ));

                return;
            }

            $object->publishRecursive();
        } catch (\Exception $e) {
            // Two separate decisions, and they do not share a rationale:
            //
            // 1) Catch \Exception, not \Throwable. publishRecursive() -> ChangeSet::publish()
            //    raises ValidationException, BadMethodCallException, LogicException or
            //    UnexpectedDataException depending on what is wrong with this record - all
            //    \Exception. A genuine \Error (TypeError and friends) is a bug in the process,
            //    not a data condition, and logging it as a routine publish failure would
            //    misclassify it. So \Error propagates, and because there is no isolation above
            //    this point it aborts the rest of that loop and the save. That is the price of
            //    not misclassifying, not a chosen isolation strategy; docs/en/index.md
            //    documents the resulting half-applied save.
            //
            // 2) Log rather than re-throw. See publishOwnedRecords() for why re-throwing is
            //    unsafe. isModifiedOnDraft() stays true after a permanent validation failure, so
            //    this record is re-attempted and re-logged on every future save of the owner
            //    until its underlying problem is fixed.
            Injector::inst()->get(LoggerInterface::class)->error(sprintf(
                'Failed to publish %s #%d owned by %s #%d: %s',
                get_class($object),
                $object->ID,
                get_class($this->getOwnedRecordsOwner()),
                $this->getOwnedRecordsOwner()->ID,
                $e->getMessage()
            ), ['exception' => $e]);
        }
    }
}
