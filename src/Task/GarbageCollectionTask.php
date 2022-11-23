<?php

namespace Dynamic\Base\Task;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\BuildTask;
use SilverStripe\GarbageCollector\GarbageCollectorService;

if (!class_exists(GarbageCollectorService::class)) {
    return;
}

class GarbageCollectionTask extends BuildTask
{
    /**
     * @var string
     */
    private static $segment = 'GarbageCollectionTask';

    /**
     * {@inheritDoc}
     * @return string
     */
    public function getDescription()
    {
        return _t(
            __CLASS__ . '.Description',
            'A task used to trim Change Sets, Versions, and any other configured objects'
        );
    }

    /**
     * @param HTTPRequest $request
     * @throws \Exception
     */
    public function run($request)
    {
        GarbageCollectorService::inst()->process();
    }
}
