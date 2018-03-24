<?php

namespace Dynamic\Base\Page;

use DNADesign\Elemental\Models\ElementalArea;
use DNADesign\Elemental\Models\ElementContent;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;

class HomePage extends \Page implements PermissionProvider
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
    private static $defaults = array(
        'ShowInMenus' => 0,
    );

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
            'Sidebar',
        ]);

        $fields->dataFieldByName('ElementalHomePage')->setTitle('Content');

        return $fields;
    }

    /**
     * @param null|Member $member
     *
     * @return bool
     */
    public function canView($member = null, $context = [])
    {
        return Permission::check('HomePage_CRUD', 'any', $member);
    }

    /**
     * @param null|Member $member
     *
     * @return bool|int
     */
    public function canEdit($member = null, $context = [])
    {
        return Permission::check('HomePage_CRUD', 'any', $member);
    }

    /**
     * @param null|Member $member
     *
     * @return bool|int
     */
    public function canDelete($member = null, $context = [])
    {
        return Permission::check('HomePage_CRUD', 'any', $member);
    }

    /**
     * @param null|Member $member
     *
     * @return bool|int
     */
    public function canCreate($member = null, $context = [])
    {
        if (!self::get()->first()) {
            return Permission::check('HomePage_CRUD', 'any', $member);
        }

        return false;
    }

    /**
     * @return array
     */
    public function providePermissions()
    {
        return [
            'HomePage_CRUD' => [
                'name' => _t(
                    'BASE_SITE.HOMEPAGE_CRUD',
                    'Manage Home Page'
                ),
                'category' => _t(
                    'Permissions.PERMISSIONS_BASE_SITE_PERMISSION',
                    'Base Website Permissions'
                ),
                'help' => _t(
                    'Homepage.CREATE_PERMISSION_HOMEPAGE_PERMISSION',
                    'Ability to add, edit and delete home pages'
                ),
                'sort' => 400,
            ],
        ];
    }

    /**
     * @throws \SilverStripe\ORM\ValidationException
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (!$this->owner->ID) {
            if (!$this->owner->ElementalHomePageID) {
                $area = ElementalArea::create();
                $area->write();

                $this->owner->ElementAreaID = $area->ID;
            }
            $content = ElementContent::create();
            $content->Title = "Main Content";
            $content->ParentID = $this->owner->ElementalHomePage()->ID;
            $content->write();
        }
    }
}
