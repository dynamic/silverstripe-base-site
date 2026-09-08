<?php

namespace Dynamic\Base\Test\Extension;

use Dynamic\Base\Model\SocialLink;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

/**
 * Class SocialLinkChannelsExtensionTest.
 */
class SocialLinkChannelsExtensionTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = '../fixtures.yml';

    /**
     * Scopes SocialLinkChannelsExtension to this test class only, so it cannot
     * leak into the tests that assert against the shipped channel list.
     *
     * @var array
     */
    protected static $required_extensions = [
        SocialLink::class => [
            SocialLinkChannelsExtension::class,
        ],
    ];

    /**
     * Tests that getSocialChannels() reflects the updateSocialChannels hook.
     */
    public function testUpdateSocialChannelsCanAddRenameAndRemoveChannels()
    {
        $channels = SocialLink::create()->getSocialChannels();

        $this->assertSame('Bandcamp', $channels['bandcamp'] ?? null);
        $this->assertSame('X', $channels['x'] ?? null);
        $this->assertArrayNotHasKey('facebook', $channels);
    }

    /**
     * Tests getSocialChannelName() through the updateSocialChannels hook.
     */
    public function testGetSocialChannelNameThroughExtensionHook()
    {
        $object = SocialLink::create();

        $object->SocialChannel = 'bandcamp';
        $this->assertSame('Bandcamp', $object->getSocialChannelName());

        $object->SocialChannel = 'x';
        $this->assertSame('X', $object->getSocialChannelName());

        // A channel the hook removed is no longer resolvable, even though the
        // shipped config still lists it.
        $object->SocialChannel = 'facebook';
        $this->assertNull($object->getSocialChannelName());
    }

    /**
     * Tests that the hook mutates the configured list rather than the hardcoded
     * fallback, because getSocialChannels() reads social_channels before it runs
     * updateSocialChannels.
     */
    public function testUpdateSocialChannelsRunsAfterConfigOverride()
    {
        Config::withConfig(function (): void {
            Config::modify()->set(SocialLink::class, 'social_channels', [
                'facebook' => 'Facebook',
                'x' => 'X (Twitter)',
                'tumblr' => 'Tumblr',
            ]);

            $channels = SocialLink::create()->getSocialChannels();

            // Removal and renaming apply to what the config provided.
            $this->assertArrayNotHasKey('facebook', $channels);
            $this->assertSame('X', $channels['x'] ?? null);
            // A configured channel the hook does not touch survives.
            $this->assertSame('Tumblr', $channels['tumblr'] ?? null);
            // The hook adds to the configured list, not to the fallback list.
            $this->assertSame('Bandcamp', $channels['bandcamp'] ?? null);
        });
    }
}
