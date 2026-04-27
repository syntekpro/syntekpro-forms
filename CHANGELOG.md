# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

## [2.0.0] - 2026-04-27

### Added - MAJOR v2.0 Release

**Form Management & Operations**
- ✅ **Form Cloning**: Duplicate forms with all fields and settings via "Duplicate Form" button on forms list.
- ✅ **Form Audit Log**: Track all form modifications (create, edit, delete, clone) with user, timestamp, and change details. New `spf_audit_log` table.
- ✅ **Form Versioning Infrastructure**: Foundation for form version snapshots with rollback capability. New `spf_form_versions` table.
- ✅ **Automated Form Backups**: Nightly automatic backups of all active forms with 30-day retention. Manual backup/restore UI in settings. New `spf_form_backups` table.
- ✅ **Form Preview Links**: Generate shareable, time-limited (24h default) preview URLs for forms. No login required. New `spf_preview_links` table.

**Advanced Admin Controls**
- ✅ **Custom Capabilities System**: New WordPress capabilities for fine-grained form admin access:
  - `spf_manage_forms`: Manage forms and form settings
  - `spf_view_entries`: View form submissions
  - `spf_manage_settings`: Manage plugin settings
  - `spf_manage_addons`: Manage add-ons
  - Allows non-admin users (Editors, custom roles) to manage forms without full site access.

**Entry Management Enhancements**
- ✅ **Advanced Entry Filtering**: Enhanced entries list with:
  - Date range picker for filtering by submission date
  - Combined filters (form + status + date + search)
  - Persistent filter state across page loads
- ✅ **Bulk Entry Export**: Export filtered entries to CSV or JSON with column selection.
- ✅ **Scheduled Entry Exports**: Foundation for automated weekly/monthly export summaries via email.

**Data Persistence & Recovery**
- ✅ **Progressive Draft Autosave**: Auto-save form draft every 30 seconds with "Last saved X seconds ago" indicator. Frontend draft resume on form re-entry.
- ✅ **Database Optimization Tools**: New admin tools page with:
  - "Optimize Tables" button (runs OPTIMIZE TABLE on all SPF tables)
  - "Vacuum Drafts" tool (delete drafts older than X days)
  - "Rebuild Indexes" tool (repair entry indexes)
  - Dry-run mode with estimated space savings

**Webhook & Integration Improvements**
- ✅ **Webhook Signature Verification**: All webhooks now signed with HMAC-SHA256. Developers receive `X-Syntekpro-Signature` header for secure third-party integration validation.
- ✅ **Enhanced Webhook Visibility**: Expanded webhook queue dashboard with:
  - Payload logs (last 5 attempts per endpoint)
  - Date/status filtering
  - Manual retry trigger for specific webhooks
  - Dead letter queue for permanently failed webhooks
- ✅ **Webhook Logs Table**: New `spf_webhook_logs` table for historical webhook audit trail.

**User Experience**
- ✅ **Conditional Section Visibility**: Extend conditional logic to Section and Page Break containers. Hide entire pages if condition not met.
- ✅ **Field PII Masking Infrastructure**: Framework for designating fields as Personal Identifiable Information (PII). Admin can mask sensitive data in entry displays (show only last 4 chars, initials for names, etc.).

**Developer Experience**
- ✅ **Webhook Signature Documentation**: Code samples for signature validation in Node.js, PHP, Python.
- ✅ **WP-CLI Enhancements**: New commands for v2.0 features:
  - `wp spf form clone <id>` - Clone a form
  - `wp spf form backup <id>` - Manually backup a form
  - `wp spf form restore-backup <backup_id>` - Restore from backup
  - `wp spf preview-link create <form_id>` - Generate shareable preview link
  - `wp spf db optimize` - Run database optimization

**Infrastructure & Architecture**
- ✅ **New Database Tables**:
  - `spf_audit_log`: Form change history and audit trail
  - `spf_form_backups`: Form definition snapshots for recovery
  - `spf_form_versions`: Version control infrastructure (ready for rollback feature)
  - `spf_webhook_logs`: Webhook request/response history
  - `spf_preview_links`: Shareable form preview URLs
- ✅ **New Scheduled Crons**:
  - `spf_backup_forms_cron` (daily): Automatic form backups
  - `spf_cleanup_preview_links` (daily): Expire old preview links
  - `spf_cleanup_webhook_logs` (daily): Archive old webhook logs
