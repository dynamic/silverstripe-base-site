<?php

namespace Dynamic\Base\Test\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\LinkField\Models\ExternalLink;

/**
 * A versioned Link whose modification check throws, standing in for a project extension
 * implementing updateIsOnDraft() (dispatched by Versioned::isOnDraft()) or a stage-table query
 * that fails - both of which Versioned::isModifiedOnDraft() reaches on its way to answering.
 *
 * Declaring the method here shadows the Versioned extension's __call() magic for this subclass
 * only, the same seam ThrowingSocialLink uses for publishRecursive().
 */
class ThrowingIsModifiedOnDraftLink extends ExternalLink implements TestOnly
{
    /**
     * @var string
     */
    private static string $table_name = 'ThrowingIsModifiedOnDraftLink';

    /**
     * @return bool
     */
    public function isModifiedOnDraft()
    {
        throw new \RuntimeException('Simulated failure in the modification check for test coverage.');
    }
}
