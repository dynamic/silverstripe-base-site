<?php

namespace Dynamic\Base\Test;

use Dynamic\Base\Page\SearchPage;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;

class SearchPageTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = '../fixtures.yml';

    /**
     *
     */
    public function testCanView()
    {
        $object = $this->objFromFixture(SearchPage::class, 'default');

        $admin = $this->objFromFixture(Member::class, 'admin');
        $this->assertTrue($object->canView($admin));

        $siteowner = $this->objFromFixture(Member::class, 'site-owner');
        $this->assertTrue($object->canView($siteowner));

        $member = $this->objFromFixture(Member::class, 'default');
        $this->assertTrue($object->canView($member));
    }

    /**
     *
     */
    public function testCanEdit()
    {
        $object = $this->objFromFixture(SearchPage::class, 'default');

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
        $object = $this->objFromFixture(SearchPage::class, 'default');

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
        $object = $this->objFromFixture(SearchPage::class, 'default');

        $admin = $this->objFromFixture(Member::class, 'admin');
        $this->assertFalse($object->canCreate($admin));

        $siteowner = $this->objFromFixture(Member::class, 'site-owner');
        $this->assertFalse($object->canCreate($siteowner));

        $member = $this->objFromFixture(Member::class, 'default');
        $this->assertFalse($object->canCreate($member));
    }

    /*
     *
     */
    /*
    public function testProvidePermissions()
    {
        $object = $this->objFromFixture(SearchPage::class, 'default');
        $expected = array(
            'SearchPage_CRUD' => 'Create, Update and Delete a Search Page',
        );
        $this->assertEquals($expected, $object->providePermissions());
    }
    */
}