- ✅ **Feature Flags & Hooks**:
  - `spf_enable_audit_logging`: Toggle audit log (default: true)
  - `spf_enable_form_cloning`: Toggle cloning (default: true)
  - `spf_enable_autosave_draft`: Toggle autosave (default: true)
  - `spf_backup_retention_days`: Set backup retention window (default: 30 days)
  - `spf_autosave_interval`: Configure autosave frequency (default: 30 seconds)

**Phase 2 Stubs (Architectural Foundation for Future Releases)**
- 🔲 Form versioning with full rollback capability
- 🔲 Advanced email templates with visual builder (conditional blocks, loop sections)
- 🔲 A/B testing infrastructure (form variants, traffic split, analytics)
- 🔲 Advanced funnel analysis dashboard (field drop-off, time-to-complete, geo breakdown)
- 🔲 Entry data visualization (charts, custom dashboards)
- 🔲 IP geolocation & fraud scoring (MaxMind integration)
- 🔲 Native integrations add-ons: SendGrid, Twilio, Google Sheets, Airtable, Notion
- 🔲 GraphQL API endpoint
- 🔲 JavaScript SDK (@syntekpro/forms-js for headless forms)
- 🔲 Fine-grained PII field masking UI (admin controls)
**Phase 2 Implementation Roadmap**

Each Phase 2 feature has an architectural stub class with documented TODO blocks. Classes are included in plugin initialization but return stub responses until implemented. Developers can extend or implement features as needed:

- **Phase 2.1 (v2.1.0)**: Form Versioning & Email Templates
  - `class-form-versioning.php`: Version snapshots, comparison, rollback
  - `class-email-templates.php`: Visual template builder with conditional/loop blocks
  - Target: Q3 2026

- **Phase 2.2 (v2.2.0)**: Analytics & A/B Testing
  - `class-ab-testing.php`: Form variants, traffic split, statistical significance
  - `class-funnel-analytics.php`: Drop-off analysis, time-to-complete, geographic breakdown
  - `class-data-visualization.php`: Charts, custom dashboards, report export
  - Target: Q4 2026

- **Phase 2.3 (v2.3.0)**: Integrations & Security
  - `class-geolocation-fraud.php`: IP geolocation (MaxMind), fraud scoring
  - `class-integrations.php`: SendGrid, Twilio, Google Sheets, Airtable, Notion
  - Target: Q1 2027

- **Phase 2.4 (v2.4.0)**: Developer APIs
  - `class-graphql.php`: Full GraphQL endpoint for forms, entries, analytics
  - `class-javascript-sdk.php`: @syntekpro/forms-js NPM package for headless forms
  - `class-pii-masking.php`: Fine-grained field masking UI and role-based access
  - Target: Q2 2027

**How to Implement Phase 2 Features**
- Each stub class has full method signatures and TODO comments documenting requirements
- Database schema for support tables are documented in class comments
- Use class methods as API surface while implementing TODO logic
- All classes follow SyntekPro coding standards (prepared statements, capability checks, audit logging)
- Admin UI exposure (forms list buttons, settings pages, etc) should be added in corresponding view files


### Changed
- Updated plugin architecture for v2.0 feature ecosystem.
- Enhanced activation flow to create v2.0 database tables.
- Deactivation now cleans up all v2.0 scheduled events.

### Security
- Added webhook request signing for secure third-party integrations.
- Audit log captures IP address and user context for all form changes.
- Field masking infrastructure for PII data protection.
- Custom capabilities prevent privilege escalation from non-admin form managers.

### Performance
- Database optimization tools allow admins to maintain query performance on large form/entry datasets.
- Indexed audit log queries for fast form change history retrieval.
- Webhook log cleanup prevents unbounded table growth.
- Preview link expiry cleanup prevents stale token accumulation.

### Database Migrations
- ✅ Automatic table creation on plugin activation (5 new tables).
- ✅ Backward compatible with existing data (no breaking changes).
- ✅ Safe uninstall cleans up all v2.0 tables when plugin is deleted with option enabled.

## [1.6.3] - 2026-04-27

### Fixed
- Hardened GitHub plugin updater directory replacement on Windows by normalizing destination paths before comparison.
- Added safer replacement fallback to copy plugin files when direct filesystem move fails during update.

## [1.6.2] - 2026-03-30

### Changed
- Enabled plugin automatic background updates by default for fresh installs.

## [1.6.1] - 2026-03-30

### Added
- Plugin auto-update wiring: the "Automatic Background Updates" setting now controls WordPress background updates for this plugin on each installed site.

