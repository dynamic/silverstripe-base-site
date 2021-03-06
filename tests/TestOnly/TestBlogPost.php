<?php

namespace Dynamic\Base\Test\TestOnly;

use DNADesign\Elemental\Extensions\ElementalPageExtension;
use Dynamic\Base\Extension\BlogPostDataExtension;
use SilverStripe\Blog\Model\BlogPost;
use SilverStripe\Dev\TestOnly;

/**
 * Class TestBlogPost
 * @package Dynamic\Base\Test
 */
class TestBlogPost extends BlogPost implements TestOnly
{
    /**
     * @var string
     */
    private static $table_name = 'TestBlogPost';
}
