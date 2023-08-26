<?php

namespace Dynamic\Base\Model;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObject;
use SilverStripe\SiteConfig\SiteConfig;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * Class NavigationColumn.
 *
 * @property string $Title
 * @property int $SortOrder
 * @property int $ConfigID
 * @method DataList|NavigationGroup[] NavigationGroups()
 */
class NavigationColumn extends DataObject
{
    /**
     * @var string
     */
    private static $singular_name = 'Column';

    /**
     * @var string
     */
    private static $plural_name = 'Columns';

    /**
     * @var string
     */
    private static $table_name = 'NavigationColumn';

    /**
     * @var array
     */
    private static $db = array(
        'Title' => 'Varchar(255)',
        'SortOrder' => 'Int',
    );

    /**
     * @var array
     */
    private static $has_one = array(
        'Config' => SiteConfig::class,
    );

    /**
     * @var array
     */
    private static $has_many = array(
        'NavigationGroups' => NavigationGroup::class,
    );

    /**
     * @var array
     */
    private static $summary_fields = [
        'Title' => 'Title',
        'GroupList' => 'Groups',
        'LinkList' => 'Links',
    ];

    /**
     * @var array
     */
    private static $searchable_fields = [
        'Title',
    ];

    /**
     * @return \SilverStripe\Forms\FieldList
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->removeByName(array(
                'ConfigID',
                'SortOrder',
                'NavigationGroups',
            ));

            $fields->dataFieldByName('Title')
                ->setDescription('For internal reference only');

            // navigation groups
            if ($this->ID) {
                $config = GridFieldConfig_RecordEditor::create()
                    ->removeComponentsByType([
                        GridFieldAddExistingAutocompleter::class,
                        GridFieldDeleteAction::class,
                    ])->addComponents(
                        new GridFieldOrderableRows('SortOrder'),
                        new GridFieldDeleteAction(false)
                    );
                $footerLinks = GridField::create(
                    'NavigationGroups',
                    'Link Groups',
                    $this->NavigationGroups()->sort('SortOrder'),
                    $config
                );

                $fields->addFieldsToTab('Root.Main', array(
                    $footerLinks
                        ->setDescription('Add a group of links to a column in the footer navigation area'),
                ));
            }
        });
        return parent::getCMSFields();
    }


    /**
     * @return string
     */
    public function GroupList()
    {
        if ($this->NavigationGroups()) {
            $i = 0;
            foreach ($this->NavigationGroups()->sort('SortOrder') as $link) {
                ++$i;
            }
        }

        return $i;
    }

    /**
     * @return string
     */
    public function LinkList()
    {
        $i = 0;

        if ($this->NavigationGroups()) {
            foreach ($this->NavigationGroups() as $group) {
                foreach ($group->NavigationLinks() as $link) {
                    ++$i;
                }
            }
        }

        return $i;
    }

    /**
     * @return \SilverStripe\ORM\ValidationResult
     */
    public function validate()
    {
        $result = parent::validate();

        if (!$this->Title) {
            $result->addError('A Title is required before you can save');
        }

        return $result;
    }

    /**
     * Set permissions, allow all users to access by default.
     * Override in descendant classes, or use PermissionProvider.
     *
     * @param null $member
     * @param array $context
     *
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        return true;
    }

    /**
     * @param null $member
     *
     * @return bool
     */
    public function canView($member = null, $context = [])
    {
        return true;
    }

    /**
     * @param null $member
     *
     * @return bool
     */
    public function canEdit($member = null, $context = [])
    {
        return true;
    }

    /**
     * @param null $member
     *
     * @return bool
     */
    public function canDelete($member = null, $context = [])
    {
        return true;
    }
}
