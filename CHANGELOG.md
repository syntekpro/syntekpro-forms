# Changelog

All notable changes to this project will be documented in this file.

## [1.2.9] - 2026-02-03

### Added
- Comprehensive user tutorial covering installation, builder workflow, styling, notifications, scheduling, anti-spam, Gutenberg block usage, entries, exports, and webhooks (see docs/TUTORIAL.md).
- README documentation section linking directly to the new tutorial resource.
- Quick-access tutorial links inside the plugin settings header and the WordPress admin toolbar menu.


## [1.2.8] - 2026-02-02

### Added
- Multi-step forms with per-step validation, progress bar, and navigation.
- New frontend submission flow using FormData for reliable file uploads.
- Privacy controls for IP anonymization and data retention windows.
- Anti-spam enhancements: honeypot, rate limiting, and Akismet checks.
- Add-ons loader plus admin Add-ons page for extensions.
- Additional frontend themes (elegant, contrast, pastel, outline, glass), each with full styling.
- Gutenberg block preview redesign (spacious layout, responsive padding) and extra inspector styling controls (colors, padding, radius, fonts, submit alignment).

### Fixed
- File handling and email notification reliability during submission.

### Changed
- Submit button now only appears on the final step when steps are enabled.

## [1.2.7] - 2026-01-19

### Changed
- Increased sidebar menu icon size to double (76px).
- Increased Admin Bar menu icon size to double (64px).
- Increased company logo size in the Settings footer to double (56px).

## [1.2.6] - 2026-01-19

### Changed
- Updated WordPress side menu icon to `SyntekPro Forms WP Icon Small.png`.
- Updated Top Admin Bar icon to `SyntekPro Forms WP Icon Small.png`.
- Replaced footer logo in Settings page with `company-logo.png`.
- Increased footer logo size for better visibility.
- Updated copyright text in the Settings page.

### Fixed
- Corrected CSS selectors for the Admin Bar menu icon.

## [1.2.5] - 2026-01-19

### Added
- Extensive Gutenberg Block sidebar settings (InspectorControls).
- Support for custom form styles (Themes, Colors, Typography, Spacing) directly from the block editor.
- Advanced block options: AJAX submission toggle, Tabindex control, and Field Value pre-filling.
- Pre-population logic for form fields based on block attributes.

### Changed
- Improved block editor preview styling to be more spacious and professional.
- Updated frontend rendering to support dynamic style overrides via CSS variables.
- Bumped plugin version to 1.2.5.

## [1.2.4] - 2026-01-19

### Added
- SyntekPro logo in the footer copyright line on the Settings page.
- 'Pop out' (scale and shadow) hover animation for the footer logo.

### Changed
- Bumped plugin version to 1.2.4 to ensure latest CSS styles are loaded.

## [1.2.3] - 2026-01-19

### Changed
- Repositioned admin modals (Templates and Entry details) to show to the right of the WordPress admin menu instead of covering it.
- Improved modal centering logic using flexbox and proper display handling in JS.
- Reduced the maximum width of the Template Selection modal from 1000px to 850px for better fit.
- Enhanced responsiveness for folded and mobile admin menus.

## [1.2.2] - 2026-01-19

### Added
- Color scheme indicators in the form template selection modal.
- Centralized modal CSS styles in `admin.css`.

### Changed
- Improved Form Template Selection UI; moved it from being inline/at the bottom of the page to a proper fixed-position popup modal.
- Optimized template selection grid for better visual clarity.

## [1.2.1] - 2026-01-19

### Fixed
- Admin menu pages background color not applying correctly (improved color visibility and added CSS fallback).
- Cache issue by bumping plugin version to 1.2.1.

## [1.2.0] - 2026-01-18

### Added
- Gutenberg Block support for easy form insertion.
- File upload field support with secure handling.
- Dashboard widget showing recent form entries.
- IP logging option for form submissions.
- Bulk actions for entry management (Delete, Mark as Read).
- Export entries to CSV functionality.
- Form preview mode in the builder.
- Export/Import settings functionality.

### Changed
- Improved admin UI responsiveness.
- Optimized database queries for entry listing.
- Enhanced email notification templates.

### Fixed
- Menu icon size issue in WordPress sidebar.
- Conflict with some themes' CSS in the frontend.

## [1.1.0] - 2026-01-05

### Added
- Pre-defined form templates (Contact, Feedback, Quote).
- Advanced conditional logic for form fields.
- Custom CSS support for individual forms.
- Entry filtering by date and form.

### Changed
- Refactored form rendering engine for better performance.

## [1.0.0] - 2025-12-20

### Added
- Initial release.
- Drag & Drop form builder.
- Basic form fields (Text, Email, Textarea, Select, Radio, Checkbox).
- Simple entry management.
- Email notifications.
