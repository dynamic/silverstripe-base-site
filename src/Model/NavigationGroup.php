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
use SilverStripe\ORM\PolymorphicHasManyList;
use SilverStripe\Versioned\Versioned;
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
 * @method PolymorphicHasManyList|Link[] NavigationLinks()
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
     * Whether onBeforeWrite() has run for the write() currently in flight. Lowered in preWrite(),
     * raised in onBeforeWrite(), nothing else - so the rejected-write path, which fires the same
     * onAfterSkippedWrite() hook, never raises it.
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
     * Re-initialise the write flag at the head of every write(). Lowered here rather than at the
     * tail of a successful write() so a write aborted between onBeforeWrite() and onAfterWrite()
     * cannot leave a flag that lets the next write publish these links.
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
     * Raise the write flag once this write has cleared onBeforeWrite(). After the parent call, so
     * a throw from the extend('onBeforeWrite') dispatch inside parent leaves it down.
     *
     * @return void
     */
    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $this->beforeWriteCompleted = true;
    }

    /**
     * Publish this group's owned NavigationLinks after the group is written - after
     * parent::onAfterWrite(), which dispatches to arbitrary third-party extensions, so a save that
     * ends in an error has not published anything on its way out.
     *
     * @return void
     */
    protected function onAfterWrite()
    {
        parent::onAfterWrite();

        $this->publishOwnedRecords();
    }

    /**
     * The trait's skipped-write gate, answered exactly rather than heuristically: publish if and
     * only if onBeforeWrite() ran for this write. A read, not a consume-and-reset - preWrite()
     * re-initialises the flag, so a second call in the same write must agree with the first.
     *
     * @return bool
     */
    protected function shouldPublishOwnedRecordsOnSkippedWrite(): bool
    {
        return $this->beforeWriteCompleted;
    }

    /**
     * Archive this group's owned NavigationLinks before the group goes, so deleting a footer group
     * does not leave Link rows whose OwnerID points at a record that no longer exists - see
     * dynamic/silverstripe-base-site#210. doArchive() rather than delete(): Link is Versioned, and a
     * plain delete only removes the stage being read, which left the Live row behind (a link the
     * trait above has already published has no path back out of the CMS). The owned set is read from
     * both stages rather than the ambient reading stage, and the links are collected as IDs before
     * any of them is archived, so neither the reading mode nor the loop's own mutations can decide
     * which orphans survive.
     *
     * @return void
     */
    protected function onBeforeDelete()
    {
        parent::onBeforeDelete();

        // Which links exist depends on the stage being read, and delete() is not only reached from
        // the CMS: a task or a cleanup script can run with the reading mode on Live, where a Draft
        // lookup misses every draft-only link, and in Draft it misses a live-only row. Ask both
        // stages and dedupe by ID, exactly as PublishesOwnedRecords pins its own owned query.
        $linkIDs = [];
        foreach ([Versioned::DRAFT, Versioned::LIVE] as $stage) {
            $stageIDs = Versioned::withVersionedMode(function () use ($stage): array {
                Versioned::set_stage($stage);

                return $this->NavigationLinks()->column('ID');
            });
            foreach ($stageIDs as $linkID) {
                $linkIDs[$linkID] = true;
            }
        }

        foreach (array_keys($linkIDs) as $linkID) {
            // Draft first, since that is the row carrying the editable state; a row that exists
            // only on Live is still owned here and still has to go.
            $link = Versioned::withVersionedMode(function () use ($linkID) {
                Versioned::set_stage(Versioned::DRAFT);
                $draftLink = Versioned::get_by_stage(Link::class, Versioned::DRAFT)->byID($linkID);
                if ($draftLink !== null) {
                    return $draftLink;
                }
                Versioned::set_stage(Versioned::LIVE);

                return Versioned::get_by_stage(Link::class, Versioned::LIVE)->byID($linkID);
            });

            if ($link === null) {
                continue;
            }

            // The same split LinkFieldController::linkDelete() makes: a link that has lost the
            // Versioned extension has no stage to archive out of, so it is deleted outright rather
            // than left behind as an orphan.
            if ($link->hasExtension(Versioned::class)) {
                $link->doArchive();
            } else {
                $link->delete();
            }
        }
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
     * resolves canPublish() through canEdit() to this record, so the gate in
     * PublishesOwnedRecords::publishOwnedRecord() never denies for links owned by this class, and
     * saving a group takes live whatever those links then hold. Restrict it here, in a descendant
     * class: this returns true without consulting extendedCan(), so an Extension cannot override it,
     * and a canPublish() on this class would be inert for the same reason - the check is made on
     * the Link, never on its owner. See dynamic/silverstripe-base-site#211.
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
