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
        'IsPrimary' => 'Boolean',
    ];

    /**
     * @var array
     */
    private static $has_one = [
        'SiteConfig' => SiteConfig::class,
    ];

    /**
     * @var string
     */
    private static $table_name = 'BaseCompanyAddress';

    private static $summary_fields = [
        'Title',
        'FullAddress',
        'IsPrimary.Nice' => [
            'title' => 'Main'
        ],
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

            $primary = $fields->dataFieldByName('IsPrimary')
                ->setTitle('Main Location')
                ->setDescription("Mark this as the main location for this company");
            $fields->insertAfter('Title', $primary);
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
