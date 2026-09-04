<?php

namespace Dynamic\Base\Extension;

use Dynamic\Base\Model\NavigationColumn;
use Dynamic\Base\Model\SocialLink;
use Psr\Log\LoggerInterface;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
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
     * SiteConfig-owned column and would otherwise be silently skipped - reproducing this
     * PR's original bug on the most common editing path.
     *
     * DataObject::preWrite() *also* fires this same hook - via the identical
     * invokeWithExtensions('onAfterSkippedWrite') call - when validateWrite() rejects the
     * write, immediately before re-throwing that ValidationException; nothing was
     * persisted in that case, so publishing here would go live from a save the editor was
     * just told failed. A per-instance flag set from onBeforeWrite() can't reliably tell
     * these two call sites apart: Extension instances are Injector singletons (shared
     * across every SiteConfig for the life of the process - see setOwner()/clearOwner()'s
     * ownerStack, which only makes sense for a shared instance re-owned per hook call), so
     * any state written here would leak between unrelated records and requests. Asking the
     * owner to validate itself again is a few extra field checks per save, but it's
     * correct regardless of what happened on some other write earlier in the process.
     *
     * @return void
     */
    public function onAfterSkippedWrite(): void
    {
        if (!$this->getOwner()->validate()->isValid()) {
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

        try {
            $object->publishRecursive();
        } catch (\Exception $e) {
            // publishRecursive() -> ChangeSet::publish() can throw ValidationException,
            // BadMethodCallException, LogicException, or UnexpectedDataException depending
            // on what's wrong with this specific record - all \Exception, not \Error, so a
            // genuine programming error (TypeError etc.) still propagates instead of being
            // caught here. \Exception (not \Throwable) is the deliberate boundary: a
            // genuine \Error on one owned object still propagates out of the loop in
            // publishOwnedRecords() and stops every subsequent owned object in that save
            // from being attempted, unlike the per-object isolation \Exception gets.
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
