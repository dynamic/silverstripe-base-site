<?php

namespace Dynamic\Base\ORM;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

class SidebarManager extends DataExtension
{
    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $sidebar = $fields->dataFieldByName('ElementalSidebar');
        if ($sidebar) {
            $sidebar->setTitle('Sidebar');
            $fields->removeByName('ElementalSidebar');
            $fields->addFieldToTab('Root.Sidebar', $sidebar);
        }
    }
}