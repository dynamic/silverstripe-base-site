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
     * Uses findOwned() (the framework's own $owns-driven traversal) rather than hardcoded
     * per-relation loops, so this stays correct if $owns ever gains another relation.
     *
     * Pinned to the draft reading stage regardless of ambient mode: the default reading
     * mode outside a CMS request is Stage.Live (Versioned::DEFAULT_MODE), under which a
     * draft-only owned record wouldn't be returned by findOwned() at all and would be
     * silently skipped.
     *
     * @return void
     */
    public function onAfterWrite(): void
    {
        Versioned::withVersionedMode(function (): void {
            Versioned::set_stage(Versioned::DRAFT);

            foreach ($this->getOwner()->findOwned(false) as $owned) {
                $this->publishOwned($owned);
            }
        });
    }

    /**
     * @param DataObject $object
     * @return void
     */
    private function publishOwned(DataObject $object): void
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
            // silently logged as a routine publish failure. One bad record's data shouldn't
            // break every future SiteConfig save, so isolate that case and log it instead.
            Injector::inst()->get(LoggerInterface::class)->error(sprintf(
                'Failed to publish %s #%d owned by SiteConfig #%d: %s',
                get_class($object),
                $object->ID,
                $this->getOwner()->ID,
                $e->getMessage()
            ));
        }
    }
}
