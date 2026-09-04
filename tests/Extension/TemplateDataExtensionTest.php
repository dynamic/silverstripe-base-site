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
     * declares no isModifiedOnDraft()/publishRecursive() methods at all (both are
     * Versioned-only), so if the hasExtension(Versioned::class) guard didn't return early,
     * the very next call in publishOwnedRecord() would fatal with "Call to undefined
     * method" instead of returning cleanly. Reaching the assertion below is itself the
     * proof the guard fired, isolated from the isModifiedOnDraft() guard beneath it (which
     * would fatal the same way on this stub if reached).
     */
    public function testPublishOwnedRecordSkipsChildWithoutVersionedExtension(): void
    {
        $extension = new TemplateDataExtension();

        $stub = TemplateDataExtensionTestUnversionedOwnedStub::create();
        $stub->write();

        $method = new ReflectionMethod(TemplateDataExtension::class, 'publishOwnedRecord');
        $method->invoke($extension, $stub);

        $this->assertTrue($stub->exists());
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
     * A ValidationException raised by publishRecursive() must still be logged, but is then
     * re-thrown rather than swallowed - the content is invalid, which is worth surfacing to
     * the editor via LeftAndMain's own ValidationException handling.
     */
    public function testPublishOwnedRecordLogsAndRethrowsValidationFailures(): void
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

        $this->expectException(ValidationException::class);
        try {
            $method->invoke($extension, $link);
        } finally {
            $this->assertCount(1, $logger->records);
        }
    }

    /**
     * A sibling that publishes cleanly must still go live even when another owned record's
     * publishRecursive() throws a non-ValidationException - and the SiteConfig save itself
     * must still succeed. Proves the per-object isolation onAfterWrite() relies on: the
     * failing record is created (and therefore iterated by findOwned()) first, since a
     * loop that aborted on the first failure would still pass this test if the good
     * record happened to be processed before the bad one.
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
     * published, but must still propagate out of SiteConfig::write() once every owned
     * record has been attempted - so the editor actually sees the failure. As above, the
     * failing record is created (and therefore iterated) first, so this only passes if the
     * loop genuinely keeps going after catching the exception.
     */
    public function testOnAfterWriteSurfacesValidationExceptionAfterPublishingSiblings(): void
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

        try {
            $siteConfig->write();
            $this->fail('Expected a ValidationException to propagate out of SiteConfig::write().');
        } catch (ValidationException $e) {
            // expected
        }

        $this->assertTrue($goodLink->isPublished());
        $this->assertFalse($badLink->isPublished());
    }
}
