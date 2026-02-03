<?php
/**
 * Form Builder View - SyntekPro Forms
 */

if (!defined('ABSPATH')) {
    exit;
}

$form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
$form = null;
$fields_array = array();
$settings_array = array();

if ($form_id > 0) {
    global $wpdb;
    $form = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}spf_forms WHERE id = %d",
        $form_id
    ));
    
    if ($form) {
        $fields_array = json_decode($form->fields, true);
        if (!is_array($fields_array)) {
            $fields_array = array();
        }
        
        $settings_array = json_decode($form->settings, true);
        if (!is_array($settings_array)) {
            $settings_array = array();
        }
    }
} else {
    // For new forms, ensure spfFormData is defined but empty
    $form_data_dummy = true;
}
?>

<div class="wrap spf-form-builder-wrap">
    <div class="spf-admin-header">
        <div class="spf-admin-header-left">
        </div>
        <div class="spf-admin-header-center">
            <img src="<?php echo SPF_PLUGIN_URL; ?>assets/images/Syntekpro%20Forms%20Logo.png" class="spf-admin-logo" alt="SyntekPro">
        </div>
        <div class="spf-admin-header-right">
        </div>
    </div>

    <div class="spf-admin-page-title-wrap" style="display: flex; justify-content: space-between; align-items: center;">
        <h2 class="spf-admin-page-title"><?php echo $form_id > 0 ? __('Edit Form', 'syntekpro-forms') : __('Create New Form', 'syntekpro-forms'); ?></h2>
        <div class="spf-builder-header-actions" style="display: flex; gap: 10px;">
            <button type="button" id="spf-save-form" class="button button-primary button-large spf-tooltip" title="<?php _e('Save all changes to this form', 'syntekpro-forms'); ?>">
                <span class="dashicons dashicons-saved"></span> <?php _e('Save Form', 'syntekpro-forms'); ?>
            </button>
            <button type="button" id="spf-preview-form" class="button button-large spf-tooltip" title="<?php _e('View a live preview of this form', 'syntekpro-forms'); ?>">
                <span class="dashicons dashicons-visibility"></span> <?php _e('Preview', 'syntekpro-forms'); ?>
            </button>
            <a href="<?php echo admin_url('admin.php?page=syntekpro-forms'); ?>" class="button button-large spf-tooltip" title="<?php _e('Return to the form list', 'syntekpro-forms'); ?>">
                <span class="dashicons dashicons-arrow-left-alt"></span> <?php _e('Back', 'syntekpro-forms'); ?>
            </a>
        </div>
    </div>
    
    <div class="spf-builder-container">
        <!-- Left Sidebar - Navigation/Field Types -->
        <div class="spf-sidebar">
            <div class="spf-sidebar-tabs">
                <button type="button" class="spf-tab-btn active" data-tab="fields">
                    <span class="dashicons dashicons-plus"></span> <?php _e('Add Fields', 'syntekpro-forms'); ?>
                </button>
                <button type="button" class="spf-tab-btn" data-tab="settings">
                    <span class="dashicons dashicons-admin-settings"></span> <?php _e('Form Settings', 'syntekpro-forms'); ?>
                </button>
                <button type="button" class="spf-tab-btn" data-tab="styling">
                    <span class="dashicons dashicons-admin-appearance"></span> <?php _e('Styling', 'syntekpro-forms'); ?>
                </button>
            </div>

            <div class="spf-sidebar-content">
                <div id="spf-tab-fields" class="spf-tab-content active">
                    <div class="spf-field-types">
                        <div class="spf-field-type spf-tooltip" data-type="text" title="<?php _e('Single line text input', 'syntekpro-forms'); ?>">
                            <span class="dashicons dashicons-edit"></span>
                            <?php _e('Text Field', 'syntekpro-forms'); ?>
                        </div>
                        <div class="spf-field-type spf-tooltip" data-type="email" title="<?php _e('Email address with validation', 'syntekpro-forms'); ?>">
                            <span class="dashicons dashicons-email"></span>
                            <?php _e('Email', 'syntekpro-forms'); ?>
                        </div>
                        <div class="spf-field-type spf-tooltip" data-type="textarea" title="<?php _e('Multi-line text input', 'syntekpro-forms'); ?>">
                            <span class="dashicons dashicons-text"></span>
                            <?php _e('Textarea', 'syntekpro-forms'); ?>
                        </div>
                        <div class="spf-field-type spf-tooltip" data-type="number" title="<?php _e('Numeric input field', 'syntekpro-forms'); ?>">
                            <span class="dashicons dashicons-calculator"></span>
                            <?php _e('Number', 'syntekpro-forms'); ?>
                        </div>
                        <div class="spf-field-type spf-tooltip" data-type="select" title="<?php _e('Drop-down selection menu', 'syntekpro-forms'); ?>">
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                            <?php _e('Dropdown', 'syntekpro-forms'); ?>
                        </div>
                        <div class="spf-field-type spf-tooltip" data-type="radio" title="<?php _e('Single choice radio buttons', 'syntekpro-forms'); ?>">
                            <span class="dashicons dashicons-yes"></span>
                            <?php _e('Radio Buttons', 'syntekpro-forms'); ?>
                        </div>
                        <div class="spf-field-type spf-tooltip" data-type="checkbox" title="<?php _e('Multiple choice checkboxes', 'syntekpro-forms'); ?>">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php _e('Checkboxes', 'syntekpro-forms'); ?>
                        </div>
                        <div class="spf-field-type spf-tooltip" data-type="date" title="<?php _e('Date picker field', 'syntekpro-forms'); ?>">
                            <span class="dashicons dashicons-calendar"></span>
                            <?php _e('Date', 'syntekpro-forms'); ?>
                        </div>
                        <div class="spf-field-type spf-tooltip" data-type="file" title="<?php _e('File upload field', 'syntekpro-forms'); ?>">
                            <span class="dashicons dashicons-upload"></span>
                            <?php _e('File Upload', 'syntekpro-forms'); ?>
                        </div>
                        <div class="spf-field-type spf-tooltip" data-type="step" title="<?php _e('Start a new step in the form', 'syntekpro-forms'); ?>">
                            <span class="dashicons dashicons-image-rotate"></span>
                            <?php _e('Step Break', 'syntekpro-forms'); ?>
                        </div>
                    </div>
                </div>

                <div id="spf-tab-settings" class="spf-tab-content">
                    <div class="spf-settings-panel">
                        <div class="spf-setting-row">
                            <label><?php _e('Form Title', 'syntekpro-forms'); ?></label>
                            <input type="text" id="spf-form-title-sidebar" class="spf-sync-title" 
                                   value="<?php echo $form ? esc_attr($form->title) : ''; ?>">
                        </div>
                        <div class="spf-setting-row">
                            <label><?php _e('Form Description', 'syntekpro-forms'); ?></label>
                            <textarea id="spf-form-description-sidebar" class="spf-sync-desc"><?php echo $form ? esc_textarea($form->description) : ''; ?></textarea>
                        </div>
                        <div class="spf-setting-row">
                            <label><?php _e('Submit Button Text', 'syntekpro-forms'); ?></label>
                            <input type="text" id="spf-submit-button-text" 
                                   value="<?php echo isset($settings_array['submit_button_text']) ? esc_attr($settings_array['submit_button_text']) : __('Submit', 'syntekpro-forms'); ?>">
                        </div>
                        <h3><?php _e('Success Handling', 'syntekpro-forms'); ?></h3>
                        <div class="spf-setting-row">
                            <label><?php _e('Success Message', 'syntekpro-forms'); ?></label>
                            <textarea id="spf-success-message"><?php echo isset($settings_array['success_message']) ? esc_textarea($settings_array['success_message']) : __('Thank you! Your form has been submitted successfully.', 'syntekpro-forms'); ?></textarea>
                        </div>
                        <div class="spf-setting-row">
                            <label><?php _e('Success Behavior', 'syntekpro-forms'); ?></label>
                            <select id="spf-success-behavior">
                                <?php $success_behavior = isset($settings_array['success_behavior']) ? $settings_array['success_behavior'] : 'message'; ?>
                                <option value="message" <?php selected($success_behavior, 'message'); ?>><?php _e('Show confirmation message', 'syntekpro-forms'); ?></option>
                                <option value="redirect" <?php selected($success_behavior, 'redirect'); ?>><?php _e('Redirect to URL', 'syntekpro-forms'); ?></option>
                            </select>
                        </div>
                        <div class="spf-setting-row">
                            <label><?php _e('Redirect URL', 'syntekpro-forms'); ?></label>
                            <input type="url" id="spf-success-redirect-url" value="<?php echo isset($settings_array['success_redirect_url']) ? esc_attr($settings_array['success_redirect_url']) : ''; ?>" placeholder="https://example.com/thank-you">
                            <p class="description"><?php _e('Used when success behavior is set to redirect.', 'syntekpro-forms'); ?></p>
                        </div>

                        <h3><?php _e('Admin Notifications', 'syntekpro-forms'); ?></h3>
                        <div class="spf-setting-row">
                            <?php
                            $notifications_enabled = isset($settings_array['notify_enabled'])
                                ? (int) $settings_array['notify_enabled']
                                : (isset($settings_array['notifications_enabled']) ? (int) $settings_array['notifications_enabled'] : 1);
                            ?>
                            <label><input type="checkbox" id="spf-notifications-enabled" <?php checked($notifications_enabled, 1); ?>> <?php _e('Send admin notification emails for this form', 'syntekpro-forms'); ?></label>
                        </div>
                        <div class="spf-setting-row">
                            <label><?php _e('Notification Recipients', 'syntekpro-forms'); ?></label>
                            <input type="text" id="spf-notification-emails" value="<?php echo isset($settings_array['notify_emails']) ? esc_attr($settings_array['notify_emails']) : (isset($settings_array['notification_emails']) ? esc_attr($settings_array['notification_emails']) : ''); ?>" placeholder="admin@example.com, manager@example.com">
                            <p class="description"><?php _e('Comma-separated list. Leave blank to use the global admin email.', 'syntekpro-forms'); ?></p>
                        </div>

                        <h3><?php _e('Submission Limits', 'syntekpro-forms'); ?></h3>
                        <div class="spf-setting-row">
                            <label><?php _e('Maximum Submissions', 'syntekpro-forms'); ?></label>
                            <input type="number" id="spf-submission-limit" min="0" value="<?php echo isset($settings_array['submission_limit']) ? esc_attr($settings_array['submission_limit']) : '0'; ?>">
                            <p class="description"><?php _e('Set to 0 for unlimited submissions.', 'syntekpro-forms'); ?></p>
                        </div>
                        <div class="spf-setting-row">
                            <label><?php _e('Limit Reached Message', 'syntekpro-forms'); ?></label>
                            <textarea id="spf-submission-limit-message"><?php echo isset($settings_array['submission_limit_message']) ? esc_textarea($settings_array['submission_limit_message']) : __('This form is no longer accepting responses.', 'syntekpro-forms'); ?></textarea>
                        </div>

                        <h3><?php _e('Scheduling', 'syntekpro-forms'); ?></h3>
                        <div class="spf-setting-row">
                            <label><?php _e('Start Date & Time', 'syntekpro-forms'); ?></label>
                            <input type="datetime-local" id="spf-schedule-start" value="<?php echo isset($settings_array['schedule_start']) ? esc_attr($settings_array['schedule_start']) : ''; ?>">
                        </div>
                        <div class="spf-setting-row">
                            <label><?php _e('End Date & Time', 'syntekpro-forms'); ?></label>
                            <input type="datetime-local" id="spf-schedule-end" value="<?php echo isset($settings_array['schedule_end']) ? esc_attr($settings_array['schedule_end']) : ''; ?>">
                        </div>
                        <div class="spf-setting-row">
                            <label><?php _e('Not Yet Open Message', 'syntekpro-forms'); ?></label>
                            <textarea id="spf-schedule-not-started-message"><?php echo isset($settings_array['schedule_not_started_message']) ? esc_textarea($settings_array['schedule_not_started_message']) : __('This form is not yet open for submissions.', 'syntekpro-forms'); ?></textarea>
                        </div>
                        <div class="spf-setting-row">
                            <label><?php _e('Closed Message', 'syntekpro-forms'); ?></label>
                            <textarea id="spf-schedule-expired-message"><?php echo isset($settings_array['schedule_expired_message']) ? esc_textarea($settings_array['schedule_expired_message']) : __('This form is no longer accepting submissions.', 'syntekpro-forms'); ?></textarea>
                        </div>

                        <h3><?php _e('Webhooks', 'syntekpro-forms'); ?></h3>
                        <div class="spf-setting-row">
                            <?php $webhook_enabled = isset($settings_array['webhook_enabled']) ? (int)$settings_array['webhook_enabled'] : 0; ?>
                            <label><input type="checkbox" id="spf-webhook-enabled" <?php checked($webhook_enabled, 1); ?>> <?php _e('Send submissions to webhook URLs', 'syntekpro-forms'); ?></label>
                        </div>
                        <div class="spf-setting-row">
                            <label><?php _e('Webhook URLs', 'syntekpro-forms'); ?></label>
                            <textarea id="spf-webhook-urls" placeholder="https://example.com/webhook&#10;https://hooks.zapier.com/..."><?php echo isset($settings_array['webhook_urls']) ? esc_textarea($settings_array['webhook_urls']) : ''; ?></textarea>
                            <p class="description"><?php _e('One URL per line. Payload includes form metadata and submitted fields.', 'syntekpro-forms'); ?></p>
                        </div>
                    </div>
                </div>

                <div id="spf-tab-styling" class="spf-tab-content">
                    <div class="spf-settings-panel">
                        <h3><?php _e('Theme Selection', 'syntekpro-forms'); ?></h3>
                        <div class="spf-setting-row">
                            <label><?php _e('Form Theme', 'syntekpro-forms'); ?></label>
                            <select id="spf-form-theme">
                                <option value="inherit" <?php selected(isset($settings_array['theme']) ? $settings_array['theme'] : 'classic', 'inherit'); ?>><?php _e('Use Site Theme', 'syntekpro-forms'); ?></option>
                                <option value="classic" <?php selected(isset($settings_array['theme']) ? $settings_array['theme'] : 'classic', 'classic'); ?>><?php _e('Classic', 'syntekpro-forms'); ?></option>
                                <option value="modern" <?php selected(isset($settings_array['theme']) ? $settings_array['theme'] : 'classic', 'modern'); ?>><?php _e('Modern', 'syntekpro-forms'); ?></option>
                                <option value="minimal" <?php selected(isset($settings_array['theme']) ? $settings_array['theme'] : 'classic', 'minimal'); ?>><?php _e('Minimal', 'syntekpro-forms'); ?></option>
                                <option value="elegant" <?php selected(isset($settings_array['theme']) ? $settings_array['theme'] : 'classic', 'elegant'); ?>><?php _e('Elegant', 'syntekpro-forms'); ?></option>
                                <option value="contrast" <?php selected(isset($settings_array['theme']) ? $settings_array['theme'] : 'classic', 'contrast'); ?>><?php _e('High Contrast', 'syntekpro-forms'); ?></option>
                                <option value="pastel" <?php selected(isset($settings_array['theme']) ? $settings_array['theme'] : 'classic', 'pastel'); ?>><?php _e('Pastel', 'syntekpro-forms'); ?></option>
                                <option value="outline" <?php selected(isset($settings_array['theme']) ? $settings_array['theme'] : 'classic', 'outline'); ?>><?php _e('Outline', 'syntekpro-forms'); ?></option>
                                <option value="glass" <?php selected(isset($settings_array['theme']) ? $settings_array['theme'] : 'classic', 'glass'); ?>><?php _e('Glassmorphism', 'syntekpro-forms'); ?></option>
                            </select>
                        </div>

                        <h3><?php _e('Typography', 'syntekpro-forms'); ?></h3>
                        <div class="spf-setting-row">
                            <label><?php _e('Font Family', 'syntekpro-forms'); ?></label>
                            <select id="spf-font-family">
                                <option value="inherit" <?php selected(isset($settings_array['font_family']) ? $settings_array['font_family'] : 'inherit', 'inherit'); ?>><?php _e('Default (Inherit)', 'syntekpro-forms'); ?></option>
                                <option value="sans-serif" <?php selected(isset($settings_array['font_family']) ? $settings_array['font_family'] : 'inherit', 'sans-serif'); ?>><?php _e('Sans Serif', 'syntekpro-forms'); ?></option>
                                <option value="serif" <?php selected(isset($settings_array['font_family']) ? $settings_array['font_family'] : 'inherit', 'serif'); ?>><?php _e('Serif', 'syntekpro-forms'); ?></option>
                                <option value="monospace" <?php selected(isset($settings_array['font_family']) ? $settings_array['font_family'] : 'inherit', 'monospace'); ?>><?php _e('Monospace', 'syntekpro-forms'); ?></option>
                            </select>
                        </div>
                        <div class="spf-setting-row">
                            <label><?php _e('Base Font Size (px)', 'syntekpro-forms'); ?></label>
                            <input type="number" id="spf-font-size" value="<?php echo isset($settings_array['font_size']) ? esc_attr($settings_array['font_size']) : '16'; ?>" min="10" max="30">
                        </div>

                        <h3><?php _e('Spacing & Layout', 'syntekpro-forms'); ?></h3>
                        <div class="spf-setting-row">
                            <label><?php _e('Field Padding (px)', 'syntekpro-forms'); ?></label>
                            <input type="number" id="spf-field-padding" value="<?php echo isset($settings_array['field_padding']) ? esc_attr($settings_array['field_padding']) : '12'; ?>" min="0" max="30">
                        </div>
                        <div class="spf-setting-row">
                            <label><?php _e('Field Border Radius (px)', 'syntekpro-forms'); ?></label>
                            <input type="number" id="spf-border-radius" value="<?php echo isset($settings_array['border_radius']) ? esc_attr($settings_array['border_radius']) : '4'; ?>" min="0" max="20">
                        </div>

                        <h3><?php _e('Colors', 'syntekpro-forms'); ?></h3>
                        <div class="spf-setting-row">
                            <label><?php _e('Primary Color', 'syntekpro-forms'); ?></label>
                            <input type="color" id="spf-primary-color" value="<?php echo isset($settings_array['primary_color']) ? esc_attr($settings_array['primary_color']) : '#0073aa'; ?>">
                        </div>
                        <div class="spf-setting-row">
                            <label><?php _e('Label Color', 'syntekpro-forms'); ?></label>
                            <input type="color" id="spf-label-color" value="<?php echo isset($settings_array['label_color']) ? esc_attr($settings_array['label_color']) : '#1d2327'; ?>">
                        </div>
                        <div class="spf-setting-row">
                            <label><?php _e('Form Background Color', 'syntekpro-forms'); ?></label>
                            <input type="color" id="spf-bg-color" value="<?php echo isset($settings_array['bg_color']) ? esc_attr($settings_array['bg_color']) : '#ffffff'; ?>">
                        </div>

                        <h3><?php _e('Submit Button', 'syntekpro-forms'); ?></h3>
                        <div class="spf-setting-row">
                            <label><?php _e('Button Alignment', 'syntekpro-forms'); ?></label>
                            <select id="spf-submit-align">
                                <option value="left" <?php selected(isset($settings_array['submit_align']) ? $settings_array['submit_align'] : 'left', 'left'); ?>><?php _e('Left', 'syntekpro-forms'); ?></option>
                                <option value="center" <?php selected(isset($settings_array['submit_align']) ? $settings_array['submit_align'] : 'left', 'center'); ?>><?php _e('Center', 'syntekpro-forms'); ?></option>
                                <option value="right" <?php selected(isset($settings_array['submit_align']) ? $settings_array['submit_align'] : 'left', 'right'); ?>><?php _e('Right', 'syntekpro-forms'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content - Form Builder -->
        <div class="spf-main-content">
            <!-- Form Header Preview -->
            <div class="spf-form-header-preview">
                <div class="spf-header-badge"><?php _e('Form Canvas', 'syntekpro-forms'); ?></div>
                <input type="text" id="spf-form-title" class="spf-form-title-input spf-sync-title" 
                       placeholder="<?php _e('Enter Form Title Here', 'syntekpro-forms'); ?>" 
                       value="<?php echo $form ? esc_attr($form->title) : ''; ?>">
                <textarea id="spf-form-description" class="spf-form-description-input spf-sync-desc" 
                          placeholder="<?php _e('Add a description for your form...', 'syntekpro-forms'); ?>"><?php echo $form ? esc_textarea($form->description) : ''; ?></textarea>
            </div>
            
            <!-- Live Preview Styles -->
            <style id="spf-live-preview-styles"></style>

            <!-- Form Fields Canvas -->
            <div class="spf-form-fields-canvas" id="spf-form-fields">
                <div class="spf-empty-builder">
                    <div class="spf-empty-icon">
                        <span class="dashicons dashicons-plus-alt"></span>
                    </div>
                    <h3><?php _e('Build Your Form', 'syntekpro-forms'); ?></h3>
                    <p><?php _e('Drag and drop fields from the sidebar or click on them to start building.', 'syntekpro-forms'); ?></p>
                </div>
            </div>

            <!-- Submit Button Preview Area -->
            <div class="spf-form-fields-canvas" style="border-top: none; min-height: auto; padding-top: 0;">
                <div class="spf-form-footer">
                    <button type="button" class="spf-submit-button" id="spf-submit-button-preview">
                        <?php echo isset($settings_array['submit_button_text']) ? esc_html($settings_array['submit_button_text']) : __('Submit', 'syntekpro-forms'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Right Sidebar - Field Settings -->
        <div class="spf-field-settings" id="spf-field-settings">
            <div class="spf-field-settings-header">
                <h3><span class="dashicons dashicons-admin-tools"></span> <?php _e('Field Settings', 'syntekpro-forms'); ?></h3>
            </div>
            <div class="spf-field-settings-content">
                <div class="spf-no-field-selected">
                    <span class="dashicons dashicons-info"></span>
                    <p><?php _e('Select a field on the canvas to edit its settings.', 'syntekpro-forms'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="spf-admin-footer" style="background:#f8ebb4;border:1px solid #ccd0d4;border-radius:4px;padding:10px 20px;margin-top:20px;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 1px rgba(0,0,0,0.04);">
    <a class="spf-footer-brand" href="https://syntekpro.com" target="_blank" rel="noopener noreferrer">
        <span><?php _e('Powered by', 'syntekpro-forms'); ?></span>
        <img src="<?php echo SPF_PLUGIN_URL; ?>assets/images/SYNTEK%20PRO%20LOGO%20Transparent%20Icon%20500x150.png" class="spf-footer-icon" alt="SyntekPro" style="height:32px !important;width:32px !important;max-height:32px !important;max-width:32px !important;object-fit:contain;display:inline-block;vertical-align:middle;">
    </a>
</div>

<input type="hidden" id="spf-form-id" value="<?php echo $form_id; ?>">

<?php if ($form || isset($form_data_dummy)): ?>
<script>
var spfFormData = {
    fields: <?php echo (!empty($form) && !empty($form->fields)) ? $form->fields : '[]'; ?>,
    settings: <?php echo (!empty($form) && !empty($form->settings)) ? $form->settings : '{}'; ?>
};
</script>
<?php endif; ?>

<style>
.spf-admin-header h1 .dashicons {
    margin-right: 10px;
    color: #2271b1;
}

.spf-header-badge {
    display: inline-block;
    background: #e7f5fe;
    color: #2271b1;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 15px;
}

.spf-sidebar-tabs .dashicons {
    font-size: 18px;
}

.spf-field-type {
    border: 1px solid #e2e4e7;
    background: #fff;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.spf-field-type:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.spf-field-item {
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    border-radius: 6px;
    border: 1px solid #ccd0d4;
}

.spf-field-item.active {
    border-color: #2271b1;
    box-shadow: 0 0 0 1px #2271b1, 0 4px 12px rgba(34, 113, 177, 0.1);
    border-left: 4px solid #2271b1;
}

.spf-field-header {
    background: #fcfcfc;
    margin: -20px -20px 20px -20px;
    padding: 12px 20px;
    border-radius: 6px 6px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.spf-field-actions .button {
    border-radius: 4px;
    box-shadow: none;
}

.spf-field-actions .spf-delete-field:hover {
    color: #d63638;
    border-color: #d63638;
}

.spf-empty-icon .dashicons {
    font-size: 60px;
    width: 60px;
    height: 60px;
    color: #ccd0d4;
}

.spf-field-settings-header {
    background: #f6f7f7;
    margin: -20px -20px 20px -20px;
    padding: 15px 20px;
    border-bottom: 1px solid #ccd0d4;
}

.spf-field-settings-header h3 {
    margin: 0;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.spf-no-field-selected {
    text-align: center;
    padding: 40px 20px;
    color: #646970;
}

.spf-no-field-selected .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
    margin-bottom: 10px;
    opacity: 0.5;
}

/* Tooltip style */
.spf-tooltip {
    position: relative;
}

/* Custom Notification Styling */
.spf-notification {
    border-radius: 4px;
    border-left-width: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.spf-admin-header-right {
    display: flex;
    gap: 10px;
}

.spf-builder-container {
    display: flex;
    gap: 20px;
    align-items: flex-start;
}

/* Sidebar Styling */
.spf-sidebar {
    width: 280px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
    overflow: hidden;
}

.spf-sidebar-tabs {
    display: flex;
    background: #f6f7f7;
    border-bottom: 1px solid #ccd0d4;
}

.spf-tab-btn {
    flex: 1;
    padding: 12px;
    border: none;
    background: none;
    cursor: pointer;
    font-weight: 500;
    color: #50575e;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.2s;
}

.spf-tab-btn:hover {
    background: #f0f0f1;
}

.spf-tab-btn.active {
    background: #fff;
    color: #2271b1;
    border-bottom: 2px solid #2271b1;
}

.spf-sidebar-content {
    padding: 20px;
    max-height: 70vh;
    overflow-y: auto;
}

.spf-tab-content {
    display: none;
}

.spf-tab-content.active {
    display: block;
}

.spf-field-types {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.spf-field-type {
    padding: 12px 10px;
    background: #f6f7f7;
    border: 1px solid #dcdcde;
    border-radius: 4px;
    cursor: move;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    text-align: center;
    transition: all 0.2s;
}

.spf-field-type:hover {
    background: #2271b1;
    color: #fff;
    border-color: #2271b1;
}

.spf-field-type .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
}

/* Main Content Styling */
.spf-main-content {
    flex: 1;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
    padding: 0;
    overflow: hidden;
}

.spf-form-header-preview {
    padding: 30px;
    background: #fcfcfc;
    border-bottom: 1px solid #f0f0f1;
}

.spf-form-title-input {
    width: 100%;
    font-size: 28px;
    font-weight: 700;
    border: 1px solid transparent;
    background: transparent;
    padding: 5px 10px;
    margin-bottom: 10px;
    transition: all 0.2s;
}

.spf-form-title-input:hover, .spf-form-title-input:focus {
    background: #fff;
    border-color: #dcdcde;
}

.spf-form-description-input {
    width: 100%;
    font-size: 15px;
    color: #50575e;
    border: 1px solid transparent;
    background: transparent;
    padding: 5px 10px;
    resize: none;
    min-height: 60px;
}

.spf-form-description-input:hover, .spf-form-description-input:focus {
    background: #fff;
    border-color: #dcdcde;
}

.spf-form-fields-canvas {
    padding: 30px;
    min-height: 500px;
    background: #f0f0f1;
}

.spf-empty-builder {
    text-align: center;
    padding: 80px 40px;
    background: #fff;
    border: 2px dashed #ccd0d4;
    border-radius: 8px;
}

.spf-empty-icon {
    font-size: 50px;
    color: #ccd0d4;
    margin-bottom: 15px;
}

/* Field Item Styling */
.spf-field-item {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-bottom: 15px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    transition: all 0.2s;
}

.spf-field-item:hover {
    border-color: #2271b1;
}

.spf-field-item.active {
    border-left: 4px solid #2271b1;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

/* Field Settings Styling */
.spf-field-settings {
    width: 320px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.spf-field-settings-header {
    padding: 15px 20px;
    border-bottom: 1px solid #f0f0f1;
    background: #f6f7f7;
}

.spf-field-settings-header h3 {
    margin: 0;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.spf-field-settings-content {
    padding: 20px;
}

.spf-no-field-selected {
    text-align: center;
    color: #787c82;
    padding: 40px 10px;
}

.spf-no-field-selected .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
    margin-bottom: 10px;
    opacity: 0.5;
}

@media (max-width: 1200px) {
    .spf-builder-container {
        flex-direction: column;
    }
    .spf-sidebar, .spf-field-settings {
        width: 100%;
    }
}
</style>