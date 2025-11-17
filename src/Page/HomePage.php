<?php

namespace Dynamic\Base\Page;

use DNADesign\Elemental\Models\ElementalArea;
use DNADesign\Elemental\Models\ElementContent;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;

/**
 * Class \Dynamic\Base\Page\HomePage
 *
 * @property int $ElementalAreaID
 * @property int $HeaderImageID
 * @property int $ElementalHomePageID
 * @method ElementalArea ElementalArea()
 * @method HeaderImage HeaderImage()
 * @method ElementalArea ElementalHomePage()
 * @mixin HeaderImageExtension
 * @mixin ElementalPageExtension
 */
class HomePage extends \Page
{
    /**
     * @var string
     */
    private static $singular_name = 'Home Page';

    /**
     * @var string
     */
    private static $plural_name = 'Home Pages';

    /**
     * @var string
     */
    private static $description = 'Home page for your website';

    /**
     * @var array
     */
    private static $has_one = [
        'ElementalHomePage' => ElementalArea::class,
    ];

    /**
     * @var array
     */
    private static $owns = [
        'ElementalHomePage',
    ];

    /**
     * @var array
     */
    private static $defaults = [
        'ShowInMenus' => 0,
    ];

    /**
     * @var string
     */
    private static $table_name = 'HomePage';

    /**
     * @return \SilverStripe\Forms\FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'ElementalArea',
        ]);

        if ($block = $fields->dataFieldByName('ElementalHomePage')) {
            $block->setTitle('Content Blocks');
        }

        return $fields;
    }
}
