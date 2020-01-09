<?php

namespace Dynamic\Base\Test\Extension;

use Dynamic\Base\Extension\CompanyDataExtension;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\SiteConfig\SiteConfig;

class CompanyConfigTest extends SapphireTest
{
    /**
     * @var array
     */
    protected static $fixture_file = array(
        '../fixtures.yml',
    );

    /**
     * @var array
     */
    protected static $required_extensions = [
        SiteConfig::class => [
            CompanyDataExtension::class,
        ]
    ];

    /**
     *
     */
    public function testGetCMSFields()
    {
        $object = $this->objFromFixture(SiteConfig::class, 'default');
        $fields = $object->getCMSFields();

        $this->assertInstanceOf(FieldList::class, $fields);
        $this->assertNotNull($fields->dataFieldByName('CompanyName'));
    }
}
