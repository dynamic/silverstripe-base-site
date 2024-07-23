<?php

namespace Dynamic\Base\Extension;

use Dynamic\Base\Model\NavigationColumn;
use Dynamic\Base\Model\SocialLink;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\LinkField\Form\MultiLinkField;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\ORM\DataExtension;
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
        'Sociallinks',
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
                new GridFieldOrderableRows('SortOrder'),
                new GridFieldDeleteAction(false)
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
}
