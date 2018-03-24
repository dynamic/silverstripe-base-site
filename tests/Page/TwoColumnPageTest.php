<?php

namespace Dynamic\Base\Test;

use Dynamic\Base\Page\HomePage;
use Dynamic\Base\Page\TwoColumnPage;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;

class TwoColumnPageTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = '../fixtures.yml';

    /**
     *
     */
    public function testGetCMSFields()
    {
        $object = $this->objFromFixture(TwoColumnPage::class, 'default');
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
    }

    /**
     *
     */
    public function testCanView()
    {
        $object = $this->objFromFixture(TwoColumnPage::class, 'default');

        $admin = $this->objFromFixture(Member::class, 'admin');
        $this->assertTrue($object->canView($admin));

        $siteowner = $this->objFromFixture(Member::class, 'site-owner');
        $this->assertTrue($object->canView($siteowner));

        $member = $this->objFromFixture(Member::class, 'default');
        $this->assertFalse($object->canView($member));
    }

    /**
     *
     */
    public function testCanEdit()
    {
        $object = $this->objFromFixture(TwoColumnPage::class, 'default');

        $admin = $this->objFromFixture(Member::class, 'admin');
        $this->assertTrue($object->canEdit($admin));

        $siteowner = $this->objFromFixture(Member::class, 'site-owner');
        $this->assertTrue($object->canEdit($siteowner));

        $member = $this->objFromFixture(Member::class, 'default');
        $this->assertFalse($object->canEdit($member));
    }

    /**
     *
     */
    public function testCanDelete()
    {
        $object = $this->objFromFixture(TwoColumnPage::class, 'default');

        $admin = $this->objFromFixture(Member::class, 'admin');
        $this->assertTrue($object->canDelete($admin));

        $siteowner = $this->objFromFixture(Member::class, 'site-owner');
        $this->assertTrue($object->canDelete($siteowner));

        $member = $this->objFromFixture(Member::class, 'default');
        $this->assertFalse($object->canDelete($member));
    }

    /**
     *
     */
    public function testCanCreate()
    {
        $object = $this->objFromFixture(TwoColumnPage::class, 'default');

        $admin = $this->objFromFixture(Member::class, 'admin');
        $this->assertTrue($object->canCreate($admin));

        $siteowner = $this->objFromFixture(Member::class, 'site-owner');
        $this->assertTrue($object->canCreate($siteowner));

        $member = $this->objFromFixture(Member::class, 'default');
        $this->assertFalse($object->canCreate($member));
    }

    /**
     *
     */
    public function testProvidePermissions()
    {
        $object = $this->objFromFixture(TwoColumnPage::class, 'default');
        $expected = array(
            'HomePage_CRUD' => 'Create, Update and Delete a Contact Page',
        );
        $this->assertTrue(is_array($object->providePermissions()));
    }
}
