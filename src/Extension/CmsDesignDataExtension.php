<?php

namespace Dynamic\Base\Extension;

use SilverStripe\Forms\FieldList;
use SilverStripe\Core\Extension;

/**
 * Class \Dynamic\Base\Extension\CmsDesignDataExtension
 *
 * @property SiteTree|CmsDesignDataExtension $owner
 */
class CmsDesignDataExtension extends Extension
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
