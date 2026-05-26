# SyntekPro Forms

Professional WordPress form builder plugin with advanced operations, analytics, security controls, integrations, and GitHub-powered updates.

## Version

Current release: 2.3.1

## Overview

SyntekPro Forms provides an end-to-end form platform inside WordPress:

- Visual drag-and-drop form building
- Multi-step forms and conditional logic
- Entry capture, moderation, export, and analytics
- Email, webhook, and CRM/automation dispatch
- Privacy, anti-spam, consent, and fraud controls
- Developer APIs (REST, WP-CLI hooks, extensibility points)
- GitHub release updater with remote push-install endpoint

## What Is New In 2.3.1

- Added secure remote push-update install endpoint:
  - POST /wp-json/syntekpro-forms/v1/push-update
- Added token authorization support for push updates:
  - filter: syntekpro_forms_push_update_token
  - header: X-SPF-Update-Token
- Added immediate update workflow:
  - clears update caches
  - refreshes GitHub release metadata
  - installs update package when available
- Enabled plugin auto-update by default (filterable):
  - syntekpro_forms_force_auto_update

## Core Capabilities

### 1. Form Builder

- Drag-and-drop builder interface
- Field insert/reorder/edit workflow
- Form templates and duplicate/clone workflow
- Section/page layout controls
- Multi-step form support with progress logic
- Live builder preview and layout adjustments

### 2. Field Types

#### Standard Fields

- Single line text
- Paragraph text
- Email
- Number
- Phone
- Website
- Select dropdown
- Multi-select
- Radio
- Checkbox
- Toggle
- Hidden
- HTML block
- Section divider
- Page break

#### Advanced Fields

- Name (first/last)
- Date picker
- Time picker
- Address
- File upload (single and multi-file)
- CAPTCHA verification fields
- Consent checkbox
- List/dynamic row style input
- Multiple choice
- Image choice

#### Post And Special Fields

- Post title/body/excerpt/tags/category/image/custom field mapping
- Calculation field
- Repeater field
- Signature field

### 3. Frontend Rendering And UX

- Shortcode rendering for classic editor/theme usage
- Gutenberg block support with inspector controls
- Conditional frontend asset loading for performance
- AJAX form submission support
- Dynamic success message or redirect handling
- Builder and frontend theming options

### 4. Entry Management

- Entry list by form
- Search/filter by form, status, date, and text
- Star/unstar and notes support
- Read/unread and bulk actions
- Entry edit and delete flows
- CSV export and JSON export helpers
- PDF-oriented export support

### 5. Email, Notification, And Template Features

- Admin notification email routing
- User confirmation email support
- Per-form email settings
- Merge-tag support in templates
- Template preview controls
- Conditional template rendering blocks
- Loop block rendering for repeatable data
- SMTP service integration via PHPMailer
- SMTP/OAuth secret storage protections
- SMTP test action and delivery logs

### 6. Integrations And Automation

- Webhook queue system with retries
- Manual webhook retry and dashboard visibility
- Webhook signature support (HMAC-based)
- Global and per-form endpoint handling
- Zapier support
- Make support
- Mailchimp contact/list sync support
- HubSpot contact sync support
- Native CRM connector dispatch:
  - Salesforce lead push
  - ActiveCampaign contact sync
  - Brevo contact sync
- Native integration backend foundations:
  - SendGrid, Twilio, Google, Airtable, Notion connectors

### 7. Analytics, Testing, And Reporting

- Form-level analytics events:
  - view
  - start
  - complete
  - abandon
  - field dropoff
- Funnel metrics and abandonment analysis
- A/B testing backend:
  - variant allocation
  - deterministic routing
  - conversion tracking
  - winner workflows
- Data visualization backend:
  - bar/pie/line/histogram datasets
  - dashboard widgets
  - summary statistics
  - SVG export scaffold

### 8. Security, Privacy, And Abuse Prevention

- Nonce validation on privileged actions
- Sanitization and validation pipeline
- Honeypot checks
- Rate limiting controls
- CAPTCHA integrations:
  - reCAPTCHA
  - hCaptcha (addon)
  - Turnstile (addon)
