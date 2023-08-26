<?php

namespace Dynamic\Base\Page;

use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;

/**
 * Class \Dynamic\Base\Page\SearchPage
 *
 */
class SearchPage extends \Page
{
    /**
     * @var string
     */
    private static $singular_name = 'Search Page';

    /**
     * @var string
     */
    private static $plural_name = 'Search Pages';

    /**
     * @var string
     */
    private static $description = 'Website search. Searches Title and Content field of each page.';

    /**
     * @var array
     */
    private static $defaults = array(
        'ShowInMenus' => 0,
    );

    /**
     * @var string
     */
    private static $table_name = 'SearchPage';
}
