<?php
/**
 * Frontend Form Display
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

if (!isset($atts)) {
    $atts = array('id' => 0);
}

$form_id = intval($atts['id']);

$form = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}spf_forms WHERE id = %d AND status = 'active'",
    $form_id
));

if (!$form) {
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return '<p>' . __('Form not found.', 'syntekpro-forms') . '</p>';
    }
    echo '<p>' . __('Form not found.', 'syntekpro-forms') . '</p>';
    return;
}

$fields = json_decode($form->fields, true);
$settings = json_decode($form->settings, true);

if (!is_array($fields)) {
    $fields = array();
}

if (!is_array($settings)) {
    $settings = array();
}

$availability = array('status' => 'open', 'message' => '');
if (class_exists('SyntekPro_Forms_Builder')) {
    $availability = SyntekPro_Forms_Builder::get_instance()->get_form_availability_state($settings, $form_id);
}

if ($availability['status'] !== 'open') {
    echo '<div class="spf-form-closed-message" style="background:#fff3cd;border:1px solid #ffeeba;padding:20px;border-radius:4px;color:#856404;">' . wpautop(wp_kses_post(!empty($availability['message']) ? $availability['message'] : __('This form is currently unavailable.', 'syntekpro-forms'))) . '</div>';
    return;
}

$plugin_settings = get_option('spf_settings');

// Attributes Overrides (Gutenberg/Shortcode)
$theme = $atts['theme'] ?? $settings['theme'] ?? 'classic';
if (empty($theme)) {
    $theme = $plugin_settings['default_theme'] ?? 'classic';
}

$show_title = isset($atts['showTitle']) ? (bool)$atts['showTitle'] : true;
$show_description = isset($atts['showDescription']) ? (bool)$atts['showDescription'] : true;

// Pre-filled values
$prefilled_values = array();
if (!empty($atts['fieldValues'])) {
    parse_str(str_replace(',', '&', (string)$atts['fieldValues']), $prefilled_values);
}

// Prepare custom styles
$custom_styles = '';
$font_family = $settings['font_family'] ?? 'inherit';
$font_size = $settings['font_size'] ?? '16';
$field_padding = $settings['field_padding'] ?? '12';
$border_radius = $settings['border_radius'] ?? '4';
$primary_color = $settings['primary_color'] ?? '#0073aa';
$label_color = $settings['label_color'] ?? '#1d2327';
$bg_color = $settings['bg_color'] ?? '#ffffff';
$submit_align = $settings['submit_align'] ?? 'left';

$custom_styles .= "font-family: {$font_family}; ";
$custom_styles .= "font-size: {$font_size}px; ";
$custom_styles .= "background-color: {$bg_color}; ";

// CSS Variables for Overrides
$vars = array(
    '--spf-primary-color' => isset($atts['primaryColor']) && $atts['primaryColor'] !== '' ? $atts['primaryColor'] : $primary_color,
    '--spf-label-color' => isset($atts['labelColor']) && $atts['labelColor'] !== '' ? $atts['labelColor'] : (!empty($atts['labelTextColor']) ? $atts['labelTextColor'] : $label_color),
    '--spf-bg-color' => isset($atts['bgColor']) && $atts['bgColor'] !== '' ? $atts['bgColor'] : $bg_color,
    '--spf-border-radius' => (isset($atts['borderRadius']) && $atts['borderRadius'] !== '' ? $atts['borderRadius'] : $border_radius) . 'px',
    '--spf-field-padding' => (isset($atts['fieldPadding']) && $atts['fieldPadding'] !== '' ? $atts['fieldPadding'] : $field_padding) . 'px',
    '--spf-font-size' => $font_size . 'px',
    '--spf-font-family' => isset($atts['fontFamily']) && $atts['fontFamily'] !== '' ? $atts['fontFamily'] : $font_family,
    
    // New Gutenberg overrides
    '--spf-input-bg' => !empty($atts['inputBgColor']) ? $atts['inputBgColor'] : '',
    '--spf-input-border' => !empty($atts['inputBorderColor']) ? $atts['inputBorderColor'] : '',
    '--spf-input-text' => !empty($atts['inputTextColor']) ? $atts['inputTextColor'] : '',
    '--spf-input-accent' => !empty($atts['inputAccentColor']) ? $atts['inputAccentColor'] : '',
    '--spf-label-size' => !empty($atts['labelFontSize']) ? $atts['labelFontSize'] . 'px' : '',
    '--spf-desc-size' => !empty($atts['descriptionFontSize']) ? $atts['descriptionFontSize'] . 'px' : '',
    '--spf-desc-color' => !empty($atts['descriptionTextColor']) ? $atts['descriptionTextColor'] : '',
    '--spf-btn-bg' => !empty($atts['buttonBgColor']) ? $atts['buttonBgColor'] : '',
    '--spf-btn-text' => !empty($atts['buttonTextColor']) ? $atts['buttonTextColor'] : '',
);

// Input size logic
if (!empty($atts['inputSize'])) {
    if ($atts['inputSize'] === 'small') {
        $vars['--spf-field-padding'] = '8px 12px';
        $vars['--spf-input-font-size'] = '14px';
    } elseif ($atts['inputSize'] === 'large') {
        $vars['--spf-field-padding'] = '16px 20px';
        $vars['--spf-input-font-size'] = '18px';
    }
}

$inline_css_content = "";
foreach ($vars as $prop => $val) {
    if (!empty($val)) {
        $inline_css_content .= "{$prop}: {$val}; ";
    }
}

$inline_css = "<style>
    #spf-form-{$form_id} {
        {$inline_css_content}
    }
    #spf-form-{$form_id} .spf-progress-bar-track { height: 8px; background: #e9ecef; border-radius: 999px; overflow: hidden; }
    #spf-form-{$form_id} .spf-progress-bar-fill { height: 8px; background: {$primary_color}; width: 0; transition: width 0.25s ease; }
    #spf-form-{$form_id} .spf-step-heading { margin: 0 0 15px; font-weight: 600; color: {$label_color}; }
    #spf-form-{$form_id} .spf-step-nav { display:flex; justify-content: space-between; gap:10px; margin-top:20px; }
    #spf-form-{$form_id} .spf-step-nav .button { padding:12px 18px; }
</style>";
echo $inline_css;

$success_behavior = isset($settings['success_behavior']) ? $settings['success_behavior'] : 'message';
$success_redirect_url = isset($settings['success_redirect_url']) ? esc_url($settings['success_redirect_url']) : '';

// Build steps from fields (split on type=step)
$steps = array();
$current_step = array('title' => __('Step', 'syntekpro-forms') . ' 1', 'fields' => array());
$step_index = 1;
foreach ($fields as $field) {
    if (($field['type'] ?? '') === 'step') {
        if (!empty($current_step['fields'])) {
            $steps[] = $current_step;
        }
        $step_index++;
        $current_step = array(
            'title' => !empty($field['label']) ? $field['label'] : sprintf(__('Step %d', 'syntekpro-forms'), $step_index),
            'fields' => array()
        );
        continue;
    }
    $current_step['fields'][] = $field;
}
if (!empty($current_step['fields'])) {
    $steps[] = $current_step;
}
if (empty($steps)) {
    $steps[] = array('title' => __('Step 1', 'syntekpro-forms'), 'fields' => array());
}
?>

<div id="spf-form-<?php echo $form_id; ?>" class="spf-form-wrapper spf-theme-<?php echo esc_attr($theme); ?>" style="<?php echo esc_attr($custom_styles); ?>">
    <?php if ($show_title && !empty($form->title)): ?>
        <h3 class="spf-form-title"><?php echo esc_html($form->title); ?></h3>
    <?php endif; ?>
    
    <?php if ($show_description && !empty($form->description)): ?>
        <div class="spf-form-description"><?php echo wpautop(esc_html($form->description)); ?></div>
    <?php endif; ?>

    <?php if (count($steps) > 1): ?>
        <div class="spf-progress" data-total="<?php echo count($steps); ?>">
            <div class="spf-progress-label"><?php _e('Step 1 of', 'syntekpro-forms'); ?> <?php echo count($steps); ?></div>
            <div class="spf-progress-bar-track"><div class="spf-progress-bar-fill"></div></div>
        </div>
    <?php endif; ?>
    
        <form class="spf-form<?php echo count($steps) > 1 ? ' spf-has-steps' : ''; ?>" data-form-id="<?php echo $form_id; ?>" 
            data-ajax="<?php echo (isset($atts['ajax']) && $atts['ajax'] === false) ? 'false' : 'true'; ?>"
            data-step-total="<?php echo count($steps); ?>"
            <?php echo (!empty($plugin_settings['recaptcha_invisible']) && !empty($plugin_settings['recaptcha_site_key'])) ? 'data-recaptcha-type="invisible"' : ''; ?>
            <?php echo !empty($atts['tabindex']) ? 'tabindex="' . intval($atts['tabindex']) . '"' : ''; ?>
            <?php echo !empty($atts['submitAlign']) ? 'data-submit-align="' . esc_attr($atts['submitAlign']) . '"' : ''; ?>
            data-success-behavior="<?php echo esc_attr($success_behavior); ?>"
            data-success-redirect="<?php echo esc_attr($success_redirect_url); ?>">
        <?php 
        // Honeypot Protection
        if (isset($settings['enable_honeypot']) && $settings['enable_honeypot']): ?>
            <div class="spf-hp-wrap" style="display:none !important; visibility:hidden !important;">
                <label for="spf-hp-<?php echo $form_id; ?>"><?php _e('Leave this field blank', 'syntekpro-forms'); ?></label>
                <input type="text" name="spf_hp_field" id="spf-hp-<?php echo $form_id; ?>" autocomplete="off">
            </div>
        <?php endif; ?>

        <?php foreach ($steps as $index => $step): ?>
            <div class="spf-step" data-step-index="<?php echo $index; ?>" <?php echo $index === 0 ? '' : 'style="display:none;"'; ?>
                 data-step-title="<?php echo esc_attr($step['title']); ?>">
                <?php if (count($steps) > 1): ?>
                    <h4 class="spf-step-heading"><?php echo esc_html($step['title']); ?></h4>
                <?php endif; ?>
                <?php foreach ($step['fields'] as $field): 
                    $field_val = $prefilled_values[$field['name']] ?? '';
                ?>
                    <div class="spf-field-wrapper spf-field-type-<?php echo esc_attr($field['type']); ?>" 
                         data-field-id="<?php echo esc_attr($field['id']); ?>">
                        
                        <?php if (!empty($field['label'])): ?>
                            <label for="spf-field-<?php echo esc_attr($field['id']); ?>" class="spf-field-label">
                                <?php echo esc_html($field['label']); ?>
                                <?php if (!empty($field['required'])): ?>
                                    <span class="spf-required">*</span>
                                <?php endif; ?>
                            </label>
                        <?php endif; ?>
                        
                        <?php
                        switch ($field['type']) {
                            case 'text':
                            case 'email':
                            case 'number':
                            case 'date':
                                ?>
                                <input type="<?php echo esc_attr($field['type']); ?>" 
                                       id="spf-field-<?php echo esc_attr($field['id']); ?>"
                                       name="<?php echo esc_attr($field['name']); ?>"
                                       class="spf-field-input"
                                       placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
                                       value="<?php echo esc_attr($field_val); ?>"
                                       <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                                <?php
                                break;
                            
                            case 'textarea':
                                ?>
                                <textarea id="spf-field-<?php echo esc_attr($field['id']); ?>"
                                          name="<?php echo esc_attr($field['name']); ?>"
                                          class="spf-field-textarea"
                                          placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
                                          rows="5"
                                          <?php echo !empty($field['required']) ? 'required' : ''; ?>><?php echo esc_textarea($field_val); ?></textarea>
                                <?php
                                break;
                            
                            case 'select':
                                ?>
                                <select id="spf-field-<?php echo esc_attr($field['id']); ?>"
                                        name="<?php echo esc_attr($field['name']); ?>"
                                        class="spf-field-select"
                                        <?php echo !empty($field['required']) ? 'required' : ''; ?>
                                        >
                                    <option value="">-- <?php _e('Select', 'syntekpro-forms'); ?> --</option>
                                    <?php if (!empty($field['options']) && is_array($field['options'])): ?>
                                        <?php foreach ($field['options'] as $option): ?>
                                            <option value="<?php echo esc_attr($option); ?>" <?php selected($field_val, $option); ?>
                                                >
                                                <?php echo esc_html($option); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <?php
                                break;
                            
                            case 'radio':
                                ?>
                                <div class="spf-radio-group">
                                    <?php if (!empty($field['options']) && is_array($field['options'])): ?>
                                        <?php foreach ($field['options'] as $index2 => $option): ?>
                                            <label class="spf-radio-label">
                                                <input type="radio" 
                                                       id="spf-field-<?php echo esc_attr($field['id'] . '-' . $index2); ?>"
                                                       name="<?php echo esc_attr($field['name']); ?>"
                                                       value="<?php echo esc_attr($option); ?>"
                                                       <?php checked($field_val, $option); ?>
                                                       <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                                                <?php echo esc_html($option); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <?php
                                break;
                            
                            case 'checkbox':
                                $field_vals = is_array($field_val) ? $field_val : explode(',', (string)$field_val);
                                ?>
                                <div class="spf-checkbox-group">
                                    <?php if (!empty($field['options']) && is_array($field['options'])): ?>
                                        <?php foreach ($field['options'] as $index2 => $option): ?>
                                            <label class="spf-checkbox-label">
                                                <input type="checkbox" 
                                                       id="spf-field-<?php echo esc_attr($field['id'] . '-' . $index2); ?>"
                                                       name="<?php echo esc_attr($field['name']); ?>[]"
                                                       value="<?php echo esc_attr($option); ?>"
                                                       <?php checked(in_array($option, $field_vals)); ?>
                                                       >
                                                <?php echo esc_html($option); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <?php
                                break;
                            
                            case 'file':
                                ?>
                                <input type="file" 
                                       id="spf-field-<?php echo esc_attr($field['id']); ?>"
                                       name="<?php echo esc_attr($field['name']); ?>"
                                       class="spf-field-file"
                                       <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                                <?php
                                break;
                        }
                        ?>
                        
                        <?php if (!empty($field['description'])): ?>
                            <p class="spf-field-description"><?php echo esc_html($field['description']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <?php if (count($steps) > 1): ?>
                    <div class="spf-step-nav">
                        <button type="button" class="button spf-prev-step" <?php echo $index === 0 ? 'style="visibility:hidden;"' : ''; ?>><?php _e('Previous', 'syntekpro-forms'); ?></button>
                        <button type="button" class="button button-primary spf-next-step" <?php echo ($index === count($steps)-1) ? 'style="display:none;"' : ''; ?>><?php _e('Next', 'syntekpro-forms'); ?></button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <?php
        // Add reCAPTCHA if enabled
        if (!empty($plugin_settings['recaptcha_site_key'])):
            $invisible = !empty($plugin_settings['recaptcha_invisible']);
        ?>
            <div class="spf-recaptcha">
                <div class="g-recaptcha" 
                     data-sitekey="<?php echo esc_attr($plugin_settings['recaptcha_site_key']); ?>"
                     <?php if ($invisible): ?>
                        data-size="invisible"
                        data-callback="spfRecaptchaCallback"
                     <?php endif; ?>></div>
            </div>
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <?php endif; ?>
        
        <div class="spf-form-footer spf-submit-align-<?php echo esc_attr(!empty($atts['submitAlign']) ? $atts['submitAlign'] : $submit_align); ?>">
            <button type="submit" class="spf-submit-button" <?php echo count($steps) > 1 ? 'style="display:none;"' : ''; ?>
                    data-final-label="<?php echo !empty($settings['submit_button_text']) ? esc_attr($settings['submit_button_text']) : __('Submit', 'syntekpro-forms'); ?>">
                <?php echo !empty($settings['submit_button_text']) ? esc_html($settings['submit_button_text']) : __('Submit', 'syntekpro-forms'); ?>
            </button>
        </div>
        
        <div class="spf-form-messages">
            <div class="spf-success-message" style="display: none;" data-success-msg="<?php echo isset($settings['success_message']) ? esc_attr($settings['success_message']) : ''; ?>"></div>
            <div class="spf-error-message" style="display: none;"></div>
        </div>
    </form>
</div>
