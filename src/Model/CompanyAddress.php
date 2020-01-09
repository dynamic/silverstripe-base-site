<?php

namespace Dynamic\Base\Model;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;
use SilverStripe\SiteConfig\SiteConfig;

class CompanyAddress extends DataObject
{
    /**
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar(255)',
        'SortOrder' => 'Int',
    ];

    /**
     * @var array
     */
    private static $has_one = [
        'SiteConfig' => SiteConfig::class,
    ];

    /**
     * @return \SilverStripe\Forms\FieldList
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->addFieldToTab(
                'Root.Address',
                $fields->dataFieldByName('Title')
            );
        });

        $fields = parent::getCMSFields();

        $fields->removeByName([
            'SortOrder',
            'SiteConfigID',
            'Main',
            'Lat',
            'Lng',
        ]);

        return $fields;
    }
}
