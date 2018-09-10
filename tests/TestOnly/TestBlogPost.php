<?php

namespace Dynamic\Base\Test;

use DNADesign\Elemental\Extensions\ElementalPageExtension;
use Dynamic\Base\ORM\BlogPostDataExtension;
use SilverStripe\Blog\Model\BlogPost;
use SilverStripe\Dev\TestOnly;

class TestBlogPost extends BlogPost implements TestOnly
{
    /**
     * @var array
     */
    private static $extensions = [
        BlogPostDataExtension::class,
        ElementalPageExtension::class,
    ];
}