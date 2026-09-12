# Social Links Documentation

## Overview

The `SocialLink` class provides a comprehensive social media link management system for SilverStripe sites. It supports 17 popular social media platforms with configurable Bootstrap Icons integration.

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

"Attempts to" is literal: each owned record is published only if the current member can
publish it. Saving Site Settings does not treat SiteConfig edit rights as implying publish
rights on the records SiteConfig owns. A record that fails the check is left in draft and a
warning is logged; because each record is decided on its own, a denied record never blocks its
permitted siblings, and the SiteConfig save itself still succeeds. If something stays in draft
with a "Skipped publishing" line in the log, saving it again as a member with publish rights on
that record will publish it.

One deliberate exception: a command-line process with **no logged-in member** publishes without
checking. Both halves matter - CLI `dev/build` and `dev/tasks/*` run that way, there is no identity
to check against, and gating them would stop those contexts publishing anything. A CLI process that
*does* set a current member is still checked, so a background job that runs as the member who queued
it is subject to the same per-record check as a CMS user.

A web request is likewise *not* exempt, even an anonymous one: `SiteConfig::write()` performs no
permission check of its own (the CMS controller enforces `EDIT_SITECONFIG` when it renders the form),
so an unauthenticated save that reaches these hooks is denied like any other. Browser `dev/build` is
worth flagging because it looks like an admin context and is not necessarily one - it needs
`CAN_DEV_BUILD` rather than `ADMIN`, and runs unauthenticated while the database is still unbuilt -
so in those runs records can stay in draft like any other denial.

What a skip does is park the draft, not seal it. The command-line exception above applies to those later
saves too, so an unauthenticated `SiteConfig::write()` on the CLI - a deploy step, a `dev/tasks/*`
migration, a fixture re-run - publishes a record that was denied, and nothing tells anyone it happened.
The gate therefore delays a publish it disapproves of rather than blocking it outright. Turning it into
a hard block means declaring the trusted contexts explicitly - a config flag, defaulting to checked -
instead of inferring them from the command line, and that default flip is itself a behaviour change: it
would stop `dev/build` and BuildTasks publishing at all, which is what #174 fixed.

The editor who triggered a denial sees nothing in the CMS: the only output is the "Skipped publishing"
warning in the log. Surfacing the skip to that user in the CMS is tracked separately as issue 197.

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

Because each owned record answers for itself, the permission that matters depends on the record:

| Owned record | Permission that decides whether a save publishes it |
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
programmatically - it fails `File::canEdit()` at save time, stays in draft while the `SiteConfig` save
reports success, and a Live frontend renders no logo until someone who can publish that file saves Site
Settings again. Folder-scoped edit rights are a supported SilverStripe pattern and satisfy this
check, so a member with no `FILE_EDIT_ALL` whose logo lives in a folder granting them edit publishes
without incident. When it does not publish, the only signal is the "Skipped publishing" warning, which
names the member it denied.

To give a CMS author the full workflow - edit social links, upload the logo, and have saving Site
Settings push it all live - grant `EDIT_SITECONFIG` and `Social_CRUD`, plus either `FILE_EDIT_ALL` or
edit rights on the folder the logo lives in. Granting only `EDIT_SITECONFIG` means they can change
social links and the logo while every one of those changes stays in draft.

## CSS Classes

Each social link receives CSS classes for styling:
- Base class: `social-icon`
- Channel-specific class: `social-icon-{channel}` (e.g., `social-icon-facebook`)

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
3. **Extensible**: Hook system for further customization
4. **Clean**: Simplified template code
5. **Future-proof**: Easy to adapt to new platforms or icon libraries

## Implementation Details

- Configuration uses SilverStripe's config system
- Icons loaded via `getIconClass()` method
- Fallback to default icon for unmapped channels
- Extension hooks available for custom functionality
- Bootstrap Icons used by default (easily changeable)

## Migration from Font Awesome

If migrating from Font Awesome icons, update your templates to use Bootstrap Icon classes:

- `fa-facebook` → `bi-facebook`
- `fa-twitter` → `bi-twitter-x`
- `fa-instagram` → `bi-instagram`
- etc.
