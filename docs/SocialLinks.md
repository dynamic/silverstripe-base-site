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
