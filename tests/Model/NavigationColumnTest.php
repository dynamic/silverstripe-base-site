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
     * Why the publish fix for #185 lives on NavigationGroup and not here.
     *
     * A column's effective $owns is ['FileTracking'], contributed by silverstripe/assets'
     * FileLinkTracking extension (applied to every DataObject, which also declares that
     * relation). It is not 'NavigationGroups', so the traversal never descends to the
     * column's groups, and FileTracking - which records asset-shortcode usage - holds no
     * rows for a navigation column. DataObject::findRelatedObjects() walks each $owns entry
     * through the relation of the same name, so with nothing to merge findOwned() returns an
     * empty list here even though one of the column's groups owns a draft link. A
     * column-level hook built on the same traversal would therefore publish nothing: inert,
     * not a second layer of the same fix.
     *
     * The group-level hook covers the real editor paths because
     * GridFieldDetailForm_ItemRequest::onSave() calls write() on the NavigationGroup itself.
     *
     * @return void
     */
    public function testColumnDeclaresNoOwnsSoAColumnLevelHookWouldBeInert(): void
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

        // The relation exists two levels down...
        $this->assertSame(1, $column->NavigationGroups()->count());
        $this->assertSame(1, $group->NavigationLinks()->count());

        // ...but the column's only $owns entry is FileTracking (never NavigationGroups),
        // and that relation holds no rows here, so the traversal bottoms out empty. The
        // relation's declared type is deliberately not asserted: it is a silverstripe/assets
        // implementation detail, and the inertness below holds whatever it is.
        $this->assertNotContains('NavigationGroups', $column->config()->get('owns'));
        $this->assertContains('FileTracking', $column->config()->get('owns'));
        $this->assertSame(0, $column->FileTracking()->count());
        $this->assertSame([], $column->findOwned(false)->toArray());

        // Saving the column therefore changes nothing about the link's stage - the fix for
        // this chain is the NavigationGroup hook, exercised in NavigationGroupTest.
        $column->Title = 'Column One Renamed';
        $column->write();
        $this->assertFalse($link->isPublished());
    }
}
