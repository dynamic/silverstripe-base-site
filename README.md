# Silverstripe Base Site

Base page types and extensions for SilverStripe websites

[![CI](https://github.com/dynamic/silverstripe-base-site/actions/workflows/ci.yml/badge.svg)](https://github.com/dynamic/silverstripe-base-site/actions/workflows/ci.yml) [![Sponsors](https://img.shields.io/badge/GitHub-Sponsors-ff69b4?logo=github)](https://github.com/sponsors/dynamic)

[![Latest Stable Version](https://poser.pugx.org/dynamic/silverstripe-base-site/v/stable)](https://packagist.org/packages/dynamic/silverstripe-base-site)
[![Total Downloads](https://poser.pugx.org/dynamic/silverstripe-base-site/downloads)](https://packagist.org/packages/dynamic/silverstripe-base-site)
[![Latest Unstable Version](https://poser.pugx.org/dynamic/silverstripe-base-site/v/unstable)](https://packagist.org/packages/dynamic/silverstripe-base-site)
[![License](https://poser.pugx.org/dynamic/silverstripe-base-site/license)](https://packagist.org/packages/dynamic/silverstripe-base-site)

## Requirements

* PHP: ^8.3
* axllent/silverstripe-email-obfuscator: ^2
* axllent/silverstripe-scaled-uploads: ^2.1
* dnadesign/silverstripe-elemental: ^6
* dynamic/silverstripe-site-tools: ^6
* jonom/silverstripe-betternavigator: ^7
* jonom/silverstripe-text-target-length: ^2
* silverstripe/linkfield: ^5.0
* silverstripe/recipe-cms: ^6
* silverstripe/sharedraftcontent: ^4
* tractorcow/silverstripe-robots: ^5
* wilr/silverstripe-googlesitemaps: ^4

## Installation

`composer require dynamic/silverstripe-base-site`

## Features

- **Multiple Page Types**: HomePage, BlockPage, CampaignLandingPage, SearchPage
- **Header Image Support**: Add hero images to pages
- **Elemental Integration**: Full page builder support with drag-and-drop elements
- **Shared Drafts**: Collaborate with shared draft content management
- **SEO Tools**: Google Sitemaps, robots.txt configuration, and configurable meta title/description length targets
- **Site Branding & Navigation**: `SiteConfig`-level logo (with retina variant) or title/slogan toggle, footer navigation columns and link groups, social links, and utility links
- **CMS Field Organization**: Reorders CMS fields for a more logical editing experience
- **Better Navigation**: Enhanced CMS navigation via jonom/betternavigator
- **Email Obfuscation**: Protected email links from spam bots
- **Image Optimization**: Automatic scaled uploads for performance

## Configuration

- `Dynamic\Base\Model\SocialLink.social_channels` / `.social_icons` / `.default_icon` — the supported social platforms and their Bootstrap Icons mapping (see `_config/social-channels.yml`)
- `Axllent\ScaledUploads\ScaledUploads.max_width` / `.max_height` — maximum dimensions for uploaded images (see `_config/scaled-uploads.yml`)

## Documentation

- [Recommended configuration](docs/en/index.md)
- [Social Links](docs/SocialLinks.md)

## Upgrading

### `SearchContent` is gone from `SeoExtension`

`SeoExtension` no longer declares a `SearchContent` field or a `SearchFields` fulltext
index over it, and no longer hooks `onBeforeWrite()`. Sites that used SilverStripe's
built-in fulltext search through that field need to move to their search service (most
Dynamic sites already use AddSearch).

`dev/build` neither drops columns nor removes indexes that a class stops declaring, so
existing installs keep the stale, frozen data behind - including the storage cost of a large
`HTMLText` column on every row of `SiteTree_Versions`. To drop it by hand after deploying:

```sql
ALTER TABLE SiteTree DROP INDEX SearchFields, DROP COLUMN SearchContent;
ALTER TABLE SiteTree_Live DROP INDEX SearchFields, DROP COLUMN SearchContent;
ALTER TABLE SiteTree_Versions DROP INDEX SearchFields, DROP COLUMN SearchContent;
```

Skip any statement for a table that has no such column or index - for example a site that
never ran an older version of this module. On a large site, altering `SiteTree_Versions`
can rebuild the whole table, so take a backup and run it in a maintenance window.

This removal breaks public API for anyone still using it: templates printing
`$SearchContent`, ORM filters on that field, and calls to `seoContentFields()` all stop
working. The module tracks no `CHANGELOG.md` past 6.x, so call this out in the release
notes for whatever version it lands in.

## Maintainers

 *  [Dynamic](https://www.dynamicagency.com) (<dev@dynamicagency.com>)

## Bugtracker

Bugs are tracked in the issues section of this repository. Before submitting an issue please read over existing issues to ensure yours is unique.

If the issue does look like a new bug:

 - Create a new issue
 - Describe the steps required to reproduce your issue, and the expected outcome. Unit tests, screenshots and screencasts can help here.
 - Describe your environment as detailed as possible: SilverStripe version, Browser, PHP version, Operating System, any installed SilverStripe modules.

Please report security issues to the module maintainers directly. Please don't file security issues in the bugtracker.

## Development and contribution

If you would like to make contributions to the module please ensure you raise a pull request and discuss with the module maintainers.

## License

See [License](LICENSE.md)
