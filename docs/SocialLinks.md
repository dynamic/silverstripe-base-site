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
