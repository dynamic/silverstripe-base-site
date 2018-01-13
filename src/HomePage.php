<?php

namespace Dynamic\Base\Page;

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
    private static $defaults = array(
        'ShowInMenus' => 0,
    );

    /**
     * @var string
     */
    private static $table_name = 'HomePage';

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
}
