# silverstripe-base-site

Base page types for SilverStripe websites

[![Build Status](https://travis-ci.com/dynamic/silverstripe-base-site.svg?token=hFT1sXd4nNmguE972zHN&branch=master)](https://travis-ci.com/dynamic/silverstripe-base-site)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/dynamic/silverstripe-base-site/badges/quality-score.png?b=master&s=6602bc588bf7da4a15e9ae4e061c92781c87caf5)](https://scrutinizer-ci.com/g/dynamic/silverstripe-base-site/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/dynamic/silverstripe-base-site/badges/coverage.png?b=master&s=fde13fa99212b7985699f22a13c22d393b76299a)](https://scrutinizer-ci.com/g/dynamic/silverstripe-base-site/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/dynamic/silverstripe-base-site/badges/build.png?b=master&s=d0c33738b6be129105fa8f507591359fcf4f40ae)](https://scrutinizer-ci.com/g/dynamic/silverstripe-base-site/build-status/master)
[![codecov](https://codecov.io/gh/dynamic/silverstripe-base-site/branch/master/graph/badge.svg?token=8qD1GBbxzV)](https://codecov.io/gh/dynamic/silverstripe-base-site)

## Requirements

- SilverStripe 4.0

## Installation

This is how you install silverstripe-base-site.

## Example usage

Recommended configuration:

```
Axllent\CMSTweaks\MetadataTab:
  use_tab: true
  tab_title: 'SEO'
  tab_to_right: true
  page_name_title: 'Page Title'
  move_title_to_advanced: false

SilverStripe\SiteConfig\SiteConfig:
  extensions:
    - Dynamic\Base\Extension\CompanyDataExtension
    - Dynamic\Base\Extension\IntegrationsDataExtension
    - Dynamic\Base\Extension\TemplateDataExtension
    - Dynamic\SiteTools\Extension\ReviewContentDataExtension

SilverStripe\CMS\Model\SiteTree:
  extensions:
    - Dynamic\Base\Extension\CmsDesignDataExtension

Page:
  extensions:
    - Vulcan\Seo\Extensions\PageHealthExtension
    - Vulcan\Seo\Extensions\PageSeoExtension
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
    - Dynamic\Base\Extension\BlogPostDataExtension

Dynamic\Base\Model\CompanyAddress:
  extensions:
    - Dynamic\SilverStripeGeocoder\AddressDataExtension

SilverStripe\UserForms\Model\EditableFormField:
  extensions:
    - Dynamic\SiteTools\Extension\DataobjectPermissionExtension

SilverStripe\UserForms\Model\EditableCustomRule:
  extensions:
    - Dynamic\SiteTools\Extension\DataobjectPermissionExtension

```

## Documentation

See the [docs/en](docs/en/index.md) folder.
