<?php

namespace Dynamic\Base\Page;

use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\UserForms\Model\UserDefinedForm;

class ContactPage extends UserDefinedForm implements PermissionProvider
{
    /**
     * @var string
     */
    private static $singular_name = "Contact Page";

    /**
     * @var string
     */
    private static $plural_name = "Contact Pages";

    /**
     * @var string
     */
    private static $description = 'Create a contact form. Includes company contact information and map';

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
        return Permission::check('Contact_CRUD', 'any', $member);
    }

    /**
     * @param null|Member $member
     * @return bool|int
     */
    public function canDelete($member = null, $context = [])
    {
        return Permission::check('Contact_CRUD', 'any', $member);
    }

    /**
     * @param null|Member $member
     * @return bool|int
     */
    public function canCreate($member = null, $context = [])
    {
        return Permission::check('Contact_CRUD', 'any', $member);
    }

    /**
     * @return array
     */
    public function providePermissions()
    {
        return array(
            'Contact_CRUD' => 'Create, Update and Delete a Contact Page',
        );
    }
}
