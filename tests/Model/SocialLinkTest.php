<?php

namespace Dynamic\Base\Tests\Model;

use Dynamic\Base\Model\SocialLink;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;

/**
 * Class SocialLinkTest.
 */
class SocialLinkTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = '../fixtures.yml';

    /**
     * Tests getCMSFields().
     */
    public function testGetCMSFields()
    {
        $object = $this->objFromFixture(SocialLink::class, 'facebook');
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
    }

    /**
     * Tests canView().
     */
    public function testCanView()
    {
        $object = $this->objFromFixture(SocialLink::class, 'facebook');

        $admin = $this->objFromFixture(Member::class, 'admin');
        $this->assertTrue($object->canView($admin));

        $siteowner = $this->objFromFixture(Member::class, 'site-owner');
        $this->assertTrue($object->canView($siteowner));

        $member = $this->objFromFixture(Member::class, 'default');
        $this->assertTrue($object->canView($member));
    }

    /**
     * Tests canCreate().
     */
    public function testCanCreate()
    {
        $object = $this->objFromFixture(SocialLink::class, 'facebook');

        $admin = $this->objFromFixture(Member::class, 'admin');
        $this->assertTrue($object->canCreate($admin));

        $siteowner = $this->objFromFixture(Member::class, 'site-owner');
        $this->assertTrue($object->canCreate($siteowner));

        $member = $this->objFromFixture(Member::class, 'default');
        $this->assertFalse($object->canCreate($member));
    }

    /**
     * Tests canEdit().
     */
    public function testCanEdit()
    {
        $object = $this->objFromFixture(SocialLink::class, 'facebook');

        $admin = $this->objFromFixture(Member::class, 'admin');
        $this->assertTrue($object->canEdit($admin));

        $siteowner = $this->objFromFixture(Member::class, 'site-owner');
        $this->assertTrue($object->canEdit($siteowner));

        $member = $this->objFromFixture(Member::class, 'default');
        $this->assertFalse($object->canEdit($member));
    }

    /**
     * Tests canDelete().
     */
    public function testCanDelete()
    {
        $object = $this->objFromFixture(SocialLink::class, 'facebook');

        $admin = $this->objFromFixture(Member::class, 'admin');
        $this->assertTrue($object->canDelete($admin));

        $siteowner = $this->objFromFixture(Member::class, 'site-owner');
        $this->assertTrue($object->canDelete($siteowner));

        $member = $this->objFromFixture(Member::class, 'default');
        $this->assertFalse($object->canDelete($member));
    }

    /**
     * Tests getSocialChannelName().
     */
    public function testGetSocialChannelName()
    {
        $object = SocialLink::create();

        // A freshly-created, unsaved record has a null SocialChannel until one is
        // chosen -- the CMS GridField's summary column calls this getter for every
        // row, including ones in this state, so it must not warn/deprecate on null.
        $this->assertNull($object->getSocialChannelName());

        $object->SocialChannel = 'facebook';
        $this->assertSame('Facebook', $object->getSocialChannelName());

        $object->SocialChannel = 'myspace';
        $this->assertNull($object->getSocialChannelName());

        $object->SocialChannel = '';
        $this->assertNull($object->getSocialChannelName());

        $object->SocialChannel = null;
        $this->assertNull($object->getSocialChannelName());
    }

    /**
     * Tests ProvidePermissions().
     */
    public function testProvidePermissions()
    {
        $object = $this->objFromFixture(SocialLink::class, 'facebook');
        $expected = array(
            'Social_CRUD' => 'Create, Update and Delete a Social Link',
        );
        $this->assertEquals($expected, $object->providePermissions());
    }
}
