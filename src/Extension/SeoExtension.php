<?php

namespace Dynamic\Base\Extension;

use DNADesign\Elemental\Models\ElementalArea;
use DNADesign\Elemental\Models\ElementContent;
use Innoweb\SocialMeta\Extensions\SiteTreeExtension;
use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Forms\FieldList;
use SilverStripe\Core\Extension;

/**
 * Class SeoExtension
 *
 * @property SiteTree|SeoExtension $owner
 * @property string $SearchContent
 */
class SeoExtension extends Extension
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
        if (class_exists(SiteTreeExtension::class)) {
            if ($meta_title = $fields->dataFieldByName('MetaTitle')) {
                $meta_title->setTargetLength(45, 25, 60);
            }
        }

        if (!$this->owner instanceof VirtualPage ||
            in_array('MetaDescription', $this->owner->config()->get('non_virtual_fields'))) {
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
