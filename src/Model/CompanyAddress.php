<?php

namespace Dynamic\Base\Model;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Class \Dynamic\Base\Model\CompanyAddress
 *
 * @property string $Phone
 * @property string $Phone2
 * @property string $Fax
 * @property string $Email
 * @property string $Address
 * @property string $Address2
 * @property string $City
 * @property string $State
 * @property string $PostalCode
 * @property string $Country
 * @property bool $LatLngOverride
 * @property float $Lat
 * @property float $Lng
 * @property string $Title
 * @property int $SortOrder
 * @property bool $IsPrimary
 * @property int $SiteConfigID
 * @method SiteConfig SiteConfig()
 * @mixin AddressDataExtension
 * @mixin ContactDataExtension
 */
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
