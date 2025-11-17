# Silverstripe Base Site

Base page types for SilverStripe websites

[![CI](https://github.com/dynamic/silverstripe-base-site/actions/workflows/ci.yml/badge.svg)](https://github.com/dynamic/silverstripe-base-site/actions/workflows/ci.yml) [![GitHub Sponsors](https://img.shields.io/github/sponsors/dynamic)](https://github.com/sponsors/dynamic)

[![Latest Stable Version](https://poser.pugx.org/dynamic/silverstripe-base-site/v/stable)](https://packagist.org/packages/dynamic/silverstripe-base-site) [![Total Downloads](https://poser.pugx.org/dynamic/silverstripe-base-site/downloads)](https://packagist.org/packages/dynamic/silverstripe-base-site) [![Latest Unstable Version](https://poser.pugx.org/dynamic/silverstripe-base-site/v/unstable)](https://packagist.org/packages/dynamic/silverstripe-base-site) [![License](https://poser.pugx.org/dynamic/silverstripe-base-site/license)](https://packagist.org/packages/dynamic/silverstripe-base-site)

## Requirements

* SilverStripe ^6
* axllent/silverstripe-email-obfuscator ^2.0
* axllent/silverstripe-scaled-uploads ^2.1
* dnadesign/silverstripe-elemental ^6.0
* dynamic/silverstripe-site-tools ^5
* jonom/silverstripe-betternavigator ^7
* jonom/silverstripe-text-target-length ^2
* silverstripe/linkfield ^5
* silverstripe/sharedraftcontent ^4
* tractorcow/silverstripe-robots ^5
* wilr/silverstripe-googlesitemaps ^4

## Installation

`composer require dynamic/silverstripe-base-site`

## Upgrading from version 7

SilverStripe Base Site 8.0 is compatible with SilverStripe 6. Key changes:

- Updated to SilverStripe CMS 6
- Requires PHP 8.1 or higher
- Updated all major dependencies to their SS6-compatible versions
- Namespace changes: `DataExtension` moved from `SilverStripe\ORM` to `SilverStripe\Core\Extension`
- Validation classes moved to `SilverStripe\Forms\Validation` and `SilverStripe\Core\Validation` namespaces

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
