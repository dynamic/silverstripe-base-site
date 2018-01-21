<?php

namespace Dynamic\Base\ORM;

use DNADesign\Elemental\Models\ElementalArea;
use DNADesign\Elemental\Models\ElementContent;
use SilverStripe\ORM\DataExtension;

class DefaultBlockExtension extends DataExtension
{
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
