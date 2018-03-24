<?php

namespace Dynamic\Base\ORM;

use DNADesign\Elemental\Models\ElementalArea;
use DNADesign\Elemental\Models\ElementContent;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

class ElementalSiteTreeDataExtension extends DataExtension
{
    /**
     * @var array
     */
    private static $has_one = [
        'ElementalSidebar' => ElementalArea::class,
    ];

    /**
     * @var array
     */
    private static $owns = [
        'ElementalSidebar',
    ];

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        if (!$this->owner->hasExtension(SidebarManager::class)) {
            $fields->removeByName('ElementalSidebar');
        }

        $content = $fields->dataFieldByName('ElementalArea');
        if ($content) {
            $content->setTitle('Content');
        }
    }

    /**
     * @throws \SilverStripe\ORM\ValidationException
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (!$this->owner->ID) {
            if (!$this->owner->ElementAreaID) {
                $area = ElementalArea::create();
                $area->write();

                $this->owner->ElementAreaID = $area->ID;
            }
            $content = ElementContent::create();
            $content->Title = "Main Content";
            $content->ParentID = $this->owner->ElementalArea()->ID;
            $content->write();
        }
    }
}
