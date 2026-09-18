<?php

namespace Dynamic\Base\Test\Extension;

use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * A minimal owner double for TemplateDataExtension::onAfterSkippedWrite()'s validation gate,
 * so that gate can be covered without dragging a real SiteConfig through the write stack.
 *
 * It is a DataObject rather than a duck-typed stand-in: the alternative was to leave the
 * production seam PublishesOwnedRecords::getOwnedRecordsOwner() untyped purely so this double
 * kept working, which weakens the contract every real consumer depends on. Extending
 * DataObject keeps the seam typed at no cost, because validate(), findOwned() and
 * ObsoleteClassName all come from the parent.
 *
 * Deliberately never written to the database - the two gate tests only ask it validate() and
 * findOwned(), and findOwned() is overridden precisely so that neither isInDB() nor a table
 * lookup decides the outcome the test is asserting.
 */
class TemplateDataExtensionTestValidationGateOwnerStub extends DataObject implements TestOnly
{
    /**
     * @var string
     */
    private static $table_name = 'ValidationGateOwnerStub';

    /**
     * @var bool
     */
    public bool $findOwnedWasCalled = false;

    /**
     * Whether validate() should report a valid record. A plain declared public property
     * rather than a constructor argument: DataObject's own constructor signature is fixed
     * (TableBuilder instantiates every DataObject in the manifest through it), and a
     * declared property is written directly instead of going through __set().
     *
     * @var bool
     */
    public bool $isValid = true;

    /**
     * @return ValidationResult
     */
    public function validate(): ValidationResult
    {
        $result = ValidationResult::create();

        if (!$this->isValid) {
            $result->addError('Simulated validation failure for test coverage.');
        }

        return $result;
    }

    /**
     * Stands in for RecursivePublishable::findOwned(), which this class cannot inherit in
     * any enforceable way: findOwned() reaches a DataObject through extension __call
     * dispatch, so PHP checks nothing here and a future change to the real method will not
     * break this stub to tell you about it. It matches the real signature by hand - same
     * name, same defaults, same ArrayList return, no declared return type - and records the
     * call so the two gate tests can assert the traversal was reached.
     *
     * @param bool $recursive
     * @param mixed $list
     * @return ArrayList
     */
    public function findOwned($recursive = true, $list = null)
    {
        $this->findOwnedWasCalled = true;

        return ArrayList::create();
    }
}
