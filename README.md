# SyntekPro Forms

A custom form builder plugin for WordPress.

## Description

SyntekPro Forms is a WordPress plugin that allows you to create and manage custom forms.
For a step-by-step walkthrough of every capability, see the comprehensive tutorial in [docs/TUTORIAL.md](docs/TUTORIAL.md).

## Installation

1. Download the plugin files
2. Upload to `/wp-content/plugins/syntekpro-forms` directory
3. Activate the plugin through the 'Plugins' menu in WordPress

### Important Update Note

- For upgrades, use a release ZIP that contains the root folder named exactly `syntekpro-forms`.
- Do **not** upload GitHub source-code ZIPs (for example `syntekpro-forms-main.zip` or `syntekpro-forms-origin.zip`) because WordPress treats those as a new plugin folder and installs side-by-side.

## Features

- **Drag & Drop Form Builder**: Build forms with an intuitive interface and advanced conditional logic.
- **Multi-step Forms**: Progress bar, per-step validation, and next/previous navigation.
- **Templates**: Start quickly with pre-defined form templates (Contact, Feedback, Quote, etc.).
- **Modern Styling**: Multiple themes (classic, modern, minimal, elegant, contrast, pastel, outline, glass) with color and typography overrides.
- **Entry Management**: View, filter, and export submissions to CSV from the dashboard.
- **Gutenberg Support**: Dedicated block with styling and behavior controls in the inspector.
- **Email Notifications**: Customizable admin notifications and user confirmation emails.
- **File Uploads**: Secure file attachments via AJAX (FormData) submissions.
- **Privacy & Anti-spam**: Honeypot, rate limiting, Akismet checks, IP anonymization, and data retention controls.
- **Add-ons Ready**: Add-ons loader and admin Add-ons page to extend functionality.

## Documentation & Tutorials

- [docs/TUTORIAL.md](docs/TUTORIAL.md): Complete walkthrough covering setup, form building, notifications, limits, anti-spam, Gutenberg block usage, entries, webhooks, and troubleshooting.

## Version

1.4.0

## Author

Syntek Pro

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a full history of changes.

### 1.4.0 (2026-02-20)
- Major builder UX refresh with improved canvas controls, bulk actions, selection flow, and cleaner Gravity-style editor look
- Frontend form rendering overhaul with responsive grid alignment, better field spacing, and reduced overlap/squeezing in narrow block/theme containers
- New styling controls for title/description/label alignment (left/center/right) with live preview and frontend output support
- Expanded typography with Google Fonts support in builder settings and Gutenberg block controls
- Added webhook queue/retry manager with admin dashboard, retry tools, and cron-based processing
- Add-ons system expanded with richer Add-ons page visuals, featured pinning, and starter production add-ons

### 1.2.8 (2026-02-02)
- Multi-step forms with progress, step validation, and final-step submit button
- Improved AJAX submissions with FormData (better file handling)
- Privacy (IP anonymization, retention) and anti-spam (honeypot, rate limit, Akismet)
- New themes and add-ons administration page

### 1.2.7 (2026-01-19)
- Doubled the size of the sidebar and admin bar menu icons
- Doubled the size of the company logo in the Settings page footer

### 1.2.6 (2026-01-19)
- Updated WordPress side menu and top bar icons with branding-consistent assets
- Replaced footer logo in Settings page and improved its visibility
- Fixed admin bar CSS selectors for correct icon rendering

### 1.2.5 (2026-01-19)
- Enhanced Gutenberg Block with comprehensive sidebar settings
- Added support for custom styling (colors, typography, sizes) in the block editor
- Added Advanced block settings (Form ID, Preview, AJAX toggle, Tabindex, Field Values)
- Improved form preview in the Gutenberg editor to be less cramped
- Implemented dynamic pre-filling of form fields via block attributes

### 1.2.4 (2026-01-19)
- Added SyntekPro logo to the footer copyright line in the Settings page
- Implemented 'pop out' hover effect for the footer logo
- Bumped version for CSS cache busting

### 1.2.3 (2026-01-19)
- Repositioned Templates modal to show next to the WordPress admin menu instead of covering it
- Improved modal centering and responsiveness
- Reduced template modal size for better usability on smaller screens

### 1.2.2 (2026-01-19)
- Improved Form Template Selection UI with a proper popup modal
- Added color scheme indicators to template previews
- Centralized modal styles for consistency across the plugin

### 1.2.1 (2026-01-19)
- Fixed Light Green background not showing on admin pages
- Bumped version for cache busting

### 1.2.0 (2026-01-18)
- Added Gutenberg Block support
- Added File upload support
- Added Dashboard widget
- Improved Entry management
- Fixed Admin UI issues