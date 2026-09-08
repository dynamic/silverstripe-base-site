<?php

namespace Dynamic\Base\Test\Extension;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * Deliberately not Versioned. RecursivePublishable (which provides publishRecursive()) is
 * applied to every DataObject regardless of Versioned (see versionedownership.yml), so
 * this stub does declare that method - it's isModifiedOnDraft() specifically, a
 * Versioned-only method, that it lacks. See
 * TemplateDataExtensionTest::testPublishOwnedRecordSkipsChildWithoutVersionedExtension().
 */
class UnversionedOwnedStub extends DataObject implements TestOnly
{
    /**
     * @var string
     */
    private static $table_name = 'UnversionedOwnedStub';
}
