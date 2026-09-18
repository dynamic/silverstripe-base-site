<?php

namespace Dynamic\Base\Model;

use Dynamic\Base\Traits\PublishesOwnedRecords;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\LinkField\Form\MultiLinkField;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Versioned\GridFieldArchiveAction;
use Symbiote\GridFieldExtensions\GridFieldAddExistingSearchButton;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * Class NavigationGroup.
 *
 * NavigationLinks are versioned but this class isn't, so the `$owns` declaration below never
 * cascades a publish on its own. PublishesOwnedRecords is what takes those links live when a
 * group is saved - see docs/en/index.md for the footer chain and what is deliberately not
 * published.
 *
 * @property string $Title
 * @property int $SortOrder
 * @property int $NavigationColumnID
 * @method NavigationColumn NavigationColumn()
 * @method HasManyList|Link[] NavigationLinks()
 */
class NavigationGroup extends DataObject
{
    use PublishesOwnedRecords;

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
    private static $db = [
        'Title' => 'Varchar(255)',
        'SortOrder' => 'Int',
    ];

    /**
     * @var array
     */
    private static $has_one = [
        'NavigationColumn' => NavigationColumn::class,
    ];

    /**
     * @var array
     */
    private static $has_many = [
        'NavigationLinks' => Link::class . '.Owner',
    ];

    /**
     * @var array|string[]
     */
    private static array $owns = [
        'NavigationLinks',
    ];

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
     * Whether onBeforeWrite() has run for the write() currently in flight.
     *
     * preWrite() runs validateWrite() *before* onBeforeWrite(), so this is never raised on the
     * rejected-write path that fires the same onAfterSkippedWrite() hook - which is what lets
     * this class answer the trait's skipped-write question exactly instead of inheriting the
     * validate() heuristic. Lowered in preWrite(), raised in onBeforeWrite(), nothing else.
     *
     * @var bool
     */
    private bool $beforeWriteCompleted = false;

    /**
     * @return string
     */
    public function LinkList()
    {
        if ($this->NavigationLinks()) {
            return $this->NavigationLinks()->count();
        }

        return 0;
    }

    /**
     * @return \SilverStripe\Forms\FieldList
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {


            $fields->removeByName([
                'SortOrder',
                'NavigationColumnID',
                'NavigationLinks',
            ]);

            $fields->dataFieldByName('Title')
                ->setDescription('For internal reference only');

            if ($this->ID) {
                $fields->addFieldsToTab('Root.Main', [
                    MultiLinkField::create('NavigationLinks')
                        ->setTitle('Links')
                        ->setDescription('Add links to this group to display in your footer navigation'),
                ]);
            }
        });
        return parent::getCMSFields();
    }

    /**
     * @return ValidationResult
     */
    public function validate(): ValidationResult
    {
        $result = parent::validate();

        if (!$this->Title) {
            $result->addError('A Title is required before you can save');
        }

        return $result;
    }

    /**
     * This record itself - the one that declares the $owns relation over NavigationLinks.
     *
     * @return DataObject
     */
    protected function getOwnedRecordsOwner(): DataObject
    {
        return $this;
    }

    /**
     * Re-initialise the write flag at the head of every write().
     *
     * This is the flag's only lowering statement, and it is sufficient: write() calls
     * preWrite() unconditionally, so both onAfterSkippedWrite() dispatch sites read a flag that
     * belongs to the write in flight. Lowering it here rather than at the tail of a successful
     * write() closes the window a tail statement cannot reach - writeBaseRecord(),
     * writeManipulation() and writeRelations() all run between onBeforeWrite() and
     * onAfterWrite() and any can throw, and a flag raised by such an aborted write would
     * otherwise publish links on the *next* write of this instance. It cannot live in a finally
     * around write(), because the rejected-write dispatch happens inside parent::preWrite().
     *
     * @param bool $skipValidation
     * @return void
     */
    protected function preWrite(bool $skipValidation = false)
    {
        $this->beforeWriteCompleted = false;

        parent::preWrite($skipValidation);
    }

    /**
     * Raise the write flag once this write has cleared onBeforeWrite().
     *
     * Raised after the parent call, so a throw from the extend('onBeforeWrite') dispatch inside
     * parent leaves it down. parent::onBeforeWrite() is required - the framework's brokenOnWrite
     * sentinel throws when an override forgets it. Untyped for subclass compatibility, as on
     * PublishesOwnedRecords::onAfterSkippedWrite().
     *
     * @return void
     */
    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $this->beforeWriteCompleted = true;
    }

    /**
     * Publish this group's owned NavigationLinks after the group is written.
     *
     * parent::onAfterWrite() fetches generated columns and dispatches extend('onAfterWrite') to
     * every other extension on this class; unlike onBeforeWrite() nothing in the framework
     * catches it being dropped. publishOwnedRecords() sits after it deliberately: parent hands
     * control to arbitrary third-party extensions, any of which can throw, and a save that ends
     * in an error should not have published anything on its way out.
     *
     * @return void
     */
    protected function onAfterWrite()
    {
        parent::onAfterWrite();

        $this->publishOwnedRecords();
    }

    /**
     * The trait's skipped-write gate, answered exactly rather than heuristically: publish if
     * and only if onBeforeWrite() ran for this write.
     *
     * A pure read, not a consume-and-reset. The framework reaches the gate at most once per
     * write() (its two dispatch sites are mutually exclusive branches) and preWrite()
     * re-initialises the flag first, so resetting it here would guard nothing and would make a
     * second call in the same write disagree with the first.
     *
     * @return bool
     */
    protected function shouldPublishOwnedRecordsOnSkippedWrite(): bool
    {
        return $this->beforeWriteCompleted;
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
     * Every member - including none - may edit, which also governs publishing: a linkfield Link
     * resolves canPublish() through its owner's canEdit(), so the permission gate in
     * PublishesOwnedRecords::publishOwnedRecords() never denies for links owned by this class.
     * Restrict this, or add an explicit canPublish() override, if footer links need gating.
     *
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
