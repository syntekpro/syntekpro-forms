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

// Form access control – restrict by login or user role
$access_type = !empty($settings['access_control']) ? $settings['access_control'] : 'everyone';
if ($access_type === 'logged_in' && !is_user_logged_in()) {
    echo '<div class="spf-form-access-denied" style="background:#f8d7da;border:1px solid #f5c2c7;padding:20px;border-radius:4px;color:#842029;">';
    echo wpautop(wp_kses_post(!empty($settings['access_denied_message']) ? $settings['access_denied_message'] : __('You must be logged in to submit this form.', 'syntekpro-forms')));
    echo '</div>';
    return;
}
if ($access_type === 'role' && !empty($settings['access_roles'])) {
    $allowed_roles = array_map('trim', explode(',', $settings['access_roles']));
    $user = wp_get_current_user();
    if (!is_user_logged_in() || empty(array_intersect($allowed_roles, (array) $user->roles))) {
        echo '<div class="spf-form-access-denied" style="background:#f8d7da;border:1px solid #f5c2c7;padding:20px;border-radius:4px;color:#842029;">';
        echo wpautop(wp_kses_post(!empty($settings['access_denied_message']) ? $settings['access_denied_message'] : __('You do not have permission to submit this form.', 'syntekpro-forms')));
        echo '</div>';
        return;
    }
}

// Track form view
$wpdb->query($wpdb->prepare(
    "UPDATE {$wpdb->prefix}spf_forms SET views = views + 1 WHERE id = %d",
    $form_id
));

$growth = null;
$resume_token = isset($_GET['spf_resume']) ? sanitize_text_field(wp_unslash((string) $_GET['spf_resume'])) : '';
if (class_exists('SyntekPro_Forms_Builder')) {
    $builder_instance = SyntekPro_Forms_Builder::get_instance();
    if (method_exists($builder_instance, 'get_growth_services')) {
        $growth = $builder_instance->get_growth_services();
        if ($growth) {
            $growth->track_event($form_id, 'view', '', '');
        }
    }
}

$plugin_settings = get_option('spf_settings');

$resolve_font_family = function($font_value) {
    $font_value = is_string($font_value) ? trim(strtolower($font_value)) : 'inherit';
    $map = array(
        'inherit' => array('css' => 'inherit', 'google' => ''),
        'sans-serif' => array('css' => 'sans-serif', 'google' => ''),
        'serif' => array('css' => 'serif', 'google' => ''),
        'monospace' => array('css' => 'monospace', 'google' => ''),
        'inter' => array('css' => "'Inter', sans-serif", 'google' => 'Inter:wght@400;500;600;700'),
        'roboto' => array('css' => "'Roboto', sans-serif", 'google' => 'Roboto:wght@400;500;700'),
        'open-sans' => array('css' => "'Open Sans', sans-serif", 'google' => 'Open+Sans:wght@400;600;700'),
        'lato' => array('css' => "'Lato', sans-serif", 'google' => 'Lato:wght@400;700'),
        'montserrat' => array('css' => "'Montserrat', sans-serif", 'google' => 'Montserrat:wght@400;500;600;700'),
        'poppins' => array('css' => "'Poppins', sans-serif", 'google' => 'Poppins:wght@400;500;600;700'),
        'nunito' => array('css' => "'Nunito', sans-serif", 'google' => 'Nunito:wght@400;600;700'),
        'source-sans-pro' => array('css' => "'Source Sans Pro', sans-serif", 'google' => 'Source+Sans+3:wght@400;600;700'),
        'work-sans' => array('css' => "'Work Sans', sans-serif", 'google' => 'Work+Sans:wght@400;500;600;700'),
        'merriweather' => array('css' => "'Merriweather', serif", 'google' => 'Merriweather:wght@400;700'),
        'playfair-display' => array('css' => "'Playfair Display', serif", 'google' => 'Playfair+Display:wght@400;600;700')
    );

    if (isset($map[$font_value])) {
        return $map[$font_value];
    }

    $raw = $font_value !== '' ? $font_value : 'inherit';
    return array('css' => $raw, 'google' => '');
};

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
if (!empty($resume_token) && !empty($growth)) {
    $draft = $growth->get_draft($resume_token, $form_id);
    if (!empty($draft['draft_data']) && is_array($draft['draft_data'])) {
        $prefilled_values = array_merge($prefilled_values, $draft['draft_data']);
    }
}

