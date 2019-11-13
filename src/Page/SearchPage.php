<?php

namespace Dynamic\Base\Page;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;

class SearchPage extends \Page implements PermissionProvider
{
    /**
     * @var string
     */
    private static $singular_name = 'Search Page';

    /**
     * @var string
     */
    private static $plural_name = 'Search Pages';

    /**
     * @var string
     */
    private static $description = 'Website search. Searches Title and Content field of each page.';

    /**
     * @var array
     */
    private static $defaults = array(
        'ShowInMenus' => 0,
    );

    /**
     * @var string
     */
    private static $table_name = 'SearchPage';

    /**
     * @param null $member
     *
     * @return bool
     */
    public function canView($member = null, $context = [])
    {
        return true;
    }

    /**
     * @param null $member
     *
     * @return bool|int
     */
    public function canEdit($member = null, $context = [])
    {
        return Permission::check('SearchPage_CRUD', 'any', $member);
    }

    /**
     * @param null $member
     *
     * @return bool|int
     */
    public function canDelete($member = null, $context = [])
    {
        return Permission::check('SearchPage_CRUD', 'any', $member);
    }

    /**
     * @param null $member
     *
     * @return bool|int
     */
    public function canCreate($member = null, $context = [])
    {
        $parent = isset($context['Parent']) ? $context['Parent'] : null;
        $strictParentInstance = ($parent && $parent instanceof SiteTree);
        if ($strictParentInstance && !in_array(static::class, $parent->allowedChildren())) {
            return false;
        }

        if (self::get()->first()) {
            return false;
        }

        return Permission::check('SearchPage_CRUD', 'any', $member);
    }

    /**
     * @return array
     */
    public function providePermissions()
    {
        return [
            'SearchPage_CRUD' => [
                'name' => _t(
                    'BASE_SITE.SEARCHPAGE_CRUD',
                    'Manage search pages'
                ),
                'category' => _t(
                    'Permissions.PERMISSIONS_BASE_SITE_PERMISSION',
                    'Base Website Permissions'
                ),
                'help' => _t(
                    'SearchPage.CREATE_PERMISSION_SEARCHPAGE_PERMISSION',
                    'Ability to add, edit and create Search pages'
                ),
                'sort' => 400,
            ],
        ];
    }
}
