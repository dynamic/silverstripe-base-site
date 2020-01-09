<?php

namespace Dynamic\Base\Test\Extension;

use Dynamic\Base\Extension\TemplateDataExtension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\SiteConfig\SiteConfig;

class TemplateDataExtensionTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = '../fixtures.yml';

    /**
     * @var array
     */
    protected static $required_extensions = [
        SiteConfig::class => [
            TemplateDataExtension::class,
        ]
    ];

    /**
     *
     */
    public function testGetCMSFields()
    {
        $object = Injector::inst()->create(SiteConfig::class);
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
        $this->assertNotNull($fields->dataFieldByName('LogoID'));
    }
}
