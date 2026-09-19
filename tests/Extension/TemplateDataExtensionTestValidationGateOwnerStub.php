<?php

namespace Dynamic\Base\Test\Extension;

use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
* Owner double for TemplateDataExtension::onAfterSkippedWrite()'s validation gate, so that
* gate is covered without a real SiteConfig in the write stack.
*
* A DataObject, not a duck-typed stand-in, so the trait's typed
* getOwnedRecordsOwner(): DataObject seam stays typed; validate(), findOwned() and
* ObsoleteClassName all come from the parent. Never written to the database - findOwned()
* is overridden so neither isInDB() nor a table lookup decides the outcome.
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
