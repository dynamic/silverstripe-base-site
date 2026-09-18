<?php

namespace Dynamic\Base\Test\Model;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

/**
 * Throws from onAfterWrite() on demand, so a test can observe that NavigationGroup publishes
 * its owned links *after* handing control to parent::onAfterWrite() - and so not at all when
 * the write aborts.
 *
 * parent::onAfterWrite() dispatches extend('onAfterWrite') to arbitrary third-party extensions,
 * so it is the call in that method most likely to throw. Because publishOwnedRecords() sits
 * after it, a throwing extension aborts the save with the links still in draft: a write that
 * ends in an error publishes nothing on its way out.
 *
 * Toggle is static because the extension is an Injector singleton shared across every owner
 * record, and the owning test resets it in a finally block.
 */
class NavigationGroupThrowingAfterWriteExtension extends Extension implements TestOnly
{
    /**
     * @var bool
     */
    public static bool $shouldThrow = false;

    /**
     * @return void
     */
    public function onAfterWrite(): void
    {
        if (self::$shouldThrow) {
            throw new \RuntimeException('Simulated extension failure inside onAfterWrite().');
        }
    }
}
