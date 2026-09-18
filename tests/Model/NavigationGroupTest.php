<?php

namespace Dynamic\Base\Test\Model;

use Dynamic\Base\Model\NavigationGroup;
use Dynamic\Base\Test\Extension\TemplateDataExtensionTestSpyLogger;
use Dynamic\Base\Test\Extension\ThrowingSocialLink;
use Dynamic\Base\Test\Extension\UnversionedOwnedStub;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Forms\FieldList;
use SilverStripe\LinkField\Models\ExternalLink;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\ChangeSet;
use SilverStripe\Versioned\Versioned;

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
     * UnversionedOwnedStub/ThrowingSocialLink are the same stubs TemplateDataExtensionTest
     * uses to cover PublishesOwnedRecords' guards - reused rather than duplicated.
     *
     * @var array
     */
    protected static $extra_dataobjects = [
        UnversionedOwnedStub::class,
        ThrowingSocialLink::class,
        MidWriteFailingNavigationGroup::class,
        DenyEditNavigationGroupStub::class,
        ThrowingIsModifiedOnDraftLink::class,
    ];

    /**
     * Applied for the whole class so NavigationGroupAfterWriteSpyExtension can prove
     * NavigationGroup::onAfterWrite() still calls parent::onAfterWrite() - the call that
     * dispatches extend('onAfterWrite') to every other extension on this class.
     *
     * @var array
     */
    protected static $required_extensions = [
        NavigationGroup::class => [
            NavigationGroupAfterWriteSpyExtension::class,
            NavigationGroupThrowingAfterWriteExtension::class,
        ],
    ];

    /**
     * Create a draft Link owned by the given NavigationGroup, mirroring what MultiLinkField
     * leaves behind: a written, unversioned-to-live record owned via the polymorphic
     * NavigationLinks has_many.
     *
     * @param NavigationGroup $group
     * @param array $values
     * @return ExternalLink
     */
    private function createDraftNavigationLink(NavigationGroup $group, array $values = []): ExternalLink
    {
        $link = ExternalLink::create(array_merge([
            'LinkText' => 'Footer link',
            'ExternalUrl' => 'https://example.test/footer',
            'OwnerID' => $group->ID,
            'OwnerClass' => NavigationGroup::class,
            'OwnerRelation' => 'NavigationLinks',
        ], $values));
        $link->write();

        return $link;
    }

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

    /**
    * Happy path: a draft NavigationLink goes live when the group that owns it is saved with a
    * changed column, via DataObject::write()'s onAfterWrite() branch.
    *
    * @return void
    */
    public function testNavigationLinksArePublishedWhenGroupFieldChanges(): void
    {
        $group = $this->objFromFixture(NavigationGroup::class, 'one');
        $link = $this->createDraftNavigationLink($group);

        $this->assertFalse($link->isPublished());

        $group->Title = 'Renamed Group';
        $group->write();

        $this->assertTrue($link->isPublished());

        $liveLink = Versioned::get_by_stage(ExternalLink::class, Versioned::LIVE)->byID($link->ID);
        $this->assertNotNull($liveLink, 'Expected a live version of the NavigationLink to exist.');
        $this->assertSame('https://example.test/footer', $liveLink->ExternalUrl);
    }

    /**
     * The other half of the reported bug, and the branch a real CMS edit actually takes:
     * MultiLinkField/LinkFieldController persist the Link record itself, so saving the
     * NavigationGroup afterwards changes no column of the group's own row and
     * DataObject::write() takes its onAfterSkippedWrite() branch instead of
     * onAfterWrite(). The link must still be published.
     *
     * @return void
     */
    public function testNavigationLinksArePublishedOnSkippedWriteWithNoGroupFieldChanges(): void
    {
        $group = $this->objFromFixture(NavigationGroup::class, 'one');
        $link = $this->createDraftNavigationLink($group);

        $this->assertFalse($link->isPublished());

        // No mutation to any NavigationGroup-owned field here - this must still publish.
        $group->write();

        $this->assertTrue($link->isPublished());
    }

    /**
    * A group whose validate() rejects the write publishes nothing: preWrite() fires the same
    * onAfterSkippedWrite() hook immediately before re-throwing ValidationException, with nothing
    * persisted.
    *
    * @return void
    */
    public function testNavigationLinksAreNotPublishedWhenGroupFailsValidation(): void
    {
        $group = $this->objFromFixture(NavigationGroup::class, 'one');
        $link = $this->createDraftNavigationLink($group);

        $group->Title = '';

        try {
            $group->write();
            $this->fail('Expected the missing Title to throw a ValidationException.');
        } catch (ValidationException $e) {
            // expected
        }

        $this->assertFalse($link->isPublished());
    }

    /**
     * Only links actually modified since their last publish may be republished - an
     * unmodified sibling must not gain a redundant ChangeSet/Version on every group save.
     *
     * @return void
     */
    public function testOnlyModifiedNavigationLinksAreRepublished(): void
    {
        $group = $this->objFromFixture(NavigationGroup::class, 'one');

        $unmodifiedLink = $this->createDraftNavigationLink($group, [
            'LinkText' => 'Unmodified',
            'ExternalUrl' => 'https://example.test/unmodified',
        ]);
        $modifiedLink = $this->createDraftNavigationLink($group, [
            'LinkText' => 'Modified',
            'ExternalUrl' => 'https://example.test/modified',
        ]);

        $group->Title = 'Renamed Group';
        $group->write();

        $this->assertTrue($unmodifiedLink->isPublished());
        $this->assertTrue($modifiedLink->isPublished());

        // Change only one link, then save the group again.
        $modifiedLink->ExternalUrl = 'https://example.test/modified-updated';
        $modifiedLink->write();

        $changeSetCountBefore = ChangeSet::get()->count();

        $group->Title = 'Renamed Group Again';
        $group->write();

        $changeSetCountAfter = ChangeSet::get()->count();

        // Exactly one new ChangeSet - for the modified link.
        $this->assertSame($changeSetCountBefore + 1, $changeSetCountAfter);

        $liveModified = Versioned::get_by_stage(ExternalLink::class, Versioned::LIVE)->byID($modifiedLink->ID);
        $this->assertSame('https://example.test/modified-updated', $liveModified->ExternalUrl);
    }

    /**
     * A group with no links at all must write without error and log nothing - the empty
     * result of findOwned() is a normal outcome, not a failure.
     *
     * @return void
     */
    public function testGroupWithNoNavigationLinksWritesWithoutErrorOrLogging(): void
    {
        $logger = new TemplateDataExtensionTestSpyLogger();
        Injector::inst()->registerService($logger, LoggerInterface::class);

        $group = NavigationGroup::create(['Title' => 'Empty Group']);
        $group->write();

        $this->assertTrue($group->isInDB());
        $this->assertSame(0, $group->NavigationLinks()->count());
        $this->assertEmpty($logger->records);

        // And the skipped-write branch of an empty group, which is the same code path with a
        // different entry point.
        $group->Title = 'Empty Group Renamed';
        $group->write();
        $group->write();

        $this->assertTrue($group->isInDB());
        $this->assertEmpty($logger->records);
    }

    /**
    * A child that is not Versioned is skipped cleanly, with nothing logged.
    *
    * @return void
    */
    public function testPublishOwnedRecordSkipsNonVersionedChild(): void
    {
        $group = $this->objFromFixture(NavigationGroup::class, 'one');

        $stub = UnversionedOwnedStub::create();
        $stub->write();

        $logger = new TemplateDataExtensionTestSpyLogger();
        Injector::inst()->registerService($logger, LoggerInterface::class);

        $method = new ReflectionMethod(NavigationGroup::class, 'publishOwnedRecord');
        $method->invoke($group, $stub);

        $this->assertFalse($stub->hasExtension(Versioned::class));
        $this->assertEmpty($logger->records);
    }

    /**
     * A child whose publishRecursive() throws must be logged and swallowed without blocking
     * its siblings or the group's own save. The failing link is created (and therefore
     * iterated by findOwned()) first, so a loop that aborted on the first failure would fail
     * this test rather than pass it by iteration-order luck.
     *
     * @return void
     */
    public function testThrowingChildIsLoggedAndDoesNotBlockSiblings(): void
    {
        $group = $this->objFromFixture(NavigationGroup::class, 'one');

        $badLink = ThrowingSocialLink::create([
            'SocialChannel' => 'instagram',
            'ExternalUrl' => 'https://instagram.example/profile',
            'OwnerID' => $group->ID,
            'OwnerClass' => NavigationGroup::class,
            'OwnerRelation' => 'NavigationLinks',
            'FailureMode' => 'generic',
        ]);
        $badLink->write();

        $goodLink = $this->createDraftNavigationLink($group, [
            'LinkText' => 'Good',
            'ExternalUrl' => 'https://example.test/good',
        ]);

        $logger = new TemplateDataExtensionTestSpyLogger();
        Injector::inst()->registerService($logger, LoggerInterface::class);

        $group->Title = 'Renamed Group';
        $group->write();

        $this->assertTrue($group->isInDB(), 'The group itself must still save.');
        $this->assertTrue($goodLink->isPublished(), 'The sibling link must still publish.');
        $this->assertFalse($badLink->isPublished(), 'The failing link must stay in draft.');

        $this->assertCount(1, $logger->records);
        $this->assertSame('error', $logger->records[0]['level']);
        $this->assertStringContainsString(ThrowingSocialLink::class, $logger->records[0]['message']);
        $this->assertArrayHasKey('exception', $logger->records[0]['context']);
        $this->assertInstanceOf(\RuntimeException::class, $logger->records[0]['context']['exception']);
    }

    /**
     * Same isolation, but for the ValidationException path and reached through the
     * onAfterSkippedWrite() branch, so neither hook re-throws out of the group's write().
     *
     * @return void
     */
    public function testThrowingChildDoesNotBlockSiblingsOnSkippedWrite(): void
    {
        $group = $this->objFromFixture(NavigationGroup::class, 'one');

        $badLink = ThrowingSocialLink::create([
            'SocialChannel' => 'instagram',
            'ExternalUrl' => 'https://instagram.example/profile',
            'OwnerID' => $group->ID,
            'OwnerClass' => NavigationGroup::class,
            'OwnerRelation' => 'NavigationLinks',
            'FailureMode' => 'validation',
        ]);
        $badLink->write();

        $goodLink = $this->createDraftNavigationLink($group, [
            'LinkText' => 'Good',
            'ExternalUrl' => 'https://example.test/good',
        ]);

        Injector::inst()->registerService(new TemplateDataExtensionTestSpyLogger(), LoggerInterface::class);

        // No group-field change: this is the onAfterSkippedWrite() branch.
        $group->write();

        $this->assertTrue($goodLink->isPublished());
        $this->assertFalse($badLink->isPublished());
    }

    /**
    * publishOwnedRecords() pins the reading stage to Draft. Every other test here runs in
    * SapphireTest's default draft mode and would pass without the pin, so this one forces
    * Stage.Live as the ambient mode, under which a draft-only link would never be returned by
    * findOwned() at all.
    *
    * @return void
    */
    public function testNavigationLinksPublishWhenAmbientReadingModeIsLive(): void
    {
        $group = $this->objFromFixture(NavigationGroup::class, 'one');
        $link = $this->createDraftNavigationLink($group);

        $this->assertFalse($link->isPublished());

        Versioned::withVersionedMode(function () use ($group, $link): void {
            Versioned::set_stage(Versioned::LIVE);

            $group->Title = 'Renamed Group';
            $group->write();
        });

        $this->assertTrue($link->isPublished());
    }

    /**
     * An already-published link that changes later must go live on the next group save, so
     * the hook isn't a first-publish-only side effect.
     *
     * @return void
     */
    public function testChangesToPublishedNavigationLinksAreRepublishedOnLaterSaves(): void
    {
        $group = $this->objFromFixture(NavigationGroup::class, 'one');
        $link = $this->createDraftNavigationLink($group);

        $group->Title = 'Renamed Group';
        $group->write();
        $this->assertTrue($link->isPublished());

        $link->ExternalUrl = 'https://example.test/footer-updated';
        $link->write();

        $group->Title = 'Renamed Group Again';
        $group->write();

        $liveLink = Versioned::get_by_stage(ExternalLink::class, Versioned::LIVE)->byID($link->ID);
        $this->assertSame('https://example.test/footer-updated', $liveLink->ExternalUrl);
    }

    /**
    * parent::onAfterWrite() on NavigationGroup is what dispatches extend('onAfterWrite') to every
    * other extension on the class and refreshes generated columns, and nothing in the framework
    * fails when it is dropped - so it is asserted here.
    *
    * @return void
    */
    public function testGroupWriteDispatchesOnAfterWriteToOtherExtensions(): void
    {
        NavigationGroupAfterWriteSpyExtension::$onAfterWriteCalls = 0;

        $group = $this->objFromFixture(NavigationGroup::class, 'one');
        $group->Title = 'Renamed Group';
        $group->write();

        $this->assertSame(
            1,
            NavigationGroupAfterWriteSpyExtension::$onAfterWriteCalls,
            'Expected parent::onAfterWrite() to dispatch extend("onAfterWrite") to extensions.'
        );
    }

    /**
    * A write($skipValidation: true) that genuinely succeeded must publish, even on a group whose
    * validate() would have objected. The trait's validate()-based default would read that as a
    * rejection; NavigationGroup's per-record flag answers exactly and publishes.
    *
    * @return void
    */
    public function testNavigationLinksPublishOnSkipValidationWriteWithNoGroupFieldChanges(): void
    {
        $group = NavigationGroup::create(['Title' => 'Valid Group']);
        $group->write();

        // Accepted only with validation skipped - validate() would reject an empty Title.
        $group->Title = '';
        $group->write(false, false, false, false, true);
        $this->assertSame('', $group->Title);
        $this->assertFalse($group->validate()->isValid());

        $link = $this->createDraftNavigationLink($group);
        $this->assertFalse($link->isPublished());

        // No changed column, so this is the onAfterSkippedWrite() branch again.
        $group->write(false, false, false, false, true);

        $this->assertTrue(
            $link->isPublished(),
            'A write() that succeeded must publish the group\'s draft links.'
        );
    }

    /**
    * The flag must be lowered by preWrite(), not merely by the previous write completing: this
    * instance has already had a successful write that raised it, so the rejected write that
    * follows is the one that proves it was cleared before the gate was consulted.
    *
    * @return void
    */
    public function testRejectedWriteAfterASuccessfulWriteDoesNotPublish(): void
    {
        $group = $this->objFromFixture(NavigationGroup::class, 'one');

        // A successful write, which raises the flag in onBeforeWrite(). The next write's
        // preWrite() is what must lower it again before the rejected write below is asked.
        $group->Title = 'Renamed Group';
        $group->write();

        $link = $this->createDraftNavigationLink($group);
        $this->assertFalse($link->isPublished());

        // Now rejected by validate() on the same instance - preWrite() must have cleared it.
        $group->Title = '';

        try {
            $group->write();
            $this->fail('Expected the missing Title to throw a ValidationException.');
        } catch (ValidationException $e) {
            // expected
        }

        $this->assertFalse(
            $link->isPublished(),
            'A rejected write must not publish a draft link, even after a prior successful'
                . ' write on the same instance raised the flag that preWrite() lowers.'
        );
    }

    /**
    * A genuine PHP \Error escapes publishOwnedRecord() rather than being logged as a routine
    * data-level failure - the catch is \Exception by design.
    *
    * @return void
    */
    public function testPHPErrorFromAChildEscapesRatherThanBeingSwallowed(): void
    {
        $group = $this->objFromFixture(NavigationGroup::class, 'one');

        $errorLink = ThrowingSocialLink::create([
            'SocialChannel' => 'instagram',
            'ExternalUrl' => 'https://instagram.example/profile',
            'OwnerID' => $group->ID,
            'OwnerClass' => NavigationGroup::class,
            'OwnerRelation' => 'NavigationLinks',
            'FailureMode' => 'error',
        ]);
        $errorLink->write();

        $logger = new TemplateDataExtensionTestSpyLogger();
        Injector::inst()->registerService($logger, LoggerInterface::class);

        $method = new ReflectionMethod(NavigationGroup::class, 'publishOwnedRecord');

        try {
            $method->invoke($group, $errorLink);
            $this->fail('Expected the simulated PHP Error to propagate out of publishOwnedRecord().');
        } catch (\Error $e) {
            // expected - deliberately not caught by the catch (\Exception) in the trait
        }

        $this->assertEmpty($logger->records, 'An \\Error must not be logged as a routine publish failure.');
        $this->assertFalse($errorLink->isPublished());
    }

    /**
    * An escaping \Error reached through a real write(), which is what docs/en/index.md
    * documents. The erroring link is created first so findOwned() reaches it before its healthy
    * sibling: \Error escapes the catch (\Exception) in publishOwnedRecord() and there is no
    * isolation above that point, so the sibling stays in draft too and nothing is logged as if
    * the failure were a data condition.
    *
    * @return void
    */
    public function testAnEscapingErrorAbortsTheWriteAndLeavesTheSiblingInDraft(): void
    {
        $group = $this->objFromFixture(NavigationGroup::class, 'one');

        $errorLink = ThrowingSocialLink::create([
            'SocialChannel' => 'instagram',
            'ExternalUrl' => 'https://instagram.example/profile',
            'OwnerID' => $group->ID,
            'OwnerClass' => NavigationGroup::class,
            'OwnerRelation' => 'NavigationLinks',
            'FailureMode' => 'error',
        ]);
        $errorLink->write();

        $goodLink = $this->createDraftNavigationLink($group, [
            'LinkText' => 'Healthy sibling',
            'ExternalUrl' => 'https://example.test/healthy',
        ]);

        $logger = new TemplateDataExtensionTestSpyLogger();
        Injector::inst()->registerService($logger, LoggerInterface::class);

        // A changed column, so write() takes the onAfterWrite() branch and reaches publishing.
        $group->Title = 'Erroring Group';

        try {
            $group->write();
            $this->fail('Expected the simulated PHP \Error to escape NavigationGroup::write().');
        } catch (\Error $e) {
            // expected - write() never completed, and docs/en/index.md says so
        }

        $this->assertFalse($errorLink->isPublished(), 'The erroring link must stay in draft.');
        $this->assertFalse(
            $goodLink->isPublished(),
            'An escaping \Error must abort the rest of the loop, so the healthy sibling'
                . ' must not have been published either.'
        );
        $this->assertEmpty(
            $logger->records,
            'An \Error must not be logged as a routine, data-level publish failure.'
        );

        // The half-applied save the docs describe: the group row is committed, because
        // onAfterWrite() runs after the write and before write()'s closing cache flush.
        $committed = DataObject::get(NavigationGroup::class)->byID($group->ID);
        $this->assertNotNull($committed, 'The group row must have been committed.');
        $this->assertSame('Erroring Group', $committed->Title);

        // And the instance is left mid-write: still dirty, which the docs also state.
        $this->assertTrue($group->isChanged('Title'));
    }

    /**
    * publishOwnedRecords() is the last statement in NavigationGroup::onAfterWrite(), after the
    * parent call that hands control to arbitrary third-party extensions - so a throwing extension
    * aborts the save before any link is taken live. A save that ends in an error publishes
    * nothing on its way out.
    *
    * @return void
    */
    public function testAThrowingAfterWriteHookAbortsBeforeTheLinksArePublished(): void
    {
        $group = $this->objFromFixture(NavigationGroup::class, 'one');
        $link = $this->createDraftNavigationLink($group);

        $this->assertFalse($link->isPublished());

        NavigationGroupThrowingAfterWriteExtension::$shouldThrow = true;

        try {
            $group->Title = 'Renamed Group';
            $group->write();
            $this->fail('Expected the throwing onAfterWrite() extension to abort the write.');
        } catch (\RuntimeException $e) {
            // expected - the row is written, the hook failed
        } finally {
            NavigationGroupThrowingAfterWriteExtension::$shouldThrow = false;
        }

        $this->assertFalse(
            $link->isPublished(),
            'A third-party onAfterWrite() extension that throws must abort the save before'
                . ' the group publishes its draft links.'
        );

        // The next clean write still publishes - the abort left nothing broken behind.
        $group->Title = 'Renamed Again';
        $group->write();

        $this->assertTrue(
            $link->isPublished(),
            'A write after an aborted one must still publish the group\'s draft links.'
        );
    }

    /**
    * shouldPublishOwnedRecordsOnSkippedWrite() is a pure read of the write flag: asked twice
    * about the same write it answers the same both times, and a write is never answered by an
    * earlier write's flag because preWrite() re-initialises it first.
    *
    * @return void
    */
    public function testTheSkippedWriteGateIsAPureReadAndRepeatsItself(): void
    {
        $group = NavigationGroup::create(['Title' => 'Pure Gate Group']);
        $group->write();

        $method = new ReflectionMethod(NavigationGroup::class, 'shouldPublishOwnedRecordsOnSkippedWrite');

        // A no-changes write reached onBeforeWrite(), so the gate answers true - twice.
        $group->write();
        $this->assertTrue($method->invoke($group));
        $this->assertTrue(
            $method->invoke($group),
            'The gate is documented as a pure read; a second call must not report false.'
        );

        // The next write re-initialises before it is asked, so a rejected write says false -
        // and keeps saying it.
        try {
            $group->Title = '';
            $group->write();
            $this->fail('Expected the missing Title to throw a ValidationException.');
        } catch (ValidationException $e) {
            // expected
        }

        $this->assertFalse($method->invoke($group));
        $this->assertFalse($method->invoke($group));
    }

    /**
     * The earlier abort window: DataObject::write() runs writeBaseRecord(),
     * writeManipulation() and writeRelations() between onBeforeWrite() and onAfterWrite(),
     * and a failure in any of them reaches neither of the two after-hooks. The flag raised
     * by such an aborted write then survives into the next write of the same instance, and if
     * that one is rejected by validateWrite() the draft link is published from a save the
     * editor was just told failed. Removing preWrite()'s clear turns this red.
     *
     * @return void
     */
    public function testAWriteThatFailsBeforeOnAfterWriteLeavesTheFlagClear(): void
    {
        MidWriteFailingNavigationGroup::$shouldFailRelations = true;
        $group = MidWriteFailingNavigationGroup::create(['Title' => 'Mid Fail Group']);

        try {
            $group->write();
            $this->fail('Expected writeRelations() to abort the write.');
        } catch (\RuntimeException $e) {
            // expected - the row is written, the write then aborted before onAfterWrite()
        } finally {
            MidWriteFailingNavigationGroup::$shouldFailRelations = false;
        }

        $this->assertTrue($group->isInDB());

        $link = ExternalLink::create([
            'LinkText' => 'Footer link',
            'ExternalUrl' => 'https://example.test/footer',
            'OwnerID' => $group->ID,
            'OwnerClass' => MidWriteFailingNavigationGroup::class,
            'OwnerRelation' => 'NavigationLinks',
        ]);
        $link->write();

        $this->assertFalse($link->isPublished());

        // Rejected by validate() on the same instance, with the flag left up by the abort.
        $group->Title = '';

        try {
            $group->write();
            $this->fail('Expected the missing Title to throw a ValidationException.');
        } catch (ValidationException $e) {
            // expected
        }

        $this->assertFalse(
            $link->isPublished(),
            'A write that failed between onBeforeWrite() and onAfterWrite() must not leave a'
                . ' flag that lets a later rejected write publish draft links.'
        );
    }

    /**
     * The permission gate is reached on this consumer, and it grants.
     *
     * Every other case in this class runs with no logged-in member, where the trait's
     * CLI-with-no-member exemption short-circuits before canPublish() is called at all - so none
     * of them says anything about the gate. Any member puts the gate in play: the exemption is
     * (!Director::is_cli() || $member !== null), which is true whenever a member exists, on the
     * CLI sapi or not. Publishing therefore proves the whole chain answered -
     * Link::canPublish() -> Versioned::canPublish() (ADMIN short-circuit, then extension hooks)
     * -> Link::canEdit() -> Link::canPerformAction('canEdit') -> NavigationGroup::canEdit() ->
     * true. That is the claim docs/en/index.md and NavigationGroup::canEdit() both make, and it
     * is pinned from the other direction by
     * testALinkOwnedByAGroupThatDeniesCanEditIsLeftInDraftWithAWarning().
     *
     * @return void
     */
    public function testThePublishGateIsEvaluatedAndGrantsForALinkOwnedByThisClass(): void
    {
        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        $member = Security::getCurrentUser();
        $this->assertNotNull($member, 'Precondition: the gate only runs when a member is present.');
        $this->assertFalse(
            Permission::checkMember($member, 'ADMIN'),
            'Precondition: a non-ADMIN member, so Versioned::canPublish() cannot short-circuit.'
        );

        $group = $this->objFromFixture(NavigationGroup::class, 'one');
        $link = $this->createDraftNavigationLink($group);

        $this->assertFalse($link->isPublished());

        $logger = new TemplateDataExtensionTestSpyLogger();
        Injector::inst()->registerService($logger, LoggerInterface::class);

        $group->Title = 'Gate Evaluated Group';
        $group->write();

        $this->assertTrue(
            $link->isPublished(),
            'A member who may edit the owning group must be able to take its link live.'
        );
        $this->assertCount(0, array_values(array_filter(
            $logger->records,
            fn (array $record): bool => str_contains($record['message'], 'Skipped publishing')
        )), 'The gate granted, so nothing may have been skipped.');
    }

    /**
     * The gate is live rather than decorative: when the owning record denies canEdit(), the link
     * stays in draft and exactly one warning names it.
     *
     * This is the behaviour a downstream project changes by overriding NavigationGroup::canEdit() -
     * the only lever there is, since that method returns true without consulting extendedCan() and
     * the publish check is made on the Link, not on its owner. Without this case a restriction that
     * silently stopped footer links publishing would show up only as a warning in someone's log.
     * Tracked as dynamic/silverstripe-base-site#211.
     *
     * @return void
     */
    public function testALinkOwnedByAGroupThatDeniesCanEditIsLeftInDraftWithAWarning(): void
    {
        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        $this->assertFalse(
            Permission::checkMember(Security::getCurrentUser(), 'ADMIN'),
            'Precondition: a non-ADMIN member, so Versioned::canPublish() cannot short-circuit.'
        );

        $group = DenyEditNavigationGroupStub::create(['Title' => 'Restricted Group']);
        $group->write();

        $link = ExternalLink::create([
            'LinkText' => 'Footer link',
            'ExternalUrl' => 'https://example.test/footer',
            'OwnerID' => $group->ID,
            'OwnerClass' => DenyEditNavigationGroupStub::class,
            'OwnerRelation' => 'NavigationLinks',
        ]);
        $link->write();

        $this->assertFalse($link->canEdit());
        $this->assertFalse($link->isPublished());

        $logger = new TemplateDataExtensionTestSpyLogger();
        Injector::inst()->registerService($logger, LoggerInterface::class);

        $group->Title = 'Restricted Group Renamed';
        $group->write();

        $this->assertFalse(
            $link->isPublished(),
            'A group that denies canEdit() must not take its owned link live.'
        );

        $skipLines = array_values(array_filter(
            $logger->records,
            fn (array $record): bool => str_contains($record['message'], 'Skipped publishing')
        ));
        $this->assertCount(1, $skipLines);
        $this->assertStringContainsString(DenyEditNavigationGroupStub::class, $skipLines[0]['message']);
    }

    /**
     * A throwing guard is caught like the publish is: one bad link's modification check must not
     * abort the write hook, and must not block its siblings.
     *
     * isModifiedOnDraft() reaches extend('updateIsOnDraft') and the stage tables, so it throws the
     * same way canPublish() and publishRecursive() do, and it used to be called outside the try
     * that exists to keep a throw out of the write hook. Reaching the assertions below is itself
     * the assertion that write() survived: before the guards moved inside, this test errored with
     * the simulated RuntimeException escaping NavigationGroup::onAfterWrite().
     *
     * @return void
     */
    public function testAThrowingModificationCheckIsLoggedAndDoesNotBlockSiblings(): void
    {
        $group = $this->objFromFixture(NavigationGroup::class, 'one');

        $throwing = ThrowingIsModifiedOnDraftLink::create([
            'LinkText' => 'Broken footer link',
            'ExternalUrl' => 'https://example.test/broken',
            'OwnerID' => $group->ID,
            'OwnerClass' => NavigationGroup::class,
            'OwnerRelation' => 'NavigationLinks',
        ]);
        $throwing->write();

        $sibling = $this->createDraftNavigationLink($group);

        $logger = new TemplateDataExtensionTestSpyLogger();
        Injector::inst()->registerService($logger, LoggerInterface::class);

        $group->Title = 'Guard Failure Group';
        $group->write();

        $this->assertTrue(
            $sibling->isPublished(),
            'A sibling must still publish when another link\'s modification check throws.'
        );
        $this->assertFalse($throwing->isPublished(), 'The failing link never published.');

        $errorLines = array_values(array_filter(
            $logger->records,
            fn (array $record): bool => $record['level'] === 'error'
                && str_contains($record['message'], 'Failed to publish')
        ));
        $this->assertCount(1, $errorLines);
        $this->assertStringContainsString(ThrowingIsModifiedOnDraftLink::class, $errorLines[0]['message']);
    }

    /**
     * The publish is scoped to the group being saved: another group's draft links must stay draft
     * when a different group is written.
     *
     * @return void
     */
    public function testSavingOneGroupLeavesAnotherGroupsDraftLinksInDraft(): void
    {
        $group = $this->objFromFixture(NavigationGroup::class, 'one');
        $otherGroup = NavigationGroup::create(['Title' => 'Group Two']);
        $otherGroup->write();

        $link = $this->createDraftNavigationLink($group);
        $otherLink = $this->createDraftNavigationLink($otherGroup);

        $this->assertFalse($link->isPublished());
        $this->assertFalse($otherLink->isPublished());

        $group->Title = 'Renamed Group One';
        $group->write();

        $this->assertTrue($link->isPublished(), 'The saved group publishes its own link.');
        $this->assertFalse(
            $otherLink->isPublished(),
            'An unrelated group\'s draft link must not be swept live by this save.'
        );
    }
}
