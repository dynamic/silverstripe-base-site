<?php

namespace Dynamic\Base\Test\Extension;

use SilverStripe\Core\Extension;
use SilverStripe\Core\Validation\ValidationResult;

/**
 * Forces validate() to fail on demand, via a static toggle rather than per-instance state -
 * this extension is a shared Injector singleton across every owner, same as
 * TemplateDataExtension itself (see TemplateDataExtension::onAfterSkippedWrite()'s
 * docblock). Applied to SiteConfig for the whole test class via $required_extensions
 * (not a dynamic add_extension() call mid-test, which doesn't reliably invalidate a
 * fixture-loaded record's already-cached extension instances).
 *
 * TemplateDataExtensionTest::$shouldFail is always reset to false in a finally block by
 * the one test that flips it, so it can't leak into any other test in the class.
 */
class TemplateDataExtensionTestFailingValidationExtension extends Extension
{
    /**
     * @var bool
     */
    public static bool $shouldFail = false;

    /**
     * @param ValidationResult $result
     * @return void
     */
    public function updateValidate(ValidationResult $result): void
    {
        if (self::$shouldFail) {
            $result->addError('Simulated validation failure for test coverage.');
        }
    }
}
