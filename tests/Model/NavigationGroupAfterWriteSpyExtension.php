<?php

namespace Dynamic\Base\Test\Model;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

/**
 * Records how many times DataObject::onAfterWrite() dispatched extend('onAfterWrite') to
 * extensions on NavigationGroup.
 *
 * Exists to pin NavigationGroup::onAfterWrite() calling parent::onAfterWrite(). That call is
 * what fetches generated columns and what reaches every other extension on the class, so a
 * downstream project can depend on it - but unlike onBeforeWrite(), where the framework's
 * brokenOnWrite sentinel throws when an override forgets parent::, nothing fails if this one
 * is dropped. Delete the parent call and only this test goes red.
 *
 * Counter is static rather than per-instance: extensions are Injector singletons shared across
 * every owner record (see PublishesOwnedRecords::shouldPublishOwnedRecordsOnSkippedWrite()),
 * so per-instance state would not reliably be the same object the ORM invokes.
 */
class NavigationGroupAfterWriteSpyExtension extends Extension implements TestOnly
{
    /**
     * @var int
     */
    public static int $onAfterWriteCalls = 0;

    /**
     * @return void
     */
    public function onAfterWrite(): void
    {
        self::$onAfterWriteCalls++;
    }
}
