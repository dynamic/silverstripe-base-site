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
 * cascades a publish on its own - the same inert-ownership shape #174 fixed for SiteConfig.
 * PublishesOwnedRecords is what actually takes those links live when a group is saved.
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
     * Whether onBeforeWrite() has run for the write() currently in flight on this record.
     *
     * Per-record state is the whole point: DataObject::preWrite() runs validateWrite()
     * *before* onBeforeWrite(), so this is never raised on the validate-and-reject path that
     * also fires onAfterSkippedWrite(). It lets this class answer the trait's skipped-write
     * question exactly instead of inheriting the validate() heuristic - that heuristic exists
     * only because Extension consumers are shared Injector singletons and cannot hold
     * per-record state.
     *
     * Its lifecycle is exactly two statements, both load-bearing: lowered at the head of
     * preWrite(), raised after parent::onBeforeWrite(). Nothing else touches it.
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
     * The flag's only lowering statement, and sufficient on its own: one producer
     * (onBeforeWrite()), one consumer (shouldPublishOwnedRecordsOnSkippedWrite()), and
     * write() calls preWrite() unconditionally first - so both onAfterSkippedWrite() dispatch
     * sites read a flag belonging to the write in flight: false on preWrite()'s
     * validate-and-reject path, true on write()'s no-changes branch.
     *
     * Lowering here, rather than at the tail of a successful write(), is what closes the abort
     * window no tail statement could reach: writeBaseRecord(), writeManipulation() and
     * writeRelations() all run between onBeforeWrite() and onAfterWrite() and any can throw.
     * A flag raised by such an aborted write would otherwise survive into the next write of the
     * same instance and publish links from a save the editor was just told failed.
     *
     * It cannot live in a finally around write(): the rejected-write dispatch of
     * onAfterSkippedWrite() happens *inside* parent::preWrite(), so this is the last point
     * before that hook where a stale value can still be corrected.
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
     * Raised *after* parent::onBeforeWrite() so a throw from the extend('onBeforeWrite')
     * dispatch inside parent leaves the flag down - the right answer, since that write never
     * reached the point of no return.
     *
     * parent::onBeforeWrite() must be called: the framework's brokenOnWrite sentinel throws a
     * LogicException when an override forgets it.
     *
     * Untyped for subclass compatibility - see PublishesOwnedRecords::onAfterSkippedWrite().
     *
     * @return void
     */
    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $this->beforeWriteCompleted = true;
    }

    /**
     * Publish the group's owned NavigationLinks after the group is written.
     *
     * parent::onAfterWrite() must not be dropped: it fetches generated columns and dispatches
     * extend('onAfterWrite') to every other extension on this class. Unlike onBeforeWrite()
     * there is no framework sentinel that fails when this call goes missing, so nothing here
     * catches it being deleted.
     *
     * publishOwnedRecords() runs last, after that parent call, deliberately: parent hands
     * control to arbitrary third-party extensions, any of which can throw, and aborting before
     * publishing keeps links in draft behind a save that ended in an error instead of taking
     * them live from it.
     *
     * Untyped for subclass compatibility; the flag is not lowered here, preWrite() owns its
     * whole lifecycle.
     *
     * @return void
     */
    protected function onAfterWrite()
    {
        parent::onAfterWrite();

        $this->publishOwnedRecords();
    }

    /**
     * Answer the trait's skipped-write gate exactly, without re-running validate(): publish if
     * and only if onBeforeWrite() ran for this write - true for every write validateWrite()
     * accepted, false for the one preWrite() rejected.
     *
     * A pure read. The framework reaches the gate at most once per write() (its two dispatch
     * sites are mutually exclusive branches) and preWrite() re-initialises the flag first, so
     * consuming it here would guard nothing while making a second call disagree with the first.
     *
     * This is why a footer link no longer stays in draft behind a successful
     * write($skipValidation: true) on a group whose validate() would have objected - the
     * heuristic this replaces is documented on
     * PublishesOwnedRecords::shouldPublishOwnedRecordsOnSkippedWrite().
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
