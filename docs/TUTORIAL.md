# SyntekPro Forms — Complete Feature Tutorial

This guide walks you through every major feature in the SyntekPro Forms plugin. Follow the sections in order for a full orientation, or jump to the area you need.

## 1. Installation & Activation

1. Download or clone the plugin into `wp-content/plugins/syntekpro-forms`.
2. In the WordPress dashboard go to **Plugins → Installed Plugins** and activate **SyntekPro Forms**.
3. Upon activation the plugin creates the `spf_forms` and `spf_entries` database tables and schedules the optional data-retention cleanup task.

## 2. Global Settings

Navigate to **Forms → Settings** to configure site-wide defaults:

| Setting | Description |
| --- | --- |
| Recaptcha Keys | Add site + secret keys to enable bot protection (supports invisible mode). |
| Default Theme | Styling preset applied when a form does not override its own theme. |
| Email From Name/Address | Used for admin and confirmation emails. |
| Anti-spam & Privacy | Toggle honeypot, rate limiting, Akismet, IP logging, anonymization, and data retention windows. |
| Toolbar/Dashboard Widgets | Enable or disable the quick-access UI affordances. |

> Tip: If you enable **Data Retention**, submissions older than the configured days will be removed automatically every six hours.

## 3. Building a Form

1. Go to **Forms → Add New** to launch the builder.
2. Give the form a title and optional description in the header or **Form Settings** tab.
3. Drag field types from the **Add Fields** tab into the canvas or click a field tile to insert it at the bottom.
4. Reorder fields via drag-and-drop; each field opens a side panel for labels, placeholders, validation rules, conditional logic, and choices.
5. Use the **Step Break** field to create multi-step forms with automatic progress tracking.

### Field Types
- Text, Email, Number, Textarea
- Select, Radio, Checkbox (with choice management, defaults, conditional visibility)
- Date picker, File upload, Hidden fields
- Step Break (multi-step wizard)

### Live Preview Styling
Switch to the **Styling** tab to preview themes, fonts, colors, padding, and border radii. Changes apply instantly to the builder preview and can be overridden per form.

## 4. Success Behavior & Notifications

Inside **Form Settings → Success Handling**:

1. Pick the submission response type: **Message** or **Redirect**.
2. Customize the success text and (if redirecting) provide the target URL.

In **Admin Notifications**:

1. Toggle admin email alerts per form.
2. Provide a comma-separated list of recipients or leave blank to use the global admin email.
3. Confirmation emails for submitters remain available when a field that looks like an email address is present.

## 5. Submission Limits & Scheduling

- **Maximum Submissions** caps total entries; once reached visitors see the configured “Limit Reached” message.
- **Scheduling** accepts start/end datetimes plus specific messages for not-yet-open and closed states.
- The front end automatically blocks submissions when the window is closed and displays the custom message in a styled notice.

## 6. Anti-spam & Security

| Feature | Configuration |
| --- | --- |
| Honeypot | Enabled globally or per form; invisible field catches bots. |
| Rate Limiting | Configure allowed frequency (seconds) in global settings. |
| Akismet | Toggle globally; requires a WordPress.com API key. |
| reCAPTCHA | Provide keys + enable invisible mode if desired. |
| File Uploads | Server-side validation for MIME type and max file size before storing uploads. |
| IP Logging | Can be disabled or anonymized to meet privacy requirements. |

## 7. Styling & Theming

Every form can override theme, fonts, base font size, padding, border radius, label colors, background, button alignment, and more. Additional options include:

- **Theme Presets:** Classic, Modern, Minimal, Elegant, Contrast, Pastel, Outline, Glass.
- **CSS Variables:** Gutenberg block exposes color slots for inputs, labels, descriptions, and buttons.
- **Custom CSS:** Use your theme or customizer to target `.spf-form-wrapper` selectors.

## 8. Templates & Duplication

- From **Forms → All Forms**, click **Add New** to open the template gallery; choose a starting layout (Contact, Feedback, Quote, etc.).
- Use the **Duplicate** button on the forms list to clone an existing configuration (fields + settings) and tweak without rebuilding.

## 9. Gutenberg Block Workflow

