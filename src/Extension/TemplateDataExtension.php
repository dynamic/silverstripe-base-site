<?php

namespace Dynamic\Base\Extension;

use Dynamic\Base\Model\NavigationColumn;
use Dynamic\Base\Model\SocialLink;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Versioned\GridFieldArchiveAction;
use Symbiote\GridFieldExtensions\GridFieldAddExistingSearchButton;
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
 * @method ManyManyList|SiteTree[] UtilityLinks()
 */
class TemplateDataExtension extends DataExtension
{
    /**
     * @var array
     */
    private static $db = [
        'TitleLogo' => "Enum(array('Logo', 'Title'))",
    ];

    /**
     * @var array
     */
    private static $has_one = [
        'Logo' => Image::class,
        'LogoRetina' => Image::class,
    ];

    /**
     * @var array
     */
    private static $owns = [
        'Logo',
        'LogoRetina',
    ];

    /**
     * @var array
     */
    private static $has_many = [
        'NavigationColumns' => NavigationColumn::class,
        'SocialLinks' => SocialLink::class,
    ];

    /**
     * @var array
     */
    private static $many_many = [
        'UtilityLinks' => SiteTree::class,
    ];

    /**
     * @var array
     */
    private static $many_many_extraFields = [
        'UtilityLinks' => [
            'SortOrder' => 'Int',
        ],
    ];


    /**
     * @var array
     */
    private static $defaults = [
        'TitleLogo' => 'Title',
    ];

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        // options for logo or title display
        $logoOptions = [
            'Logo' => 'Display Logo',
            'Title' => 'Display Site Title and Slogan',
        ];

        $fields->addFieldsToTab('Root.Main', [
            //HeaderField::create('BrandingHD', 'Branding', 3),
            //LiteralField::create('HeaderDescrip', '<p>Adjust the settings of your template header.</p>'),
            $titlelogo = OptionsetField::create('TitleLogo', 'Branding', $logoOptions),
            //$title = TextField::create("Title", _t(SiteConfig::class . '.SITETITLE', "Site title")),
            //$tagline = TextField::create("Tagline", _t(SiteConfig::class . '.SITETAGLINE', "Site Tagline/Slogan")),
            // normal logos
            $logo = UploadField::create('Logo', 'Logo'),
            $retinaLogo = UploadField::create('LogoRetina', 'Retina Logo'),
        ]);

        //$title->hideUnless($titlelogo->getName())->isEqualTo('Title');
        //$tagline->hideUnless($titlelogo->getName())->isEqualTo('Title');

        $logo->hideUnless($titlelogo->getName())->isEqualTo('Logo');
        $retinaLogo->hideUnless($titlelogo->getName())->isEqualTo('Logo');

        if ($this->owner->ID) {
            // utility navigation
            $config = GridFieldConfig_RelationEditor::create()
                ->removeComponentsByType([
                    GridFieldAddNewButton::class,
                    GridFieldAddExistingAutocompleter::class,
                    GridFieldEditButton::class,
                    GridFieldArchiveAction::class,
                ])->addComponents(
                    new GridFieldOrderableRows('SortOrder'),
                    new GridFieldAddExistingSearchButton()
                );
            $linksField = GridField::create(
                'UtilityLinks',
                'Utility Navigation',
                $this->owner->UtilityLinks()->sort('SortOrder'),
                $config
            );

            $fields->addFieldsToTab('Root.Links.Utility', [
                $linksField
                    ->setDescription('Add links to the utility navigation area of your template'),
            ]);

            // footer navigation
            $config = GridFieldConfig_RecordEditor::create()->removeComponentsByType([
                GridFieldAddExistingAutocompleter::class,
                GridFieldDeleteAction::class,
            ])->addComponents(
                new GridFieldOrderableRows('SortOrder'),
                new GridFieldDeleteAction(false)
            );
            $footerLinks = GridField::create(
                'NavigationColumns',
                'Footer Navigation',
                $this->owner->NavigationColumns()->sort('SortOrder'),
                $config
            );

            $fields->addFieldsToTab('Root.Links.Footer', [
                $footerLinks
                    ->setDescription('Add columns to the footer area of your template. After you create a column,
                        you\'ll be able to add groups of links to the footer navigation'),
            ]);
        }

        // social links
        $config = GridFieldConfig_RecordEditor::create();
        $config->addComponent(new GridFieldOrderableRows('SortOrder'));

        $socialLinks = GridField::create(
            'SocialLinks',
            'Social Properties',
            $this->owner->SocialLinks()->sort('SortOrder'),
            $config
        );

        $fields->addFieldsToTab('Root.Links.Social', [
            $socialLinks
                ->setDescription('Add links to your social media properties'),
        ]);
    }
}
