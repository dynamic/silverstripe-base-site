<?php

namespace Dynamic\Base\Test\Model;

use Dynamic\Base\Model\NavigationGroup;
use SilverStripe\Dev\TestOnly;

/**
 * A NavigationGroup whose write aborts *after* onBeforeWrite() but *before* onAfterWrite(),
 * which is the window where DataObject::write() runs writeBaseRecord(), writeManipulation()
 * and writeRelations().
 *
 * This exists because those failures are invisible to a hook-level stub: an extension
 * throwing from onAfterWrite() lands in a later window, so a guard that only covers that one
 * still lets the write flag survive an aborted write. writeRelations() is a real DataObject
 * method rather than an extension hook, so it can only be intercepted by a subclass.
 *
 * Toggle is static and reset by the owning test in a finally block.
 */
class MidWriteFailingNavigationGroup extends NavigationGroup implements TestOnly
{
    /**
     * @var string
     */
    private static $table_name = 'MidWriteFailingNavigationGroup';

    /**
     * @var bool
     */
    public static bool $shouldFailRelations = false;

    /**
     * @return void
     */
    public function writeRelations()
    {
        if (self::$shouldFailRelations) {
            throw new \RuntimeException('Simulated failure between onBeforeWrite() and onAfterWrite().');
        }

        parent::writeRelations();
    }
}
