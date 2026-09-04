<?php

namespace Dynamic\Base\Test\Extension;

use Dynamic\Base\Extension\TemplateDataExtension;
use Dynamic\Base\Model\SocialLink;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\LinkField\Models\ExternalLink;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Versioned\ChangeSet;
use SilverStripe\Versioned\Versioned;

class TemplateDataExtensionTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = '../fixtures.yml';

    /**
     * @var array
     */
    protected static $required_extensions = [
        SiteConfig::class => [
            TemplateDataExtension::class,
            TemplateDataExtensionTestFailingValidationExtension::class,
        ]
    ];

    /**
     * @var array
     */
    protected static $extra_dataobjects = [
        TemplateDataExtensionTestUnversionedOwnedStub::class,
        TemplateDataExtensionTestThrowingSocialLink::class,
    ];

    /**
     * DataObject::write() takes its onAfterSkippedWrite() branch, not onAfterWrite(),
     * when nothing about the record's own fields changed value - exactly what a real
     * "add a social link, click Save" edit produces: MultiLinkField saves the SocialLink
     * itself directly, so SiteConfig's own Save changes no SiteConfig-owned column. Both
     * hooks must publish owned records, or this is exactly the bug #174 exists to fix,
     * still reachable through the most common editing path.
     */
    public function testSocialLinksArePublishedOnWriteWithNoSiteConfigFieldChanges(): void
    {
        $siteConfig = $this->objFromFixture(SiteConfig::class, 'default');

        $socialLink = SocialLink::create([
            'SocialChannel' => 'facebook',
            'ExternalUrl' => 'https://facebook.example/profile',
            'OwnerID' => $siteConfig->ID,
            'OwnerClass' => SiteConfig::class,
            'OwnerRelation' => 'SocialLinks',
        ]);
        $socialLink->write();

        $this->assertFalse($socialLink->isPublished());

        // No mutation to any SiteConfig-owned field here - this must still publish.
        $siteConfig->write();

        $this->assertTrue($socialLink->isPublished());
    }

    /**
     * DataObject::preWrite() invokes this exact same onAfterSkippedWrite() hook on two
     * distinct branches: the genuine no-field-changes case, and the validate-and-reject
     * case, invoked immediately before re-throwing the ValidationException with nothing
     * actually persisted. Only the first should publish. Extension instances are Injector
     * singletons shared across every owner for the life of the process (see setOwner()/
     * clearOwner()'s ownerStack), so the gate can't be a flag set on the extension itself -
     * it has to ask the current owner directly, which is what this proves via a minimal
     * owner double rather than the extension's real SiteConfig owner.
     */
    public function testOnAfterSkippedWriteDoesNotPublishWhenOwnerFailsValidation(): void
    {
        $extension = new TemplateDataExtension();
        $owner = new TemplateDataExtensionTestValidationGateOwnerStub(false);
        $extension->setOwner($owner);

        $extension->onAfterSkippedWrite();

        $this->assertFalse($owner->findOwnedWasCalled);
    }

    /**
     * The other half of testOnAfterSkippedWriteDoesNotPublishWhenOwnerFailsValidation():
     * a validation-passing owner's genuine no-changes save must still publish.
     */
    public function testOnAfterSkippedWritePublishesWhenOwnerPassesValidation(): void
    {
        $extension = new TemplateDataExtension();
        $owner = new TemplateDataExtensionTestValidationGateOwnerStub(true);
        $extension->setOwner($owner);

        $extension->onAfterSkippedWrite();

        $this->assertTrue($owner->findOwnedWasCalled);
    }

    /**
     * The end-to-end counterpart to the two owner-double tests above: a real SiteConfig
     * write rejected by validate() must not publish a draft owned record, exercising
     * DataObject::preWrite()'s actual validation-rejection call to onAfterSkippedWrite()
     * rather than a stand-in for it.
     */
    public function testOnAfterSkippedWriteDoesNotPublishOnARealRejectedWrite(): void
    {
        $siteConfig = $this->objFromFixture(SiteConfig::class, 'default');

        $socialLink = SocialLink::create([
            'SocialChannel' => 'facebook',
            'ExternalUrl' => 'https://facebook.example/profile',
            'OwnerID' => $siteConfig->ID,
            'OwnerClass' => SiteConfig::class,
            'OwnerRelation' => 'SocialLinks',
        ]);
        $socialLink->write();

        TemplateDataExtensionTestFailingValidationExtension::$shouldFail = true;

        try {
            $siteConfig->write();
            $this->fail('Expected the forced validation failure to throw.');
        } catch (ValidationException $e) {
            // expected
        } finally {
            TemplateDataExtensionTestFailingValidationExtension::$shouldFail = false;
        }

        $this->assertFalse($socialLink->isPublished());
    }

    /**
     *
     */
    public function testGetCMSFields()
    {
        $object = Injector::inst()->create(SiteConfig::class);
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
    }

    /**
     * Draft SocialLinks/UtilityLinks records should go live as soon as the owning SiteConfig is saved.
     */
    public function testSocialAndUtilityLinksArePublishedOnWrite(): void
    {
        $siteConfig = $this->objFromFixture(SiteConfig::class, 'default');

        $socialLink = SocialLink::create([
            'SocialChannel' => 'facebook',
            'ExternalUrl' => 'https://facebook.example/profile',
            'OwnerID' => $siteConfig->ID,
            'OwnerClass' => SiteConfig::class,
            'OwnerRelation' => 'SocialLinks',
        ]);
        $socialLink->write();

        $utilityLink = ExternalLink::create([
            'Title' => 'Contact',
            'ExternalUrl' => 'https://example.test/contact',
            'OwnerID' => $siteConfig->ID,
            'OwnerClass' => SiteConfig::class,
            'OwnerRelation' => 'UtilityLinks',
        ]);
        $utilityLink->write();

        $this->assertFalse($socialLink->isPublished());
        $this->assertFalse($utilityLink->isPublished());

        // Mutate a SiteConfig field so this specifically exercises the onAfterWrite()
        // branch of DataObject::write(); testSocialLinksArePublishedOnWriteWithNoSiteConfigFieldChanges()
        // below covers the onAfterSkippedWrite() branch a real "add a link, Save" click
        // usually takes instead.
        $siteConfig->Title = 'Updated Site Name';
        $siteConfig->write();

        $this->assertTrue($socialLink->isPublished());
        $this->assertTrue($utilityLink->isPublished());
    }

    /**
     * Logo/LogoRetina are has_one relations, also declared in $owns, and also Versioned
     * (Image extends the Versioned File) - they must be published the same way as the
     * has_many SocialLinks/UtilityLinks children.
     */
    public function testLogoAndLogoRetinaArePublishedOnWrite(): void
    {
        $siteConfig = $this->objFromFixture(SiteConfig::class, 'default');

        $logo = Image::create();
        $logo->Name = 'logo.png';
        $logo->write();

        $logoRetina = Image::create();
        $logoRetina->Name = 'logo-retina.png';
        $logoRetina->write();

        $this->assertFalse($logo->isPublished());
        $this->assertFalse($logoRetina->isPublished());

        $siteConfig->LogoID = $logo->ID;
        $siteConfig->LogoRetinaID = $logoRetina->ID;
        $siteConfig->write();

        $this->assertTrue($logo->isPublished());
        $this->assertTrue($logoRetina->isPublished());
    }

    /**
     * A change to an already-published child made after the first publish should also go live,
     * proving the hook isn't a first-publish-only side effect.
     */
    public function testChangesToAlreadyPublishedLinksArePublishedOnSubsequentWrites(): void
    {
        $siteConfig = $this->objFromFixture(SiteConfig::class, 'default');

        $socialLink = SocialLink::create([
            'SocialChannel' => 'facebook',
            'ExternalUrl' => 'https://facebook.example/profile',
            'OwnerID' => $siteConfig->ID,
            'OwnerClass' => SiteConfig::class,
            'OwnerRelation' => 'SocialLinks',
        ]);
        $socialLink->write();
        $siteConfig->Title = 'Updated Site Name';
        $siteConfig->write();

        $socialLink->ExternalUrl = 'https://facebook.example/updated';
        $socialLink->write();
        $siteConfig->Title = 'Updated Site Name Again';
        $siteConfig->write();

        $liveLink = Versioned::get_by_stage(SocialLink::class, Versioned::LIVE)->byID($socialLink->ID);
        $this->assertNotNull($liveLink, 'Expected a live version of the SocialLink to exist.');
        $this->assertSame('https://facebook.example/updated', $liveLink->ExternalUrl);
    }

    /**
     * A published child that hasn't changed since its last publish must not be re-published
     * on a subsequent, unrelated SiteConfig save (avoids writing a redundant Version/ChangeSet
     * per link on every save).
     */
    public function testUnmodifiedPublishedLinksAreNotRepublishedOnWrite(): void
    {
        $siteConfig = $this->objFromFixture(SiteConfig::class, 'default');

        $socialLink = SocialLink::create([
            'SocialChannel' => 'facebook',
            'ExternalUrl' => 'https://facebook.example/profile',
            'OwnerID' => $siteConfig->ID,
            'OwnerClass' => SiteConfig::class,
            'OwnerRelation' => 'SocialLinks',
        ]);
        $socialLink->write();
        $siteConfig->Title = 'Updated Site Name';
        $siteConfig->write();

        // publishRecursive() always writes a new ChangeSet record, even when nothing about the
        // link changed, so a version-number comparison alone wouldn't reveal a redundant
        // republish. Counting ChangeSets is what actually exposes it.
        $changeSetCountBefore = ChangeSet::get()->count();

        $siteConfig->Title = 'Updated Site Name Again';
        $siteConfig->write();

        $changeSetCountAfter = ChangeSet::get()->count();

        $this->assertSame($changeSetCountBefore, $changeSetCountAfter);
    }

    /**
     * When one of several links has been modified and the others haven't, only the
     * modified one should be republished on a SiteConfig save.
     */
    public function testOnlyModifiedLinksAreRepublishedWhenSiblingsAreUnmodified(): void
    {
        $siteConfig = $this->objFromFixture(SiteConfig::class, 'default');

        $unmodifiedLink = SocialLink::create([
            'SocialChannel' => 'facebook',
            'ExternalUrl' => 'https://facebook.example/profile',
            'OwnerID' => $siteConfig->ID,
            'OwnerClass' => SiteConfig::class,
            'OwnerRelation' => 'SocialLinks',
        ]);
        $unmodifiedLink->write();

        $modifiedLink = SocialLink::create([
            'SocialChannel' => 'instagram',
            'ExternalUrl' => 'https://instagram.example/profile',
            'OwnerID' => $siteConfig->ID,
            'OwnerClass' => SiteConfig::class,
            'OwnerRelation' => 'SocialLinks',
        ]);
        $modifiedLink->write();

        $siteConfig->Title = 'Updated Site Name';
        $siteConfig->write();

        // Both links are now published and unmodified; change only one before the next save.
        $modifiedLink->ExternalUrl = 'https://instagram.example/updated';
        $modifiedLink->write();

        $changeSetCountBefore = ChangeSet::get()->count();

        $siteConfig->Title = 'Updated Site Name Again';
        $siteConfig->write();

        $changeSetCountAfter = ChangeSet::get()->count();

        // Exactly one new ChangeSet - for the modified link. The unmodified sibling must
        // not be republished alongside it.
        $this->assertSame($changeSetCountBefore + 1, $changeSetCountAfter);

        $liveModifiedLink = Versioned::get_by_stage(SocialLink::class, Versioned::LIVE)->byID($modifiedLink->ID);
        $this->assertNotNull($liveModifiedLink, 'Expected a live version of the modified SocialLink to exist.');
        $this->assertSame('https://instagram.example/updated', $liveModifiedLink->ExternalUrl);
    }

    /**
     * SiteConfig with no Social/Utility links attached should write without error.
     */
    public function testWritingSiteConfigWithNoLinksDoesNotError(): void
    {
        $siteConfig = SiteConfig::create(['Title' => 'Empty SiteConfig']);
        $siteConfig->write();

        $this->assertTrue($siteConfig->isInDB());
    }

    /**
     * A child lacking the Versioned extension must be skipped without an exception.
     *
     * Uses a plain, non-Versioned $extra_dataobjects stub rather than a mock: the stub
     * declares no isModifiedOnDraft() (Versioned-only - unlike publishRecursive(), which
     * RecursivePublishable adds to every DataObject regardless of Versioned), so if the
     * hasExtension(Versioned::class) guard didn't return early, the very next call in
     * publishOwnedRecord() would fatal with "Call to undefined method" instead of
     * returning cleanly and logging nothing.
     */
    public function testPublishOwnedRecordSkipsChildWithoutVersionedExtension(): void
    {
        $extension = new TemplateDataExtension();

        $stub = TemplateDataExtensionTestUnversionedOwnedStub::create();
        $stub->write();

        $logger = new TemplateDataExtensionTestSpyLogger();
        Injector::inst()->registerService($logger, LoggerInterface::class);

        $method = new ReflectionMethod(TemplateDataExtension::class, 'publishOwnedRecord');
        $method->invoke($extension, $stub);

        $this->assertFalse($stub->hasExtension(Versioned::class));
        $this->assertEmpty($logger->records);
    }

    /**
     * A non-ValidationException raised by publishRecursive() must be logged (with the
     * exception itself as log context, not just its message) and swallowed rather than
     * propagated - one bad record shouldn't be able to abort the whole SiteConfig save.
     */
    public function testPublishOwnedRecordLogsAndSwallowsNonValidationFailures(): void
    {
        $siteConfig = $this->objFromFixture(SiteConfig::class, 'default');

        $extension = new TemplateDataExtension();
        $extension->setOwner($siteConfig);

        $link = TemplateDataExtensionTestThrowingSocialLink::create([
            'SocialChannel' => 'facebook',
            'ExternalUrl' => 'https://facebook.example/profile',
            'FailureMode' => 'generic',
        ]);
        $link->write();

        $logger = new TemplateDataExtensionTestSpyLogger();
        Injector::inst()->registerService($logger, LoggerInterface::class);

        $method = new ReflectionMethod(TemplateDataExtension::class, 'publishOwnedRecord');
        $method->invoke($extension, $link);

        $this->assertFalse($link->isPublished());
        $this->assertCount(1, $logger->records);
        $this->assertSame('error', $logger->records[0]['level']);
        $this->assertArrayHasKey('exception', $logger->records[0]['context']);
        $this->assertInstanceOf(\RuntimeException::class, $logger->records[0]['context']['exception']);
    }

    /**
     * A ValidationException raised by publishRecursive() is logged and swallowed exactly
     * like any other exception, not re-thrown - see publishOwnedRecords() for why
     * re-throwing from inside a DataObject::write() call is unsafe (the row is already
     * committed by the time onAfterWrite()/onAfterSkippedWrite() run, so an exception
     * thrown there skips write()'s own isChanged()/cache bookkeeping).
     */
    public function testPublishOwnedRecordLogsAndSwallowsValidationFailuresToo(): void
    {
        $siteConfig = $this->objFromFixture(SiteConfig::class, 'default');

        $extension = new TemplateDataExtension();
        $extension->setOwner($siteConfig);

        $link = TemplateDataExtensionTestThrowingSocialLink::create([
            'SocialChannel' => 'facebook',
            'ExternalUrl' => 'https://facebook.example/profile',
            'FailureMode' => 'validation',
        ]);
        $link->write();

        $logger = new TemplateDataExtensionTestSpyLogger();
        Injector::inst()->registerService($logger, LoggerInterface::class);

        $method = new ReflectionMethod(TemplateDataExtension::class, 'publishOwnedRecord');
        $method->invoke($extension, $link);

        $this->assertFalse($link->isPublished());
        $this->assertCount(1, $logger->records);
        $this->assertSame('error', $logger->records[0]['level']);
        $this->assertArrayHasKey('exception', $logger->records[0]['context']);
        $this->assertInstanceOf(ValidationException::class, $logger->records[0]['context']['exception']);
    }

    /**
     * A sibling that publishes cleanly must still go live even when another owned record's
     * publishRecursive() throws a non-ValidationException - and the SiteConfig save itself
     * must still succeed. Proves the per-object isolation onAfterWrite() relies on: the
     * failing record is created (and therefore iterated by findOwned()) first, since a
     * loop that aborted on the first failure would still pass this test if the good
     * record happened to be processed before the bad one. This ordering is deterministic,
     * not incidental: Link declares `default_sort = 'Sort'`, and Link::onBeforeWrite()
     * assigns each new record the next Sort value, so creation order is iteration order.
     */
    public function testOnAfterWritePublishesSiblingsWhenOneFailsWithNonValidationException(): void
    {
        $siteConfig = $this->objFromFixture(SiteConfig::class, 'default');

        $badLink = TemplateDataExtensionTestThrowingSocialLink::create([
            'SocialChannel' => 'instagram',
            'ExternalUrl' => 'https://instagram.example/profile',
            'OwnerID' => $siteConfig->ID,
            'OwnerClass' => SiteConfig::class,
            'OwnerRelation' => 'SocialLinks',
            'FailureMode' => 'generic',
        ]);
        $badLink->write();

        $goodLink = SocialLink::create([
            'SocialChannel' => 'facebook',
            'ExternalUrl' => 'https://facebook.example/profile',
            'OwnerID' => $siteConfig->ID,
            'OwnerClass' => SiteConfig::class,
            'OwnerRelation' => 'SocialLinks',
        ]);
        $goodLink->write();

        // Registering a spy keeps the expected publish-failure log line out of the real
        // configured logger during this run.
        Injector::inst()->registerService(new TemplateDataExtensionTestSpyLogger(), LoggerInterface::class);

        $siteConfig->Title = 'Updated Site Name';
        $siteConfig->write();

        $this->assertTrue($siteConfig->isInDB());
        $this->assertTrue($goodLink->isPublished());
        $this->assertFalse($badLink->isPublished());
    }

    /**
     * A ValidationException from one owned record must not stop its siblings from being
     * published, and must not stop SiteConfig::write() itself from succeeding either - see
     * publishOwnedRecords() for why a publish failure is never re-thrown out of these
     * hooks. As above, the failing record is created (and therefore iterated) first, so
     * this only passes if the loop genuinely keeps going after catching the exception.
     */
    public function testOnAfterWritePublishesSiblingsWhenOneFailsWithValidationException(): void
    {
        $siteConfig = $this->objFromFixture(SiteConfig::class, 'default');

        $badLink = TemplateDataExtensionTestThrowingSocialLink::create([
            'SocialChannel' => 'instagram',
            'ExternalUrl' => 'https://instagram.example/profile',
            'OwnerID' => $siteConfig->ID,
            'OwnerClass' => SiteConfig::class,
            'OwnerRelation' => 'SocialLinks',
            'FailureMode' => 'validation',
        ]);
        $badLink->write();

        $goodLink = SocialLink::create([
            'SocialChannel' => 'facebook',
            'ExternalUrl' => 'https://facebook.example/profile',
            'OwnerID' => $siteConfig->ID,
            'OwnerClass' => SiteConfig::class,
            'OwnerRelation' => 'SocialLinks',
        ]);
        $goodLink->write();

        // Registering a spy keeps the expected publish-failure log line out of the real
        // configured logger during this run.
        Injector::inst()->registerService(new TemplateDataExtensionTestSpyLogger(), LoggerInterface::class);

        $siteConfig->Title = 'Updated Site Name';
        $siteConfig->write();

        $this->assertTrue($siteConfig->isInDB());
        $this->assertTrue($goodLink->isPublished());
        $this->assertFalse($badLink->isPublished());
    }

    /**
     * Guards against Versioned::withVersionedMode()'s Stage.Draft pin in
     * publishOwnedRecords() being simplified away: every other test in this class runs
     * under SapphireTest's default draft reading mode, so all of them would still pass
     * even without the pin. This one explicitly forces Stage.Live as the ambient reading
     * mode before saving - a draft-only owned record must still be found and published.
     */
    public function testOwnedRecordsPublishEvenWhenAmbientReadingModeIsLive(): void
    {
        $siteConfig = $this->objFromFixture(SiteConfig::class, 'default');

        $socialLink = SocialLink::create([
            'SocialChannel' => 'facebook',
            'ExternalUrl' => 'https://facebook.example/profile',
            'OwnerID' => $siteConfig->ID,
            'OwnerClass' => SiteConfig::class,
            'OwnerRelation' => 'SocialLinks',
        ]);
        $socialLink->write();

        $this->assertFalse($socialLink->isPublished());

        Versioned::withVersionedMode(function () use ($siteConfig): void {
            Versioned::set_stage(Versioned::LIVE);

            $siteConfig->Title = 'Updated Site Name';
            $siteConfig->write();
        });

        $this->assertTrue($socialLink->isPublished());
    }
}
