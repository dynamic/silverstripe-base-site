<?php

namespace Dynamic\Base\Test\Extension;

use Dynamic\Base\Model\SocialLink;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Dev\TestOnly;

/**
 * A real SocialLink subclass (rather than a PHPUnit mock) whose publishRecursive() can be
 * made to throw on demand via a persisted field - a PHPUnit mock can't intercept
 * publishRecursive()/isModifiedOnDraft(), since those are provided to SocialLink via
 * extensions' __call() magic (RecursivePublishable and Versioned respectively) rather
 * than being real declared methods. Declaring a real publishRecursive() method here
 * shadows that magic dispatch for this subclass only; the default (non-failing) branch
 * delegates to parent::publishRecursive(), which still resolves to the real
 * RecursivePublishable implementation via the same __call() fallback.
 */
class TemplateDataExtensionTestThrowingSocialLink extends SocialLink implements TestOnly
{
    /**
     * @var string
     */
    private static string $table_name = 'TemplateDataExtensionTestThrowingSocialLink';

    /**
     * @var array
     */
    private static array $db = [
        'FailureMode' => "Enum('none,validation,generic')",
    ];

    /**
     * @return bool
     */
    public function publishRecursive()
    {
        switch ($this->FailureMode) {
            case 'validation':
                throw new ValidationException('Simulated validation failure for test coverage.');
            case 'generic':
                throw new \RuntimeException('Simulated non-validation failure for test coverage.');
            default:
                return parent::publishRecursive();
        }
    }
}
