<?php

namespace Dynamic\Base\Page;

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
