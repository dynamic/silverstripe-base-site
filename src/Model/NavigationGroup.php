<?php

namespace Dynamic\Base\Model;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\GridFieldArchiveAction;
use Symbiote\GridFieldExtensions\GridFieldAddExistingSearchButton;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * Class NavigationGroup.
 *
 * @property string $Title
 * @property int $SortOrder
 * @property int $NavigationColumnID
 * @method NavigationColumn NavigationColumn()
 * @method ManyManyList|SiteTree[] NavigationLinks()
 */
class NavigationGroup extends DataObject
{
    /**
     * @var string
     */
    private static $singular_name = 'Link Group';

    /**
     * @var string
     */
    private static $plural_name = 'Link Groups';

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
        'NavigationColumn' => NavigationColumn::class,
    );

    /**
     * @var array
     */
    private static $many_many = array(
        'NavigationLinks' => SiteTree::class,
    );

    /**
     * @var array
     */
    private static $many_many_extraFields = array(
        'NavigationLinks' => array(
            'SortOrder' => 'Int',
        ),
    );

    /**
     * @var string
     */
    private static $table_name = 'NavigationGroup';

    /**
     * @var array
     */
    private static $summary_fields = [
        'Title' => 'Title',
        'LinkList' => 'Links',
    ];

    /**
     * @var array
     */
    private static $searchable_fields = [
        'Title',
    ];

    /**
     * @return string
     */
    public function LinkList()
    {
        if ($this->NavigationLinks()) {
            $i = 0;
            foreach ($this->NavigationLinks()->sort('SortOrder') as $link) {
                ++$i;
            }
        }

        return $i;
    }

    /**
     * @return \SilverStripe\Forms\FieldList
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {


            $fields->removeByName(array(
                'SortOrder',
                'NavigationColumnID',
                'NavigationLinks',
            ));

            $fields->dataFieldByName('Title')
                ->setDescription('For internal reference only');

            if ($this->ID) {
                $config = GridFieldConfig_RelationEditor::create()
                    ->removeComponentsByType([
                        GridFieldAddNewButton::class,
                        GridFieldAddExistingAutocompleter::class,
                        GridFieldEditButton::class,
                        GridFieldArchiveAction::class,
                    ])->addComponents(
                        new GridFieldOrderableRows('SortOrder'),
                        new GridFieldAddExistingSearchButton()
                    );

                $linksField = GridField::create(
                    'NavigationLinks',
                    'Links',
                    $this->NavigationLinks()->sort('SortOrder'),
                    $config
                );

                $fields->addFieldsToTab('Root.Main', [
                    $linksField
                        ->setDescription('Add links to this group to display in your footer navigation'),
                ]);
            }
        });
        return parent::getCMSFields();
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
    public function canView($member = null)
    {
        return true;
    }

    /**
     * @param null $member
     *
     * @return bool
     */
    public function canEdit($member = null)
    {
        return true;
    }

    /**
     * @param null $member
     *
     * @return bool
     */
    public function canDelete($member = null)
    {
        return true;
    }
}
