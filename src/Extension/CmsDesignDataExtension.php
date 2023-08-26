<?php

namespace Dynamic\Base\Extension;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\ORM\DataExtension;
use Vulcan\Seo\Builders\FacebookMetaGenerator;
use Vulcan\Seo\Extensions\PageSeoExtension;

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
