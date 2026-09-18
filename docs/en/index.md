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

Neither `NavigationColumn` nor `NavigationGroup` is versioned, so `NavigationGroup`'s `$owns`
declaration cannot cascade a publish on its own. Saving a `NavigationGroup` - from the footer
GridField detail form, or from a `write()` outside the CMS, e.g. a BuildTask or deploy script -
publishes its draft `NavigationLinks` to Live automatically, so there's usually no separate
manual publish step for footer links.

Saving a `NavigationColumn` does not publish anything: the only entry in a column's effective
`$owns` is `FileTracking` (contributed by `silverstripe/assets`' `FileLinkTracking` extension,
which is applied to every `DataObject`), `NavigationGroups` is not owned, and editing a column
never carries a link edit - links are edited on the group.

Publishing is permission-gated, same as the Site-owned links: a record the current member
cannot `publish` is left in draft with a logged notice, and a CLI process with no logged-in
member is exempt from that check (see [SocialLinks.md](SocialLinks.md)).

A publish failure on one link - any `\Exception` raised while publishing it, e.g. a validation
failure - is logged and doesn't block the group's save or its sibling links; check the logs if
a footer link doesn't go live as expected.

A genuine PHP `\Error` is deliberately *not* caught. It escapes and aborts the remaining links
in that save, and because it escapes through `write()` the request surfaces as a 500 with
`write()`'s own closing steps skipped. What that leaves behind depends on which branch of
`write()` the save took:

- **A column of the group did change.** The group row has already been committed, and because
  `onAfterWrite()` runs before `write()` clears its change tracking and its cache, the
  in-memory group still reports itself dirty and its cached lookups are stale for the rest of
  the request.
- **No column of the group changed** (links were edited directly, so `write()` took its
  no-changes branch). There was no row to commit and the group was never marked dirty, so
  only its cache is left unflushed.

That is intentional: an `\Error` is a bug in the process, not a data condition to log and carry
on from.