1. In the block editor insert **SyntekPro Form**.
2. Choose the desired form from the sidebar dropdown; preview renders inside the editor.
3. Adjust styling (theme, colors, typography, padding), button alignment, AJAX toggle, tabindex, success behavior, and even pass preset field values via the Inspector controls.
4. Use the block’s **Preview Form** button to test multi-step flow without leaving the editor.

## 10. Frontend Shortcode

Use `[syntekpro_form id="123"]` in classic editors or widgets. Attributes include:

- `theme`, `bgColor`, `primaryColor`, `labelColor`, `borderRadius`, `fieldPadding`
- `showTitle`, `showDescription`, `ajax` (true/false)
- `fieldValues` (`name=John,email=john@example.com`)

## 11. Entry Management

Navigate to **Forms → Entries** for dashboard tools:

- Filter by form, status (read/unread), search keyword, date range.
- Bulk actions: mark as read or delete permanently.
- Export filtered results to CSV (honors current filters and search terms).
- Click the eye icon to view full submission details in a modal (includes IP/user agent if logging enabled).

## 12. Webhooks & Integrations

Per form, enable **Webhooks** and add one URL per line. On successful submission the plugin POSTs JSON containing form metadata, field data, and entry ID. Use this to trigger automation platforms (Zapier, Make, custom endpoints).

## 13. Add-ons & Extensibility

- Drop PHP files into `wp-content/plugins/syntekpro-forms/addons/` to auto-load them.
- Filter `syntekpro_forms_addons_paths` to register custom directories.
- Hooks and filters are scattered through rendering, submissions, and notifications; inspect the core class for entry points.

*New in 1.4.1* – **REST API & developer hooks**

Three new endpoints allow external systems to work with forms and entries directly via `wp-json/syntekpro-forms/v1`.

- `GET /forms` (list, supports `page`, `per_page`, `search`)
- `POST /forms` (create)
- `GET /forms/<id>`, `PUT /forms/<id>`, `DELETE /forms/<id>`
- `GET /entries` (filterable by `form_id`, `status`, `search`)
- `POST /entries` (public, accepts `form_id` and `entry_data` payload)
- `GET|PUT|DELETE /entries/<id>`

All administrative routes require `manage_options` capability; create action is open so you can submit forms from headless sites. A slew of new actions/filters accompany each REST event (`syntekpro_forms_rest_insert_form`, `syntekpro_forms_rest_prepare_entry`, etc.), giving developers fine-grained control of API behavior.

## 14. Growth Features (New)

The plugin now includes product-growth tooling to increase conversion and post-submit automation:

- **Payments (Stripe-ready):** Number/payment fields are aggregated into a payment summary with coupon support. When Stripe keys are configured and payment is enabled for a form, successful submission can return a Stripe Checkout URL.
- **Automation connectors:** Global endpoints for Zapier and Make, plus native Mailchimp and HubSpot sync support after successful submission.
- **Save & Resume:** Multi-step forms can be saved as drafts and resumed later from a tokenized resume URL.
- **Analytics dashboard:** Open **Forms → Analytics** to view per-form views, starts, completions, abandons, and top field-dropoff events.

## 15. Troubleshooting Checklist

## 14. Troubleshooting Checklist

1. **Form not showing?** Confirm shortcode ID matches a published form and status is **Active**.
2. **Emails missing?** Verify notifications are enabled, the recipient list is valid, and WordPress can send email (try WP Mail Logging).
3. **File uploads failing?** Check PHP `upload_max_filesize` and ensure allowed MIME types in `includes/file-handler.php`.
4. **Conditional logic script missing?** The plugin only enqueues `conditional-logic.js` when a form block/shortcode is detected. Wrap forms in `do_shortcode()` so detection works.
5. **Rate limit warning?** Reduce the cooldown seconds or disable rate limiting while testing.

## 16. Recommended Launch Checklist

- ✅ Test each step (including validation, AJAX errors, success message/redirect).
- ✅ Submit sample entries and confirm they appear under **Entries** and email notifications land as expected.
- ✅ Exercise export, webhook, and admin notification paths.
- ✅ Review styling on desktop + mobile and adjust in the builder or block inspector.
- ✅ Backup database and exports before enabling automated data retention.

Need more help? Reach out to SyntekPro support or extend the plugin using the add-on loader for bespoke workflows.
