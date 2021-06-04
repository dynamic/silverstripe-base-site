<?php

namespace Dynamic\Base\Extension;

use Axllent\CMSTweaks\MetadataTab;
use DNADesign\Elemental\Models\ElementalArea;
use DNADesign\Elemental\Models\ElementContent;
use QuinnInteractive\Seo\Extensions\PageSeoExtension;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use QuinnInteractive\Seo\Builders\FacebookMetaGenerator;
use QuinnInteractive\Seo\Extensions\PageHealthExtension;
use QuinnInteractive\Seo\Forms\GoogleSearchPreview;
use QuinnInteractive\Seo\Forms\HealthAnalysisField;

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
        if (class_exists(MetadataTab::class)) {
            $config = Config::inst();
            $tab_title = $config->get(MetadataTab::class, 'tab_title');
        } else {
            $tab_title = 'SEO';
        }

        if (class_exists(PageHealthExtension::class)) {
            if ($pagehealth = $fields->fieldByName('Root.Main.SEOHealthAnalysis')) {
                $pagehealth_fields = $pagehealth->FieldList();
                $fields->removeFieldFromTab('Root.Main', 'SEOHealthAnalysis');

                $fields->addFieldsToTab(
                    'Root.' . $tab_title,
                    $pagehealth
                );
            }
        }

        if (class_exists(PageSeoExtension::class)) {
            $facebook = $fields->fieldByName('Root.Main.FacebookSeoComposite');
            $fields->removeFieldFromTab('Root.Main', 'FacebookSeoComposite');

            $fields->addFieldsToTab(
                'Root.' . $tab_title,
                $facebook
            );

            $twitter = $fields->fieldByName('Root.Main.TwitterSeoComposite');
            $fields->removeFieldFromTab('Root.Main', 'TwitterSeoComposite');

            $fields->addFieldsToTab(
                'Root.' . $tab_title,
                $twitter
            );
        }
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
     * @return null
     */
    protected function generateMetaDescription()
    {
        if ($this->owner->Content) {
            return $this->owner->dbObject('Content')->LimitCharacters(180);
        }

        if ($this->owner->hasMethod('ElementalArea')) {
            /** @var ElementalArea $area */
            if ($area = $this->owner->ElementalArea()) {
                if ($content = $area->Elements()->filter('ClassName', ElementContent::class)->first()) {
                    return $content->dbObject('HTML')->LimitCharacters(180);
                }
            }
        }

        return null;
    }

    /**
     *
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // set SearchContent to output of blocks for search
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

        // generate MetaDescription
        if (!$this->owner->MetaDescription) {
            $this->owner->MetaDescription = $this->generateMetaDescription();
        }
    }
}
