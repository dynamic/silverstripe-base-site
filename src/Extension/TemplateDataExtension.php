<?php

namespace Dynamic\Base\Extension;

use Dynamic\Base\Model\NavigationColumn;
use Dynamic\Base\Model\SocialLink;
use Psr\Log\LoggerInterface;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Control\Director;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\LinkField\Form\MultiLinkField;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * Class \Dynamic\Base\Extension\TemplateDataExtension
 *
 * @property SiteConfig|TemplateDataExtension $owner
 * @property string $TitleLogo
 * @property int $LogoID
 * @property int $LogoRetinaID
 * @method Image Logo()
 * @method Image LogoRetina()
 * @method DataList|NavigationColumn[] NavigationColumns()
 * @method DataList|SocialLink[] SocialLinks()
 * @method DataList|Link[] UtilityLinks()
 */
class TemplateDataExtension extends Extension
{
    /**
     * @var array
     */
    private static array $db = [
        'TitleLogo' => "Enum(array('Logo', 'Title'))",
    ];

    /**
     * @var array
     */
    private static array $has_one = [
        'Logo' => Image::class,
        'LogoRetina' => Image::class,
    ];

    /**
     * @var array
     */
    private static array $has_many = [
        'NavigationColumns' => NavigationColumn::class,
        'SocialLinks' => SocialLink::class . '.Owner',
        'UtilityLinks' => Link::class . '.Owner',
    ];

    /**
     * @var array
     */
    private static array $owns = [
        'Logo',
        'LogoRetina',
        'UtilityLinks',
        'SocialLinks',
    ];

    /**
     * @var array
     */
    private static array $defaults = [
        'TitleLogo' => 'Title',
    ];

    /**
     * @param FieldList $fields
     * @return void
     */
    public function updateCMSFields(FieldList $fields): void
    {
        // options for logo or title display
        $logoOptions = [
            'Logo' => 'Display Logo',
            'Title' => 'Display Site Title and Slogan',
        ];

        $fields->addFieldsToTab('Root.Main', [
            $titlelogo = OptionsetField::create('TitleLogo', 'Branding', $logoOptions),
            $logo = UploadField::create('Logo', 'Logo'),
            $retinaLogo = UploadField::create('LogoRetina', 'Retina Logo'),
        ]);

        $logo->hideUnless($titlelogo->getName())->isEqualTo('Logo');
        $retinaLogo->hideUnless($titlelogo->getName())->isEqualTo('Logo');

        if ($this->getOwner()->ID) {
            $fields->addFieldsToTab('Root.Links.Utility', [
                MultiLinkField::create('UtilityLinks')
                    ->setTitle('Utility Links')
                    ->setDescription('Add links to the utility navigation area of your template'),
            ]);

            // footer navigation
            $config = GridFieldConfig_RecordEditor::create()->removeComponentsByType([
                GridFieldAddExistingAutocompleter::class,
                GridFieldDeleteAction::class,
            ])->addComponents(
                GridFieldOrderableRows::create('SortOrder'),
                GridFieldDeleteAction::create(false)
            );
            $footerLinks = GridField::create(
                'NavigationColumns',
                'Footer Navigation',
                $this->getOwner()->NavigationColumns()->sort('SortOrder'),
                $config
            );

            $fields->addFieldsToTab('Root.Links.Footer', [
                $footerLinks
                    ->setDescription('Add columns to the footer area of your template. After you create a column,
                        you\'ll be able to add groups of links to the footer navigation'),
            ]);
        }

        $fields->addFieldsToTab('Root.Links.Social', [
            MultiLinkField::create('SocialLinks')
                ->setDescription('Add links to your social media properties')
                ->setAllowedTypes([
                    SocialLink::class,
                ]),
        ]);
    }

    /**
     * SiteConfig itself isn't versioned, so the `$owns` ownership declaration above never
     * cascades a publish to its owned records (Logo, LogoRetina, SocialLinks, UtilityLinks
     * are all Versioned). Publish them explicitly whenever the owner is saved so CMS edits
     * go live without a separate manual publish step.
     *
     * @return void
     */
    public function onAfterWrite(): void
    {
        $this->publishOwnedRecords();
    }

