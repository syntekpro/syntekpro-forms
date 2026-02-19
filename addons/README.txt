SyntekPro Forms Add-ons

Place custom PHP add-ons in this folder. Each file will be loaded on plugins_loaded (priority 20).

Guidelines:
- One add-on per file, named with .php extension.
- Avoid naming conflicts with core plugin classes/functions.
- Use WordPress hooks/filters to extend behavior.
- You can add paths programmatically with the filter: syntekpro_forms_addons_paths
- Optional header fields for Add-ons showcase cards:
	- Add-on Name: Friendly display name.
	- Description: Short explanation shown on the card.
	- Icon: Either a dashicon class (example: dashicons-randomize) or an image URL/path.
	- Graphic: Optional image URL/path for a top banner thumbnail.

Example header:
/**
 * Add-on Name: CRM Sync Pro
 * Description: Pushes submissions to your CRM.
 * Version: 1.0.0
 * Author: Your Team
 * Icon: dashicons-groups
 * Graphic: assets/images/crm-banner.jpg
 */

Included add-ons:
- spf-addon-disposable-email-blocker.php
	- Blocks submissions using disposable email domains.
	- Filter to customize list: spf_disposable_email_domains

- spf-addon-min-submit-time.php
	- Adds a minimum wait time before form submit (anti-bot).
	- Filter to customize time (milliseconds): spf_min_submit_time_ms

Submission extension hooks (for advanced add-ons):
- Filter: syntekpro_forms_submission_raw_data
	- Modify normalized incoming form payload before validation/sanitization.

- Filter: syntekpro_forms_submission_pre_validate
	- Run pre-validation logic.
	- Return true to continue, false to block, or WP_Error with a user-facing message.

- Filter: syntekpro_forms_submission_field_value
	- Modify individual field value before required checks and sanitization.

- Filter: syntekpro_forms_submission_sanitized_data
	- Modify final sanitized data before database insert.

- Action: syntekpro_forms_submission_before_insert
- Action: syntekpro_forms_submission_after_insert
- Action: syntekpro_forms_submission_before_notifications
- Action: syntekpro_forms_submission_after_notifications

- Filter: syntekpro_forms_submission_success_payload
	- Modify the AJAX success payload returned to the frontend.
