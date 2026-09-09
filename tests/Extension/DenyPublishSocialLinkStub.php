<?php

namespace Dynamic\Base\Test\Extension;

use Dynamic\Base\Model\SocialLink;
use SilverStripe\Dev\TestOnly;

/**
 * A SocialLink whose canEdit() denies outright, so that Versioned::canPublish() answers false for
 * a non-ADMIN member (it falls back to canEdit() once the ADMIN short-circuit and any extension
 * hooks have had their say). Driven by a persisted flag so it survives the reload findOwned()
 * performs - the same shape as ThrowingSocialLink's FailureMode.
 *
 * Denying at canEdit() rather than overriding canPublish() on this subclass is deliberate:
 * overriding canPublish() would shadow Versioned's implementation, and with it the ADMIN
 * short-circuit that testAdminStillPublishesEveryOwnedRecord exists to prove still works.
 */
class DenyPublishSocialLinkStub extends SocialLink implements TestOnly
{
    /**
     * @var string
     */
    private static string $table_name = 'DenyPublishSocialLinkStub';

    /**
     * @var array
     */
    private static array $db = [
        'DenyPublish' => 'Boolean',
    ];

    /**
     * @param null|mixed $member
     * @param array $context
     * @return bool
     */
    public function canEdit($member = null, $context = [])
    {
        if ($this->DenyPublish) {
            return false;
        }

        return parent::canEdit($member, $context);
    }
}
