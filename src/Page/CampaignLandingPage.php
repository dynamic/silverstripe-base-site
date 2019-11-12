<?php

namespace Dynamic\Base\Page;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;

class CampaignLandingPage extends \Page implements PermissionProvider
{
    /**
     * @var string
     */
    private static $singular_name = 'Campaign Landing Page';

    /**
     * @var string
     */
    private static $plural_name = 'Campaign Landing Pages';

    /**
     * @var string
     */
    private static $description = 'Create a landing page for your marketing campaign';

    /**
     * @var string
     */
    private static $table_name = 'CampaignLandingPage';

    /**
     * @var array
     */
    private static $defaults = [
        'ShowInMenus' => 0,
        'ShowInSearch' => 0,
    ];

    /**
     * @return mixed
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName(
            [
                'Sidebar',
                'ElementalSidebar',
            ]
        );

        return $fields;
    }

    /**
     * @param null|Member $member
     *
     * @return bool
     */
    public function canView($member = null, $context = [])
    {
        return parent::canView($member);
    }

    /**
     * @param null|Member $member
     *
     * @return bool|int
     */
    public function canEdit($member = null, $context = [])
    {
        return Permission::check('CamLan_CRUD', 'any', $member);
    }

    /**
     * @param null|Member $member
     *
     * @return bool|int
     */
    public function canDelete($member = null, $context = [])
    {
        return Permission::check('CamLan_CRUD', 'any', $member);
    }

    /**
     * @param null|Member $member
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

        return Permission::check('CamLan_CRUD', 'any', $member);
    }

    /**
     * @return array
     */
    public function providePermissions()
    {
        return [
            'CamLan_CRUD' => [
                'name' => _t(
                    'BASE_SITE.CAMLAN_CRUD',
                    'Manage Campaign Landing Page'
                ),
                'category' => _t(
                    'Permissions.PERMISSIONS_BASE_SITE_PERMISSION',
                    'Base Website Permissions'
                ),
                'help' => _t(
                    'CampaignLandingPage.CREATE_PERMISSION_TWOCOL_PERMISSION',
                    'Ability to manage campaign landing pages in the CMS'
                ),
                'sort' => 400,
            ],
        ];
    }
}
