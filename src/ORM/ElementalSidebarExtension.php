<?php

namespace Dynamic\Base\ORM;

use DNADesign\Elemental\Models\ElementalArea;
use DNADesign\Elemental\Models\ElementContent;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

class ElementalSidebarExtension extends DataExtension
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
        $sidebar = $fields->dataFieldByName('ElementalSidebar')->setTitle('Sidebar');
        $fields->removeByName('ElementalSidebar');
        $fields->addFieldToTab('Root.Sidebar', $sidebar);
    }
}