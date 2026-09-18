# silverstripe-base-site

Base page types for SilverStripe websites

Also see [dynamic/recipe-silverstripe-base-site](https://github.com/dynamic/recipe-silverstripe-base-site):

## Recommended configuration

```
---
name: base-site-config
After:
  - '*'
---
SilverStripe\SiteConfig\SiteConfig:
  extensions:
    - Dynamic\Base\Extension\TemplateDataExtension
    - Dynamic\SiteTools\Extension\ReviewContentDataExtension

SilverStripe\CMS\Model\SiteTree:
  extensions:
    - Dynamic\Base\Extension\CmsDesignDataExtension
    - Dynamic\Base\Extension\SeoExtension

Dynamic\Base\Page\HomePage:
  extensions:
    - Dynamic\SiteTools\Extension\HeaderImageExtension
    - DNADesign\Elemental\Extensions\ElementalPageExtension

Dynamic\Base\Page\BlockPage:
  extensions:
    - Dynamic\SiteTools\Extension\HeaderImageExtension
    - DNADesign\Elemental\Extensions\ElementalPageExtension

Dynamic\Base\Page\CampaignLandingPage:
  extensions:
    - Dynamic\SiteTools\Extension\HeaderImageExtension
    - DNADesign\Elemental\Extensions\ElementalPageExtension

SilverStripe\Blog\Model\BlogPost:
  extensions:
    - DNADesign\Elemental\Extensions\ElementalPageExtension
    - Dynamic\SiteTools\Extension\PreviewExtension
    - Dynamic\SiteTools\Extension\BlogPostDataExtension

SilverStripe\UserForms\Model\EditableFormField:
  extensions:
    - Dynamic\SiteTools\Extension\DataobjectPermissionExtension

SilverStripe\UserForms\Model\EditableCustomRule:
  extensions:
    - Dynamic\SiteTools\Extension\DataobjectPermissionExtension
```

## Site Search

Base site ships `Dynamic\Base\Page\SearchPage`, a page type that renders search results using SilverStripe core's built-in `SearchForm()`. Create a `SearchPage` in the CMS to add a search results page to your site.

## Footer navigation

Footer navigation is a two-level chain: `SiteConfig` has many `NavigationColumn`s, each of
which has many `NavigationGroup`s, and each group owns its `NavigationLinks`
(`silverstripe/linkfield` `Link` records, which are versioned).

Permissions on footer links are open. `NavigationGroup::canEdit()` returns `true` for every member,
including none, and a `linkfield` `Link` resolves `canPublish()` through `canEdit()` to its owner -
so any member who gets a save of the group to happen takes that group's draft links live, and
nothing between the link edit and the publish asks who authored the edit. Restricting that means
overriding `NavigationGroup::canEdit()` in a descendant class: the publish check is made on the
`Link`, so a `canPublish()` on `NavigationGroup` is never consulted, and `canEdit()` returns `true`
without consulting `extendedCan()`, so an Extension cannot override it either. Requiring a specific
permission there is tracked as dynamic/silverstripe-base-site#211. The control that matters today is
that the footer GridField sits under Site Settings.

Site-owned links are different - `SocialLink`s and the logos sit under `SiteConfig`, whose
permissions are restrictive, so there the gate in
`Dynamic\Base\Traits\PublishesOwnedRecords::publishOwnedRecord()` genuinely denies and logs
"Skipped publishing ... left in draft". See [SocialLinks.md](../SocialLinks.md).

`NavigationGroup` is not versioned, so its `$owns` declaration cannot cascade a publish on its
own. `Dynamic\Base\Traits\PublishesOwnedRecords` publishes those links whenever a group is
saved - from the footer GridField detail form, or from a `write()` outside the CMS, e.g. a
BuildTask or deploy script.

Links handled through the link modal are the exception, and there the manual step is still real:
`LinkFieldController::save()` writes the `Link` and then writes the owner only when the owner
relation is a `has_one`, and reordering (`linkSort()`) writes the `Link` alone, so for the
`has_many` `NavigationLinks` relation nothing saves the group at that moment. Save the group's own
detail form - or let anything else write it later - and the link goes live then; close the modal
without saving the group and the link stays in draft, with no warning and nothing in the logs.

Saving a `NavigationColumn` publishes nothing, because `NavigationColumn` has no publish hook at
all: it does not use the trait and declares no write hook. Its effective `$owns` is only
`FileTracking` (contributed by `silverstripe/assets`' `FileLinkTracking`, which is applied to
every `DataObject`), so adding `NavigationGroups` to it would not make column saves cascade
either - the hook would have to be added, and links are edited on the group, not the column.

A publish failure on one link - any `\Exception` raised while publishing it, e.g. a validation
failure - is logged and blocks neither the group's save nor its sibling links; check the logs if
a footer link doesn't go live as expected.

A genuine PHP `\Error` is deliberately *not* caught. It escapes `write()`, so the request
surfaces as an error with `write()`'s own closing steps skipped, and the remaining links in that
save stay in draft. What is left behind depends on which branch `write()` took:

- **A column of the group changed.** The group row is committed, and because `onAfterWrite()`
  runs before `write()` clears its change tracking and cache, the in-memory group still reports
  itself dirty and cached lookups are stale for the rest of the request.
- **No column of the group changed** (links were edited directly, so `write()` took its
  no-changes branch). There was no row to commit and the group was never marked dirty, so only
  its cache is left unflushed.

That is intentional: an `\Error` is a bug in the process, not a data condition to log and carry
on from.