// Prepare custom styles
$custom_styles = '';
$font_family = $settings['font_family'] ?? 'inherit';
$font_size = $settings['font_size'] ?? '16';
$field_padding = $settings['field_padding'] ?? '14';
$border_radius = $settings['border_radius'] ?? '6';
$primary_color = $settings['primary_color'] ?? '#0073aa';
$label_color = $settings['label_color'] ?? '#1d2327';
$bg_color = $settings['bg_color'] ?? '#ffffff';
$submit_align = $settings['submit_align'] ?? 'left';
$title_align = $settings['title_align'] ?? 'left';
$description_align = $settings['description_align'] ?? 'left';
$label_align = $settings['label_align'] ?? 'left';

$active_font = isset($atts['fontFamily']) && $atts['fontFamily'] !== '' ? $atts['fontFamily'] : $font_family;
$font_data = $resolve_font_family($active_font);
$resolved_font_family = $font_data['css'];
$google_font_family = $font_data['google'];

$custom_styles .= "font-family: {$resolved_font_family}; ";
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
    '--spf-font-family' => $resolved_font_family,
    '--spf-title-align' => !empty($atts['titleAlign']) ? $atts['titleAlign'] : $title_align,
    '--spf-desc-align' => !empty($atts['descriptionAlign']) ? $atts['descriptionAlign'] : $description_align,
    '--spf-label-align' => !empty($atts['labelAlign']) ? $atts['labelAlign'] : $label_align,
    
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

