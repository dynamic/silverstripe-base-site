<?php

namespace Dynamic\Base\Test\Extension;

use Dynamic\Base\Extension\SeoExtension;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Dev\SapphireTest;

/**
 * Class SeoExtensionTest.
 *
 * Covers the removal of the SearchContent fulltext field, its index and the
 * onBeforeWrite hook that populated it (issue #168), the deprecated
 * seoContentFields()/generateElementPreview() stubs kept until 9.0.0 (issue #221),
 * and guards the meta helpers that intentionally stay on the class.
 */
class SeoExtensionTest extends SapphireTest
{
    /**
     * Set for consistency with the other PHPUnit test classes in this module, most of
     * which get a temp database through their fixture file. Nothing in this class writes
     * to the database: the reflection and deprecation tests need no connection, and
     * neither does MetaComponents(), which truncates through DBString::Plain().
     */
    protected $usesDatabase = true;

    /**
     * The recipe applies SeoExtension to SiteTree; this module does not, so the test does.
     */
    protected static $required_extensions = [
        SiteTree::class => [SeoExtension::class],
    ];

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
     * The write hook that populated SearchContent must be gone from the class itself,
     * not just emptied out - otherwise every page save still calls into it. The two
     * helpers stay callable as deprecated stubs until 9.0.0 (issue #221).
     */
    public function testWriteHookIsRemovedAndHelpersAreDeprecatedStubs(): void
    {
        $reflection = new ReflectionClass(SeoExtension::class);

        $this->assertFalse(
            $reflection->hasMethod('onBeforeWrite'),
            'SeoExtension must not define an onBeforeWrite hook'
        );
        $this->assertTrue(
            $reflection->hasMethod('seoContentFields'),
            'SeoExtension must keep seoContentFields() as a deprecated stub until 9.0.0'
        );
        $this->assertTrue(
            $reflection->hasMethod('generateElementPreview'),
            'SeoExtension must keep generateElementPreview() as a deprecated stub until 9.0.0'
        );
    }

    /**
     * seoContentFields() is a no-op that a site reaches through the owner, so PHPStan
     * cannot flag its callers: the runtime notice is the only signal, and it must print
     * once a site turns deprecations on.
     */
    public function testSeoContentFieldsIsADeprecatedNoOp(): void
    {
        $page = SiteTree::create();

        // Call through the owner, as a site does. SS6 dispatches extension methods through
        // a ModelData closure frame, which Deprecation does not count as supported code, so
        // this path prints the notice with or without SCOPE_GLOBAL; the stub uses
        // SCOPE_GLOBAL anyway, per the platform deprecation rule.
        $result = null;
        $notices = $this->captureDeprecationNotices(function () use ($page, &$result): void {
            $result = $page->seoContentFields();
        });

        $this->assertSame([], $result);
        $this->assertStringContainsString('seoContentFields', implode("\n", $notices));
    }

    /**
     * generateElementPreview() is protected API, so it is restored with a notice too.
     */
    public function testGenerateElementPreviewRaisesDeprecationNotice(): void
    {
        $extension = new SeoExtension();
        $extension->setOwner(new SiteTree());
        $method = (new ReflectionMethod(SeoExtension::class, 'generateElementPreview'));

        $notices = $this->captureDeprecationNotices(function () use ($extension, $method): void {
            $method->invoke($extension);
        });

        $this->assertStringContainsString('generateElementPreview', implode("\n", $notices));
    }

    /**
     * Run $callback with deprecations on and return the E_USER_DEPRECATED messages it
     * raised. Deprecation::notice() buffers until outputNotices(), so that is flushed
     * inside the handler's lifetime.
     *
     * @return list<string>
     */
    private function captureDeprecationNotices(callable $callback): array
    {
        $wasEnabled = Deprecation::isEnabled();
        Deprecation::enable();
        if (!Deprecation::isEnabled()) {
            $this->markTestSkipped(
                'SS_DEPRECATION_ENABLED is set to a falsy value here, which overrides '
                . 'Deprecation::enable(), so the deprecation notice cannot be observed.'
            );
        }

        // Flush anything earlier tests buffered through the normal handler first, and only
        // capture this extension's own messages, so other deprecations are not swallowed.
        Deprecation::outputNotices();

        $notices = [];
        set_error_handler(function (int $errno, string $message) use (&$notices): bool {
            if (!str_contains($message, 'SeoExtension::')) {
                return false;
            }
            $notices[] = $message;
            return true;
        }, E_USER_DEPRECATED);

        try {
            $callback();
            Deprecation::outputNotices();
        } finally {
            restore_error_handler();
            if (!$wasEnabled) {
                Deprecation::disable();
            }
        }

        return $notices;
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
