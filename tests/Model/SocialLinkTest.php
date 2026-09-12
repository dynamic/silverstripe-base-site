<?php

namespace Dynamic\Base\Test\Model;

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

    /**
     * Every SocialLink fixture row must land in a column the ORM actually declares.
     *
     * This is the #188 regression guard. The fixtures used to set `Title`, `Link` and
     * `SortOrder`, none of which exist on SocialLink/ExternalLink/Link: the YAML fixture
     * parser has no unknown-key error, it quietly skips fields that aren't in $db, so
     * every row "loaded" while writing nothing at all. Asserting the persisted value of
     * each real column is what distinguishes a fixture that populates a record from one
     * that only creates an empty row with a matching fixture name.
     */
    public function testFixtureRowsPopulateDeclaredColumns(): void
    {
        $expected = [
            'facebook' => [
                'LinkText' => 'Facebook',
                'ExternalUrl' => 'https://facebook.com/example',
                'SocialChannel' => 'facebook',
                'Sort' => 1,
            ],
            'twitter' => [
                'LinkText' => 'Twitter',
                'ExternalUrl' => 'https://x.com/example',
                'SocialChannel' => 'x',
                'Sort' => 3,
            ],
            'linked' => [
                'LinkText' => 'LinkedIn',
                'ExternalUrl' => 'https://linkedin.com/company/example',
                'SocialChannel' => 'linkedin',
                'Sort' => 2,
            ],
        ];

        foreach ($expected as $name => $fields) {
            // objFromFixture() resolves through FixtureFactory::get(), whose last line is
            // DataObject::get($class)->byID($id) - so this already IS the row as it was
            // read back out of the database, not the in-memory object the blueprint
            // assembled. Re-fetching it by ID here would be a duplicate query that proves
            // nothing the returned object doesn't already prove.
            $object = $this->objFromFixture(SocialLink::class, $name);

            foreach ($fields as $column => $value) {
                $actual = $object->{$column};
                if ($column === 'Sort') {
                    $actual = (int) $actual;
                }
                $this->assertSame(
                    $value,
                    $actual,
                    sprintf('Fixture row "%s" has an unexpected "%s" column value', $name, $column)
                );
            }
        }
    }

    /**
     * getTitle() is a computed getter over LinkText, so it only returns the label the
     * old `Title:` fixture key appears to set if that text actually went into LinkText.
     */
    public function testFixtureRowsResolveTitleFromLinkText(): void
    {
        $this->assertSame('Facebook', $this->objFromFixture(SocialLink::class, 'facebook')->getTitle());
        $this->assertSame('Twitter', $this->objFromFixture(SocialLink::class, 'twitter')->getTitle());
        $this->assertSame('LinkedIn', $this->objFromFixture(SocialLink::class, 'linked')->getTitle());
    }

    /**
     * getSocialChannelName() and getIconClass() both read SocialChannel, which the
     * pre-#188 fixtures never set - so every row rendered as the fallback icon with a
     * null label. SocialChannelName is what the CMS GridField summary renders, and
     * IconClass is what the front-end social template renders.
     */
    public function testFixtureRowsResolveChannelLabelAndIcon(): void
    {
        $expected = [
            'facebook' => ['Facebook', 'bi-facebook'],
            'twitter' => ['X (Twitter)', 'bi-twitter-x'],
            'linked' => ['LinkedIn', 'bi-linkedin'],
        ];

        foreach ($expected as $name => [$label, $icon]) {
            $object = $this->objFromFixture(SocialLink::class, $name);
            $this->assertSame($label, $object->getSocialChannelName());
            $this->assertSame($icon, $object->getIconClass());
        }
    }

    /**
     * The fixture rows must order by Link's declared `Sort` column, which is also
     * Link::$default_sort and therefore the order MultiLinkField renders them in.
     * SortOrder never existed, so pre-#188 nothing ordered these rows deterministically.
     */
    public function testFixtureRowsOrderDefaultSort(): void
    {
        $this->assertSame(
            ['facebook', 'linkedin', 'x'],
            SocialLink::get()->column('SocialChannel')
        );
    }

    /**
     * getIconClass()'s no-match branch: a channel absent from the social_icons map falls
     * back to default_icon rather than warning on the missing array key. The mapped half
     * of this getter is covered by testFixtureRowsResolveChannelLabelAndIcon().
     *
     * The override is load-bearing, not decoration: getIconClass() hardcodes
     * 'bi-link-45deg' as its own `?:` fallback, which is also the shipped YAML value, so
     * asserting that literal passes whether or not the config was ever read. Overriding
     * it with a distinctive value is what makes this prove the config read - the same
     * reasoning testGetSocialChannelsConfigOverride() documents for its own override.
     */
    public function testUnmappedChannelUsesConfiguredFallbackIcon(): void
    {
        Config::withConfig(function (): void {
            Config::modify()->set(SocialLink::class, 'default_icon', 'zzz-configured-fallback');

            $object = SocialLink::create(['SocialChannel' => 'myspace']);

            $this->assertSame('zzz-configured-fallback', $object->getIconClass());
        });
    }
}