if (!empty($google_font_family)) {
    echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=' . esc_attr($google_font_family) . '&display=swap">';
}

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
            data-resume-token="<?php echo esc_attr($resume_token); ?>"
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
                    $layout_width = (!empty($field['layout_width']) && $field['layout_width'] === 'half') ? 'half' : 'full';
                ?>
                    <div class="spf-field-wrapper spf-field-width-<?php echo esc_attr($layout_width); ?> spf-field-type-<?php echo esc_attr($field['type']); ?>" 
                         data-field-id="<?php echo esc_attr($field['id']); ?>">
                        
                        <?php if (!empty($field['label'])): ?>
                            <label for="spf-field-<?php echo esc_attr($field['id']); ?>" class="spf-field-label">
                                <?php echo esc_html($field['label']); ?>
                                <?php if (!empty($field['required'])): ?>
                                    <span class="spf-required">(<?php _e('Required', 'syntekpro-forms'); ?>)</span>
                                <?php endif; ?>
                            </label>
                        <?php endif; ?>
                        
                        <?php
                        switch ($field['type']) {
                            case 'text':
                            case 'email':
                            case 'number':
                            case 'date':
                            case 'phone':
                            case 'website':
                            case 'time':
                            case 'post-title':
                            case 'post-tags':
                            case 'post-custom-field':
                                $input_type = $field['type'];
                                if ($input_type === 'website') $input_type = 'url';
                                if ($input_type === 'phone') $input_type = 'tel';
                                if (strpos($input_type, 'post-') === 0) $input_type = 'text';
                                ?>
                                <input type="<?php echo esc_attr($input_type); ?>" 
                                       id="spf-field-<?php echo esc_attr($field['id']); ?>"
                                       name="<?php echo esc_attr($field['name']); ?>"
                                       class="spf-field-input"
                                       placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
                                       value="<?php echo esc_attr($field_val); ?>"
                                       <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                                <?php
                                break;
                            
                            case 'textarea':
                            case 'post-body':
                            case 'post-excerpt':
                                $rows = ($field['type'] === 'post-body') ? 10 : 5;
                                ?>
                                <textarea id="spf-field-<?php echo esc_attr($field['id']); ?>"
                                          name="<?php echo esc_attr($field['name']); ?>"
                                          class="spf-field-textarea"
                                          placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
                                          rows="<?php echo esc_attr($rows); ?>"
                                          <?php echo !empty($field['required']) ? 'required' : ''; ?>><?php echo esc_textarea($field_val); ?></textarea>
                                <?php
                                break;
                            
                            case 'select':
                            case 'post-category':
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
                            
                            case 'multi-select':
                                $field_vals = is_array($field_val) ? $field_val : explode(',', (string)$field_val);
                                ?>
                                <select id="spf-field-<?php echo esc_attr($field['id']); ?>"
                                        name="<?php echo esc_attr($field['name']); ?>[]"
                                        class="spf-field-select"
                                        multiple
                                        style="height: 120px;"
                                        <?php echo !empty($field['required']) ? 'required' : ''; ?>
                                        >
                                    <?php if (!empty($field['options']) && is_array($field['options'])): ?>
                                        <?php foreach ($field['options'] as $option): ?>
                                            <option value="<?php echo esc_attr($option); ?>" <?php selected(in_array($option, $field_vals)); ?>
                                                >
                                                <?php echo esc_html($option); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <?php
                                break;
                            
                            case 'radio':
                            case 'multiple-choice':
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
                            case 'post-image':
                                $accept = ($field['type'] === 'post-image') ? 'image/*' : '';
                                $multiple = !empty($field['multiple']);
                                ?>
                                <input type="file" 
                                       id="spf-field-<?php echo esc_attr($field['id']); ?>"
                                       name="<?php echo esc_attr($field['name']); ?><?php echo $multiple ? '[]' : ''; ?>"
                                       class="spf-field-file"
                                       <?php if ($accept): ?>accept="<?php echo esc_attr($accept); ?>"<?php endif; ?>
                                       <?php if ($multiple): ?>multiple<?php endif; ?>
                                       <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                                <?php if ($multiple): ?>
                                    <div class="spf-file-list" data-field="<?php echo esc_attr($field['name']); ?>"></div>
                                <?php endif; ?>
                                <?php
                                break;
                            
                            case 'hidden':
                                ?>
                                <input type="hidden" 
                                       id="spf-field-<?php echo esc_attr($field['id']); ?>"
                                       name="<?php echo esc_attr($field['name']); ?>"
                                       value="<?php echo esc_attr($field_val); ?>">
                                <?php
                                break;
                            
                            case 'html':
                            case 'section':
                            case 'page':
                                ?>
                                <div class="spf-<?php echo esc_attr($field['type']); ?>-field">
                                    <?php if ($field['type'] === 'html'): ?>
                                        <?php echo wp_kses_post($field['description'] ?? ''); ?>
                                    <?php elseif ($field['type'] === 'section'): ?>
                                        <hr class="spf-section-divider">
                                    <?php elseif ($field['type'] === 'page'): ?>
                                        <div class="spf-page-break"></div>
                                    <?php endif; ?>
                                </div>
                                <?php
                                break;
                            
                            case 'name':
                                ?>
                                <div class="spf-name-field">
                                    <input type="text" 
                                           id="spf-field-<?php echo esc_attr($field['id']); ?>-first"
                                           name="<?php echo esc_attr($field['name']); ?>[first]"
                                           class="spf-field-input"
                                           placeholder="<?php _e('First Name', 'syntekpro-forms'); ?>"
                                           style="width: 48%; margin-right: 4%;"
                                           <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                                    <input type="text" 
                                           id="spf-field-<?php echo esc_attr($field['id']); ?>-last"
                                           name="<?php echo esc_attr($field['name']); ?>[last]"
                                           class="spf-field-input"
                                           placeholder="<?php _e('Last Name', 'syntekpro-forms'); ?>"
                                           style="width: 48%;"
                                           <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                                </div>
                                <?php
                                break;
                            
                            case 'address':
                                ?>
                                <div class="spf-address-field">
                                    <input type="text" 
                                           name="<?php echo esc_attr($field['name']); ?>[street]"
                                           class="spf-field-input"
                                           placeholder="<?php _e('Street Address', 'syntekpro-forms'); ?>"
                                           style="margin-bottom: 8px;"
                                           <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                                    <div style="display: flex; gap: 8px;">
                                        <input type="text" 
                                               name="<?php echo esc_attr($field['name']); ?>[city]"
                                               class="spf-field-input"
                                               placeholder="<?php _e('City', 'syntekpro-forms'); ?>"
                                               style="flex: 1;"
                                               <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                                        <input type="text" 
                                               name="<?php echo esc_attr($field['name']); ?>[state]"
                                               class="spf-field-input"
                                               placeholder="<?php _e('State', 'syntekpro-forms'); ?>"
                                               style="flex: 1;"
                                               <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                                        <input type="text" 
                                               name="<?php echo esc_attr($field['name']); ?>[zip]"
                                               class="spf-field-input"
                                               placeholder="<?php _e('ZIP', 'syntekpro-forms'); ?>"
                                               style="flex: 1;"
                                               <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                                    </div>
                                </div>
                                <?php
                                break;
                            
                            case 'image-choice':
                                ?>
                                <div class="spf-image-choice-group">
                                    <?php if (!empty($field['options']) && is_array($field['options'])): ?>
                                        <?php foreach ($field['options'] as $index2 => $option): ?>
                                            <?php 
                                            $opt_label = is_array($option) ? ($option['label'] ?? 'Choice') : $option;
                                            $opt_image = is_array($option) ? ($option['image'] ?? '') : '';
                                            ?>
                                            <label class="spf-image-choice-label" style="display: inline-block; margin: 10px; text-align: center; cursor: pointer;">
                                                <div class="spf-image-choice-img" style="width: 120px; height: 120px; border: 2px solid #ddd; border-radius: 8px; overflow: hidden; margin-bottom: 8px;">
                                                    <?php if ($opt_image): ?>
                                                        <img src="<?php echo esc_url($opt_image); ?>" alt="<?php echo esc_attr($opt_label); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: #f5f5f5;">
                                                            <span class="dashicons dashicons-format-image" style="font-size: 48px; color: #ccc;"></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <input type="radio" 
                                                       id="spf-field-<?php echo esc_attr($field['id'] . '-' . $index2); ?>"
                                                       name="<?php echo esc_attr($field['name']); ?>"
                                                       value="<?php echo esc_attr($opt_label); ?>"
                                                       <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                                                <?php echo esc_html($opt_label); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <?php
                                break;
                            
                            case 'captcha':
                                // Server-side validated captcha using transients
                                $cap_a = rand(1, 10);
                                $cap_b = rand(1, 10);
                                $cap_answer = $cap_a + $cap_b;
                                $captcha_key = 'spf_captcha_' . wp_generate_password(16, false);
                                set_transient($captcha_key, $cap_answer, 600);
                                ?>
                                <div class="spf-captcha-field" style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center;">
                                    <p><?php _e('Please verify you are human:', 'syntekpro-forms'); ?></p>
                                    <label>
                                        <?php printf(__('What is %d + %d?', 'syntekpro-forms'), $cap_a, $cap_b); ?>
                                        <input type="text" 
                                               name="<?php echo esc_attr($field['name']); ?>"
                                               class="spf-field-input"
                                               required
                                               autocomplete="off"
                                               style="width: 80px; margin-left: 10px;">
                                    </label>
                                    <input type="hidden" name="<?php echo esc_attr($field['name']); ?>_key" value="<?php echo esc_attr($captcha_key); ?>">
                                </div>
                                <?php
                                break;
                            
                            case 'list':
                                ?>
                                <div class="spf-list-field" data-field-id="<?php echo esc_attr($field['id']); ?>">
                                    <div class="spf-list-items">
                                        <input type="text" 
                                               name="<?php echo esc_attr($field['name']); ?>[]" 
                                               class="spf-field-input" 
                                               placeholder="<?php echo esc_attr($field['placeholder'] ?? 'Item 1'); ?>" 
                                               style="margin-bottom: 5px;">
                                    </div>
                                    <button type="button" class="spf-add-list-item" style="margin-top: 5px;"><?php _e('+ Add Item', 'syntekpro-forms'); ?></button>
                                </div>
                                <?php
                                break;
                            
                            case 'consent':
                                $consent_text = $field['description'] ?? __('I agree to the terms and conditions', 'syntekpro-forms');
                                ?>
                                <label class="spf-consent-label">
                                    <input type="checkbox" 
                                           id="spf-field-<?php echo esc_attr($field['id']); ?>"
                                           name="<?php echo esc_attr($field['name']); ?>"
                                           value="yes"
                                           <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                                    <?php echo wp_kses_post($consent_text); ?>
                                </label>
                                <?php
                                break;

                            case 'calculation':
                                // Read-only field whose value is computed by frontend JS
                                $formula = !empty($field['formula']) ? $field['formula'] : '';
                                ?>
                                <div class="spf-calculation-field">
                                    <input type="text"
                                           id="spf-field-<?php echo esc_attr($field['id']); ?>"
                                           name="<?php echo esc_attr($field['name']); ?>"
                                           class="spf-field-input spf-calc-result"
                                           data-formula="<?php echo esc_attr($formula); ?>"
                                           readonly
                                           value="0">
                                    <?php if (!empty($field['prefix']) || !empty($field['suffix'])): ?>
                                        <span class="spf-calc-affix">
                                            <?php echo esc_html(($field['prefix'] ?? '') . ' {value} ' . ($field['suffix'] ?? '')); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php
                                break;

                            case 'repeater':
                                // A group of sub-fields that users can add/remove
                                $sub_fields = !empty($field['sub_fields']) ? (array) $field['sub_fields'] : array(array('name' => 'item', 'label' => 'Item', 'type' => 'text'));
                                $min_rows = max(1, intval($field['min_rows'] ?? 1));
                                $max_rows = max($min_rows, intval($field['max_rows'] ?? 10));
                                ?>
                                <div class="spf-repeater-field" data-field-name="<?php echo esc_attr($field['name']); ?>" data-min="<?php echo $min_rows; ?>" data-max="<?php echo $max_rows; ?>">
                                    <div class="spf-repeater-rows">
                                        <?php for ($ri = 0; $ri < $min_rows; $ri++): ?>
                                        <div class="spf-repeater-row" data-row-index="<?php echo $ri; ?>">
                                            <?php foreach ($sub_fields as $sf): ?>
                                                <div class="spf-repeater-cell">
                                                    <label><?php echo esc_html($sf['label'] ?? $sf['name']); ?></label>
                                                    <input type="text"
                                                           name="<?php echo esc_attr($field['name'] . '[' . $ri . '][' . ($sf['name'] ?? 'item') . ']'); ?>"
                                                           class="spf-field-input"
                                                           placeholder="<?php echo esc_attr($sf['placeholder'] ?? ''); ?>">
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if ($min_rows < $max_rows): ?>
                                                <button type="button" class="spf-repeater-remove" style="display:<?php echo $ri === 0 ? 'none' : 'inline-block'; ?>;">&times;</button>
                                            <?php endif; ?>
                                        </div>
                                        <?php endfor; ?>
                                    </div>
                                    <?php if ($min_rows < $max_rows): ?>
                                        <button type="button" class="spf-repeater-add button"><?php _e('+ Add Row', 'syntekpro-forms'); ?></button>
                                    <?php endif; ?>
                                </div>
                                <?php
                                break;

                            case 'signature':
                                ?>
                                <div class="spf-signature-field" data-field-name="<?php echo esc_attr($field['name']); ?>">
                                    <canvas class="spf-signature-canvas" width="400" height="150" style="border:1px solid #ddd;border-radius:4px;cursor:crosshair;touch-action:none;"></canvas>
                                    <input type="hidden" name="<?php echo esc_attr($field['name']); ?>" class="spf-signature-data">
                                    <div style="margin-top:6px;">
                                        <button type="button" class="spf-signature-clear button"><?php _e('Clear', 'syntekpro-forms'); ?></button>
                                    </div>
                                </div>
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

        <?php if (count($steps) > 1): ?>
            <div class="spf-save-draft-wrap" style="margin-top:12px;">
                <button type="button" class="button spf-save-draft"><?php _e('Save & Resume Later', 'syntekpro-forms'); ?></button>
                <div class="spf-draft-message" style="display:none;margin-top:8px;"></div>
            </div>
        <?php endif; ?>
        
        <?php
        // Per-form reCAPTCHA toggle: form settings can override global
        $recaptcha_enabled_for_form = true; // default: follow global setting
        if (isset($settings['recaptcha_enabled'])) {
            $recaptcha_enabled_for_form = !empty($settings['recaptcha_enabled']);
        }

        // Add reCAPTCHA if enabled globally AND for this form
        if (!empty($plugin_settings['recaptcha_site_key']) && $recaptcha_enabled_for_form):
            $captcha_provider = !empty($plugin_settings['captcha_provider']) ? $plugin_settings['captcha_provider'] : 'recaptcha';

            // Only render reCAPTCHA widget if provider is recaptcha (Turnstile/hCaptcha handled by addon)
            if ($captcha_provider === 'recaptcha' || empty($captcha_provider)):
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
        <?php endif; endif; ?>

        <?php
        // Action hook for addons to inject captcha widgets (Turnstile, hCaptcha, etc.)
        do_action('syntekpro_forms_after_fields', $form_id, $settings, $plugin_settings);
        ?>
        
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
