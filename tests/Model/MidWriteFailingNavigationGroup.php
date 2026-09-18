<?php

namespace Dynamic\Base\Test\Model;

use Dynamic\Base\Model\NavigationGroup;
use SilverStripe\Dev\TestOnly;

/**
* A NavigationGroup whose write aborts *after* onBeforeWrite() but *before*
* onAfterWrite() - the window in which DataObject::write() runs writeBaseRecord(),
* writeManipulation() and writeRelations(). Only a subclass can intercept that, since
* writeRelations() is a real DataObject method rather than an extension hook.
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
