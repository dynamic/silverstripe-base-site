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
 * Regression coverage for the SearchForm() parent call on sites that never supply a
 * search form (dynamic/silverstripe-base-site#230): the call reached
 * CustomMethods::__call() and threw BadMethodCallException, so <searchpage>/SearchForm
 * was a 500. Also pins the behaviour that must survive that guard - any provider of
 * SearchForm(), not just core's ContentControllerSearchExtension, still gets through.
 */
class SearchPageControllerTest extends SapphireTest
{
    /**
     * The controller's constructor and the search form both read the site from the
     * database, so the schema is needed even though no fixture rows are used.
     */
    protected $usesDatabase = true;

    /**
     * With nothing supplying SearchForm() there is nothing to delegate to: the public
     * method must return null rather than throwing. SearchPage.ss still prints its
     * <form> markup around a null form, which is the follow-up filed separately; what
     * this case pins is that the request no longer fatals.
     */
    public function testSearchFormWithoutAnyProviderReturnsNull(): void
    {
        $this->assertFalse(
            SearchPageController::has_extension(ContentControllerSearchExtension::class),
            'Precondition: this module does not apply ContentControllerSearchExtension itself'
        );
        $this->assertFalse(
            method_exists(PageController::class, 'SearchForm'),
            'Precondition: the test app\'s PageController does not define SearchForm() itself'
        );

        $controller = SearchPageController::create(SearchPage::create());

        $this->assertNull(
            $controller->SearchForm(),
            'SearchForm() returns null instead of throwing when nothing supplies the form'
        );
    }

    /**
     * The extension applied to ContentController - what FulltextSearchable::enable()
     * does - must still produce the form through the inherited parent call.
     */
    public function testSearchFormWithCoreExtensionOnContentController(): void
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
     * why the guard asks its own object whether a custom method answers the call rather
     * than asking ContentController whether it has an extension.
     */
    public function testSearchFormWithCoreExtensionOnPageController(): void
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

    /**
     * A project that supplies SearchForm() some other way - its own search extension,
     * which is what the pre-guard parent call supported - must keep working. Naming
     * core's extension in the guard would silently take the form away from those sites.
     */
    public function testSearchFormWithProjectSuppliedExtension(): void
    {
        PageController::add_extension(SearchPageControllerTestProjectSearchExtension::class);

        try {
            $controller = SearchPageController::create(SearchPage::create());

            $this->assertInstanceOf(
                SearchForm::class,
                $controller->SearchForm(),
                'SearchForm() delegates to an extension other than ContentControllerSearchExtension'
            );
        } finally {
            PageController::remove_extension(SearchPageControllerTestProjectSearchExtension::class);
        }
    }
}
