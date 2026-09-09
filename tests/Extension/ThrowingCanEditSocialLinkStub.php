<?php

namespace Dynamic\Base\Test\Extension;

use Dynamic\Base\Model\SocialLink;
use SilverStripe\Dev\TestOnly;

/**
 * A SocialLink whose canEdit() throws, proving publishOwnedRecord()'s permission check sits inside
 * the try that protects the SiteConfig write. Versioned::canPublish() falls through to
 * $owner->canEdit() for a non-ADMIN member, so the throw reaches canPublish() by the real
 * framework path rather than through a stand-in.
 *
 * Persisted flag rather than a static, matching ThrowingSocialLink: it survives the reload
 * findOwned() performs, so the record that throws is the record the loop iterates.
 */
class ThrowingCanEditSocialLinkStub extends SocialLink implements TestOnly
{
    /**
     * @var string
     */
    private static string $table_name = 'ThrowingCanEditSocialLinkStub';

    /**
     * @var array
     */
    private static array $db = [
        'ThrowOnCanEdit' => 'Boolean',
    ];

    /**
     * @param null|mixed $member
     * @param array $context
     * @return bool
     */
    public function canEdit($member = null, $context = [])
    {
        if ($this->ThrowOnCanEdit) {
            throw new \RuntimeException('Simulated failure from a permission hook for test coverage.');
        }

        return parent::canEdit($member, $context);
    }
}