- Akismet compatibility
- OTP email verification gate before final submission
- Password-protected form submission flow
- Geolocation/fraud backend foundations:
  - risk scoring
  - fraud event logging
  - manual review actions
- WordPress Consent API registration:
  - consent type: syntekpro_forms
- GDPR exporter and eraser integration
- IP logging and anonymization options
- Data retention automation and scheduled cleanup

### 9. Operations, Recovery, And Governance

- Audit log for form lifecycle changes
- Form backup system with retention controls
- Form preview links with expiration handling
- Form versioning backend:
  - snapshots
  - diffs
  - rollback support
- Database optimization helpers
- Scheduled tasks for cleanup/maintenance workflows

## Admin Areas Included

- Forms list
- Add new / form builder
- Entries
- Settings
- Analytics
- Add-ons
- Webhook queue
- Help
- About

## Add-on System

Bundled add-on modules in the addon directory include:

- Disposable email blocker
- hCaptcha + Turnstile
- Minimum submit-time guard
- Slack + Discord dispatch
- SMTP add-on module

The plugin can auto-load supported add-on files from the add-ons path.

## Developer Features

### REST API

Base namespace:

- /wp-json/syntekpro-forms/v1

Primary groups:

- Forms CRUD endpoints
- Entries CRUD endpoints
- Public entry submission endpoint (with validation and anti-spam checks)

### Push Update Endpoint (2.3.1)

- POST /wp-json/syntekpro-forms/v1/push-update

Behavior:

- clears update cache
- checks latest GitHub release
- installs available plugin update

Authorization options:

- authenticated user with plugin update capability
- shared token strategy via:
  - syntekpro_forms_push_update_token filter
  - X-SPF-Update-Token header

### Useful Hooks And Filters

- spf_github_repo
- syntekpro_forms_force_auto_update
- syntekpro_forms_push_update_token
- syntekpro_forms_rest_prepare_forms
- Additional submission and notification lifecycle hooks in core classes

### WP-CLI Support

- Plugin includes CLI support class for form/entry/cache operations.
- Includes cache flush and feature-oriented helper commands for operational tasks.

## Data Model Summary

Main tables created/managed by plugin features include:

- spf_forms
- spf_entries
- spf_webhook_queue
- spf_drafts
- spf_analytics
- spf_email_logs
- spf_audit_log
- spf_form_backups
- spf_form_versions
- spf_preview_links
- spf_webhook_logs
- spf_email_templates
- spf_ab_variants
- spf_ab_events
- spf_dashboards
- spf_fraud_settings
- spf_fraud_events
- spf_integrations
- spf_integration_logs

## Installation

1. Download the release package.
2. Upload to WordPress plugins directory:
   - wp-content/plugins/syntekpro-forms
3. Activate SyntekPro Forms from the Plugins page.

### Important Upgrade Rule

For manual updates, use release zip assets that contain root folder name exactly:

- syntekpro-forms

Do not use GitHub source zip archives with alternate root folder names, or WordPress may install side-by-side instead of replacing the existing plugin directory.

## Updating

### Automatic

- Plugin auto-update is enabled by default for this plugin and can be adjusted via filters/settings.
- GitHub release metadata is used for update discovery.

### Remote Push Install

You can trigger immediate update install per site using:

- POST /wp-json/syntekpro-forms/v1/push-update

Example curl:

curl -X POST "https://example.com/wp-json/syntekpro-forms/v1/push-update" -H "X-SPF-Update-Token: YOUR_SHARED_TOKEN"

## Quick Start

1. Create a form in the builder.
2. Configure notifications, anti-spam, and success behavior.
3. Publish using shortcode or Gutenberg block.
4. Submit test entries and review under Entries.
5. Enable integrations/webhooks as needed.

## Documentation

- docs/TUTORIAL.md for complete usage walkthrough
- CHANGELOG.md for full release history

## Support

SyntekPro team support and release tracking are handled through your project channels and repository workflow.
