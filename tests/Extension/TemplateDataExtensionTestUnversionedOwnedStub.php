<?php

namespace Dynamic\Base\Test\Extension;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * Deliberately not Versioned and declares neither isModifiedOnDraft() nor
 * publishRecursive() - see
 * TemplateDataExtensionTest::testPublishOwnedRecordSkipsChildWithoutVersionedExtension().
 */
class TemplateDataExtensionTestUnversionedOwnedStub extends DataObject implements TestOnly
{
    /**
     * @var string
     */
    private static $table_name = 'TemplateDataExtensionTestUnversionedOwnedStub';
}