    /**
     * DataObject::write() only calls onAfterWrite() when a field on the record itself
     * actually changed value; otherwise it takes this onAfterSkippedWrite() branch
     * instead. That's exactly what a real "add a social link, click Save" CMS edit
     * produces: MultiLinkField/GridField save the SocialLink/UtilityLink/Logo record
     * directly, so a SiteConfig save that only touches an owned record changes no
     * SiteConfig-owned column and would otherwise be silently skipped.
     *
     * DataObject::preWrite() *also* fires this same hook - via the identical
     * invokeWithExtensions('onAfterSkippedWrite') call - when validateWrite() rejects the
     * write, immediately before re-throwing that ValidationException; nothing was
     * persisted in that case, so publishing here would go live from a save the editor was
     * just told failed. A per-instance flag set from onBeforeWrite() can't reliably tell
     * these two call sites apart: Extension instances are Injector singletons (shared
     * across every SiteConfig for the life of the process - see setOwner()/clearOwner()'s
     * ownerStack, which only makes sense for a shared instance re-owned per hook call), so
     * any state written here would leak between unrelated records and requests.
     *
     * Re-checking validate()/ObsoleteClassName is a heuristic for "did preWrite() reject
     * this", not the exact same check validateWrite() itself makes (it can't be - nothing
     * this extension can observe distinguishes a rejected write from a genuine
     * no-changes one beyond re-deriving what validateWrite() would have concluded). It
     * mirrors validateWrite()'s two rejection paths in order: ObsoleteClassName is checked
     * first (an object of a class that no longer exists), then validate() (field/business
     * rules, including via updateValidate()). It does not account for
     * write($skipValidation: true) - a call which accepts an object validate() would
     * reject - which would then be misread as a rejected write and its owned records left
     * unpublished, even though the write actually succeeded.
     *
     * @return void
     */
    public function onAfterSkippedWrite(): void
    {
        $owner = $this->getOwner();

        if ($owner->ObsoleteClassName || !$owner->validate()->isValid()) {
            return;
        }

        $this->publishOwnedRecords();
    }

    /**
     * Publishes every record currently owned (via $owns) by the extension's owner.
     *
     * Uses findOwned() (the framework's own $owns-driven traversal) rather than hardcoded
     * per-relation loops, so this stays correct if $owns ever gains another relation.
     *
     * Pinned to the draft reading stage regardless of ambient mode: the default reading
     * mode outside a CMS request is Stage.Live (Versioned::DEFAULT_MODE), under which a
     * draft-only owned record wouldn't be returned by findOwned() at all and would be
     * silently skipped.
     *
     * Every owned record is attempted independently of its siblings (see
     * publishOwnedRecord()) - one record's publish failure is logged and doesn't stop the
     * rest of this loop from running, and doesn't stop SiteConfig's own save from
     * succeeding. It's deliberately never re-thrown from here: DataObject::write() calls
     * onAfterWrite()/onAfterSkippedWrite() *before* resetting its own isChanged()
     * bookkeeping and flushing its cache, so an exception thrown from inside this method
     * would leave the SiteConfig row committed to the database but the in-memory object
     * still reporting itself dirty, with stale cached lookups elsewhere in the same
     * request - a worse failure mode than a link staying in draft with a logged error.
     *
     * @return void
     */
    protected function publishOwnedRecords(): void
    {
        Versioned::withVersionedMode(function (): void {
            Versioned::set_stage(Versioned::DRAFT);

            foreach ($this->getOwner()->findOwned(false) as $owned) {
                $this->publishOwnedRecord($owned);
            }
        });
    }

