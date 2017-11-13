<?php

namespace Dynamic\Base\Page;

use DNADesign\Elemental\Models\ElementalArea;

class ElementPage extends \Page
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
        'ElementalSidebar'
    ];
}
