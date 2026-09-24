<?php

namespace Dynamic\Base\Test\Extension;

use Dynamic\Base\Extension\SeoExtension;
use ReflectionClass;
use ReflectionProperty;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;

/**
 * Class SeoExtensionTest.
 *
 * Covers the removal of the SearchContent fulltext field, its index and the
 * onBeforeWrite hook that populated it (issue #168), and guards the meta helpers
 * that intentionally stay on the class.
 */
class SeoExtensionTest extends SapphireTest
{
    /**
     * Set for consistency with the other PHPUnit test classes in this module, most of
     * which get a temp database through their fixture file. Nothing in this class writes
     * to the database: the two reflection tests need no connection, and neither does
     * MetaComponents(), which truncates through DBString::Plain().
     */
    protected $usesDatabase = true;

    /**
     * The extension must not declare a SearchContent DB field or a SearchFields fulltext
     * index of its own: both made every page save expensive for every downstream site.
     *
     * Read straight off the class rather than through Config, because SilverStripe 6 only
     * merges an extension's `$db` into the *owner* class' config, and this module never
     * applies SeoExtension to SiteTree in its own `_config` (the recipe does), so the
     * config layer can't see it here.
     */
    public function testSearchContentFieldAndIndexAreNotDeclared(): void
    {
        $reflection = new ReflectionClass(SeoExtension::class);

        $this->assertFalse(
            $this->declaresStaticKey($reflection, 'db', 'SearchContent'),
            'SeoExtension must not declare a SearchContent DB field'
        );
        $this->assertFalse(
            $this->declaresStaticKey($reflection, 'indexes', 'SearchFields'),
            'SeoExtension must not declare a SearchFields fulltext index'
        );
    }

    /**
     * The write hooks that populated SearchContent must be gone from the class itself,
     * not just emptied out - otherwise every page save still calls into them.
     */
    public function testSearchContentWriteHooksAreRemoved(): void
    {
        $reflection = new ReflectionClass(SeoExtension::class);

        $this->assertFalse(
            $reflection->hasMethod('onBeforeWrite'),
            'SeoExtension must not define an onBeforeWrite hook'
        );
        $this->assertFalse(
            $reflection->hasMethod('seoContentFields'),
            'SeoExtension must not declare seoContentFields()'
        );
        $this->assertFalse(
            $reflection->hasMethod('generateElementPreview'),
            'SeoExtension must not declare generateElementPreview()'
        );
    }

    /**
     * The retained behaviour: MetaComponents() still shortens an over-long MetaDescription
     * and leaves a short one alone.
     */
    public function testMetaComponentsStillTruncatesLongDescription(): void
    {
        $page = new SiteTree();
        $long = str_repeat('a', 200);
        $page->MetaDescription = $long;

        $extension = new SeoExtension();
        $extension->setOwner($page);

        $tags = [
            'description' => [
                'attributes' => [
                    'name' => 'description',
                    'content' => $long,
                ],
            ],
        ];
        $extension->MetaComponents($tags);

        $content = $tags['description']['attributes']['content'];
        $this->assertNotSame($long, $content);
        $this->assertLessThanOrEqual(SeoExtension::META_CHAR_COUNT_MAX + 10, strlen($content));

        // An empty tag set and a short description must both be left untouched.
        $shortTags = [];
        $page->MetaDescription = 'A short description.';
        $extension->MetaComponents($shortTags);
        $this->assertSame([], $shortTags);
    }

    /**
     * True when $class itself declares the static array $property with a $key in it.
     */
    private function declaresStaticKey(ReflectionClass $class, string $property, string $key): bool
    {
        if (!$class->hasProperty($property)) {
            return false;
        }

        /** @var ReflectionProperty $declared */
        $declared = $class->getProperty($property);
        if (!$declared->isStatic() || $declared->getDeclaringClass()->getName() !== $class->getName()) {
            return false;
        }

        return array_key_exists($key, (array)$declared->getValue());
    }
}
