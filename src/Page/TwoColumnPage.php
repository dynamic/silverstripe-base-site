<?php

namespace Dynamic\Base\Page;

use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;

class TwoColumnPage extends \Page implements PermissionProvider
{
    /**
     * @var string
     */
    private static $singular_name = 'Two Column Page';

    /**
     * @var string
     */
    private static $plural_name = 'Two Column Pages';

    /**
     * @var string
     */
    private static $description = 'Create your page using two columns of content blocks';

    /**
     * @param null|Member $member
     *
     * @return bool
     */
    public function canView($member = null, $context = [])
    {
        return Permission::check('TwoCol_CRUD', 'any', $member);
    }

    /**
     * @param null|Member $member
     *
     * @return bool|int
     */
    public function canEdit($member = null, $context = [])
    {
        return Permission::check('TwoCol_CRUD', 'any', $member);
    }

    /**
     * @param null|Member $member
     *
     * @return bool|int
     */
    public function canDelete($member = null, $context = [])
    {
        return Permission::check('TwoCol_CRUD', 'any', $member);
    }

    /**
     * @param null|Member $member
     *
     * @return bool|int
     */
    public function canCreate($member = null, $context = [])
    {
        return Permission::check('TwoCol_CRUD', 'any', $member);
    }

    /**
     * @return array
     */
    public function providePermissions()
    {
        return [
            'TwoCol_CRUD' => [
                'name' => _t(
                    'BASE_SITE.TWOCOL_CRUD',
                    'Manage Two Column Page'
                ),
                'category' => _t(
                    'Permissions.PERMISSIONS_BASE_SITE_PERMISSION',
                    'Base Website Permissions'
                ),
                'help' => _t(
                    '``.CREATE_PERMISSION_TWOCOL_PERMISSION',
                    'Ability to manage two column pages in the CMS'
                ),
                'sort' => 400,
            ],
        ];
    }
}
