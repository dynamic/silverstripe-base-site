<?php

namespace Dynamic\Base\Test\Model;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

/**
* Counts how many times DataObject::onAfterWrite() dispatched extend('onAfterWrite') to
* extensions on NavigationGroup, so the parent call there is observable: it is the call
* that reaches every other extension on the class, and unlike onBeforeWrite() no framework
* sentinel fails when it is dropped.
*
* Counter is static because extensions are Injector singletons shared across every owner
* record, so per-instance state would not reliably be the object the ORM invokes.
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
