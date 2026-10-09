<?php

namespace Dynamic\Base\Page;

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
     * The form itself is supplied by whatever the parent chain offers: core's
     * ContentControllerSearchExtension (applied by FulltextSearchable::enable()), a
     * project's own search extension, or a SearchForm() defined directly on the
     * project's PageController.
     *
     * @return SearchForm|null null when nothing answers the parent call, instead of a
     * fatal error from that call reaching CustomMethods::__call().
     */
    public function SearchForm()
    {
        // hasCustomMethod() covers extensions applied anywhere in the inheritance
        // chain, including the PageController a project owns.
        if (!method_exists(parent::class, 'SearchForm') && !$this->hasCustomMethod('SearchForm')) {
            return null;
        }

        return parent::SearchForm();
    }
}
