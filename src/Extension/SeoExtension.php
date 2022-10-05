<?php

namespace Dynamic\Base\Extension;

use Axllent\CMSTweaks\MetadataTab;
use DNADesign\Elemental\Models\ElementalArea;
use DNADesign\Elemental\Models\ElementContent;
use QuinnInteractive\Seo\Extensions\PageSeoExtension;
use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use QuinnInteractive\Seo\Builders\FacebookMetaGenerator;
use QuinnInteractive\Seo\Extensions\PageHealthExtension;
use QuinnInteractive\Seo\Forms\GoogleSearchPreview;
use QuinnInteractive\Seo\Forms\HealthAnalysisField;
use SilverStripe\ORM\FieldType\DBField;

/**
 * Class SeoExtension
 * @package Dynamic\Base\Extension
 */
class SeoExtension extends DataExtension
{
    const META_CHAR_COUNT_MAX = 155;

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

        if (!$this->owner instanceof VirtualPage) {
            if ($page_title = $fields->dataFieldByName('Title')) {
                $page_title->setTargetLength(45, 25, 60);
            }
        }

        if (!$this->owner instanceof VirtualPage || in_array('MetaDescription', $this->owner->config()->get('non_virtual_fields'))) {
            if ($meta_description = $fields->dataFieldByName('MetaDescription')) {
                $meta_description->setTargetLength(130, 70, static::META_CHAR_COUNT_MAX);
            }
        }
    }

    public function MetaComponents(&$tags)
    {
        $metaLimit = $this->owner->config()->get('meta_description_character_limit')
            ?: static::META_CHAR_COUNT_MAX;

        /**
         * https://stackoverflow.com/a/35653771
         */
        if ($this->owner->MetaDescription && preg_match_all('/[^ \.]/', $this->owner->MetaDescription) > $metaLimit) {
            $tags['description'] = [
                'attributes' => [
                    'name' => 'description',
                    'content' => $this->owner->dbObject('MetaDescription')->LimitCharacters($metaLimit),
                ],
            ];
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
     * @return string|void
     */
    protected function generateElementPreview()
    {
        if ($this->owner->hasMethod('getElementsForSearch')) {
            return
                ltrim(
                    rtrim(
                        preg_replace("/\r|\n|\s+/", " ", $this->owner->getElementsForSearch())
                    )
                );
        }
    }

    /**
     * @return null
     *
     * @deprecated deprecated since version 4.0.9
     */
    protected function generateMetaDescription()
    {
        if ($this->owner->hasMethod('ElementalArea')) {
            /** @var ElementalArea $area */
            if ($area = $this->owner->ElementalArea()) {
                if ($content = $area->Elements()->filter('ClassName', ElementContent::class)->first()) {
                    return $content->dbObject('HTML')->LimitCharacters(150);
                }
            }
        }

        if ($this->owner->Content) {
            return $this->owner->dbObject('Content')->LimitCharacters(150);
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
            $this->owner->SearchContent = $this->generateElementPreview();
        } else {
            $this->owner->SearchContent = $this->owner->Content;
        }
    }
}
