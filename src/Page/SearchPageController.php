<?php

namespace Dynamic\Base\Page;

use SilverStripe\CMS\Search\ContentControllerSearchExtension;
use SilverStripe\CMS\Search\SearchForm;

/**
 * Class \Dynamic\Base\Page\SearchPageController
 *
 * @property SearchPage $dataRecord
 * @method SearchPage data()
 * @mixin SearchPage
 */
class SearchPageController extends \PageController
{
    /**
     * @var array
     */
    private static $allowed_actions = array(
        'SearchForm',
    );

    /**
     * The form itself comes from ContentControllerSearchExtension, which a project
     * applies through its own configuration (usually FulltextSearchable::enable()).
     *
     * @return SearchForm|null null when no search extension is applied, instead of a
     * fatal error from the parent call reaching CustomMethods::__call().
     */
    public function SearchForm()
    {
        // has_extension() reads inherited configuration, so this covers the extension
        // applied to either ContentController or PageController.
        if (!static::has_extension(ContentControllerSearchExtension::class)) {
            return null;
        }

        return parent::SearchForm();
    }
}
