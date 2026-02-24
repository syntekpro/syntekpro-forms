# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added
- WordPress Consent API compatibility via a `syntekpro_forms` consent type so banners can gate submissions, webhooks, and notifications before consent is granted.

## [1.4.0] - 2026-02-20

### Added
- Webhook queue + retry manager with cron processing, failure dashboard, and manual retry actions.
- Submission lifecycle hook pipeline for extensibility in `class-ajax-handler.php`.
- New add-ons: disposable email blocker and minimum submit time guard.
- Add-ons showcase upgrades: card metadata (icon/graphic), featured hero strip, and pin-as-featured controls.
- Builder enhancements: undo/redo history, multi-select, bulk actions, context menu, quick templates, zoom/device/fullscreen controls, and form health strip.
- Styling controls for title/description/label alignment and Google Fonts selection.
- Gutenberg block inspector support for per-block title/description/label alignment and expanded font choices.

### Changed
- Frontend form rendering now uses responsive width classes and stronger layout spacing/alignment rules.
- Improved frontend block behavior in constrained theme columns to avoid squeezed/overlapping field layouts.
- Increased default field padding and border radius for cleaner out-of-box presentation.
- Builder and frontend visual polish to a cleaner Gravity-style look and hierarchy.

### Fixed
- Required toggle and field settings interaction stability in builder.
- Bulk/keyboard delete consistency with history snapshots.
- Empty-state/canvas update edge cases while adding/removing fields.

## [1.3.1] - 2026-02-08

### Added
- Redesigned Add Fields menu with 3-column grid layout, icons on top, text below
- Organized fields into 3 collapsible sections: Standard Fields (12), Advanced Fields (12), Post Fields (7)
- Added 19 new field types: Hidden, HTML, Section, Page Break, Multiple Choice, Image Choice, Name, Time, Phone, Address, Website, CAPTCHA, List, Multi Select, Consent, Post Title, Post Body, Post Excerpt, Post Tags, Post Category, Post Image, Custom Field
- Full rendering and processing for all new field types with preview functionality
- Dynamic list field with "Add Item" button functionality
- Drag hint message in Add Fields section
- Form Canvas badge moved to top and centered
- Icons added to all styling labels with clean box design
- Tab headers (Add Fields, Form Settings, Styling) redesigned with icons on top and square boxes
- Consistent dropdown arrow styling across all sections
- Fully functional embed modal with post/page loading
- Icon added to Embed button

### Changed
- Field type icons reduced to 16px (half original size)
- Field boxes made perfect square with aspect-ratio: 1/1
- Styling labels redesigned to match field box aesthetic with icons
- Field section headers use same dropdown arrow as form settings
- Form Canvas badge minimized to reduce vertical space
- All dropdowns now have unified arrow styling and positioning
- Tab buttons now display as square boxes with icon on top, text below

### Fixed
- Styling labels no longer shake on hover (removed all animations and pointer events)
- Dropdown arrow now appears correctly in Standard/Advanced/Post field sections
- Embed modal now loads and displays available posts/pages
- Form save confirmation message now displays properly in builder page
- Embed button icon now displays correctly (changed to dashicons-media-code)

## [1.3.0] - 2026-02-03

### Added
- Completely redesigned Forms list page with enhanced UI and functionality.
- Status filter tabs (All, Active, Inactive, Trash) with item counts.
- Search functionality for forms with dedicated search bar and button.
- Bulk actions dropdown: Mark As Active, Mark As Inactive, Reset Views, Permanently Delete Entries, Move to Trash.
- Sortable table columns: Status, Title, ID, Entries, Views, and Conversion rate.
- Views tracking - automatically tracks how many times each form is displayed.
- Conversion rate calculation (Entries/Views percentage).
- Row hover actions: Edit, Settings, Entries, Preview, Duplicate, Trash.
- Settings modal showing Form Settings and Confirmations tabs.
- Preview modal for quick form preview without leaving the list page.
- Select all checkbox functionality for bulk operations.
- Red "Add New" button styling (changed from blue).

### Changed
- Forms list page heading changed from "Forms List" to "Forms".
- Table structure completely redesigned with better column organization.
- Improved row actions displayed on hover instead of inline buttons.

### Fixed
- Database schema updated to include views column in forms table.

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
