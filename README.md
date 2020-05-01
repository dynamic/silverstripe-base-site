# Silverstripe Base Site

Base page types for SilverStripe websites

[![Build Status](https://travis-ci.com/dynamic/silverstripe-base-site.svg?token=hFT1sXd4nNmguE972zHN&branch=master)](https://travis-ci.com/dynamic/silverstripe-base-site)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/dynamic/silverstripe-base-site/badges/quality-score.png?b=master&s=6602bc588bf7da4a15e9ae4e061c92781c87caf5)](https://scrutinizer-ci.com/g/dynamic/silverstripe-base-site/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/dynamic/silverstripe-base-site/badges/coverage.png?b=master&s=fde13fa99212b7985699f22a13c22d393b76299a)](https://scrutinizer-ci.com/g/dynamic/silverstripe-base-site/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/dynamic/silverstripe-base-site/badges/build.png?b=master&s=d0c33738b6be129105fa8f507591359fcf4f40ae)](https://scrutinizer-ci.com/g/dynamic/silverstripe-base-site/build-status/master)
[![codecov](https://codecov.io/gh/dynamic/silverstripe-base-site/branch/master/graph/badge.svg?token=8qD1GBbxzV)](https://codecov.io/gh/dynamic/silverstripe-base-site)

## Requirements

* SilverStripe ^4.3
* axllent/silverstripe-email-obfuscator ^2.0
* axllent/silverstripe-scaled-uploads ^2.1
* dnadesign/silverstripe-elemental ^4.0
* dynamic/silverstripe-geocoder ^1.1
* dynamic/silverstripe-site-tools ^1.0
* jonom/silverstripe-betternavigator ^4.0
* silverstripe/blog ^3.0
* silverstripe/recipe-cms ^4.3
* silverstripe/sharedraftcontent ^2.0
* silverstripe/userforms ^5.0
* tractorcow/silverstripe-sitemap2 ^4.0
* unclecheese/display-logic ^2.0
* wilr/silverstripe-googlesitemaps ^2.1

## Installation

`coposer require dynamic/silverstripe-base-site`

## Example usage

Also see [dynamic/recipe-silverstripe-base-site](https://github.com/dynamic/recipe-silverstripe-base-site):

Recommended configuration:

```
---
name: base-site-config
After:
  - '*'
---
SilverStripe\SiteConfig\SiteConfig:
  extensions:
    - Dynamic\Base\Extension\CompanyDataExtension
    - Dynamic\Base\Extension\IntegrationsDataExtension
    - Dynamic\Base\Extension\TemplateDataExtension
    - Dynamic\SiteTools\Extension\ReviewContentDataExtension

SilverStripe\CMS\Model\SiteTree:
  extensions:
    - Dynamic\Base\Extension\CmsDesignDataExtension

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
    - Dynamic\Base\Extension\BlogPostDataExtension

Dynamic\Base\Model\CompanyAddress:
  extensions:
    - Dynamic\SilverStripeGeocoder\AddressDataExtension
    - Dynamic\SiteTools\Extension\ContactDataExtension

SilverStripe\UserForms\Model\EditableFormField:
  extensions:
    - Dynamic\SiteTools\Extension\DataobjectPermissionExtension

SilverStripe\UserForms\Model\EditableCustomRule:
  extensions:
    - Dynamic\SiteTools\Extension\DataobjectPermissionExtension
```

## Maintainers
 *  [Dynamic](http://www.dynamicagency.com) (<dev@dynamicagency.com>)

## Bugtracker
Bugs are tracked in the issues section of this repository. Before submitting an issue please read over
existing issues to ensure yours is unique.

If the issue does look like a new bug:

 - Create a new issue
 - Describe the steps required to reproduce your issue, and the expected outcome. Unit tests, screenshots
 and screencasts can help here.
 - Describe your environment as detailed as possible: SilverStripe version, Browser, PHP version,
 Operating System, any installed SilverStripe modules.

Please report security issues to the module maintainers directly. Please don't file security issues in the bugtracker.

## Development and contribution
If you would like to make contributions to the module please ensure you raise a pull request and discuss with the module maintainers.
