<?php

namespace Dynamic\Base\Test\Extension;

use SilverStripe\Core\Validation\ValidationResult;

/**
 * A minimal extension-owner double - not a DataObject at all - used to prove
 * TemplateDataExtension::onAfterSkippedWrite()'s validation gate without touching the
 * real SiteConfig owner: Extension instances are Injector singletons shared across every
 * owner, so a real end-to-end reproduction would need to force an actual DataObject
 * validation failure through the full write() stack, which fights SilverStripe's
 * per-instance extension/config caching far more than this focused double does.
 */
class TemplateDataExtensionTestValidationGateOwnerStub
{
    /**
     * Mirrors DataObject::$ObsoleteClassName, which onAfterSkippedWrite() also reads -
     * always null here, since neither gate test is exercising that specific branch.
     *
     * @var string|null
     */
    public ?string $ObsoleteClassName = null;

    /**
     * @var bool
     */
    private bool $isValid;

    /**
     * @var bool
     */
    public bool $findOwnedWasCalled = false;

    /**
     * @param bool $isValid
     */
    public function __construct(bool $isValid)
    {
        $this->isValid = $isValid;
    }

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
     * Matches RecursivePublishable::findOwned()'s real signature (name, default, and the
     * $list param), so a future refactor there can't silently desync this stub from the
     * production method it stands in for.
     *
     * @param bool $recursive
     * @param mixed $list
     * @return array
     */
    public function findOwned($recursive = true, $list = null): array
    {
        $this->findOwnedWasCalled = true;

        return [];
    }
}
