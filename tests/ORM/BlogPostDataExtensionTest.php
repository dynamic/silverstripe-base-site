<?php

namespace Dynamic\CoreTools\Tests;

use SilverStripe\Blog\Model\BlogPost;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;

class BlogPostDataExtensionTest extends SapphireTest
{
    /**
     * @var array
     */
    protected static $fixture_file = array(
        '../Fixtures.yml',
    );

    /**
     *
     */
    public function testUpdateCMSFields()
    {
        $object = Injector::inst()->create(BlogPost::class);
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
    }

    /**
     *
     */
    public function testGetRelatedPosts()
    {
        $object = $this->objFromFixture(BlogPost::class, 'one');
        $expected = $this->objFromFixture(BlogPost::class, 'two');
        $this->assertEquals($object->getRelatedArticles()->first(), $expected);
    }
}
