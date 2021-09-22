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

PageController:
  restrict_results_to_pages: true
  extensions:
    - Dynamic\Base\Extension\SearchExtension
```

## Site Search

As an alternative to the FullText search feature built into Silverstripe, base site supports utilizing [`silverstripers/elemental-search`](https://github.com/SilverStripers/elemental-seach) module. By applying `Dynamic\Base\Extension\SearchExtension` to your `PageController` (covers all pages on the site, apply to more specific controller for page type specific actions) to implement the proper form and result methods.

Out of the box, elemental-search does not restrict results to Page (or it's sublcasses). There is a config option you can apply to the same class you have also implemented `Dynamic\Base\Extension\SearchExtension`. Set `restrict_results_to_pages` to `true` in your config. This will ensure the result is a sublcass of `SiteTree`.