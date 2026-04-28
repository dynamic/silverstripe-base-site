<?php

namespace Dynamic\Base\Test\Extension;

use Dynamic\Base\Extension\TemplateDataExtension;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
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
     * Tests that TemplateDataExtension::updateCMSFields adds branding fields.
     */
    public function testUpdateCMSFieldsAddsBrandingFields()
    {
        // Test updateCMSFields directly to avoid interference from
        // other extensions (e.g. essentials-tools) that may remove fields
        $object = SiteConfig::create();
        $fields = FieldList::create(TabSet::create('Root', Tab::create('Main')));
        $extension = $object->getExtensionInstance(TemplateDataExtension::class);
        $this->assertNotNull($extension);
        $extension->setOwner($object);
        $extension->updateCMSFields($fields);

        $this->assertNotNull($fields->dataFieldByName('TitleLogo'));
        $this->assertNotNull($fields->dataFieldByName('Logo'));
        $this->assertNotNull($fields->dataFieldByName('LogoRetina'));
    }
}
