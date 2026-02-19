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

    <!-- New Top Navigation Bar -->
    <div class="spf-builder-top-nav">
        <div class="spf-nav-left">
            <!-- Recent Forms Dropdown -->
            <div class="spf-nav-recent-forms">
                <button type="button" class="spf-nav-btn" id="spf-recent-forms-toggle">
                    <span class="dashicons dashicons-clock"></span> <?php _e('Recent Forms', 'syntekpro-forms'); ?>
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </button>
                <div class="spf-dropdown-menu" id="spf-recent-forms-dropdown" style="display: none;">
                    <div class="spf-dropdown-search">
                        <input type="text" id="spf-forms-search" placeholder="<?php _e('Search forms...', 'syntekpro-forms'); ?>">
                    </div>
                    <div class="spf-dropdown-list" id="spf-forms-list">
                        <div class="spf-loading"><?php _e('Loading forms...', 'syntekpro-forms'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Settings Button -->
            <a href="<?php echo admin_url('admin.php?page=syntekpro-forms-settings'); ?>" class="spf-nav-btn">
                <span class="dashicons dashicons-admin-settings"></span> <?php _e('Settings', 'syntekpro-forms'); ?>
            </a>

            <!-- Entries Button -->
            <a href="<?php echo admin_url('admin.php?page=syntekpro-forms-entries'); ?>" class="spf-nav-btn">
                <span class="dashicons dashicons-list-view"></span> <?php _e('Entries', 'syntekpro-forms'); ?>
            </a>
        </div>

        <div class="spf-nav-right">
            <!-- Save Form -->
            <button type="button" id="spf-save-form" class="button button-primary button-large spf-tooltip" title="<?php _e('Save all changes to this form', 'syntekpro-forms'); ?>">
                <span class="dashicons dashicons-saved"></span> <?php _e('Save Form', 'syntekpro-forms'); ?>
            </button>

            <!-- Preview Button -->
            <button type="button" id="spf-preview-form" class="button button-large spf-tooltip" title="<?php _e('View a live preview of this form', 'syntekpro-forms'); ?>">
                <span class="dashicons dashicons-visibility"></span> <?php _e('Preview', 'syntekpro-forms'); ?>
            </button>

            <!-- Embed Button -->
            <button type="button" id="spf-embed-form-btn" class="button button-large spf-tooltip" title="<?php _e('Get embed code for this form', 'syntekpro-forms'); ?>">
                <span class="dashicons dashicons-media-code"></span> <?php _e('Embed', 'syntekpro-forms'); ?>
            </button>

            <!-- Editor Preferences Settings Icon -->
            <button type="button" id="spf-editor-preferences-btn" class="spf-icon-btn spf-tooltip" title="<?php _e('Editor preferences', 'syntekpro-forms'); ?>">
                <span class="dashicons dashicons-admin-generic"></span>
            </button>
        </div>
    </div>

    <div class="spf-builder-ux-toolbar">
        <div class="spf-builder-ux-left">
            <div class="spf-builder-device-group">
                <button type="button" class="button spf-device-btn is-active" data-device="desktop"><span class="dashicons dashicons-desktop"></span> <?php _e('Desktop', 'syntekpro-forms'); ?></button>
                <button type="button" class="button spf-device-btn" data-device="tablet"><span class="dashicons dashicons-tablet"></span> <?php _e('Tablet', 'syntekpro-forms'); ?></button>
                <button type="button" class="button spf-device-btn" data-device="mobile"><span class="dashicons dashicons-smartphone"></span> <?php _e('Mobile', 'syntekpro-forms'); ?></button>
            </div>
            <div class="spf-builder-zoom-group">
                <button type="button" class="button spf-zoom-btn" id="spf-zoom-out"><span class="dashicons dashicons-minus"></span></button>
                <span id="spf-zoom-level">100%</span>
                <button type="button" class="button spf-zoom-btn" id="spf-zoom-in"><span class="dashicons dashicons-plus"></span></button>
                <button type="button" class="button" id="spf-zoom-fit"><?php _e('Fit', 'syntekpro-forms'); ?></button>
                <button type="button" class="button" id="spf-builder-fullscreen"><span class="dashicons dashicons-editor-expand"></span> <?php _e('Fullscreen', 'syntekpro-forms'); ?></button>
            </div>
        </div>
        <div class="spf-builder-ux-right">
            <button type="button" class="button" id="spf-undo-btn"><span class="dashicons dashicons-undo"></span> <?php _e('Undo', 'syntekpro-forms'); ?></button>
            <button type="button" class="button" id="spf-redo-btn"><span class="dashicons dashicons-redo"></span> <?php _e('Redo', 'syntekpro-forms'); ?></button>
        </div>
    </div>

    <div id="spf-form-health" class="spf-form-health-strip" aria-live="polite"></div>

    <div id="spf-bulk-actions-bar" class="spf-bulk-actions-bar" style="display:none;">
        <span id="spf-selected-count">0 <?php _e('selected', 'syntekpro-forms'); ?></span>
        <button type="button" class="button spf-bulk-action" data-action="required_on"><?php _e('Set Required', 'syntekpro-forms'); ?></button>
        <button type="button" class="button spf-bulk-action" data-action="required_off"><?php _e('Unset Required', 'syntekpro-forms'); ?></button>
        <button type="button" class="button spf-bulk-action" data-action="width_half"><?php _e('Width 1/2', 'syntekpro-forms'); ?></button>
        <button type="button" class="button spf-bulk-action" data-action="width_full"><?php _e('Width Full', 'syntekpro-forms'); ?></button>
        <button type="button" class="button spf-bulk-action" data-action="duplicate"><?php _e('Duplicate', 'syntekpro-forms'); ?></button>
        <button type="button" class="button spf-bulk-action" data-action="delete"><?php _e('Delete', 'syntekpro-forms'); ?></button>
    </div>
    
    <div class="spf-builder-container">
        <!-- Main Content - Form Canvas (LEFT) -->
        <div class="spf-main-content" id="spf-main-content">
            <!-- Form Canvas Badge -->
            <div class="spf-canvas-badge-wrapper">
                <div class="spf-header-badge"><?php _e('Form Canvas', 'syntekpro-forms'); ?></div>
            </div>
            <div id="spf-canvas-stage" class="spf-canvas-stage">
            
            <!-- Form Header Preview -->
            <div class="spf-form-header-preview">
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
                    <div class="spf-template-quickstart">
                        <button type="button" class="button spf-template-btn" data-template="contact"><?php _e('Contact', 'syntekpro-forms'); ?></button>
                        <button type="button" class="button spf-template-btn" data-template="lead"><?php _e('Lead', 'syntekpro-forms'); ?></button>
                        <button type="button" class="button spf-template-btn" data-template="support"><?php _e('Support', 'syntekpro-forms'); ?></button>
                        <button type="button" class="button spf-template-btn" data-template="event"><?php _e('Event', 'syntekpro-forms'); ?></button>
                    </div>
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
        </div>

        <!-- Right Sidebar - Tabs (Add Fields | Form Settings | Styling | Field Settings) -->
        <div class="spf-sidebar spf-sidebar-right">
            <!-- Field Settings Window (Moved inside to push content) -->
            <div id="spf-field-settings-window" class="spf-field-settings-window" style="display: none;">
                <div class="spf-field-settings-window-header">
                    <h3><?php _e('Field Settings', 'syntekpro-forms'); ?></h3>
                    <button type="button" class="spf-field-settings-close" aria-label="<?php esc_attr_e('Close', 'syntekpro-forms'); ?>">&times;</button>
                </div>
                <div class="spf-field-settings-window-body" id="spf-field-settings">
                    <div class="spf-no-field-selected">
                        <p><?php _e('Select a field on the canvas to edit its settings.', 'syntekpro-forms'); ?></p>
                    </div>
                </div>
            </div>

            <div class="spf-sidebar-tabs">
                <button type="button" class="spf-tab-btn active" data-tab="fields">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <span class="spf-tab-label"><?php _e('Add Fields', 'syntekpro-forms'); ?></span>
                </button>
                <button type="button" class="spf-tab-btn" data-tab="settings">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <span class="spf-tab-label"><?php _e('Form Settings', 'syntekpro-forms'); ?></span>
                </button>
                <button type="button" class="spf-tab-btn" data-tab="styling">
                    <span class="dashicons dashicons-admin-customizer"></span>
                    <span class="spf-tab-label"><?php _e('Styling', 'syntekpro-forms'); ?></span>
                </button>
            </div>

            <div class="spf-sidebar-content">
                <div id="spf-tab-fields" class="spf-tab-content active">
                    
                    <!-- Drag Hint -->
                    <div class="spf-drag-hint" style="padding: 12px 15px; background: #f0f6ff; border: 1px solid #bfdbfe; border-radius: 6px; margin-bottom: 15px; font-size: 12px; color: #1e40af; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-move" style="font-size: 16px;"></span>
                        <span><?php _e('Drag fields to the form canvas or click to add', 'syntekpro-forms'); ?></span>
                    </div>
                    
                    <!-- Standard Fields Section -->
                    <div class="spf-field-section">
                        <h3 class="spf-field-section-header" data-section="standard">
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                            <?php _e('Standard Fields', 'syntekpro-forms'); ?>
                        </h3>
                        <div class="spf-field-types spf-field-grid" data-section-content="standard">
                            <div class="spf-field-type" data-type="text" title="<?php _e('Single line text input', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-edit"></span>
                                <span class="spf-field-label"><?php _e('Single Line Text', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="textarea" title="<?php _e('Multi-line text input', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-text"></span>
                                <span class="spf-field-label"><?php _e('Paragraph Text', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="select" title="<?php _e('Drop-down selection menu', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                                <span class="spf-field-label"><?php _e('Drop Down', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="number" title="<?php _e('Numeric input field', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-calculator"></span>
                                <span class="spf-field-label"><?php _e('Number', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="checkbox" title="<?php _e('Multiple choice checkboxes', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <span class="spf-field-label"><?php _e('Checkboxes', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="radio" title="<?php _e('Single choice radio buttons', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-yes"></span>
                                <span class="spf-field-label"><?php _e('Radio Buttons', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="hidden" title="<?php _e('Hidden field for passing data', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-hidden"></span>
                                <span class="spf-field-label"><?php _e('Hidden', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="html" title="<?php _e('Custom HTML content block', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-editor-code"></span>
                                <span class="spf-field-label"><?php _e('HTML', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="section" title="<?php _e('Section divider with heading', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-minus"></span>
                                <span class="spf-field-label"><?php _e('Section', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="page" title="<?php _e('Multi-page form break', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-admin-page"></span>
                                <span class="spf-field-label"><?php _e('Page Break', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="multiple-choice" title="<?php _e('Multiple choice selection', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-format-aside"></span>
                                <span class="spf-field-label"><?php _e('Multiple Choice', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="image-choice" title="<?php _e('Image-based choice selection', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-format-image"></span>
                                <span class="spf-field-label"><?php _e('Image Choice', 'syntekpro-forms'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Fields Section -->
                    <div class="spf-field-section">
                        <h3 class="spf-field-section-header" data-section="advanced">
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                            <?php _e('Advanced Fields', 'syntekpro-forms'); ?>
                        </h3>
                        <div class="spf-field-types spf-field-grid" data-section-content="advanced">
                            <div class="spf-field-type" data-type="name" title="<?php _e('Name field with first/last name', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-admin-users"></span>
                                <span class="spf-field-label"><?php _e('Name', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="date" title="<?php _e('Date picker field', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-calendar"></span>
                                <span class="spf-field-label"><?php _e('Date', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="time" title="<?php _e('Time picker field', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-clock"></span>
                                <span class="spf-field-label"><?php _e('Time', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="phone" title="<?php _e('Phone number with formatting', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-phone"></span>
                                <span class="spf-field-label"><?php _e('Phone', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="address" title="<?php _e('Full address with street, city, state, zip', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-location"></span>
                                <span class="spf-field-label"><?php _e('Address', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="website" title="<?php _e('Website URL with validation', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-admin-site"></span>
                                <span class="spf-field-label"><?php _e('Website', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="email" title="<?php _e('Email address with validation', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-email"></span>
                                <span class="spf-field-label"><?php _e('Email', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="file" title="<?php _e('File upload field', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-upload"></span>
                                <span class="spf-field-label"><?php _e('File Upload', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="captcha" title="<?php _e('CAPTCHA verification', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-shield"></span>
                                <span class="spf-field-label"><?php _e('CAPTCHA', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="list" title="<?php _e('Dynamic list with add/remove rows', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-list-view"></span>
                                <span class="spf-field-label"><?php _e('List', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="multi-select" title="<?php _e('Multi-select dropdown', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-menu"></span>
                                <span class="spf-field-label"><?php _e('Multi Select', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="consent" title="<?php _e('Consent checkbox with custom text', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-privacy"></span>
                                <span class="spf-field-label"><?php _e('Consent', 'syntekpro-forms'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Post Fields Section -->
                    <div class="spf-field-section">
                        <h3 class="spf-field-section-header" data-section="post">
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                            <?php _e('Post Fields', 'syntekpro-forms'); ?>
                        </h3>
                        <div class="spf-field-types spf-field-grid" data-section-content="post">
                            <div class="spf-field-type" data-type="post-title" title="<?php _e('Post title field', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-edit-large"></span>
                                <span class="spf-field-label"><?php _e('Post Title', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="post-body" title="<?php _e('Post content editor', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-media-document"></span>
                                <span class="spf-field-label"><?php _e('Post Body', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="post-excerpt" title="<?php _e('Post excerpt/summary', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-welcome-write-blog"></span>
                                <span class="spf-field-label"><?php _e('Post Excerpt', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="post-tags" title="<?php _e('Post tags field', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-tag"></span>
                                <span class="spf-field-label"><?php _e('Post Tags', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="post-category" title="<?php _e('Post category selection', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-category"></span>
                                <span class="spf-field-label"><?php _e('Post Category', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="post-image" title="<?php _e('Featured image upload', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-format-image"></span>
                                <span class="spf-field-label"><?php _e('Post Image', 'syntekpro-forms'); ?></span>
                            </div>
                            <div class="spf-field-type" data-type="post-custom-field" title="<?php _e('Custom field/meta data', 'syntekpro-forms'); ?>">
                                <span class="dashicons dashicons-admin-generic"></span>
                                <span class="spf-field-label"><?php _e('Custom Field', 'syntekpro-forms'); ?></span>
                            </div>
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
                        <div class="spf-panel-collapsible-content">
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
                        </div>

                        <h3><?php _e('Admin Notifications', 'syntekpro-forms'); ?></h3>
                        <div class="spf-panel-collapsible-content">
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
                        </div>

                        <h3><?php _e('Submission Limits', 'syntekpro-forms'); ?></h3>
                        <div class="spf-panel-collapsible-content">
                            <div class="spf-setting-row">
                                <label><?php _e('Maximum Submissions', 'syntekpro-forms'); ?></label>
                                <input type="number" id="spf-submission-limit" min="0" value="<?php echo isset($settings_array['submission_limit']) ? esc_attr($settings_array['submission_limit']) : '0'; ?>">
                                <p class="description"><?php _e('Set to 0 for unlimited submissions.', 'syntekpro-forms'); ?></p>
                            </div>
                            <div class="spf-setting-row">
                                <label><?php _e('Limit Reached Message', 'syntekpro-forms'); ?></label>
                                <textarea id="spf-submission-limit-message"><?php echo isset($settings_array['submission_limit_message']) ? esc_textarea($settings_array['submission_limit_message']) : __('This form is no longer accepting responses.', 'syntekpro-forms'); ?></textarea>
                            </div>
                        </div>

                        <h3><?php _e('Scheduling', 'syntekpro-forms'); ?></h3>
                        <div class="spf-panel-collapsible-content">
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
                        </div>

                        <h3><?php _e('Webhooks', 'syntekpro-forms'); ?></h3>
                        <div class="spf-panel-collapsible-content">
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
                </div>

                <div id="spf-tab-styling" class="spf-tab-content">
                    <div class="spf-settings-panel">
                        <h3><?php _e('Theme Selection', 'syntekpro-forms'); ?></h3>
                        <div class="spf-panel-collapsible-content">
                            <div class="spf-setting-row">
                                <label><span class="dashicons dashicons-admin-appearance"></span><?php _e('Form Theme', 'syntekpro-forms'); ?></label>
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
                        </div>

                        <h3><?php _e('Typography', 'syntekpro-forms'); ?></h3>
                        <div class="spf-panel-collapsible-content">
                            <div class="spf-setting-row">
                                <label><span class="dashicons dashicons-editor-textcolor"></span><?php _e('Font Family', 'syntekpro-forms'); ?></label>
                                <select id="spf-font-family">
                                    <option value="inherit" <?php selected(isset($settings_array['font_family']) ? $settings_array['font_family'] : 'inherit', 'inherit'); ?>><?php _e('Default (Inherit)', 'syntekpro-forms'); ?></option>
                                    <option value="sans-serif" <?php selected(isset($settings_array['font_family']) ? $settings_array['font_family'] : 'inherit', 'sans-serif'); ?>><?php _e('Sans Serif', 'syntekpro-forms'); ?></option>
                                    <option value="serif" <?php selected(isset($settings_array['font_family']) ? $settings_array['font_family'] : 'inherit', 'serif'); ?>><?php _e('Serif', 'syntekpro-forms'); ?></option>
                                    <option value="monospace" <?php selected(isset($settings_array['font_family']) ? $settings_array['font_family'] : 'inherit', 'monospace'); ?>><?php _e('Monospace', 'syntekpro-forms'); ?></option>
                                    <option value="inter" <?php selected(isset($settings_array['font_family']) ? $settings_array['font_family'] : 'inherit', 'inter'); ?>>Inter (Google)</option>
                                    <option value="roboto" <?php selected(isset($settings_array['font_family']) ? $settings_array['font_family'] : 'inherit', 'roboto'); ?>>Roboto (Google)</option>
                                    <option value="open-sans" <?php selected(isset($settings_array['font_family']) ? $settings_array['font_family'] : 'inherit', 'open-sans'); ?>>Open Sans (Google)</option>
                                    <option value="lato" <?php selected(isset($settings_array['font_family']) ? $settings_array['font_family'] : 'inherit', 'lato'); ?>>Lato (Google)</option>
                                    <option value="montserrat" <?php selected(isset($settings_array['font_family']) ? $settings_array['font_family'] : 'inherit', 'montserrat'); ?>>Montserrat (Google)</option>
                                    <option value="poppins" <?php selected(isset($settings_array['font_family']) ? $settings_array['font_family'] : 'inherit', 'poppins'); ?>>Poppins (Google)</option>
                                    <option value="nunito" <?php selected(isset($settings_array['font_family']) ? $settings_array['font_family'] : 'inherit', 'nunito'); ?>>Nunito (Google)</option>
                                    <option value="source-sans-pro" <?php selected(isset($settings_array['font_family']) ? $settings_array['font_family'] : 'inherit', 'source-sans-pro'); ?>>Source Sans Pro (Google)</option>
                                    <option value="work-sans" <?php selected(isset($settings_array['font_family']) ? $settings_array['font_family'] : 'inherit', 'work-sans'); ?>>Work Sans (Google)</option>
                                    <option value="merriweather" <?php selected(isset($settings_array['font_family']) ? $settings_array['font_family'] : 'inherit', 'merriweather'); ?>>Merriweather (Google)</option>
                                    <option value="playfair-display" <?php selected(isset($settings_array['font_family']) ? $settings_array['font_family'] : 'inherit', 'playfair-display'); ?>>Playfair Display (Google)</option>
                                </select>
                            </div>
                            <div class="spf-setting-row">
                                <label><span class="dashicons dashicons-editor-expand"></span><?php _e('Base Font Size (px)', 'syntekpro-forms'); ?></label>
                                <input type="number" id="spf-font-size" value="<?php echo isset($settings_array['font_size']) ? esc_attr($settings_array['font_size']) : '16'; ?>" min="10" max="30">
                            </div>
                            <div class="spf-setting-row">
                                <label><span class="dashicons dashicons-editor-alignleft"></span><?php _e('Form Title Alignment', 'syntekpro-forms'); ?></label>
                                <select id="spf-title-align">
                                    <option value="left" <?php selected(isset($settings_array['title_align']) ? $settings_array['title_align'] : 'left', 'left'); ?>><?php _e('Left', 'syntekpro-forms'); ?></option>
                                    <option value="center" <?php selected(isset($settings_array['title_align']) ? $settings_array['title_align'] : 'left', 'center'); ?>><?php _e('Center', 'syntekpro-forms'); ?></option>
                                    <option value="right" <?php selected(isset($settings_array['title_align']) ? $settings_array['title_align'] : 'left', 'right'); ?>><?php _e('Right', 'syntekpro-forms'); ?></option>
                                </select>
                            </div>
                            <div class="spf-setting-row">
                                <label><span class="dashicons dashicons-editor-alignleft"></span><?php _e('Form Description Alignment', 'syntekpro-forms'); ?></label>
                                <select id="spf-description-align">
                                    <option value="left" <?php selected(isset($settings_array['description_align']) ? $settings_array['description_align'] : 'left', 'left'); ?>><?php _e('Left', 'syntekpro-forms'); ?></option>
                                    <option value="center" <?php selected(isset($settings_array['description_align']) ? $settings_array['description_align'] : 'left', 'center'); ?>><?php _e('Center', 'syntekpro-forms'); ?></option>
                                    <option value="right" <?php selected(isset($settings_array['description_align']) ? $settings_array['description_align'] : 'left', 'right'); ?>><?php _e('Right', 'syntekpro-forms'); ?></option>
                                </select>
                            </div>
                            <div class="spf-setting-row">
                                <label><span class="dashicons dashicons-editor-alignleft"></span><?php _e('Field Label Alignment', 'syntekpro-forms'); ?></label>
                                <select id="spf-label-align">
                                    <option value="left" <?php selected(isset($settings_array['label_align']) ? $settings_array['label_align'] : 'left', 'left'); ?>><?php _e('Left', 'syntekpro-forms'); ?></option>
                                    <option value="center" <?php selected(isset($settings_array['label_align']) ? $settings_array['label_align'] : 'left', 'center'); ?>><?php _e('Center', 'syntekpro-forms'); ?></option>
                                    <option value="right" <?php selected(isset($settings_array['label_align']) ? $settings_array['label_align'] : 'left', 'right'); ?>><?php _e('Right', 'syntekpro-forms'); ?></option>
                                </select>
                            </div>
                        </div>

                        <h3><?php _e('Spacing & Layout', 'syntekpro-forms'); ?></h3>
                        <div class="spf-panel-collapsible-content">
                            <div class="spf-setting-row">
                                <label><span class="dashicons dashicons-align-center"></span><?php _e('Field Padding (px)', 'syntekpro-forms'); ?></label>
                                <input type="number" id="spf-field-padding" value="<?php echo isset($settings_array['field_padding']) ? esc_attr($settings_array['field_padding']) : '14'; ?>" min="0" max="40">
                            </div>
                            <div class="spf-setting-row">
                                <label><span class="dashicons dashicons-marker"></span><?php _e('Field Border Radius (px)', 'syntekpro-forms'); ?></label>
                                <input type="number" id="spf-border-radius" value="<?php echo isset($settings_array['border_radius']) ? esc_attr($settings_array['border_radius']) : '6'; ?>" min="0" max="30">
                            </div>
                        </div>

                        <h3><?php _e('Colors', 'syntekpro-forms'); ?></h3>
                        <div class="spf-panel-collapsible-content">
                            <div class="spf-setting-row">
                                <label><span class="dashicons dashicons-art"></span><?php _e('Primary Color', 'syntekpro-forms'); ?></label>
                                <input type="color" id="spf-primary-color" value="<?php echo isset($settings_array['primary_color']) ? esc_attr($settings_array['primary_color']) : '#0073aa'; ?>">
                            </div>
                            <div class="spf-setting-row">
                                <label><span class="dashicons dashicons-tag"></span><?php _e('Label Color', 'syntekpro-forms'); ?></label>
                                <input type="color" id="spf-label-color" value="<?php echo isset($settings_array['label_color']) ? esc_attr($settings_array['label_color']) : '#1d2327'; ?>">
                            </div>
                            <div class="spf-setting-row">
                                <label><span class="dashicons dashicons-admin-customizer"></span><?php _e('Form Background Color', 'syntekpro-forms'); ?></label>
                                <input type="color" id="spf-bg-color" value="<?php echo isset($settings_array['bg_color']) ? esc_attr($settings_array['bg_color']) : '#ffffff'; ?>">
                            </div>
                        </div>

                        <h3><?php _e('Submit Button', 'syntekpro-forms'); ?></h3>
                        <div class="spf-panel-collapsible-content">
                            <div class="spf-setting-row">
                                <label><span class="dashicons dashicons-align-pull-left"></span><?php _e('Button Alignment', 'syntekpro-forms'); ?></label>
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
        </div>

    <!-- Embed Form Modal -->
    <div id="spf-embed-modal" class="spf-modal" style="display: none;">
        <div class="spf-modal-content">
            <button type="button" class="spf-modal-close" aria-label="Close">
                <span class="dashicons dashicons-no"></span>
            </button>
            <h2><?php _e('Embed Form', 'syntekpro-forms'); ?></h2>
            
            <div class="spf-embed-tabs">
                <button type="button" class="spf-embed-tab-btn active" data-tab="shortcode">
                    <?php _e('Shortcode', 'syntekpro-forms'); ?>
                </button>
                <button type="button" class="spf-embed-tab-btn" data-tab="post-page">
                    <?php _e('Add to Post/Page', 'syntekpro-forms'); ?>
                </button>
                <button type="button" class="spf-embed-tab-btn" data-tab="create">
                    <?php _e('Create New', 'syntekpro-forms'); ?>
                </button>
            </div>

            <!-- Shortcode Tab -->
            <div id="spf-embed-shortcode" class="spf-embed-tab-content active">
                <div class="spf-embed-info">
                    <p><?php _e('Form ID: ', 'syntekpro-forms'); ?><strong id="spf-embed-form-id"><?php echo $form_id; ?></strong></p>
                    <label><?php _e('Copy and paste this shortcode into any post or page:', 'syntekpro-forms'); ?></label>
                    <div class="spf-shortcode-box">
                        <code id="spf-shortcode-copy">[syntekpro_form id="<?php echo $form_id; ?>"]</code>
                        <button type="button" class="button" id="spf-copy-shortcode">
                            <span class="dashicons dashicons-admin-page"></span> <?php _e('Copy', 'syntekpro-forms'); ?>
                        </button>
                    </div>
                    <p style="font-size: 12px; color: #666; margin-top: 10px;"><?php _e('Or use this code in your theme/plugin:', 'syntekpro-forms'); ?></p>
                    <div class="spf-shortcode-box">
                        <code>do_shortcode('[syntekpro_form id="<?php echo $form_id; ?>"]');</code>
                        <button type="button" class="button" id="spf-copy-php-code">
                            <span class="dashicons dashicons-admin-page"></span> <?php _e('Copy', 'syntekpro-forms'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Add to Post/Page Tab -->
            <div id="spf-embed-post-page" class="spf-embed-tab-content">
                <div class="spf-embed-option">
                    <h3><?php _e('Add to Existing Content', 'syntekpro-forms'); ?></h3>
                    <div class="spf-embed-form-group">
                        <label><?php _e('Select Type', 'syntekpro-forms'); ?></label>
                        <div>
                            <label><input type="radio" name="embed-post-type" value="page" checked> <?php _e('Page', 'syntekpro-forms'); ?></label>
                            <label><input type="radio" name="embed-post-type" value="post"> <?php _e('Post', 'syntekpro-forms'); ?></label>
                        </div>
                    </div>
                    <div class="spf-embed-form-group">
                        <label for="spf-select-post"><?php _e('Select Post/Page', 'syntekpro-forms'); ?></label>
                        <select id="spf-select-post">
                            <option value=""><?php _e('-- Choose One --', 'syntekpro-forms'); ?></option>
                        </select>
                    </div>
                    <button type="button" class="button button-primary" id="spf-insert-to-post">
                        <?php _e('Insert Form', 'syntekpro-forms'); ?>
                    </button>
                </div>
            </div>

            <!-- Create New Tab -->
            <div id="spf-embed-create" class="spf-embed-tab-content">
                <div class="spf-embed-option">
                    <h3><?php _e('Create New Page/Post', 'syntekpro-forms'); ?></h3>
                    <div class="spf-embed-form-group">
                        <label><?php _e('Select Type', 'syntekpro-forms'); ?></label>
                        <div>
                            <label><input type="radio" name="create-type" value="page" checked> <?php _e('Page', 'syntekpro-forms'); ?></label>
                            <label><input type="radio" name="create-type" value="post"> <?php _e('Post', 'syntekpro-forms'); ?></label>
                        </div>
                    </div>
                    <div class="spf-embed-form-group">
                        <label for="spf-create-title"><?php _e('Title', 'syntekpro-forms'); ?></label>
                        <input type="text" id="spf-create-title" placeholder="<?php _e('Enter page/post title...', 'syntekpro-forms'); ?>">
                    </div>
                    <button type="button" class="button button-primary" id="spf-create-and-insert">
                        <?php _e('Create & Insert', 'syntekpro-forms'); ?>
                    </button>
                </div>
                <p style="font-size: 12px; color: #666; margin-top: 15px;"><?php _e('Not using the Block Editor? Copy and paste the shortcode within your page builder.', 'syntekpro-forms'); ?></p>
            </div>

            <div class="spf-embed-help">
                <a href="https://docs.syntekpro.com" target="_blank" rel="noopener">
                    <?php _e('Learn more', 'syntekpro-forms'); ?> <span class="dashicons dashicons-external"></span>
                </a>
            </div>
        </div>
    </div>

    <!-- Editor Preferences Modal -->
    <div id="spf-editor-prefs-modal" class="spf-modal" style="display: none;">
        <div class="spf-modal-content" style="max-width: 500px;">
            <button type="button" class="spf-modal-close" aria-label="Close">
                <span class="dashicons dashicons-no"></span>
            </button>
            <h2><?php _e('Editor Preferences', 'syntekpro-forms'); ?></h2>

            <div class="spf-preferences">
                <div class="spf-preference-option">
                    <label>
                        <input type="checkbox" id="spf-compact-view" />
                        <strong><?php _e('Compact View', 'syntekpro-forms'); ?></strong>
                    </label>
                    <p><?php _e('Simplify the preview of form fields for a more streamlined editing experience.', 'syntekpro-forms'); ?></p>
                </div>

                <div class="spf-preference-option">
                    <label>
                        <input type="checkbox" id="spf-show-field-ids" />
                        <strong><?php _e('Show Field IDs', 'syntekpro-forms'); ?></strong>
                    </label>
                    <p><?php _e('Show the ID of each field in Compact View.', 'syntekpro-forms'); ?></p>
                </div>
            </div>

            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ccd0d4; display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="button" id="spf-prefs-close">
                    <?php _e('Close', 'syntekpro-forms'); ?>
                </button>
            </div>
        </div>
    </div>

</div>

<div id="spf-field-context-menu" class="spf-field-context-menu" style="display:none;">
    <button type="button" data-action="edit"><?php _e('Edit', 'syntekpro-forms'); ?></button>
    <button type="button" data-action="duplicate"><?php _e('Duplicate', 'syntekpro-forms'); ?></button>
    <button type="button" data-action="move_up"><?php _e('Move Up', 'syntekpro-forms'); ?></button>
    <button type="button" data-action="move_down"><?php _e('Move Down', 'syntekpro-forms'); ?></button>
    <button type="button" data-action="delete"><?php _e('Delete', 'syntekpro-forms'); ?></button>
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
    color: #dc3232;
}

/* Beautiful Centered Canvas Header */
.spf-canvas-badge-wrapper {
    padding: 8px 0 0 0;
    text-align: center;
}

.spf-form-header-preview {
    padding: 38px 30px;
    background: linear-gradient(135deg, #ffffff 0%, #f5f7fb 100%);
    border-bottom: 1px solid #edf0f4;
    position: relative;
    text-align: center;
}

.spf-header-badge {
    display: inline-block;
    background: #f1f3f7;
    color: #6b7280;
    padding: 6px 14px;
    border-radius: 24px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    margin-bottom: 18px;
    box-shadow: 0 2px 6px rgba(17, 24, 39, 0.06);
}

.spf-form-title-input {
    width: 80%;
    margin: 0 auto 15px;
    font-size: 32px;
    font-weight: 800;
    text-align: center;
    border: 1px solid transparent;
    background: transparent;
    color: #1d2327;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.spf-form-title-input:hover, .spf-form-title-input:focus {
    background: #fff;
    border-bottom: 2px solid #dc3232;
    transform: translateY(-2px);
}

.spf-form-description-input {
    width: 70%;
    margin: 0 auto;
    font-size: 16px;
    color: #646970;
    text-align: center;
    border: 1px solid transparent;
    background: transparent;
    padding: 10px;
    resize: none;
    min-height: 50px;
    transition: all 0.3s;
}

.spf-form-description-input:hover, .spf-form-description-input:focus {
    background: #fff;
    border-bottom: 1px solid #ccd0d4;
}

.spf-form-fields-canvas {
    padding: 30px;
    min-height: 500px;
    background: #f8fafc;
    background-image: radial-gradient(rgba(12, 18, 28, 0.03) 1px, transparent 1px);
    background-size: 18px 18px;
    border-top: 1px solid #eef1f4;
}

/* Texture Pattern for Sidebar and Canvas */
.spf-sidebar, .spf-field-settings-window {
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    box-shadow: 0 12px 30px rgba(17, 24, 39, 0.04);
}

.spf-sidebar-content {
    background: #ffffff;
    border-top: 1px solid #eef1f4;
}

/* Beautiful Field Items on Canvas */
.spf-field-item {
    background: #fff;
    border: 1px solid #e6e9ed;
    border-radius: 12px;
    margin-bottom: 16px;
    box-shadow: 0 6px 18px rgba(17, 24, 39, 0.05);
    transition: all 0.25s ease;
    position: relative;
    overflow: hidden;
}

.spf-field-item:hover {
    border-color: #d8dde3;
    box-shadow: 0 10px 26px rgba(17, 24, 39, 0.08);
    transform: translateY(-2px);
}

.spf-field-item.active {
    border-color: #dc3232;
    box-shadow: 0 0 0 1px #dc3232, 0 14px 30px rgba(220, 50, 50, 0.12);
}

/* Redesigned Field Header & Actions */
.spf-field-header {
    background: #fdfdfd;
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f1;
    display: flex;
    align-items: center;
    gap: 15px;
}

.spf-field-sort-handle {
    cursor: grab;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #ccd0d4;
    transition: color 0.2s;
    height: 30px;
}

.spf-field-sort-handle:hover {
    color: #dc3232;
}

.spf-field-sort-handle .dashicons {
    font-size: 16px;
    width: 16px;
    height: 10px;
    line-height: 10px;
}

.spf-field-title {
    flex: 1;
    font-weight: 700;
    font-size: 14px;
    color: #3c434a;
}

.spf-field-title small {
    font-weight: 400;
    color: #8c8f94;
    text-transform: uppercase;
    font-size: 10px;
    margin-left: 5px;
}

.spf-field-actions {
    display: flex;
    gap: 5px;
}

.spf-action-btn {
    background: #f6f7f7;
    border: 1px solid #dcdcde;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    color: #50575e;
}

.spf-action-btn .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.spf-edit-field:hover {
    background: #2271b1;
    color: #fff;
    border-color: #2271b1;
}

.spf-delete-field:hover {
    background: #d63638;
    color: #fff;
    border-color: #d63638;
}

/* Sidebar Tabs Container */
.spf-sidebar-tabs {
    display: flex;
    gap: 8px;
    padding: 12px;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

/* Sidebar Tab Improvements */
.spf-tab-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    flex: 1;
    padding: 16px 8px;
    background: #fff;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    margin: 0 4px;
    min-height: 85px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.spf-tab-btn .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    color: #6b7280;
}

.spf-tab-btn .spf-tab-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6b7280;
    font-weight: 500;
    text-align: center;
    line-height: 1.2;
}

.spf-tab-btn:hover {
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    border-color: #3b82f6;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

.spf-tab-btn:hover .dashicons {
    color: #3b82f6;
}

.spf-tab-btn:hover .spf-tab-label {
    color: #1e40af;
}

.spf-tab-btn.active {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
}

.spf-tab-btn.active .dashicons {
    color: #2563eb;
}

.spf-tab-btn.active .spf-tab-label {
    color: #1e40af;
    font-weight: 600;
}

/* Tab content visibility */
.spf-tab-content {
    display: none;
}

.spf-tab-content.active {
    display: block;
}

/* Field Sections */
.spf-field-section {
    margin-bottom: 15px;
}

.spf-field-section-header {
    margin: 0 !important;
    padding: 12px 15px !important;
    background: #ffffff !important;
    border-radius: 6px !important;
    border: 1px solid #e5e7eb !important;
    cursor: pointer !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    color: #4b5563 !important;
    letter-spacing: 0.3px !important;
    transition: all 0.2s ease !important;
    position: relative !important;
}

.spf-field-section-header::after {
    content: '';
    width: 12px;
    height: 12px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: center;
    background-size: 12px;
    transition: transform 0.2s ease;
}

.spf-field-section-header:hover {
    background: #f9fafb !important;
    border-color: #3b82f6 !important;
    color: #3b82f6 !important;
}

.spf-field-section-header .dashicons {
    display: none;
}

.spf-field-section-header.collapsed::after {
    transform: rotate(-90deg);
}

/* Field Grid - 3 Columns */
.spf-field-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    padding: 15px 10px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-top: none;
    border-radius: 0 0 8px 8px;
    max-height: 500px;
    overflow-y: auto;
}

.spf-field-grid.collapsed {
    display: none;
}

/* Sidebar Field Types - Icon on Top, Text Below */
.spf-field-type {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 12px;
    background: #fff;
    border: 2px solid #e1e4e8;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
    aspect-ratio: 1 / 1;
}

.spf-field-type .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    margin-bottom: 8px;
    color: #6b7280;
    transition: all 0.2s ease;
}

.spf-field-type .spf-field-label {
    font-size: 11px;
    font-weight: 500;
    color: #4b5563;
    line-height: 1.3;
    word-break: break-word;
}

.spf-field-type:hover {
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    border-color: #3b82f6;
    transform: translateY(-3px);
    box-shadow: 0 8px 16px rgba(59, 130, 246, 0.15);
}

.spf-field-type:hover .dashicons {
    color: #3b82f6;
    transform: scale(1.1);
}

.spf-field-type:hover .spf-field-label {
    color: #1e40af;
}

.spf-field-type:active {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(59, 130, 246, 0.2);
}

/* Custom Scrollbar for Field Grid */
.spf-field-grid::-webkit-scrollbar {
    width: 8px;
}

.spf-field-grid::-webkit-scrollbar-track {
    background: #f1f3f4;
    border-radius: 4px;
}

.spf-field-grid::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.spf-field-grid::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Settings Panels Beautification */
.spf-settings-panel h3 {
    margin: 20px 0 0;
    padding: 14px 18px;
    background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
    border-radius: 10px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 2px 8px rgba(17, 24, 39, 0.04);
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    letter-spacing: 0.3px;
}

.spf-settings-panel h3:hover {
    background: linear-gradient(135deg, #f1f3f9 0%, #fafbfc 100%);
    border-color: #d1d5db;
    box-shadow: 0 4px 12px rgba(17, 24, 39, 0.06);
}

.spf-panel-collapsible-content {
    background: #ffffff;
    padding: 24px 18px;
    border: 1px solid #e5e7eb;
    border-top: none;
    border-radius: 0 0 10px 10px;
    margin-bottom: 20px;
    margin-top: -8px;
}

.spf-settings-panel h3::after {
    content: "\f140";
    font-family: dashicons;
    font-size: 18px;
    color: #9ca3af;
    font-weight: normal;
}

.spf-settings-panel h3.collapsed {
    border-radius: 10px;
    margin-bottom: 8px;
}

.spf-settings-panel h3.collapsed::after {
    transform: rotate(-90deg);
}

.spf-setting-row label {
    font-size: 11px;
    letter-spacing: 0.3px;
    color: #4b5563;
    font-weight: 500;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    pointer-events: auto;
    user-select: auto;
}

.spf-setting-row label .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    color: #6b7280;
}

/* Field Settings Window Header */
.spf-field-settings-window-header {
    background: #ffffff;
    padding: 16px 20px;
    border-bottom: 1px solid #eef1f4;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.spf-field-settings-window-header h3 {
    color: #1f2933;
    font-weight: 700;
}

.spf-field-settings-close {
    background: #f5f6f7;
    border: 1px solid #e5e7eb;
    color: #6b7280;
    border-radius: 4px;
}

.spf-field-settings-close:hover {
    background: #fff;
    color: #dc3232;
    border-color: #dc3232;
}

/* Empty State Styling */
.spf-empty-builder {
    border: 2px dashed #e5e7eb;
    background: #fff;
    padding: 90px 36px;
    transition: all 0.3s;
}

.spf-empty-builder:hover {
    background: #fdfefe;
    border-color: #cfd6dd;
}

/* Global Navigation Buttons */
.spf-nav-btn, .spf-nav-right .button {
    border-radius: 8px !important;
    font-weight: 600 !important;
    transition: all 0.2s !important;
}

.spf-nav-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}

.spf-icon-btn {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #ccd0d4 !important;
    background: #fff !important;
}

.spf-icon-btn:hover {
    border-color: #dc3232 !important;
    color: #dc3232 !important;
}

/* Tooltip style */
.spf-tooltip {
    position: relative;
}

/* Footer alignment fix */
#wpfooter {
    position: relative !important;
    clear: both !important;
    margin-top: 50px !important;
    background: transparent !important;
}

/* Field Settings Window Styling */
.spf-field-settings-window {
    background: #fff;
    border-bottom: 1px solid #eef1f4;
    max-height: none;
    display: flex;
    flex-direction: column;
    margin-bottom: 0;
    box-shadow: inset 0 -5px 12px rgba(17, 24, 39, 0.02);
}

.spf-field-settings-window-body {
    flex: 1;
    overflow-y: visible;
    scrollbar-width: thin;
    scrollbar-color: #c3c8ce #f1f1f1;
}

.spf-field-settings-window-body::-webkit-scrollbar {
    width: 6px;
}

.spf-field-settings-window-body::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.spf-field-settings-window-body::-webkit-scrollbar-thumb {
    background: #c3c8ce;
    border-radius: 10px;
}

.spf-field-settings-content {
    padding: 0;
}

/* Collapsible Settings Sections */
.spf-settings-section {
    border-bottom: 1px solid #f0f0f1;
}

.spf-section-header {
    padding: 14px 20px;
    background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 8px;
    margin-bottom: 2px;
}

.spf-section-header:hover {
    background: linear-gradient(135deg, #f1f3f9 0%, #fafbfc 100%);
}

.spf-section-header h4 {
    margin: 0;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #374151;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.spf-section-header .dashicons {
    font-size: 18px;
    color: #9ca3af;
}

.spf-settings-section.active .spf-section-header {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
}

.spf-settings-section.active .spf-section-header .dashicons {
    transform: rotate(180deg);
    color: #3b82f6;
}

.spf-section-body {
    padding: 20px;
    background: #ffffff;
    display: none;
    border: 1px solid #e5e7eb;
    border-top: none;
    border-radius: 0 0 8px 8px;
    margin-bottom: 12px;
}

.spf-settings-section.active .spf-section-body {
    display: block;
}

.spf-setting-row {
    margin-bottom: 20px;
}

.spf-setting-row label {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 10px;
    font-weight: 500;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #4b5563;
    padding: 8px 12px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    pointer-events: auto;
    user-select: auto;
}

.spf-setting-row label .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    color: #6b7280;
}

.spf-setting-row input[type="text"],
.spf-setting-row input[type="number"],
.spf-setting-row textarea,
.spf-setting-row select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background: #ffffff;
    font-size: 13px;
    color: #4b5563;
    font-weight: 400;
}

.spf-setting-row select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 12px;
    padding-right: 36px;
}

.spf-setting-row input:focus,
.spf-setting-row textarea:focus,
.spf-setting-row select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    outline: none;
}

/* Submit Button Beautification */
.spf-submit-button {
    background: linear-gradient(135deg, #dc3232 0%, #a00 100%);
    color: #fff !important;
    border: none !important;
    padding: 12px 28px !important;
    border-radius: 6px !important;
    font-weight: 700 !important;
    font-size: 15px !important;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(220, 50, 50, 0.2);
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-shadow: 0 1px 1px rgba(0,0,0,0.2);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.spf-submit-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(220, 50, 50, 0.3);
    background: linear-gradient(135deg, #e34545 0%, #b30000 100%);
}

.spf-submit-button:active {
    transform: translateY(0);
}

.spf-form-footer {
    display: flex;
    padding: 20px 0;
    justify-content: center;
}

@media (max-width: 1200px) {
    .spf-builder-container {
        flex-direction: column;
    }
    .spf-sidebar {
        width: 100%;
    }
}

/* Modern Hover Animations - DISABLED to prevent shaking */
.spf-tab-content.active {
    /* No animations - instant display */
}

/* ===== NEW TOP NAVIGATION BAR STYLES ===== */
.spf-builder-top-nav {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 12px 15px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}

.spf-nav-left, .spf-nav-right {
    display: flex;
    align-items: center;
    gap: 10px;
}

.spf-nav-btn {
    padding: 8px 15px;
    background: #f6f7f7;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: #50575e;
    text-decoration: none;
    transition: all 0.2s;
}

.spf-nav-btn:hover {
    background: #f0f0f1;
    border-color: #8f8f8f;
    color: #000;
}

.spf-nav-btn .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* Recent Forms Dropdown */
.spf-nav-recent-forms {
    position: relative;
}

.spf-dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    min-width: 250px;
    z-index: 1000;
    margin-top: 5px;
}

.spf-dropdown-search {
    padding: 10px;
    border-bottom: 1px solid #f0f0f1;
}

.spf-dropdown-search input {
    width: 100%;
    padding: 6px 8px;
    border: 1px solid #ccd0d4;
    border-radius: 3px;
    font-size: 12px;
}

.spf-dropdown-list {
    max-height: 300px;
    overflow-y: auto;
}

.spf-dropdown-list .spf-loading {
    padding: 15px;
    text-align: center;
    color: #999;
    font-size: 12px;
}

.spf-dropdown-item {
    padding: 8px 12px;
    border-bottom: 1px solid #f0f0f1;
    cursor: pointer;
    color: #32373c;
    text-decoration: none;
    display: block;
    transition: background 0.2s;
}

.spf-dropdown-item:hover {
    background: #f8f9f9;
}

/* Icon Button */
.spf-icon-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 6px 8px;
    border-radius: 4px;
    transition: all 0.2s;
}

.spf-icon-btn:hover {
    background: #f0f0f1;
}

.spf-icon-btn .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

/* Right sidebar adjustments */
.spf-sidebar-right {
    width: 320px;
    order: 2;
}

/* Modal Styles */
.spf-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 20px;
}

.spf-modal-content {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.3);
    max-width: 700px;
    max-height: 90vh;
    overflow-y: auto;
    padding: 30px;
    position: relative;
    width: 100%;
}

.spf-modal-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #50575e;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.spf-modal-close:hover {
    color: #dc3232;
    background: #f0f0f1;
    border-radius: 4px;
}

.spf-modal h2 {
    margin-top: 0;
    margin-bottom: 20px;
}

/* Embed Modal Tabs */
.spf-embed-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    border-bottom: 2px solid #f0f0f1;
}

.spf-embed-tab-btn {
    background: none;
    border: none;
    padding: 10px 15px;
    cursor: pointer;
    color: #50575e;
    font-weight: 500;
    border-bottom: 3px solid transparent;
    transition: all 0.2s;
    margin-bottom: -2px;
}

.spf-embed-tab-btn:hover {
    color: #32373c;
}

.spf-embed-tab-btn.active {
    color: #dc3232;
    border-bottom-color: #dc3232;
}

.spf-embed-tab-content {
    display: none;
}

.spf-embed-tab-content.active {
    display: block;
}

.spf-embed-info,
.spf-embed-option {
    margin-bottom: 20px;
}

.spf-embed-info p {
    margin: 10px 0;
}

.spf-shortcode-box {
    background: #f6f7f7;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 10px 0;
}

.spf-shortcode-box code {
    font-family: 'Courier New', monospace;
    font-size: 12px;
    flex: 1;
    word-break: break-all;
    color: #2c3338;
}

.spf-shortcode-box .button {
    flex-shrink: 0;
    font-size: 12px;
    padding: 5px 10px;
}

.spf-embed-form-group {
    margin-bottom: 15px;
}

.spf-embed-form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #32373c;
}

.spf-embed-form-group input,
.spf-embed-form-group select {
    width: 100%;
    max-width: 350px;
    padding: 8px 12px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    font-size: 13px;
}

.spf-embed-form-group input[type="radio"] {
    width: auto;
    margin-right: 8px;
}

.spf-embed-form-group div {
    display: flex;
    gap: 15px;
}

.spf-embed-help {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #f0f0f1;
    text-align: right;
}

.spf-embed-help a {
    color: #dc3232;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
}

.spf-embed-help a:hover {
    color: #b81a23;
}

.spf-embed-help .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Editor Preferences Modal */
.spf-preferences {
    margin: 20px 0;
}

.spf-preference-option {
    padding: 15px;
    background: #f8f9f9;
    border-radius: 4px;
    margin-bottom: 15px;
}

.spf-preference-option label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    margin-bottom: 8px;
}

.spf-preference-option input[type="checkbox"] {
    width: auto;
    cursor: pointer;
}

.spf-preference-option strong {
    color: #32373c;
}

.spf-preference-option p {
    margin: 8px 0 0 0;
    font-size: 12px;
    color: #646970;
    line-height: 1.5;
}

/* Gravity-style canvas polish */
.spf-builder-container {
    gap: 20px;
}

.spf-main-content {
    background: #ffffff;
    border: 1px solid #dcdcde;
    border-radius: 8px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}

.spf-sidebar,
.spf-field-settings-window {
    background: #ffffff;
    border: 1px solid #dcdcde;
    border-radius: 8px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}

.spf-sidebar-content {
    background: #ffffff;
}

.spf-builder-top-nav,
.spf-builder-ux-toolbar,
.spf-form-health-strip,
.spf-bulk-actions-bar {
    background: #ffffff;
    border: 1px solid #dcdcde;
    border-radius: 6px;
    box-shadow: none;
}

.spf-form-header-preview {
    background: #ffffff;
    border-bottom: 1px solid #e0e0e0;
    padding: 28px 24px;
}

.spf-header-badge {
    background: #f0f6fc;
    color: #2271b1;
    box-shadow: none;
}

.spf-form-title-input {
    font-size: 28px;
    font-weight: 700;
}

.spf-form-title-input:hover,
.spf-form-title-input:focus {
    border-bottom: 2px solid #2271b1;
    transform: none;
}

.spf-form-fields-canvas {
    background: #f6f7f7;
    background-image: none;
    padding: 24px;
    border-top: 1px solid #e5e5e5;
}

.spf-field-item {
    border: 1px solid #dcdcde;
    border-radius: 8px;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
    margin-bottom: 12px;
}

.spf-field-item:hover {
    border-color: #bfc3c7;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    transform: none;
}

.spf-field-item.active,
.spf-field-item.is-selected {
    border-color: #2271b1 !important;
    box-shadow: 0 0 0 1px #2271b1, 0 2px 8px rgba(34, 113, 177, 0.12) !important;
}

.spf-field-header {
    background: #fcfcfc;
    padding: 10px 12px;
    gap: 10px;
}

.spf-field-body {
    padding: 14px;
}

.spf-field-sort-handle {
    color: #8c8f94;
}

.spf-field-sort-handle:hover {
    color: #2271b1;
}

.spf-action-btn {
    width: 28px;
    height: 28px;
    border-radius: 4px;
    background: #ffffff;
    border-color: #c3c4c7;
}

.spf-edit-field:hover {
    background: #2271b1;
    border-color: #2271b1;
}

.spf-tab-btn {
    min-height: 74px;
    border: 1px solid #dcdcde;
    border-radius: 6px;
    margin: 0;
    background: #ffffff;
}

.spf-tab-btn .dashicons {
    color: #50575e;
}

.spf-tab-btn .spf-tab-label {
    color: #50575e;
}

.spf-tab-btn:hover,
.spf-tab-btn.active {
    background: #f0f6fc;
    border-color: #2271b1;
    box-shadow: none;
    transform: none;
}

.spf-tab-btn:hover .dashicons,
.spf-tab-btn.active .dashicons,
.spf-tab-btn:hover .spf-tab-label,
.spf-tab-btn.active .spf-tab-label {
    color: #2271b1;
}

.spf-field-grid {
    background: #ffffff;
    border: 1px solid #dcdcde;
    border-top: none;
}

.spf-field-type {
    border: 1px solid #dcdcde;
    border-radius: 6px;
    aspect-ratio: auto;
    min-height: 90px;
}

.spf-field-type:hover {
    background: #f0f6fc;
    border-color: #2271b1;
    box-shadow: none;
    transform: none;
}

.spf-field-type:hover .dashicons,
.spf-field-type:hover .spf-field-label {
    color: #2271b1;
    transform: none;
}

.spf-setting-row label {
    text-transform: none;
    letter-spacing: 0;
    font-size: 12px;
    color: #1d2327;
    background: #f6f7f7;
}

.spf-setting-row input[type="text"],
.spf-setting-row input[type="number"],
.spf-setting-row textarea,
.spf-setting-row select {
    border-color: #c3c4c7;
    border-radius: 4px;
}

.spf-setting-row input:focus,
.spf-setting-row textarea:focus,
.spf-setting-row select:focus {
    border-color: #2271b1;
    box-shadow: 0 0 0 1px #2271b1;
}

.spf-submit-button {
    background: #2271b1 !important;
    border: 1px solid #2271b1 !important;
    border-radius: 4px !important;
    box-shadow: none;
    text-shadow: none;
    text-transform: none;
    letter-spacing: 0;
    font-size: 14px !important;
    font-weight: 600 !important;
    padding: 10px 18px !important;
}

.spf-submit-button:hover {
    background: #135e96 !important;
    border-color: #135e96 !important;
    transform: none;
    box-shadow: none;
}

.spf-empty-builder {
    border-radius: 8px;
    border: 2px dashed #c3c4c7;
    background: #ffffff;
}

.spf-context-menu {
    border: 1px solid #c3c4c7;
    border-radius: 6px;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
}

/* Gravity-style refinement pass: tighter typography + reduced icon weight */
.spf-form-title-input {
    font-size: 24px;
    line-height: 1.25;
}

.spf-form-description-input {
    font-size: 14px;
    line-height: 1.45;
}

.spf-header-badge {
    font-size: 9px;
    letter-spacing: 0.8px;
    padding: 5px 12px;
}

.spf-field-header {
    padding: 8px 10px;
}

.spf-field-title {
    font-size: 13px;
    font-weight: 600;
}

.spf-field-title small {
    font-size: 9px;
    letter-spacing: 0.2px;
}

.spf-field-badge {
    font-size: 10px;
    padding: 2px 7px;
    border-radius: 10px;
}

.spf-field-body label {
    font-size: 12px;
    font-weight: 500;
    color: #1d2327;
}

.spf-field-body input,
.spf-field-body textarea,
.spf-field-body select {
    font-size: 12px;
}

.spf-tab-btn {
    min-height: 64px;
    padding: 12px 6px;
    gap: 4px;
}

.spf-tab-btn .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.spf-tab-btn .spf-tab-label {
    font-size: 9px;
    letter-spacing: 0.3px;
}

.spf-field-grid {
    gap: 10px;
    padding: 12px;
}

.spf-field-type {
    min-height: 78px;
    padding: 10px 8px;
    border-radius: 5px;
}

.spf-field-type .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    margin-bottom: 6px;
    opacity: 0.82;
}

.spf-field-type .spf-field-label {
    font-size: 10px;
    font-weight: 500;
    line-height: 1.25;
}

.spf-builder-ux-toolbar .button,
.spf-bulk-actions-bar .button,
.spf-nav-btn,
.spf-nav-right .button {
    font-size: 12px !important;
    min-height: 30px;
    padding: 5px 10px !important;
}

.spf-action-btn {
    width: 26px;
    height: 26px;
}

.spf-action-btn .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.spf-setting-row label {
    font-size: 11px;
}

.spf-settings-panel h3,
.spf-section-header h4 {
    font-size: 12px;
}

.spf-submit-button {
    font-size: 13px !important;
    padding: 9px 14px !important;
}

/* Responsive adjustments */
@media (max-width: 1400px) {
    .spf-builder-top-nav {
        flex-wrap: wrap;
    }
}

@media (max-width: 768px) {
    .spf-modal-content {
        padding: 20px;
        max-width: 100%;
    }
    
    .spf-embed-tabs {
        flex-wrap: wrap;
    }
    
    .spf-embed-form-group input,
    .spf-embed-form-group select {
        max-width: 100%;
    }
}
</style>
```