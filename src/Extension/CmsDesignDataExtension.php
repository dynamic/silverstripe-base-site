<?php

namespace Dynamic\Base\Extension;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

/**
 * Class \Dynamic\Base\Extension\CmsDesignDataExtension
 *
 * @property SiteTree|CmsDesignDataExtension $owner
 */
class CmsDesignDataExtension extends DataExtension
{
    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        if ($fields->dataFieldByName('MenuTitle')) {
            $fields->insertBefore(
                'MetaDescription',
                $fields->dataFieldByName('MenuTitle')
            );
        }
        if ($fields->dataFieldByName('URLSegment')) {
            $fields->insertBefore(
                'MetaDescription',
                $fields->dataFieldByName('URLSegment')
            );
        }
    }
}
