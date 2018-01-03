<?php

namespace Dynamic\Base\Page;

use DNADesign\Elemental\Models\ElementalArea;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Forms\FieldList;

class ElementPage extends \Page implements PermissionProvider
{
    /**
     * @var string
     */
    private static $singular_name = 'Content Block Page';

    /**
     * @var string
     */
    private static $plural_name = 'Content Block Pages';

    /**
     * @var string
     */
    private static $description = 'Flexible page layout via content blocks';

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

    /**
     * @return FieldList
     */
    public function getCMSFields() {
        $fields = parent::getCMSFields();
        $fields->dataFieldByName('ElementalSidebar')->setTitle('Sidebar');
        $fields->dataFieldByName('ElementalArea')->setTitle('Main');
        return $fields;
    }

    /**
     * @param null|Member $member
     *
     * @return bool
     */
    public function canView($member = null, $context = [])
    {
        return true;
    }

    /**
     * @param null|Member $member
     * @return bool|int
     */
    public function canEdit($member = null, $context = [])
    {
        return Permission::check('Element_CRUD', 'any', $member);
    }

    /**
     * @param null|Member $member
     * @return bool|int
     */
    public function canDelete($member = null, $context = [])
    {
        return Permission::check('Element_CRUD', 'any', $member);
    }

    /**
     * @param null|Member $member
     * @return bool|int
     */
    public function canCreate($member = null, $context = [])
    {
        return Permission::check('Element_CRUD', 'any', $member);
    }

    /**
     * @return array
     */
    public function providePermissions()
    {
        return [
            'Element_CRUD' => [
                'name' => _t(
                    'BASE_SITE.ELEMENTPAGE_CRUD',
                    'Manage Content Block Pages'
                ),
                'category' => _t(
                    'Permissions.PERMISSIONS_BASE_SITE_PERMISSION',
                    'Base Website Permissions'
                ),
                'help' => _t(
                    'ElementPage.CREATE_PERMISSION_ELEMENTPAGE_PERMISSION',
                    'Ability to add, edit and delete content block pages'
                ),
                'sort' => 400,
            ]
        ];
    }
}
