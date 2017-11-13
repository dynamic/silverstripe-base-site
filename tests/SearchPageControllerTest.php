<?php

namespace Dynamic\Base\Test;

use Dynamic\Base\Page\SearchPageController;
use SilverStripe\CMS\Search\SearchForm;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\FunctionalTest;

/**
 * Class SearchPageControllerTest
 */
class SearchPageControllerTest extends FunctionalTest
{

    /**
     * @var bool
     */
    protected static $disable_themes = true;

    /**
     * @var bool
     */
    protected static $use_draft_site = true;

    /**
     * Tests SearchForm()
     */
    public function testSearchForm()
    {
        $object = Injector::inst()->create(SearchPageController::class);
        $form = $object->SearchForm();
        $this->assertInstanceOf(SearchForm::class, $form);
    }
}
