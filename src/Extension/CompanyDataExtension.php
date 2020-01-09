<?php

namespace Dynamic\Base\Extension;

use Dynamic\Base\Model\CompanyAddress;
use Dynamic\Locator\Location;
use Dynamic\SilverStripeGeocoder\Form\GoogleMapField;
use Dynamic\SilverStripeGeocoder\GoogleGeocoder;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;

/**
 * Class CompanyConfig.
 *
 * @property string $CompanyName
 * @property string $Phone
 * @property string $Email
 * @property bool $ShowDirections
 * @property string $Hours
 */
class CompanyDataExtension extends DataExtension
{
    /**
     * @var array
     */
    private static $db = array(
        'CompanyName' => 'Varchar(200)',
    );

    /**
     * @var array
     */
    private static $has_many = [
        'Addresses' => CompanyAddress::class,
    ];

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName([
            'CompanyName',
        ]);

        $addressField = GridField::create(
            'Addresses',
            'Locations',
            $this->owner->Addresses()->sort('SortOrder'),
            GridFieldConfig_RelationEditor::create()
        );

        $fields->addFieldsToTab('Root.Company', array(
            HeaderField::create('CompanyInfo', 'Company Information', 3),
            LiteralField::create(
                'EnterInfo',
                '<p>Enter information about your company.</p>'
            ),
            TextField::create('CompanyName', 'Company Name'),
            $addressField,
        ));
    }
}
