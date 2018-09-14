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
SilverStripe\Admin\LeftAndMain:
  application_name: 'Dynamic'
  application_link: 'http://www.dynamicagency.com'

SilverStripe\CMS\Model\SiteTree:
  extensions:
    - Dynamic\CoreTools\ORM\CMSDesign

Page:
  extensions:
    - Dynamic\CoreTools\ORM\DynamicPageHealthExtension
    - Dynamic\CoreTools\ORM\DynamicPageSeoExtension

Dynamic\Base\Page\HomePage:
  extensions:
    - Dynamic\CoreTools\ORM\HeaderImageDataExtension
    - DNADesign\Elemental\Extensions\ElementalPageExtension
    - Dynamic\CoreTools\ORM\ElementalSearch

Dynamic\Base\Page\BlockPage:
  extensions:
    - Dynamic\CoreTools\ORM\HeaderImageDataExtension
    - DNADesign\Elemental\Extensions\ElementalPageExtension
    - Dynamic\CoreTools\ORM\ElementalSearch

Dynamic\Base\Page\CampaignLandingPage:
  extensions:
    - Dynamic\CoreTools\ORM\HeaderImageDataExtension
    - DNADesign\Elemental\Extensions\ElementalPageExtension
    - Dynamic\CoreTools\ORM\ElementalSearch

SilverStripe\ORM\DataList:
  extensions:
  - Dynamic\CoreTools\ORM\CoreToolsDataListDataExtension

SilverStripe\UserForms\Model\EditableFormField:
  extensions:
  - Dynamic\CoreTools\ORM\ContentAuthorPermissionManager

SilverStripe\UserForms\Model\EditableCustomRule:
  extensions:
  - Dynamic\CoreTools\ORM\ContentAuthorPermissionManager

SilverStripe\Blog\Model\BlogPost:
  extensions:
  - DNADesign\Elemental\Extensions\ElementalPageExtension
  - Dynamic\CoreTools\ORM\ElementalSearch
  - Dynamic\CoreTools\ORM\PreviewExtension
  - Dynamic\Base\ORM\BlogPostDataExtension

DNADesign\Elemental\ElementalEditor:
  extensions:
  - Dynamic\Base\ORM\ElementalEditorExtension
```

## Documentation

See the [docs/en](docs/en/index.md) folder.
