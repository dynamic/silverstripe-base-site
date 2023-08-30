<?php

namespace Dynamic\Base\Extension;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;
use UncleCheese\DisplayLogic\Forms\Wrapper;

/**
 * Class \Dynamic\Base\Extension\IntegrationsDataExtension
 *
 * @property IntegrationsDataExtension $owner
 * @property bool $UseGA
 * @property string $GACode
 * @property bool $UseGTM
 * @property string $GTMHeadCode
 * @property string $GTMBodyCode
 * @property bool $UseHubSpot
 * @property string $HubSpotAccountID
 */
class IntegrationsDataExtension extends DataExtension
{
    /**
     * @var array
     */
    private static $db = array(
        'UseGA' => 'Boolean',
        'GACode' => 'Varchar(16)',
        'UseGTM' => 'Boolean',
        'GTMHeadCode' => 'HTMLText',
        'GTMBodyCode' => 'HTMLText',
        'UseHubSpot' => 'Boolean',
        'HubSpotAccountID' => 'Varchar(16)',
    );

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $hubspot = $fields->dataFieldByName('HubSpotAccountID');

        $fields->removeByName([
            'UseGA',
            'GACode',
            'UseGTM',
            'GTMHeadCode',
            'GTMBodyCode',
            'HubSpotAccountID',
        ]);

        $fields->addFieldsToTab(
            'Root.Integrations',
            [
                CheckboxField::create('UseGA', 'Enable Google Analytics', $this->owner->GACode),
                Wrapper::create(
                    $gaCode = TextField::create('GACode')
                        ->setTitle('Google Analytics Profile ID')
                        ->setDescription('in the format <strong>UA-XXXXX-X</strong>')
                )->displayIf('UseGA')->isChecked()->end(),
                CheckboxField::create('UseGTM', 'Enable Google Tag Manager', $this->owner->GTMCode),
                Wrapper::create(
                    LiteralField::create(
                        'GTMDescription',
                        '<p>It is strongly recomended to set up a google analytics tag in tag manager,
                    instead of managing tags and analytics sepratly.</p>'
                    ),
                    $gtmHeadCode = TextareaField::create('GTMHeadCode')
                        ->setTitle('Google Tag Manager Head Code')
                        ->setDescription('The code that should go in the &lt;head&gt; tag.'),
                    $gtmBodyCode = TextareaField::create('GTMBodyCode')
                        ->setTitle('Google Tag Manager Body Code')
                        ->setDescription('The code that goes after the opening &lt;body&gt; tag.')
                )->displayIf('UseGTM')->isChecked()->end(),
                CheckboxField::create('UseHubSpot', 'Enable HubSpot', $this->owner->UseHubSpot),
                Wrapper::create(
                    TextField::create('HubSpotAccountID')
                        ->setTitle('HubSpot Tracking-ID')
                        ->setDescription('~7 digits')
                )->displayIf('UseHubSpot')->isChecked()->end()
            ]
        );
    }
}
