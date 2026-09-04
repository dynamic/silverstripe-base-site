<?php

namespace Dynamic\Base\Test\Extension;

use Psr\Log\AbstractLogger;

/**
 * Minimal PSR-3 logger double that records every call for assertion.
 */
class TemplateDataExtensionTestSpyLogger extends AbstractLogger
{
    /**
     * @var array
     */
    public array $records = [];

    /**
     * @param mixed $level
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = []): void
    {
        $this->records[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];
    }
}
