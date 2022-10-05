<?php

namespace Dynamic\Base\Tests\Model;

use Dynamic\Base\Model\NavigationGroup;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;

/**
 * Class NavigationGroupTest.
 */
class NavigationGroupTest extends SapphireTest
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
        $object = Injector::inst()->create(NavigationGroup::class);
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
        $this->assertNull($fields->dataFieldByName('NavigationLinks'));

        $object = $this->objFromFixture(NavigationGroup::class, 'one');
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
        $this->assertNotNull($fields->dataFieldByName('NavigationLinks'));
    }

    /**
     *
     */
    public function testValidateTitle()
    {
        $object = $this->objFromFixture(NavigationGroup::class, 'one');
        $object->Title = '';
        $this->expectException(ValidationException::class);
        $object->write();
    }

    /**
     *
     */
    public function testCanView()
    {
        $object = $this->objFromFixture(NavigationGroup::class, 'one');

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
        $object = $this->objFromFixture(NavigationGroup::class, 'one');

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
        $object = $this->objFromFixture(NavigationGroup::class, 'one');

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
        $object = $this->objFromFixture(NavigationGroup::class, 'one');

        $admin = $this->objFromFixture(Member::class, 'admin');
        $this->assertTrue($object->canCreate($admin));

        $member = $this->objFromFixture(Member::class, 'default');
        $this->assertTrue($object->canCreate($member));
    }
}
