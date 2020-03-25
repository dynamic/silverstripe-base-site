<?php

namespace Dynamic\Base\Extension;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\ValidationException;
use Vulcan\Seo\Builders\FacebookMetaGenerator;
use Vulcan\Seo\Extensions\PageHealthExtension;
use Vulcan\Seo\Forms\GoogleSearchPreview;
use Vulcan\Seo\Forms\HealthAnalysisField;

/**
 * Class SeoExtension
 * @package Dynamic\Base\Extension
 */
class SeoExtension extends DataExtension
{
    /**
     * @var array
     */
    private static $db = [
        'SearchContent' => 'HTMLText',
    ];

    /**
     * @var array
     */
    private static $indexes = [
        'SearchFields' => [
            'type' => 'fulltext',
            'columns' => ['SearchContent'],
        ],
    ];

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        parent::updateCMSFields($fields);

        if ($this->owner instanceof \SilverStripe\ErrorPage\ErrorPage) {
            return;
        }

        $fields->removeByName([
            'SEOHealthAnalysis'
        ]);

        $fields->addFieldsToTab('Root.SEO', [
            ToggleCompositeField::create('SEOHealthAnalysis', 'SEO Health Analysis', [
                GoogleSearchPreview::create(
                    'GoogleSearchPreview',
                    'Search Preview',
                    $this->getOwner(),
                    $this->owner->getRenderedHtmlDomParser()
                ),
                TextField::create('FocusKeyword', 'Set focus keyword'),
                HealthAnalysisField::create('ContentAnalysis', 'Content Analysis', $this->getOwner()),
            ])
        ]);

        $fields->removeByName([
            'FacebookSeoComposite',
            'TwitterSeoComposite',
        ]);

        $fields->addFieldsToTab('Root.SEO', [
            ToggleCompositeField::create('FacebookSeoComposite', 'Facebook SEO', [
                DropdownField::create('FacebookPageType', 'Type', FacebookMetaGenerator::getValidTypes()),
                TextField::create('FacebookPageTitle', 'Title')
                    ->setAttribute('placeholder', $this->getOwner()->Title)
                    ->setRightTitle('If blank, inherits default page title')
                    ->setTargetLength(45, 25, 70),
                UploadField::create('FacebookPageImage', 'Image')
                    ->setRightTitle('Facebook recommends images to be 1200 x 630 pixels. If no image is
                        provided, facebook will choose the first image that appears on the page which usually has
                        bad results')
                    ->setFolderName('seo'),
                TextareaField::create('FacebookPageDescription', 'Description')
                    ->setAttribute(
                        'placeholder',
                        $this->getOwner()->MetaDescription ?:
                        $this->getOwner()->dbObject('Content')->LimitCharacters(297)
                    )
                    ->setRightTitle('If blank, inherits meta description if it exists or gets the first 297
                        characters from content')
                    ->setTargetLength(200, 160, 320),
            ]),
            ToggleCompositeField::create('TwitterSeoComposite', 'Twitter SEO', [
                TextField::create('TwitterPageTitle', 'Title')
                    ->setAttribute('placeholder', $this->getOwner()->Title)
                    ->setRightTitle('If blank, inherits default page title')
                    ->setTargetLength(45, 25, 70),
                UploadField::create('TwitterPageImage', 'Image')
                    ->setRightTitle('Must be at least 280x150 pixels')
                    ->setFolderName('seo'),
                TextareaField::create('TwitterPageDescription', 'Description')
                    ->setAttribute(
                        'placeholder',
                        $this->getOwner()->MetaDescription ?:
                        $this->getOwner()->dbObject('Content')->LimitCharacters(297)
                    )
                    ->setRightTitle('If blank, inherits meta description if it exists or gets the first 297
                        characters from content')
                    ->setTargetLength(200, 160, 320),
            ])
        ]);
    }

    /**
     * @return array
     */
    public function seoContentFields()
    {
        return [
            'SearchContent',
        ];
    }

    /**
     * @throws ValidationException
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // set Content to output of blocks for search
        if ($this->owner->hasMethod('getElementsForSearch')) {
            $this->owner->SearchContent =
                ltrim(
                    rtrim(
                        preg_replace("/\r|\n|\s+/", " ", $this->owner->getElementsForSearch())
                    )
                );
        } else {
            $this->owner->SearchContent = $this->owner->Content;
        }
    }
}
