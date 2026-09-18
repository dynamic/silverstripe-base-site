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
 * An unversioned owner's $owns declaration never cascades a publish on its own. This trait is
 * what publishes them instead, and it is shared by the module's unversioned owners: SiteConfig
 * (through TemplateDataExtension) and NavigationGroup.
 *
 * Scope: one level of $owns, each owned child treated as a leaf. A consumer that owns records
 * which themselves declare $owns must revisit that check - see publishOwnedRecord().
 *
 * A consumer supplies the record through getOwnedRecordsOwner() and wires BOTH entry points,
 * because DataObject::write() reaches only one per save: onAfterSkippedWrite() (supplied here)
 * for a save that changed no column of the owner, and its own onAfterWrite() for a save that
 * did. They cannot share one body: a DataObject consumer must call parent::onAfterWrite(), and
 * SilverStripe\Core\Extension declares no such parent method.
 */
trait PublishesOwnedRecords
{
    /**
     * The record whose $owns declarations drive publishOwnedRecords(): the DataObject itself
     * for a DataObject consumer, getOwner() for an Extension consumer. Typed because everything
     * downstream calls findOwned(), validate() and reads ObsoleteClassName/ID off it.
     *
     * @return DataObject
     */
    abstract protected function getOwnedRecordsOwner(): DataObject;

    /**
     * Entry point for a write that changed no column of the owner - which is what an
     * "add a link, click Save" edit produces, since MultiLinkField persists the Link record
     * directly rather than touching the owner.
     *
     * DataObject::preWrite() fires this same hook when validateWrite() *rejects* the write,
     * immediately before re-throwing ValidationException. Nothing was persisted on that path, so
     * publishing unconditionally here would publish from a save the editor was just told failed.
     * shouldPublishOwnedRecordsOnSkippedWrite() separates the two call sites.
     *
     * Public because Extensible::invokeWithExtensions() reaches this hook from outside the
     * object. Return type omitted deliberately: PHP rejects a subclass that drops a return type
     * its parent declared, so leaving these untyped is what keeps a downstream project's
     * conventional untyped override loadable.
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
     * records: true for the genuine no-changes branch of write(), false for preWrite()'s
     * validate-and-reject branch, which fires the same hook.
     *
     * The default re-derives validate()/ObsoleteClassName. It is a heuristic, and it exists only
     * because an Extension consumer is an Injector singleton shared across every owner record for
     * the life of the process and so cannot hold per-record state. A DataObject consumer is one
     * object per record and can answer exactly - NavigationGroup overrides this with a flag.
     *
     * The heuristic's false negatives all fail safe, leaving records in draft to be re-attempted
     * on the next save: write($skipValidation: true) and config validation_enabled = false both
     * accept a write that validate() would object to, and this still reads that as a rejection.
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
     * Publish every record the owner currently owns, as found by the framework's own
     * $owns-driven findOwned() rather than hardcoded per-relation loops.
     *
     * Pinned to the draft reading stage: outside a CMS request the ambient mode is Live
     * (Versioned::DEFAULT_MODE), and findOwned() would then not return a draft-only record at
     * all.
     *
     * Nothing here is re-thrown. write() calls these hooks *before* resetting its change
     * tracking and flushing its cache, so an exception escaping would leave the owner row
     * committed while the in-memory object still reported itself dirty, with stale cached lookups
     * elsewhere in the request - worse than a record staying in draft beside a logged error.
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
     * Publish one owned record.
     *
     * The modification check is shallow: isModifiedOnDraft() compares only this record's own
     * draft and live versions, so an owned record that is itself the owner of a modified draft
     * child is skipped. That is this trait's reuse boundary.
     *
     * Every owned child here is a leaf as well, and the test for that is the child's effective
     * $owns resolved through findOwned() - not whether it declares relations, which is a different
     * question and answers it wrongly. linkfield's concrete Links all declare relations: FileLink
     * has has_one File, SiteTreeLink has has_one Page plus Anchor and QueryString in $db, and
     * every DataObject, Links included, inherits FileTracking from assets' FileLinkTracking. What
     * makes them leaves is that not one of those is a populated owns entry: no Link declares $owns
     * of its own, so its effective list is only the inherited FileTracking, and FileTracking is
     * filled from HTMLText shortcode usage, which a Link never has. findOwned() therefore returns
     * nothing for a Link, and publishRecursive() publishes the link alone.
     *
     * Which means a link's own target does not come live with it: publishing a FileLink leaves its
     * File in draft, and publishing a SiteTreeLink leaves its Page in draft. For a Page that is the
     * intent. For a File it is a live-but-broken-link gap this module does not close -
     * docs/en/index.md documents what that renders as, and dynamic/silverstripe-base-site#213
     * tracks closing it.
     *
     * canPublish() is checked because nothing downstream does: publishRecursive() hands an
     * inferred ChangeSet to ChangeSet::publish(), whose docblock puts that call on the caller,
     * and these owners aren't versioned - this trait is the only thing that publishes them. It
     * still defers to the record's own permission chain, so it is only as strong as that chain:
     * for a linkfield Link, canPublish() resolves through canEdit() to the owner's canEdit(),
     * and an owner that grants canEdit() to everyone (this module's NavigationGroup) therefore
     * never produces a denial here. docs/en/index.md documents that per chain.
     *
     * A denial logs a warning and leaves the record in draft, still flagged modified, so it is
     * reconsidered - and, while the answer is unchanged, re-logged - on each later save. This
     * delays a publish it disapproves of rather than blocking it outright.
     *
     * Every call that can throw is inside the try, the three guards and the publish alike.
     * isModifiedOnDraft() dispatches extend('updateIsOnDraft') and queries the stage tables, so it
     * throws the way canPublish() and publishRecursive() do. A guard outside the try would abort
     * the write hook mid-loop - committed row, dirty in-memory object, unflushed cache, remaining
     * links skipped - which is the state this method exists to avoid.
     *
     * @param DataObject $object
     * @return void
     */
    protected function publishOwnedRecord(DataObject $object): void
    {
        $member = Security::getCurrentUser();

        // Inside the try on purpose, all four: each guard reaches permission hooks and the database
        // as directly as the publish does (see above), and one of them throwing must not be the
        // reason the write hook aborts.
        try {
            if (!$object->hasExtension(Versioned::class)) {
                return;
            }

            if (!$object->isModifiedOnDraft()) {
                return;
            }

            // CLI-with-no-member is exempt: dev/build, dev/tasks/* and test fixtures have no
            // identity to check, and gating them would stop those contexts publishing anything.
            if ((!Director::is_cli() || $member !== null) && !$object->canPublish($member)) {
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
            // \Exception, not \Throwable: publishRecursive() raises ValidationException,
            // BadMethodCallException, LogicException or UnexpectedDataException depending on what
            // is wrong with this record. A genuine \Error is a bug in the process, not a data
            // condition, so it propagates rather than being logged as one - and since there is no
            // isolation above this point, it aborts the rest of the loop and the save. That cost
            // is accepted rather than misclassifying the failure; docs/en/index.md documents what
            // the resulting half-applied save leaves behind.
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