### Changed
- Form builder sidebar menu structure restored to direct tabs (Add Fields, Field Settings, Form Config).

### Fixed
- Sidebar tab label visibility on active click.
- Entries rendering now normalizes array/object values and formats keys into readable labels.

## [1.6.0] - 2026-03-04

### Added
- **New field types**: Calculation (formula-based), Repeater (dynamic rows), and Signature (canvas drawing) fields with full builder and frontend support.
- **GDPR compliance**: WordPress Privacy Tool integration with personal data exporter and eraser (`class-gdpr.php`).
- **PDF export**: Print-friendly HTML export for entries via admin modal or AJAX endpoint (`class-pdf-export.php`).
- **WP-CLI commands**: Full CLI suite under `wp spf` — form list/get/delete, entry list/export/delete/purge, cache flush (`class-wpcli.php`).
- **Slack & Discord addon**: Webhook notifications on form submission with Block Kit (Slack) and rich embeds (Discord).
- **hCaptcha & Turnstile addon**: Cloudflare Turnstile and hCaptcha as reCAPTCHA alternatives with per-form provider selection.
- **Form import/export**: Export forms as JSON and import from file via the forms list page.
- **Entry starring**: Star/unstar entries in the entries table for quick flagging.
- **Admin notes**: Private notes per entry, editable from the entry detail modal.
- **Entry editing**: Inline edit entry field values from the detail modal.
- **Email routing**: Route admin notifications to different recipients based on field values (e.g., department dropdown).
- **Merge tags**: Use `{form_title}`, `{entry_id}`, `{date}`, `{site_name}`, `{all_fields}`, and `{field_name}` in email subjects and bodies.
- **Per-form email settings**: Custom from name, from email, reply-to from submitter, and per-form notification subject/body templates.
- **Access control**: Restrict form visibility to logged-in users or specific roles with custom denied message.
- **Per-form reCAPTCHA toggle**: Enable/disable reCAPTCHA on individual forms.
- **Admin notification system**: Queue-based admin notices for email failures and webhook errors with `log_error()` utility.
- **Multi-file upload**: File fields now support `multiple` attribute with file list preview.

### Changed
- **Async webhooks**: Webhook dispatch now queues to `spf_webhook_queue` table for async cron processing instead of blocking page load.
- **Transient-based captcha**: Math captcha answers stored in server-side transients instead of client-readable hidden fields.
- **Conditional frontend loading**: CSS/JS only enqueued on pages containing a SyntekPro form shortcode or block.
- **Email error logging**: Failed `wp_mail()` calls and permanently failed webhooks now logged to `WP_DEBUG_LOG` and shown as admin notices.

### Fixed
- **REST API security**: `create_entry` endpoint now validates form existence, active status, availability schedule, applies rate limiting, honeypot check, and sanitizes all entry data.
- **`$_SERVER['REMOTE_ADDR']` sanitization**: All IP address reads now use `sanitize_text_field(wp_unslash())`.
- **`$_POST` superglobal fallback**: Uses explicit excluded-keys array instead of raw dump.
- **Cache busting**: Removed `time()` from asset version strings; uses `SPF_VERSION` consistently.
- **Dead code removal**: Removed unused `render_dashboard_widget()` method.
- **Upload directory**: Renamed from `advanced-forms` to `syntekpro-forms` with `index.php` protection; uninstall cleans both directories.
- **Text domain consistency**: Fixed hardcoded text domain strings in file handler.

## [1.5.1] - 2026-03-04

### Added
- REST API support (`/wp-json/syntekpro-forms/v1`) with settings toggle and dedicated admin controls.
- Growth feature set: Stripe-ready payment summary flow, automation connectors, draft save/resume support, and analytics tracking/reporting.
- New Help page in the main plugin menu with user, developer, and designer documentation entry points.

### Changed
- Admin navigation and IA refresh: Add-ons hub tabs (Add-ons/Webhooks/Growth), Analytics surfaced in Settings, and About content consolidated.
- Add-ons tab switching now uses instant client-side pane switching for a faster Settings-like experience.
- Entries page visual polish for cleaner layout, status tabs, improved table readability, and consistent action styling.
- Standardized admin page headings to concise titles (Settings, Add-ons, Entries, Help, Analytics, About).

### Fixed
- Add-ons tab pane rendering and sidebar layout issues that caused empty sections/overlap in some configurations.
- Help page REST base display now uses a neutral relative path (`/wp-json/syntekpro-forms/v1`).

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
