<?php

namespace Dynamic\Base\Cache;

use Dynamic\Base\Model\NavigationGroup;
use SilverStripe\ORM\DataExtension;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Class \Dynamic\Base\Cache\SiteTreeDataExtension
 *
 * @property SiteTree|SiteTreeDataExtension $owner
 * @method ManyManyList|SiteConfig[] SiteConfigs()
 * @method ManyManyList|NavigationGroup[] NavigationGroups()
 */
class SiteTreeDataExtension extends DataExtension
{
    /**
     * @var array|string[]
     */
    private static array $belongs_many_many = [
        'SiteConfigs' => SiteConfig::class,
        'NavigationGroups' => NavigationGroup::class,
    ];
}
