<?php

namespace Dynamic\Base\Test\Model;

use Dynamic\Base\Model\NavigationColumn;
use Dynamic\Base\Model\NavigationGroup;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\LinkField\Models\ExternalLink;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;

/**
 * Class NavigationColumnTest.
 */
class NavigationColumnTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = '../fixtures.yml';

    /**
     *
     */
    public function testGetCMSFields()
    {
        $object = Injector::inst()->create(NavigationColumn::class);
        $fields = $object->getCMSFields();

        $this->assertInstanceOf(FieldList::class, $fields);
        $this->assertNull($fields->dataFieldByName('NavigationLinks'));

        $object = $this->objFromFixture(NavigationColumn::class, 'one');
        $fields = $object->getCMSFields();

        $this->assertInstanceOf(FieldList::class, $fields);
        $this->assertNotNull($fields->dataFieldByName('NavigationGroups'));
    }

    /**
     *
     */
    public function testValidateTitle()
    {
        $object = $this->objFromFixture(NavigationColumn::class, 'one');
        $object->Title = '';
        $this->expectException(ValidationException::class);
        $object->write();
    }

    /**
     *
     */
    public function testCanView()
    {
        $object = $this->objFromFixture(NavigationColumn::class, 'one');

        $admin = $this->objFromFixture(Member::class, 'admin');
        $this->assertTrue($object->canView($admin));

        $member = $this->objFromFixture(Member::class, 'default');
        $this->assertTrue($object->canView($member));
    }

    /**
     *
     */
    public function testCanEdit()
    {
        $object = $this->objFromFixture(NavigationColumn::class, 'one');

        $admin = $this->objFromFixture(Member::class, 'admin');
        $this->assertTrue($object->canEdit($admin));

        $member = $this->objFromFixture(Member::class, 'default');
        $this->assertTrue($object->canEdit($member));
    }

    /**
     *
     */
    public function testCanDelete()
    {
        $object = $this->objFromFixture(NavigationColumn::class, 'one');

        $admin = $this->objFromFixture(Member::class, 'admin');
        $this->assertTrue($object->canDelete($admin));

        $member = $this->objFromFixture(Member::class, 'default');
        $this->assertTrue($object->canDelete($member));
    }

    /**
     *
     */
    public function testCanCreate()
    {
        $object = $this->objFromFixture(NavigationColumn::class, 'one');

        $admin = $this->objFromFixture(Member::class, 'admin');
        $this->assertTrue($object->canCreate($admin));

        $member = $this->objFromFixture(Member::class, 'default');
        $this->assertTrue($object->canCreate($member));
    }

    /**
     * Saving a NavigationColumn publishes nothing, and the primary reason is that the column
     * has no publish hook at all: NavigationColumn neither uses PublishesOwnedRecords nor
     * declares a write hook.
     *
     * The secondary reason matters only if a hook is added later. A column's effective $owns is
     * just FileTracking (contributed by silverstripe/assets' FileLinkTracking, which is applied
     * to every DataObject), and that relation records asset-shortcode usage, so it holds no
     * rows for a navigation column. findOwned() walks each $owns entry through the relation of
     * the same name, so it returns an empty list here even though one of the column's groups
     * owns a draft link - a hook built on that traversal would publish nothing without
     * 'NavigationGroups' being owned as well. The group-level hook covers the real editor paths,
     * because GridFieldDetailForm_ItemRequest::onSave() calls write() on the group itself.
     *
     * @return void
     */
    public function testSavingANavigationColumnPublishesNothing(): void
    {
        $column = $this->objFromFixture(NavigationColumn::class, 'one');

        $group = NavigationGroup::create(['Title' => 'Group In Column', 'NavigationColumnID' => $column->ID]);
        $group->write();

        $link = ExternalLink::create([
            'LinkText' => 'Footer link',
            'ExternalUrl' => 'https://example.test/footer',
            'OwnerID' => $group->ID,
            'OwnerClass' => NavigationGroup::class,
            'OwnerRelation' => 'NavigationLinks',
        ]);
        $link->write();

        $this->assertSame(1, $column->NavigationGroups()->count());
        $this->assertSame(1, $group->NavigationLinks()->count());

        $this->assertNotContains('NavigationGroups', $column->config()->get('owns'));
        $this->assertContains('FileTracking', $column->config()->get('owns'));
        $this->assertSame(0, $column->FileTracking()->count());
        $this->assertSame([], $column->findOwned(false)->toArray());

        $column->Title = 'Column One Renamed';
        $column->write();

        $this->assertFalse($link->isPublished());
    }
}
