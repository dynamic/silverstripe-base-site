# Social Links Documentation

## Overview

The `SocialLink` class provides a comprehensive social media link management system for SilverStripe sites. The platforms it supports are the ones listed under [Supported Social Channels](#supported-social-channels) below; that list is configuration, so it is not repeated here as a count. Icons for every channel are configurable Bootstrap Icons classes.

## Features

### ✅ **Comprehensive Social Platform Support**
- **Core Platforms**: Facebook, Instagram, X (Twitter), LinkedIn
- **Video/Entertainment**: YouTube, TikTok, Pinterest, Snapchat
- **Communication**: Discord, WhatsApp, Telegram, Slack
- **Professional**: GitHub
- **Alternative**: Threads, Mastodon, Reddit

### ✅ **Configuration-Driven Icons**
- Bootstrap Icons classes are configured in YAML
- Easy to override icons per project
- Automatic fallback to generic link icon
- No template changes needed to add new platforms

### ✅ **Clean Template Implementation**
- Single line icon rendering: `<i class="bi {$IconClass}"></i>`
- No complex if/else chains
- Maintainable and readable code

## Supported Social Channels

### Core Social Media Platforms
- **Facebook** (`facebook`) - `bi-facebook`
- **Instagram** (`instagram`) - `bi-instagram`
- **X/Twitter** (`x`) - `bi-twitter-x`
- **LinkedIn** (`linkedin`) - `bi-linkedin`

### Video & Entertainment Platforms
- **YouTube** (`youtube`) - `bi-youtube`
- **TikTok** (`tiktok`) - `bi-tiktok`
- **Pinterest** (`pinterest`) - `bi-pinterest`
- **Snapchat** (`snapchat`) - `bi-snapchat`

### Communication & Community
- **Discord** (`discord`) - `bi-discord`
- **WhatsApp** (`whatsapp`) - `bi-whatsapp`
- **Telegram** (`telegram`) - `bi-telegram`
- **Slack** (`slack`) - `bi-slack`

### Professional & Developer
- **GitHub** (`github`) - `bi-github`

### Newer/Alternative Platforms
- **Threads** (`threads`) - `bi-threads`
- **Mastodon** (`mastodon`) - `bi-mastodon`
- **Reddit** (`reddit`) - `bi-reddit`

## Configuration

### Location
`/vendor/dynamic/silverstripe-base-site/_config/social-channels.yml`

### Structure
Social channels are configured via YAML configuration:

```yaml
Dynamic\Base\Model\SocialLink:
  social_channels:
    'facebook': 'Facebook'
    'instagram': 'Instagram'
    'x': 'X (Twitter)'
    'linkedin': 'LinkedIn'
    'youtube': 'YouTube'
    'tiktok': 'TikTok'
    'pinterest': 'Pinterest'
    'snapchat': 'Snapchat'
    'discord': 'Discord'
    'whatsapp': 'WhatsApp'
    'telegram': 'Telegram'
    'slack': 'Slack'
    'github': 'GitHub'
    'threads': 'Threads'
    'mastodon': 'Mastodon'
    'reddit': 'Reddit'
  
  # Bootstrap Icons mapping for social channels
  social_icons:
    'facebook': 'bi-facebook'
    'instagram': 'bi-instagram'
    'x': 'bi-twitter-x'
    'linkedin': 'bi-linkedin'
    'youtube': 'bi-youtube'
    'tiktok': 'bi-tiktok'
    'pinterest': 'bi-pinterest'
    'snapchat': 'bi-snapchat'
    'discord': 'bi-discord'
    'whatsapp': 'bi-whatsapp'
    'telegram': 'bi-telegram'
    'slack': 'bi-slack'
    'github': 'bi-github'
    'threads': 'bi-threads'
    'mastodon': 'bi-mastodon'
    'reddit': 'bi-reddit'
  
  # Default fallback icon
  default_icon: 'bi-link-45deg'
```

## Usage

### In CMS
1. Navigate to Site Settings
2. Add Social Links
3. Choose from dropdown of available platforms
4. Enter URL
5. Icon automatically assigned based on platform

Saving Site Settings attempts to publish every draft SocialLink/UtilityLink (and the site
Logo/LogoRetina) to Live automatically - including from a `SiteConfig::write()` called
outside the CMS, e.g. a BuildTask or deploy script - so there's usually no separate manual
publish step. A publish failure on one owned record is logged and doesn't block the
SiteConfig save or its other owned records; check the logs if a link doesn't go live as
expected.

On a `SiteConfig::write()` from code, "attempts to" is literal: each owned record is published
only if the current member can publish it, and SiteConfig edit rights do not imply publish
rights on the records SiteConfig owns. A record that fails the check is left in draft and a
warning is logged; because each record is decided on its own, a denied record never blocks its
permitted siblings, and the SiteConfig save itself still succeeds. If something stays in draft
with a "Skipped publishing" line in the log, saving it again as a member with publish rights on
that record will publish it.

A save from the Site Settings screen is different. After the write, the framework's own
publish-on-save publishes every owned record with no permission check, so a record denied there
goes live anyway, after its "Skipped publishing" line is logged. See "Publishing on save" in
[docs/en/index.md](en/index.md#publishing-on-save).

Publishing a logo also moves its file out of protected asset storage. `Logo` and `LogoRetina` are
`Image` records and `Image` extends the `Versioned` `File`, so the auto-publish above writes their
Live record through `Versioned::writeToStage()`, which assets' `AssetControlExtension` picks up in
its `onBeforeWrite()` hook: `getRecordState()` reports a Live-stage record as public, and
`processManipulation()` then hands the file to `publishAll()`, which moves it from the protected
store to the public one. Before that first publish the file sits in the protected store, reachable
only through `ProtectedFileController`; afterwards it is in the public store, so its URL serves to an
anonymous visitor without that controller being consulted.

Publishing and making public are two different questions, though. `getRecordState()` reports
`STATE_PUBLIC` only when `Member::actAs(null, ...)` finds that an anonymous visitor passes
`canView()` on the record; otherwise it reports `STATE_PROTECTED` even on the Live stage. So a logo
whose own `CanViewType`, or the folder it inherits from, is restricted from anonymous visitors does
go Live on the save and stays in the protected store all the same.

The move is not one-way: un-publishing sends the file back. `Versioned::doUnpublish()` deletes the
Live record while the draft remains, `AssetControlExtension::onAfterDelete()` collects the file as
deleted, and `addAssetsFromOtherStages()` immediately re-claims it from the draft record as
`STATE_PROTECTED` - a protected claim removes an asset from the pending-deletion set - so
`processManipulation()` protects rather than deletes it. An un-publish does not stick, though: the
logo is left as a draft-only record, so the next Site Settings save publishes it (a write from
code applies the per-record publish check described under Permissions first) and moves it public
again, with nothing in the log mentioning the earlier un-publish. Saving Site Settings never performs that un-publishing
itself: it only ever publishes these records, so the reversal happens to the `Image` record
separately, by whatever un-publishes it (`doUnpublish()`, or a project's own publish flow that
removes the record from Live).

One deliberate exception: a command-line process with **no logged-in member** publishes without
checking. Both halves matter - CLI `dev/build` and `dev/tasks/*` run that way, there is no identity
to check against, and gating them would stop those contexts publishing anything. A CLI process that
*does* set a current member is still checked, so a background job that runs as the member who queued
it is subject to the same per-record check as a CMS user.

The exception is deprecated in 8.1.0 and removed in 9.0.0. A publish that goes ahead only because of
it, one that `canPublish()` would deny, raises a deprecation notice and logs a warning naming the
record. A task or deploy script that must keep publishing wraps its write in `Member::actAs()`, e.g.
`Member::actAs(Injector::inst()->get(DefaultAdminService::class)->findOrCreateDefaultAdmin(), fn () => $siteConfig->write())`.

A web request is likewise *not* exempt, even an anonymous one: `SiteConfig::write()` performs no
permission check of its own (the CMS controller enforces `EDIT_SITECONFIG` when it renders the form),
so an unauthenticated save that reaches these hooks is denied like any other. Browser `dev/build` is
worth flagging because it looks like an admin context and is not necessarily one - it needs
`CAN_DEV_BUILD` rather than `ADMIN`, and runs unauthenticated while the database is still unbuilt -
so in those runs records can stay in draft like any other denial.

What a skip does is park the draft, not seal it. The command-line exception above applies to those later
saves too, so an unauthenticated `SiteConfig::write()` on the CLI - a deploy step, a `dev/tasks/*`
migration, a fixture re-run - publishes a record that was denied, with only the deprecation warning
above to say so. Until 9.0.0 the gate therefore delays a publish it disapproves of rather than blocking
it outright.

A denial on a write from code shows nowhere but the "Skipped publishing" warning in the log.

### In Templates
```html
<% loop $SocialLinks %>
    <a href="$URL" class="social-icon {$SocialChannel.LowerCase}">
        <i class="bi {$IconClass}"></i>
    </a>
<% end_loop %>
```

## Methods

### SocialLink Model Methods
- `getSocialChannels()` - Returns configured social platforms
- `getIconClass()` - Returns Bootstrap icon class for current channel
- `getSocialIcons()` - Returns all icon mappings
- `getSocialChannelName()` - Returns the display label for the selected channel, or `null` if unmapped

### Extension Hooks
- `updateSocialChannels(array &$channels): void` - Adds, renames, or removes channels in the list returned by `getSocialChannels()`

`$channels` is an ordered map of `channel key => display label` (for example
`'mastodon' => 'Mastodon'`) passed **by reference**, so mutate it in place:
`$channels['bandcamp'] = 'Bandcamp';` adds a channel, `unset($channels['facebook']);` removes
one, and assigning a key that already exists (`$channels['x'] = 'X';`) renames its label.
`getSocialChannels()` returns its own local `$channels` variable and never looks at what the
hook returned, so only in-place mutation has any effect.

**Ordering contract.** `getSocialChannels()` reads the `social_channels` config first, falling
back to the hardcoded list inside the method when that config is unset or empty, and only then calls
`$this->extend('updateSocialChannels', $channels)` on whatever that lookup resolved to. The hook
therefore always receives the already-resolved configured list, and a `social_channels` entry in
YAML cannot undo a change the hook makes: config decides the hook's input, the hook decides the
final output, and where both describe the same channel the hook wins.

`getSocialChannelName()` calls `getSocialChannels()` rather than reading the config itself, so
the hook shapes that label too - a channel the hook renamed resolves to its new label, and a
channel the hook removed resolves to `null` even though the shipped config still lists it.

`tests/Extension/SocialLinkChannelsExtension.php` is the reference implementation: one hook that
adds, renames, and removes in a single pass.

## Customization

### Adding New Platforms
1. Add to `social_channels` config
2. Add icon mapping to `social_icons` config
3. Platform immediately available in CMS dropdown

### Project-Specific Overrides
Create `app/_config/social-overrides.yml`:
```yaml
Dynamic\Base\Model\SocialLink:
  social_channels:
    'custom-platform': 'Custom Platform'
  social_icons:
    'custom-platform': 'bi-custom-icon'
```

### Changing Channels In Code
Config is the right tool for a fixed list. When the list has to be decided at runtime - per
member, per environment, or derived from data the config system cannot see - use the
`updateSocialChannels` hook instead (see [Extension Hooks](#extension-hooks)).

Create `app/src/Extension/SocialChannelsExtension.php`:
```php
<?php

namespace App\Extension;

use SilverStripe\Core\Extension;

class SocialChannelsExtension extends Extension
{
    /**
     * @param array<string,string> $channels
     */
    public function updateSocialChannels(array &$channels): void
    {
        // Added here - not present in _config/social-channels.yml
        $channels['bandcamp'] = 'Bandcamp';

        // Relabel a channel the shipped config already provides
        $channels['x'] = 'X';

        // Drop a channel this project never uses
        unset($channels['facebook']);
    }
}
```

Apply it to the model in `app/_config/social-extensions.yml`:
```yaml
Dynamic\Base\Model\SocialLink:
  extensions:
    - App\Extension\SocialChannelsExtension
```

That yields the channels `_config/social-channels.yml` defines, minus `facebook`, plus
`bandcamp`, with `x` labelled `X` instead of `X (Twitter)`.

The CMS follows automatically: `getCMSFields()` builds the `SocialChannel` dropdown from
`getSocialChannels()`, so the hook changes the dropdown options too - no field or template code
to touch. Give the new channel an icon by adding it to `social_icons` in config; `getIconClass()`
reads config only, so this hook never affects the icon.

Removing a channel reaches beyond the list itself. The hook never rewrites existing records - a
`SocialLink` that already stores `facebook` keeps that value in the database - but the dropdown
stops offering it, so saving that record again in the CMS can be rejected with "Not an allowed
value": `DropdownField` inherits `OptionFieldValidator` from `SelectField`, which checks the
submitted key against `getSocialChannels()`. `SocialChannel` is required too
(`RequiredFieldsValidator`, `src/Model/SocialLink.php`), so clearing it is not a way round the
failure. Rename while old records exist - a rename leaves stored keys untouched and only changes
the label shown in `summary_fields` - and remove the key once no record uses it.

### Changing Icon Library
Update icon mappings to use different icon library:
```yaml
Dynamic\Base\Model\SocialLink:
  social_icons:
    'facebook': 'fa-facebook'  # Font Awesome instead
    # ... update all mappings
```

## Permissions

Social links use the `Social_CRUD` permission for:
- Creating new social links
- Editing existing social links
- Deleting social links

Publishing is a separate check. Publish rights come from `Versioned::canPublish()`, which works
in this order: `ADMIN` is granted outright; otherwise an extension on the record may decide it
(`canPublish()`, or `extendCanPublish()` - there is no `updateCanPublish()`, that name is never
called); and only if none of them has an opinion does it fall back to `canEdit()`. If your project
gates publishing through such a hook, that hook wins over the table below.

This check applies to a `SiteConfig::write()` from code. A save from the Site Settings screen
publishes every owned record regardless; see "Publishing on save" in
[docs/en/index.md](en/index.md#publishing-on-save). In the CMS the permissions below decide what an
editor can add or change (links through `canEdit()`, files through Asset Admin's checks), not what
goes live.

Because each owned record answers for itself, the permission that matters depends on the record:

| Owned record | Permission that decides whether a write from code publishes it |
|---|---|
| `SocialLink` | `Social_CRUD` (SocialLink's own `canEdit()`) |
| `UtilityLinks` | `EDIT_SITECONFIG` - plain link records delegate to their owner's `canEdit()`, so this row is not really a gate: anyone who can save Site Settings can publish them |
| `Logo` / `LogoRetina` | `FILE_EDIT_ALL`, **or** edit rights on the file's parent folder - `File::canEdit()` checks `FILE_EDIT_ALL` first and, failing that, delegates to the parent folder when the file inherits permissions; a file at the root with nothing to delegate to needs `FILE_EDIT_ALL` |

`CMS_ACCESS_AssetAdmin` is **not** `FILE_EDIT_ALL`, and the `Logo`/`LogoRetina`
records are the ones where that shows. The usual CMS route is already defended - `AssetAdmin` gates an
upload on `File::canCreate()` against the target folder and gates selecting an existing file on
`$file->canEdit()` - so a member with no asset rights generally cannot get a logo into Site Settings in
the first place. What is reachable is the permissions-changed-after-the-fact case: a file created while
the member held the right, or in a folder that granted edit and later stopped, or written
programmatically. A `SiteConfig::write()` from code then fails `File::canEdit()`, leaves the logo in
draft while the save reports success, and a Live frontend renders no logo until it is published; a Site
Settings save in the CMS publishes it anyway. Folder-scoped edit rights are a supported SilverStripe pattern and satisfy this
check, so a member with no `FILE_EDIT_ALL` whose logo lives in a folder granting them edit publishes
without incident. When the check denies, the only signal is the "Skipped publishing" warning, which
names the member it denied.

To give a CMS author the full workflow - edit social links and upload or choose the logo - grant
`EDIT_SITECONFIG` and `Social_CRUD`, plus either `FILE_EDIT_ALL` or edit rights on the folder the logo
lives in. Saving Site Settings then publishes all of it. The same grants let a write from code made as
that member publish the same records.

## CSS Classes

base-site ships no `SocialLink` template: the module defines the model, the channel config and the
icon class, and emits no markup of its own, so it adds no CSS class to a rendered social link.

The class names documented here come from the template example in [In Templates](#in-templates)
above - a pattern a consuming theme writes, not something the module generates. The Essentials
theme's `Includes/Footer.ss` uses that same pattern (`class="social-icon {$SocialChannel.LowerCase}"`),
so a Facebook link renders the two classes `social-icon facebook` there:
- `social-icon` - the class shared by every link, and the one the Essentials theme's SCSS styles
- the lowercased channel key as a second class (`facebook`, `mastodon`, ...) - for per-channel styling, opt-in in your own template

## Bootstrap Icons Requirement

This implementation requires Bootstrap Icons to be included in your theme. Ensure you have:

```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
```

Or install via npm:

```bash
npm install bootstrap-icons
```

## Benefits

1. **Maintainable**: No template changes for new platforms
2. **Configurable**: Easy per-project customization
3. **Extensible**: `updateSocialChannels()` changes the channel list at runtime - see [Extension Hooks](#extension-hooks)
4. **Clean**: Simplified template code
5. **Future-proof**: Easy to adapt to new platforms or icon libraries

## Implementation Details

- Configuration uses SilverStripe's config system
- Icons loaded via `getIconClass()` method
- Fallback to default icon for unmapped channels
- `updateSocialChannels(array &$channels): void` mutates the config-resolved channel list in place - see [Extension Hooks](#extension-hooks)
- Bootstrap Icons used by default (easily changeable)

## Migration from Font Awesome

If migrating from Font Awesome icons, update your templates to use Bootstrap Icon classes:

- `fa-facebook` → `bi-facebook`
- `fa-twitter` → `bi-twitter-x`
- `fa-instagram` → `bi-instagram`
- etc.
