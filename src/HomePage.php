<?php

namespace Dynamic\Base\Page;

use DNADesign\Elemental\Models\ElementalArea;
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
        'ShowInMenus' => 0
    );

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
        return Permission::check('HomePage_CRUD', 'any', $member);
    }

    /**
     * @param null|Member $member
     * @return bool|int
     */
    public function canDelete($member = null, $context = [])
    {
        return Permission::check('HomePage_CRUD', 'any', $member);
    }

    /**
     * @param null|Member $member
     * @return bool|int
     */
    public function canCreate($member = null, $context = [])
    {
        if (!HomePage::get()->first()) {
            return Permission::check('HomePage_CRUD', 'any', $member);
        }
        return false;
    }

    /**
     * @return array
     */
    public function providePermissions()
    {
        return array(
            'HomePage_CRUD' => 'Create, Update and Delete a Contact Page',
        );
    }
}
