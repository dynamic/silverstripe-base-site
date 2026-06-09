<?php

namespace Dynamic\Base\Test\Extension;

use Dynamic\Base\Extension\TemplateDataExtension;
use SilverStripe\Core\Injector\Injector;
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
     * Test the extension method in isolation, bypassing other registered
     * extensions (e.g. SiteConfigExtension from essentials-tools removes
     * Logo/TitleLogo in non-EssentialsConfig contexts, which would cause
     * this assertion to fail in recipe CI where that extension is loaded).
     */
    public function testUpdateCMSFields()
    {
        $siteConfig = Injector::inst()->create(SiteConfig::class);
        $fields = FieldList::create(TabSet::create('Root', Tab::create('Main')));

        /** @var TemplateDataExtension $ext */
        $ext = $siteConfig->getExtensionInstance(TemplateDataExtension::class);
        $ext->updateCMSFields($fields);

        $this->assertInstanceOf(FieldList::class, $fields);
        $this->assertNotNull($fields->dataFieldByName('Logo'));
        $this->assertNotNull($fields->dataFieldByName('LogoRetina'));
    }
}
