<?php

namespace Dynamic\Base\Test;

use Dynamic\Base\Page\CampaignLandingPage;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;

class CampaignLandingPageTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = '../fixtures.yml';

    /**
     *
     */
    public function testGetCMSFields()
    {
        $object = $this->objFromFixture(CampaignLandingPage::class, 'spring');
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
    }
}