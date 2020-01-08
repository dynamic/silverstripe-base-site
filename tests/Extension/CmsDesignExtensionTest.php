<?php

namespace Dynamic\BaseRecipe\Test\Extension;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;

class CmsDesignExtensionTest extends SapphireTest
{
    /**
     *
     */
    public function testUpdateCMSFields()
    {
        $object = singleton(\Page::class);
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
    }
}
