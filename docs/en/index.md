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