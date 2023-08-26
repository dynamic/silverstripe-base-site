<?php

namespace Dynamic\Base\Page;

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
     * @return mixed
     */
    public function SearchForm()
    {
        return parent::SearchForm();
    }
}
