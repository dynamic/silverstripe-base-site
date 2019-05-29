<?php

namespace Dynamic\Base\Test;

use DNADesign\Elemental\Extensions\ElementalPageExtension;
use DNADesign\Elemental\Models\ElementContent;
use Dynamic\Base\ORM\BlogPostDataExtension;
use SilverStripe\Blog\Model\BlogPost;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;

class BlogPostDataExtensionTest extends SapphireTest
{
    /**
     * @var array
     */
    protected static $fixture_file = [
        '../fixtures.yml',
    ];

    /**
     * @var array
     */
    protected static $extra_dataobjects = [
        TestBlogPost::class,
    ];

    /**
     * @var array
     */
    protected static $required_extensions = [
        TestBlogPost::class => [
            BlogPostDataExtension::class,
            ElementalPageExtension::class,
        ],
    ];

    /**
     *
     */
    public function testUpdateCMSFields()
    {
        $this->markTestSkipped('Bug in silverstripe-seo throwing errors, revisit after update');
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
        $object = $this->objFromFixture(TestBlogPost::class, 'one');
        /** @var BlogPost $expected */
        $expected = $this->objFromFixture(TestBlogPost::class, 'two');
        $this->assertEquals($expected->ID, $object->getRelatedPosts()->first()->ID);
    }

    public function testGetContent()
    {
        $expected = "Test";
        /** @var BlogPost $post */
        $post = $this->objFromFixture(TestBlogPost::class, 'one');
        $this->assertEquals('', $post->getContent());

        $post->ElementalArea()->Elements()->add(ElementContent::create());
        $this->assertEquals('', $post->getContent());

        $element = $post->ElementalArea()
            ->Elements()->filter([
                'ClassName' => ElementContent::class,
            ])->first();
        $element->HTML = $expected;
        $element->write();

        $this->assertEquals($expected, $post->getContent());
    }
}
