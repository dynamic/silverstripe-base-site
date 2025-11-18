# Silverstripe Base Site

Base page types and extensions for SilverStripe websites

[![CI](https://github.com/dynamic/silverstripe-base-site/actions/workflows/ci.yml/badge.svg)](https://github.com/dynamic/silverstripe-base-site/actions/workflows/ci.yml)
[![GitHub Sponsors](https://img.shields.io/github/sponsors/dynamic?label=Sponsors&logo=GitHub%20Sponsors&style=flat&color=ea4aaa)](https://github.com/sponsors/dynamic)

[![Latest Stable Version](https://poser.pugx.org/dynamic/silverstripe-base-site/v/stable)](https://packagist.org/packages/dynamic/silverstripe-base-site)
[![Total Downloads](https://poser.pugx.org/dynamic/silverstripe-base-site/downloads)](https://packagist.org/packages/dynamic/silverstripe-base-site)
[![License](https://poser.pugx.org/dynamic/silverstripe-base-site/license)](https://packagist.org/packages/dynamic/silverstripe-base-site)

## Requirements

* PHP: ^8.1
* SilverStripe: ^6
* axllent/silverstripe-email-obfuscator: ^3
* axllent/silverstripe-scaled-uploads: ^3
* dnadesign/silverstripe-elemental: ^6
* dynamic/silverstripe-site-tools: ^5
* jonom/silverstripe-betternavigator: ^7
* jonom/silverstripe-text-target-length: ^3
* silverstripe/linkfield: ^5
* silverstripe/sharedraftcontent: ^4
* tractorcow/silverstripe-robots: ^5
* wilr/silverstripe-googlesitemaps: ^3

## Installation

`composer require dynamic/silverstripe-base-site`

## Features

- **Multiple Page Types**: HomePage, BlockPage, CampaignLandingPage, SearchPage
- **Header Image Support**: Add hero images to pages
- **Elemental Integration**: Full page builder support with drag-and-drop elements
- **Shared Drafts**: Collaborate with shared draft content management
- **SEO Tools**: Google Sitemaps and robots.txt configuration
- **Accessibility Features**: Built-in accessibility enhancements
- **Better Navigation**: Enhanced CMS navigation via jonom/betternavigator
- **Email Obfuscation**: Protected email links from spam bots
- **Image Optimization**: Automatic scaled uploads for performance

## Upgrading from version 7

Base Site v8 is compatible with SilverStripe 6. Key changes:

- Updated all dependencies to SilverStripe 6 compatible versions
- Requires PHP 8.1 or higher
- Updated LinkField integration from v3 to v5

See the [SilverStripe 6 Upgrade Guide](https://docs.silverstripe.org/en/6/) for more details.
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
