<?php

namespace Dynamic\Base\Extension;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripers\ElementalSearch\CMS\Search\SearchForm;

/**
 *
 */
class SearchExtension extends Extension
{
    /**
     * @return SearchForm
     */
    public function SearchForm()
    {
        return SearchForm::create($this->owner, 'SearchForm');
    }

    /**
     * @param $data
     * @param $form
     * @param $request
     * @return mixed
     */
    public function results($data, $form, $request)
    {
        $data = [
            'Results' => ($this->owner->config()->get('restrict_to_pages'))
                ? $this->getFilteredResults($form)
                : $form->getResults(),
            'Query' => DBField::create_field('Text', $form->getSearchQuery()),
            'Title' => _t('SilverStripe\\CMS\\Search\\SearchForm.SearchResults', 'Search Results'),
        ];
        return $this->owner->customise($data)->renderWith(['Page_results', 'Page']);
    }

    /**
     * @param SearchForm $form
     * @return mixed
     */
    protected function getFilteredResults(SearchForm $form)
    {
        return $form->getResults()->filterByCallback(function ($result) {
            return is_subclass_of($result, SiteTree::class);
        });
    }
}
