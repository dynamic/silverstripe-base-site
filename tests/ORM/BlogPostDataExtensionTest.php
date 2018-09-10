<?php

namespace Dynamic\Base\Test;

use DNADesign\Elemental\Models\ElementContent;
use SilverStripe\Blog\Model\BlogPost;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;

class BlogPostDataExtensionTest extends SapphireTest
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
    protected static $extra_dataobjects = [
        TestBlogPost::class,
    ];

    /**
     *
     */
    public function testUpdateCMSFields()
    {
        /** @var BlogPost $object */
        $object = Injector::inst()->create(TestBlogPost::class);
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
    }

    /**
     *
     */
    public function testGetRelatedPosts()
    {
        /** @var BlogPost $object */
        $object = $this->objFromFixture(BlogPost::class, 'one');
        /** @var BlogPost $expected */
        $expected = $this->objFromFixture(BlogPost::class, 'two');
        $this->assertEquals($expected, $object->getRelatedPosts()->first());
    }

    public function testGetContent()
    {
        $expected = "Test";
        /** @var BlogPost $post */
        $post = $this->objFromFixture(BlogPost::class, 'one');
        $this->assertEquals('', $post->getContent());

        $post->ElementalArea()->Elements()->add(ElementContent::create());
        $this->assertEquals('', $post->getContent());

        $element = $post->ElementalArea()
            ->Elements()->filter(array(
                'ClassName' => ElementContent::class
            ))->first();
        $element->HTML = $expected;
        $element->write();

        $this->assertEquals($expected, $post->getContent());
    }
}
