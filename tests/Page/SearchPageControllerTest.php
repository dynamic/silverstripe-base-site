<?php

namespace Dynamic\Base\Test;

use Dynamic\Base\Page\SearchPage;
use Dynamic\Base\Page\SearchPageController;
use PageController;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Search\ContentControllerSearchExtension;
use SilverStripe\CMS\Search\SearchForm;
use SilverStripe\Dev\SapphireTest;

/**
 * Regression coverage for the SearchForm() parent call on sites that never apply
 * SilverStripe\CMS\Search\ContentControllerSearchExtension
 * (dynamic/silverstripe-base-site#230): the call reached CustomMethods::__call() and
 * threw BadMethodCallException, so <searchpage>/SearchForm was a 500.
 */
class SearchPageControllerTest extends SapphireTest
{
    /**
     * The controller's constructor and the search form both read the site from the
     * database, so the schema is needed even though no fixture rows are used.
     */
    protected $usesDatabase = true;

    /**
     * Without the extension there is no SearchForm to delegate to: the public method
     * must return null so the template renders an empty form slot rather than throwing.
     */
    public function testSearchFormWithoutExtensionReturnsNull(): void
    {
        $this->assertFalse(
            SearchPageController::has_extension(ContentControllerSearchExtension::class),
            'Precondition: this module does not apply ContentControllerSearchExtension itself'
        );

        $controller = SearchPageController::create(SearchPage::create());

        $this->assertNull(
            $controller->SearchForm(),
            'SearchForm() returns null instead of throwing when no search extension is applied'
        );
    }

    /**
     * The extension applied to ContentController - what FulltextSearchable::enable()
     * does - must still produce the form through the inherited parent call.
     */
    public function testSearchFormWithExtensionOnContentController(): void
    {
        ContentController::add_extension(ContentControllerSearchExtension::class);

        try {
            // Built after add_extension(): extension methods are cached per instance.
            $controller = SearchPageController::create(SearchPage::create());

            $this->assertInstanceOf(
                SearchForm::class,
                $controller->SearchForm(),
                'SearchForm() delegates to the extension applied to ContentController'
            );
        } finally {
            ContentController::remove_extension(ContentControllerSearchExtension::class);
        }
    }

    /**
     * The extension applied to the project's PageController must be seen too, which is
     * why the guard calls static::has_extension() rather than ContentController::has_extension().
     */
    public function testSearchFormWithExtensionOnPageController(): void
    {
        PageController::add_extension(ContentControllerSearchExtension::class);

        try {
            $controller = SearchPageController::create(SearchPage::create());

            $this->assertInstanceOf(
                SearchForm::class,
                $controller->SearchForm(),
                'SearchForm() delegates to the extension applied to PageController'
            );
        } finally {
            PageController::remove_extension(ContentControllerSearchExtension::class);
        }

        $this->assertFalse(
            SearchPageController::has_extension(ContentControllerSearchExtension::class),
            'Precondition for later tests: the added extension did not leak'
        );
    }
}