    /**
     * Publishes a single record owned (via $owns) by the extension's owner.
     *
     * The record must be publishable by whoever is asking, and nothing downstream checks that:
     * publishRecursive() hands an inferred ChangeSet to ChangeSet::publish(), whose own docblock
     * puts the canPublish() call on the caller. SiteConfig isn't versioned, so this extension is
     * the only thing that publishes these records at all (see onAfterWrite()) - without a check
     * here, the right to save Site Settings would be the right to publish everything it owns.
     *
     * Permission resolves differently per owned type - Social_CRUD for a SocialLink, FILE_EDIT_ALL
     * for the logos, SiteConfig::canEdit() for utility links - so a denial is per record: siblings
     * still publish and the save still succeeds. It does not reach records that an owned record owns
     * in turn; publishRecursive() writes those without re-checking. Nothing here declares $owns on
     * SocialLink, Link or Image so there is no second level today, but a project subclass that adds
     * one reopens the bypass a level down, and closing that means checking the built ChangeSet
     * rather than the single record.
     *
     * Exempt: a CLI process with no logged-in member - CLI dev/build, `dev/tasks/*`, test fixtures
     * - where there is no identity to check and gating them would stop those contexts publishing
     * anything, regressing #174. Anything with a current member is checked, CLI or not. A web
     * request is not exempt even when anonymous, because SiteConfig::write() enforces no permission
     * of its own - the CMS controller does, when it renders the form - and these hooks fire on
     * every write from any caller, framework code included. "No current member" is therefore not
     * the same as "trusted". docs/SocialLinks.md covers what browser dev/build actually needs.
     *
     * A denial logs a warning, not an error: nothing failed, the publish was not attempted. The
     * record keeps isModifiedOnDraft() true and stays owned by SiteConfig, so it is reconsidered -
     * and, while the answer is unchanged, re-logged - on each later save. The editor gets no notice
     * of the skip beyond that log line; the record is parked in draft, not sealed there, because the
     * CLI-with-no-member exemption above applies to those later saves too. So this delays a publish
     * it disapproves of rather than blocking it outright, and docs/SocialLinks.md states that in
     * full.
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
        // project permission hooks and, for a File, into folder-permission lookups, so it can throw
        // the way the publish can. Outside, that throw would escape onAfterWrite() and leave the
        // committed row reporting itself dirty for the rest of the request - the failure mode
        // publishOwnedRecords() exists to prevent.
        try {
            if ((!Director::is_cli() || $member !== null) && !$object->canPublish($member)) {
                // Facts only - the reasoning belongs in the docblock and docs/SocialLinks.md,
                // not in a line this method re-emits on every later save of the same record.
                Injector::inst()->get(LoggerInterface::class)->warning(sprintf(
                    'Skipped publishing %s #%d owned by %s #%d: canPublish() denied for %s, '
                    . 'left in draft.',
                    get_class($object),
                    $object->ID,
                    get_class($this->getOwner()),
                    $this->getOwner()->ID,
                    $member ? sprintf('member #%d', $member->ID) : 'no logged-in member'
                ));

                return;
            }

            $object->publishRecursive();
        } catch (\Exception $e) {
            // publishRecursive() -> ChangeSet::publish() can throw ValidationException,
            // BadMethodCallException, LogicException, or UnexpectedDataException depending
            // on what's wrong with this specific record - all \Exception, not \Error.
            // Catching \Exception (not \Throwable) means a genuine \Error (TypeError etc.)
            // still propagates out of this method instead of being swallowed as if it were
            // a routine, data-level publish failure - it isn't ours to catch and log as
            // one. That does mean an \Error on one owned object aborts the rest of the
            // loop in publishOwnedRecords(), unlike the per-object isolation \Exception
            // gets here; that's an accepted consequence of not misclassifying it, not a
            // deliberately chosen isolation strategy.
            //
            // Covers both guarded calls: publishRecursive() and the canPublish() check above it.
            //
            // Logged rather than re-thrown, including for ValidationException - see
            // publishOwnedRecords() for why re-throwing from here is unsafe. Note
            // isModifiedOnDraft() staying true after a permanent validation failure means
            // this record will keep being attempted (and re-logged) on every future
            // SiteConfig save until its underlying validation problem is fixed.
            Injector::inst()->get(LoggerInterface::class)->error(sprintf(
                'Failed to publish %s #%d owned by %s #%d: %s',
                get_class($object),
                $object->ID,
                get_class($this->getOwner()),
                $this->getOwner()->ID,
                $e->getMessage()
            ), ['exception' => $e]);
        }
    }
}
