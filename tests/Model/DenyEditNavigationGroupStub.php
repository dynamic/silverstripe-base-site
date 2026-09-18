<?php

namespace Dynamic\Base\Test\Model;

use Dynamic\Base\Model\NavigationGroup;
use SilverStripe\Dev\TestOnly;

/**
 * A NavigationGroup whose canEdit() denies, so the publish gate in
 * PublishesOwnedRecords::publishOwnedRecord() has something to deny on.
 *
 * The chain it exercises is the one a downstream project reaches to gate footer publishing:
 * Link::canPublish() -> Versioned::canPublish() (ADMIN short-circuit and extension hooks first)
 * -> Link::canEdit() -> Link::canPerformAction('canEdit') -> $this->Owner()->canEdit(). Subclass
 * rather than extension is deliberate twice over: NavigationGroup::canEdit() returns true without
 * consulting extendedCan(), so no Extension can override it; and denying on the owner's canEdit()
 * rather than overriding canPublish() leaves Versioned's ADMIN short-circuit intact.
 */
class DenyEditNavigationGroupStub extends NavigationGroup implements TestOnly
{
    /**
     * @var string
     */
    private static string $table_name = 'DenyEditNavigationGroupStub';

    /**
     * @param null $member
     *
     * @return bool
     */
    public function canEdit($member = null)
    {
        return false;
    }
}
