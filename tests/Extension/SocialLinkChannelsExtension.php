<?php

namespace Dynamic\Base\Test\Extension;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

/**
 * Test-only extension that mutates the social channel list through the
 * updateSocialChannels hook: it adds a channel, renames one, and removes one.
 */
class SocialLinkChannelsExtension extends Extension implements TestOnly
{
    /**
     * @param array<string,string> $channels
     */
    public function updateSocialChannels(array &$channels): void
    {
        $channels['bandcamp'] = 'Bandcamp';
        $channels['x'] = 'X';
        unset($channels['facebook']);
    }
}
