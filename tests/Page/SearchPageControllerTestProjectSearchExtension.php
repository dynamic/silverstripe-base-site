<?php

namespace Dynamic\Base\Test;

use SilverStripe\CMS\Search\SearchForm;
use SilverStripe\Core\Extension;

/**
 * Stands in for a project's own search integration (a Solr-backed extension or
 * similar): it supplies SearchForm() without core's
 * ContentControllerSearchExtension, which SearchPageController::SearchForm() must
 * still delegate to rather than short-circuit to null.
 *
 * @mixin \PageController
 */
class SearchPageControllerTestProjectSearchExtension extends Extension
{
    /**
     * @return SearchForm
     */
    public function SearchForm()
    {
        return SearchForm::create($this->owner, 'SearchForm');
    }
}
