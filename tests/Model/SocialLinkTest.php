<?php

namespace Dynamic\Base\Tests\Model;

use Dynamic\Base\Model\SocialLink;
use SilverStripe\Core\Config\Config;
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

    /**
     * Tests that the social_channels config setting replaces the hardcoded
     * fallback list rather than being ignored by it.
     *
     * The override has to be deliberately unlike the shipped list: the module's
     * own _config/social-channels.yml ships a map identical to the hardcoded
     * fallback, so asserting a default key proves nothing about the config read.
     */
    public function testGetSocialChannelsConfigOverride()
    {
        $channels = [
            'bandcamp' => 'Bandcamp',
            'tumblr' => 'Tumblr',
        ];

        Config::withConfig(function () use ($channels): void {
            Config::modify()->set(SocialLink::class, 'social_channels', $channels);

            $object = SocialLink::create();
            $this->assertSame($channels, $object->getSocialChannels());

            $object->SocialChannel = 'bandcamp';
            $this->assertSame('Bandcamp', $object->getSocialChannelName());

            // Facebook is configured by the shipped YAML, so it only stops
            // resolving if the override genuinely replaced that config.
            $object->SocialChannel = 'facebook';
            $this->assertNull($object->getSocialChannelName());
        });
    }

    /**
     * Tests that the CMS channel dropdown is built from the overridden config.
     */
    public function testGetCMSFieldsUsesConfigOverride()
    {
        $channels = [
            'bandcamp' => 'Bandcamp',
        ];

        Config::withConfig(function () use ($channels): void {
            Config::modify()->set(SocialLink::class, 'social_channels', $channels);

            $field = $this->objFromFixture(SocialLink::class, 'facebook')
                ->getCMSFields()
                ->dataFieldByName('SocialChannel');

            $this->assertNotNull($field);
            $this->assertSame($channels, $field->getSource());
        });
    }

    /**
     * Tests that an empty social_channels config falls back to the hardcoded
     * list, because getSocialChannels() tests the config value with ?:.
     */
    public function testGetSocialChannelsFallsBackOnEmptyConfig()
    {
        Config::withConfig(function (): void {
            Config::modify()->set(SocialLink::class, 'social_channels', []);

            $channels = SocialLink::create()->getSocialChannels();

            $this->assertNotEmpty($channels);
            $this->assertSame('Facebook', $channels['facebook'] ?? null);
            $this->assertArrayNotHasKey('bandcamp', $channels);
        });
    }
}
