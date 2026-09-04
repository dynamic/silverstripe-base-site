<?php

namespace Dynamic\Base\Test\Extension;

use Dynamic\Base\Extension\TemplateDataExtension;
use Dynamic\Base\Model\SocialLink;
use ReflectionMethod;
use SilverStripe\Core\Injector\Injector;
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

        // DataObject::write() skips onAfterWrite entirely when the record has no field-level
        // changes of its own; a real CMS save always submits at least one field, so mutate one
        // here to exercise the hook the way an actual "Save" click does.
        $siteConfig->Title = 'Updated Site Name';
        $siteConfig->write();

        $this->assertTrue($socialLink->isPublished());
        $this->assertTrue($utilityLink->isPublished());
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
     * Note: a PHPUnit mock's dynamically-generated class name fails SilverStripe's
     * ClassName enum validation on write(), so this can't use a persisted, genuinely
     * modified-on-draft mock to isolate the hasExtension() guard from the separate
     * isModifiedOnDraft() guard below it - both independently skip a never-persisted
     * record. This confirms the aggregate behaviour ("an unversioned child is safely
     * skipped, no exception"), not that hasExtension() specifically is what causes it;
     * see the PR's Verification gaps.
     */
    public function testPublishLinkSkipsChildrenWithoutVersionedExtension(): void
    {
        $extension = new TemplateDataExtension();

        $link = $this->getMockBuilder(SocialLink::class)
            ->onlyMethods(['hasExtension'])
            ->getMock();
        $link->method('hasExtension')->willReturn(false);

        $method = new ReflectionMethod(TemplateDataExtension::class, 'publishLink');
        $method->setAccessible(true);
        $method->invoke($extension, $link);

        $this->assertFalse($link->exists());
    }
}
