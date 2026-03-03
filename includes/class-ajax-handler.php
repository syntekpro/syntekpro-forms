<?php
/**
 * AJAX handlers extracted from the main SyntekPro Forms class.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SyntekPro_Forms_Ajax_Handler {

    /** @var SyntekPro_Forms_Builder */
    private $builder;

    /** @var SyntekPro_Forms_Spam_Filter */
    private $spam_filter;

    /** @var SyntekPro_Forms_Growth_Services */
    private $growth_services;

    public function __construct($builder, $spam_filter, $growth_services = null) {
        $this->builder = $builder;
        $this->spam_filter = $spam_filter;
        $this->growth_services = $growth_services;
    }

    /**
     * Hook the AJAX actions handled by this class.
     */
    public function register_hooks() {
        add_action('wp_ajax_spf_save_form', array($this, 'ajax_save_form'));
        add_action('wp_ajax_spf_delete_form', array($this, 'ajax_delete_form'));
        add_action('wp_ajax_spf_duplicate_form', array($this, 'ajax_duplicate_form'));
        add_action('wp_ajax_spf_get_form', array($this, 'ajax_get_form'));
        add_action('wp_ajax_nopriv_spf_submit_form', array($this, 'ajax_submit_form'));
        add_action('wp_ajax_spf_submit_form', array($this, 'ajax_submit_form'));
        add_action('wp_ajax_spf_create_form_from_template', array($this, 'ajax_create_form_from_template'));
        add_action('wp_ajax_spf_export_settings', array($this, 'ajax_export_settings'));
        add_action('wp_ajax_spf_import_settings', array($this, 'ajax_import_settings'));
        add_action('wp_ajax_spf_preview_form', array($this, 'ajax_preview_form'));
        add_action('wp_ajax_spf_bulk_action_forms', array($this, 'ajax_bulk_action_forms'));
        add_action('wp_ajax_spf_get_form_settings', array($this, 'ajax_get_form_settings'));
        add_action('wp_ajax_spf_trash_form', array($this, 'ajax_trash_form'));
        add_action('wp_ajax_spf_get_form_preview', array($this, 'ajax_get_form_preview'));
        // NEW: Form Builder Page AJAX Actions
        add_action('wp_ajax_spf_get_recent_forms', array($this, 'ajax_get_recent_forms'));
        add_action('wp_ajax_spf_get_posts_for_embed', array($this, 'ajax_get_posts_for_embed'));
        add_action('wp_ajax_spf_insert_form_to_post', array($this, 'ajax_insert_form_to_post'));
        add_action('wp_ajax_spf_create_post_with_form', array($this, 'ajax_create_post_with_form'));
        add_action('wp_ajax_nopriv_spf_save_draft', array($this, 'ajax_save_draft'));
        add_action('wp_ajax_spf_save_draft', array($this, 'ajax_save_draft'));
        add_action('wp_ajax_nopriv_spf_get_draft', array($this, 'ajax_get_draft'));
        add_action('wp_ajax_spf_get_draft', array($this, 'ajax_get_draft'));
        add_action('wp_ajax_nopriv_spf_track_analytics', array($this, 'ajax_track_analytics'));
        add_action('wp_ajax_spf_track_analytics', array($this, 'ajax_track_analytics'));
    }

    public function ajax_save_form() {
        check_ajax_referer('spf_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'syntekpro-forms'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'spf_forms';

        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';

        if (empty($title)) {
            wp_send_json_error(__('Form title is required', 'syntekpro-forms'));
        }

        $fields = isset($_POST['fields']) ? wp_unslash((string) $_POST['fields']) : '';
        $settings = isset($_POST['settings']) ? wp_unslash((string) $_POST['settings']) : '';

        if (empty($fields)) {
            wp_send_json_error(__('Form fields are required', 'syntekpro-forms'));
        }

        $data = array(
            'title'       => $title,
            'description' => $description,
            'fields'      => $fields,
            'settings'    => $settings,
        );

        $format = array('%s', '%s', '%s', '%s');

        if ($form_id > 0) {
            $wpdb->update($table, $data, array('id' => $form_id), $format, array('%d'));
        } else {
            $wpdb->insert($table, $data, $format);
            $form_id = $wpdb->insert_id;
        }

        if (!empty($wpdb->last_error)) {
            wp_send_json_error('Database error: ' . $wpdb->last_error);
        }

        wp_send_json_success(array(
            'form_id' => $form_id,
            'message' => __('Form saved successfully!', 'syntekpro-forms'),
        ));
    }

    public function ajax_delete_form() {
        check_ajax_referer('spf_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $form_id = intval($_POST['form_id']);

        $wpdb->query('START TRANSACTION');

        $entries_result = $wpdb->delete(
            $wpdb->prefix . 'spf_entries',
            array('form_id' => $form_id),
            array('%d')
        );

        if ($entries_result === false) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error('Failed to delete form entries');
        }

        $form_result = $wpdb->delete(
            $wpdb->prefix . 'spf_forms',
            array('id' => $form_id),
            array('%d')
        );

        if ($form_result === false || $form_result === 0) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error('Failed to delete form');
        }

        $wpdb->query('COMMIT');
        wp_send_json_success('Form and related entries deleted successfully');
    }

    public function ajax_duplicate_form() {
        check_ajax_referer('spf_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'syntekpro-forms'));
        }

        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        if ($form_id <= 0) {
            wp_send_json_error(__('Invalid form ID.', 'syntekpro-forms'));
        }

        global $wpdb;
        $form = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spf_forms WHERE id = %d",
            $form_id
        ));

        if (!$form) {
            wp_send_json_error(__('Form not found.', 'syntekpro-forms'));
        }

        $base_title = !empty($form->title) ? $form->title : __('Untitled Form', 'syntekpro-forms');
        $new_title = sprintf(__('%s (Copy)', 'syntekpro-forms'), $base_title);
        $counter = 2;
        $max_attempts = 100;

        while ($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}spf_forms WHERE title = %s",
            $new_title
        ))) {
            if ($counter > $max_attempts) {
                wp_send_json_error(__('Could not generate a unique title.', 'syntekpro-forms'));
            }

            $new_title = sprintf(__('%s (Copy %d)', 'syntekpro-forms'), $base_title, $counter);
            $counter++;
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'spf_forms',
            array(
                'title'       => $new_title,
                'description' => $form->description,
                'fields'      => $form->fields,
                'settings'    => $form->settings,
                'status'      => $form->status,
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            wp_send_json_error(__('Failed to duplicate form.', 'syntekpro-forms'));
        }

        $new_form_id = $wpdb->insert_id;

        wp_send_json_success(array(
            'form_id'  => $new_form_id,
            'redirect' => admin_url('admin.php?page=syntekpro-forms-new&form_id=' . $new_form_id),
        ));
    }

    public function ajax_get_form() {
        check_ajax_referer('spf_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $form_id = intval($_POST['form_id']);

        $form = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spf_forms WHERE id = %d",
            $form_id
        ));

        if ($form) {
            $form->fields = json_decode($form->fields, true);
            $form->settings = json_decode($form->settings, true);
            wp_send_json_success($form);
        }

        wp_send_json_error('Form not found');
    }

    public function ajax_submit_form() {
        check_ajax_referer('spf_frontend_nonce', 'nonce');

        $settings = get_option('spf_settings');
        $client_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

        $lock_key = '';
        $rate_limit_seconds = isset($settings['rate_limit_seconds']) ? absint($settings['rate_limit_seconds']) : 0;
        if (!empty($settings['rate_limit_enabled']) && $rate_limit_seconds > 0 && !empty($client_ip)) {
            $lock_key = $this->spam_filter->get_rate_limit_lock_key($client_ip);

            if ($this->spam_filter->has_rate_limit_lock($lock_key)) {
                $this->send_submission_error(sprintf(__('Please wait %d seconds before submitting again.', 'syntekpro-forms'), $rate_limit_seconds));
            }
        }

        if (!empty($settings['recaptcha_site_key']) && !empty($settings['recaptcha_secret_key'])) {
            $recaptcha_response = isset($_POST['g-recaptcha-response']) ? sanitize_text_field($_POST['g-recaptcha-response']) : '';
            if (empty($recaptcha_response) && isset($_POST['form_data']) && is_array($_POST['form_data']) && isset($_POST['form_data']['g-recaptcha-response'])) {
                $recaptcha_response = sanitize_text_field($_POST['form_data']['g-recaptcha-response']);
            }

            if (!$this->spam_filter->verify_recaptcha($recaptcha_response, $settings['recaptcha_secret_key'])) {
                $this->send_submission_error(__('reCAPTCHA verification failed. Please try again.', 'syntekpro-forms'));
            }
        }

        if (isset($settings['enable_honeypot']) && $settings['enable_honeypot']) {
            if (!empty($_POST['spf_hp_field'])) {
                $this->send_submission_error(__('Spam detected', 'syntekpro-forms'));
            }
        }

        global $wpdb;
        $form_id = intval($_POST['form_id']);

        $raw_form_data = isset($_POST['form_data']) && is_array($_POST['form_data']) ? $_POST['form_data'] : $_POST;
        unset($raw_form_data['action'], $raw_form_data['nonce'], $raw_form_data['form_id'], $raw_form_data['g-recaptcha-response']);

        $form = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spf_forms WHERE id = %d",
            $form_id
        ));

        if (!$form) {
            $this->send_submission_error(__('Form not found', 'syntekpro-forms'));
        }

        $form_settings = json_decode($form->settings, true);
        if (!is_array($form_settings)) {
            $form_settings = array();
        }

        $raw_form_data = apply_filters(
            'syntekpro_forms_submission_raw_data',
            $raw_form_data,
            $form,
            $form_id,
            $form_settings,
            $settings
        );

        if (!is_array($raw_form_data)) {
            $this->send_submission_error(__('Invalid submission payload.', 'syntekpro-forms'));
        }

        $availability = $this->builder->get_form_availability_state($form_settings, $form_id);
        if ($availability['status'] !== 'open') {
            $this->send_submission_error($availability['message']);
        }

        $fields = json_decode($form->fields, true);
        if (!is_array($fields)) {
            $this->send_submission_error(__('Form configuration invalid', 'syntekpro-forms'));
        }

        $pre_validate = apply_filters(
            'syntekpro_forms_submission_pre_validate',
            true,
            $raw_form_data,
            $form,
            $fields,
            $form_settings,
            $form_id,
            $settings
        );

        if (is_wp_error($pre_validate)) {
            $this->send_submission_error($pre_validate->get_error_message());
        }

        if ($pre_validate === false) {
            $this->send_submission_error(__('Submission blocked by validation rules.', 'syntekpro-forms'));
        }

        $sanitized_data = array();

        foreach ($fields as $field) {
            $field_name = $field['name'] ?? '';
            $field_type = $field['type'] ?? 'text';
            $is_required = !empty($field['required']);

            $value = $raw_form_data[$field_name] ?? null;

            if ($field_type === 'checkbox') {
                $value = isset($raw_form_data[$field_name]) ? (array) $raw_form_data[$field_name] : array();
            }

            $value = apply_filters(
                'syntekpro_forms_submission_field_value',
                $value,
                $field,
                $raw_form_data,
                $form,
                $form_id,
                $form_settings,
                $settings
            );

            if ($field_type === 'file') {
                $file_array = $_FILES[$field_name] ?? null;

                if ($is_required && (empty($file_array) || empty($file_array['tmp_name']))) {
                    $this->send_submission_error(sprintf(__('Field "%s" is required.', 'syntekpro-forms'), $field_name));
                }

                if (!empty($file_array) && !empty($file_array['tmp_name'])) {
                    $file_handler = SPF_file_handler();

                    $type_check = $file_handler->validate_file_type($file_array);
                    if (is_wp_error($type_check)) {
                        $this->send_submission_error($type_check->get_error_message());
                    }

                    $size_check = $file_handler->validate_file_size($file_array);
                    if (is_wp_error($size_check)) {
                        $this->send_submission_error($size_check->get_error_message());
                    }

                    $uploaded = $file_handler->handle_upload($file_array, $field_name);
                    if (is_wp_error($uploaded)) {
                        $this->send_submission_error($uploaded->get_error_message());
                    }

                    $sanitized_data[$field_name] = sprintf(
                        '%s (%s)',
                        sanitize_text_field(basename((string) $uploaded['file'])),
                        esc_url_raw((string) $uploaded['url'])
                    );
                }

                continue;
            }

            if ($is_required && (empty($value) && $value !== '0')) {
                $this->send_submission_error(sprintf(__('Field "%s" is required.', 'syntekpro-forms'), $field_name));
            }

            if ($value === null) {
                continue;
            }

            $sanitized_data[$field_name] = $this->sanitize_field_value($field_type, $value);
        }

        $sanitized_data = apply_filters(
            'syntekpro_forms_submission_sanitized_data',
            $sanitized_data,
            $raw_form_data,
            $fields,
            $form,
            $form_settings,
            $form_id,
            $settings
        );

        if (is_wp_error($sanitized_data)) {
            $this->send_submission_error($sanitized_data->get_error_message());
        }

        if (!is_array($sanitized_data)) {
            $this->send_submission_error(__('Submission data processing failed.', 'syntekpro-forms'));
        }

        $ip_address_logged = '';
        $user_agent = '';
        if (!empty($settings['enable_ip_logging'])) {
            $ip_address_logged = !empty($settings['anonymize_ip'])
                ? $this->builder->anonymize_ip_address($client_ip)
                : $client_ip;
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        }

        if (!empty($settings['enable_akismet']) && $this->spam_filter->check_akismet_spam($form, $sanitized_data, $client_ip, $user_agent)) {
            $this->send_submission_error(__('Submission flagged as spam.', 'syntekpro-forms'));
        }

        do_action(
            'syntekpro_forms_submission_before_insert',
            $form_id,
            $sanitized_data,
            $raw_form_data,
            $form,
            $form_settings,
            $settings
        );

        $result = $wpdb->insert(
            $wpdb->prefix . 'spf_entries',
            array(
                'form_id'   => $form_id,
                'entry_data'=> wp_json_encode($sanitized_data),
                'user_id'   => get_current_user_id(),
                'ip_address'=> $ip_address_logged,
                'user_agent'=> $user_agent,
            ),
            array('%d', '%s', '%d', '%s', '%s')
        );

        $payment_summary = array();

        if ($result) {
            $entry_id = $wpdb->insert_id;

            if ($this->growth_services) {
                $payment_summary = $this->growth_services->build_payment_summary($sanitized_data, $fields, $form_settings, $settings);
                if (!empty($payment_summary['enabled'])) {
                    $sanitized_data['spf_payment'] = $payment_summary;
                    $wpdb->update(
                        $wpdb->prefix . 'spf_entries',
                        array('entry_data' => wp_json_encode($sanitized_data)),
                        array('id' => $entry_id),
                        array('%s'),
                        array('%d')
                    );
                }
            }

            do_action(
                'syntekpro_forms_submission_after_insert',
                $entry_id,
                $form_id,
                $sanitized_data,
                $raw_form_data,
                $form,
                $form_settings,
                $settings
            );

            if (!empty($lock_key) && !empty($settings['rate_limit_enabled']) && $rate_limit_seconds > 0) {
                $this->spam_filter->acquire_rate_limit_lock($lock_key, $rate_limit_seconds);
            }

            delete_transient('spf_count_' . $form_id);

            do_action(
                'syntekpro_forms_submission_before_notifications',
                $entry_id,
                $form_id,
                $sanitized_data,
                $raw_form_data,
                $form,
                $form_settings,
                $settings
            );

            SPF_email_templates()->send_admin_notification($form, $sanitized_data, $entry_id);
            SPF_email_templates()->send_user_confirmation($form, $sanitized_data);

            $this->builder->trigger_form_webhooks($form, $sanitized_data, $entry_id, $form_settings);

            if ($this->growth_services) {
                $this->growth_services->dispatch_connectors($form, $sanitized_data, $entry_id, $form_settings, $settings);
                $this->growth_services->track_event($form_id, 'complete', '', isset($_POST['spf_session_id']) ? sanitize_text_field((string) $_POST['spf_session_id']) : '');
            }

            do_action(
                'syntekpro_forms_submission_after_notifications',
                $entry_id,
                $form_id,
                $sanitized_data,
                $raw_form_data,
                $form,
                $form_settings,
                $settings
            );

            $success_payload = $this->builder->build_success_payload($form_settings);
            if (!empty($payment_summary) && is_array($payment_summary)) {
                $success_payload['payment'] = $payment_summary;
            }
            $success_payload = apply_filters(
                'syntekpro_forms_submission_success_payload',
                $success_payload,
                $entry_id,
                $form_id,
                $sanitized_data,
                $raw_form_data,
                $form,
                $form_settings,
                $settings
            );

            wp_send_json_success($success_payload);
        }

        $this->send_submission_error(__('Failed to submit form', 'syntekpro-forms'));
    }

    public function ajax_create_form_from_template() {
        check_ajax_referer('spf_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'syntekpro-forms'));
        }

        $template_id = isset($_POST['template_id']) ? sanitize_text_field($_POST['template_id']) : '';
        $templates = SyntekPro_Forms_Templates::get_templates();

        if (!isset($templates[$template_id])) {
            wp_send_json_error(__('Invalid template selected', 'syntekpro-forms'));
        }

        $template = $templates[$template_id];

        global $wpdb;
        $table = $wpdb->prefix . 'spf_forms';

        $result = $wpdb->insert(
            $table,
            array(
                'title'       => $template['title'],
                'description' => $template['description'],
                'fields'      => wp_json_encode($template['fields']),
                'settings'    => wp_json_encode($template['settings']),
            ),
            array('%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            wp_send_json_error('Database error: ' . $wpdb->last_error);
        }

        $form_id = $wpdb->insert_id;

        wp_send_json_success(array(
            'form_id'  => $form_id,
            'redirect' => admin_url('admin.php?page=syntekpro-forms-new&form_id=' . $form_id),
        ));
    }

    public function ajax_export_settings() {
        check_ajax_referer('spf_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $settings = get_option('spf_settings');
        if (is_array($settings)) {
            unset($settings['recaptcha_secret_key']);
        }
        wp_send_json_success($settings);
    }

    public function ajax_import_settings() {
        check_ajax_referer('spf_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $import_data = isset($_POST['import_data']) ? json_decode(wp_unslash((string) $_POST['import_data']), true) : null;
        if (!$import_data || !is_array($import_data)) {
            wp_send_json_error(__('Invalid import data', 'syntekpro-forms'));
        }

        $allowed_settings = array(
            'recaptcha_site_key'          => 'text',
            'recaptcha_secret_key'        => 'text',
            'recaptcha_invisible'         => 'bool',
            'default_theme'               => 'text',
            'from_email'                  => 'email',
            'from_name'                   => 'text',
            'delete_entries_on_uninstall' => 'bool',
            'enable_ip_logging'           => 'bool',
            'anonymize_ip'                => 'bool',
            'data_retention_days'         => 'int',
            'trash_retention_days'        => 'int',
            'rate_limit_enabled'          => 'bool',
            'rate_limit_seconds'          => 'int',
            'enable_honeypot'             => 'bool',
            'enable_akismet'              => 'bool',
            'enable_dashboard_widget'     => 'bool',
            'enable_toolbar_menu'         => 'bool',
            'no_conflict_mode'            => 'bool',
            'rest_api_enabled'            => 'bool',
            'stripe_publishable_key'      => 'text',
            'stripe_secret_key'           => 'text',
            'payment_currency'            => 'text',
            'automation_zapier_url'       => 'text',
            'automation_make_url'         => 'text',
            'mailchimp_api_key'           => 'text',
            'mailchimp_audience_id'       => 'text',
            'hubspot_private_token'       => 'text',
            'hubspot_default_list_id'     => 'text',
        );

        $sanitized = array();
        foreach ($allowed_settings as $key => $type) {
            if (!array_key_exists($key, $import_data)) {
                continue;
            }

            $value = $import_data[$key];
            switch ($type) {
                case 'email':
                    $sanitized[$key] = sanitize_email((string) $value);
                    break;
                case 'int':
                    $sanitized[$key] = absint($value);
                    break;
                case 'bool':
                    $sanitized[$key] = absint($value) > 0 ? 1 : 0;
                    break;
                case 'text':
                default:
                    $sanitized[$key] = sanitize_text_field((string) $value);
                    break;
            }
        }

        if (empty($sanitized)) {
            wp_send_json_error(__('No valid settings found in import file.', 'syntekpro-forms'));
        }

        $current_settings = get_option('spf_settings', array());
        update_option('spf_settings', array_merge($current_settings, $sanitized));
        wp_send_json_success(__('Settings imported successfully', 'syntekpro-forms'));
    }

    public function ajax_preview_form() {
        check_ajax_referer('spf_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;

        if (!$form_id) {
            wp_die('Invalid Form ID');
        }

        $this->builder->enqueue_frontend_scripts();

        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php _e('Form Preview', 'syntekpro-forms'); ?></title>
            <?php wp_head(); ?>
            <style>
                body { background: #f0f2f5; padding: 50px 20px; }
                .spf-preview-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .spf-preview-header { margin-bottom: 30px; text-align: center; border-bottom: 1px solid #eee; padding-bottom: 20px; }
                .spf-preview-label { background: #0073aa; color: #fff; padding: 5px 15px; border-radius: 20px; font-size: 12px; text-transform: uppercase; font-weight: 600; }
            </style>
        </head>
        <body <?php body_class(); ?>>
            <div class="spf-preview-container">
                <div class="spf-preview-header">
                    <span class="spf-preview-label"><?php _e('Form Preview', 'syntekpro-forms'); ?></span>
                </div>
                <?php echo do_shortcode('[syntekpro_form id="' . $form_id . '"]'); ?>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
        exit;
    }

    private function sanitize_field_value($field_type, $value) {
        if (is_array($value)) {
            return array_map('sanitize_text_field', $value);
        }

        switch ($field_type) {
            case 'email':
                return sanitize_email($value);
            case 'textarea':
                return sanitize_textarea_field($value);
            case 'number':
                return is_numeric($value) ? $value : '';
            case 'date':
            case 'text':
            default:
                return sanitize_text_field($value);
        }
    }

    private function send_submission_error($message) {
        wp_send_json_error($message);
    }

    /**
     * AJAX handler for bulk actions on forms
     */
    public function ajax_bulk_action_forms() {
        check_ajax_referer('spf_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'syntekpro-forms'));
        }

        $action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';
        $form_ids = isset($_POST['form_ids']) ? array_map('intval', (array)$_POST['form_ids']) : [];

        if (empty($form_ids)) {
            wp_send_json_error(__('No forms selected', 'syntekpro-forms'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'spf_forms';
        $ids_placeholder = implode(',', array_fill(0, count($form_ids), '%d'));

        switch ($action) {
            case 'mark_active':
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$table} SET status = 'active' WHERE id IN ({$ids_placeholder})",
                    ...$form_ids
                ));
                wp_send_json_success(__('Forms marked as active', 'syntekpro-forms'));
                break;

            case 'mark_inactive':
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$table} SET status = 'inactive' WHERE id IN ({$ids_placeholder})",
                    ...$form_ids
                ));
                wp_send_json_success(__('Forms marked as inactive', 'syntekpro-forms'));
                break;

            case 'reset_views':
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$table} SET views = 0 WHERE id IN ({$ids_placeholder})",
                    ...$form_ids
                ));
                wp_send_json_success(__('Views reset', 'syntekpro-forms'));
                break;

            case 'delete_entries':
                $entries_table = $wpdb->prefix . 'spf_entries';
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$entries_table} WHERE form_id IN ({$ids_placeholder})",
                    ...$form_ids
                ));
                wp_send_json_success(__('Entries deleted', 'syntekpro-forms'));
                break;

            case 'delete_forms':
                $entries_table = $wpdb->prefix . 'spf_entries';
                $wpdb->query('START TRANSACTION');

                $entries_result = $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$entries_table} WHERE form_id IN ({$ids_placeholder})",
                    ...$form_ids
                ));

                if ($entries_result === false) {
                    $wpdb->query('ROLLBACK');
                    wp_send_json_error(__('Failed to delete form entries', 'syntekpro-forms'));
                }

                $forms_result = $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$table} WHERE id IN ({$ids_placeholder})",
                    ...$form_ids
                ));

                if ($forms_result === false) {
                    $wpdb->query('ROLLBACK');
                    wp_send_json_error(__('Failed to delete forms', 'syntekpro-forms'));
                }

                $wpdb->query('COMMIT');
                wp_send_json_success(__('Forms deleted permanently', 'syntekpro-forms'));
                break;

            case 'trash':
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$table} SET status = 'trash' WHERE id IN ({$ids_placeholder})",
                    ...$form_ids
                ));
                wp_send_json_success(__('Forms moved to trash', 'syntekpro-forms'));
                break;

            default:
                wp_send_json_error(__('Invalid action', 'syntekpro-forms'));
        }
    }

    /**
     * AJAX handler to get form settings
     */
    public function ajax_get_form_settings() {
        check_ajax_referer('spf_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'syntekpro-forms'));
        }

        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;

        if (!$form_id) {
            wp_send_json_error(__('Invalid form ID', 'syntekpro-forms'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'spf_forms';
        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $form_id));

        if (!$form) {
            wp_send_json_error(__('Form not found', 'syntekpro-forms'));
        }

        $settings = json_decode($form->settings, true);
        if (!is_array($settings)) {
            $settings = [];
        }

        $settings['status'] = $form->status;
        $settings['title'] = $form->title;

        wp_send_json_success($settings);
    }

    /**
     * AJAX handler to trash a form
     */
    public function ajax_trash_form() {
        check_ajax_referer('spf_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'syntekpro-forms'));
        }

        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;

        if (!$form_id) {
            wp_send_json_error(__('Invalid form ID', 'syntekpro-forms'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'spf_forms';
        
        $result = $wpdb->update(
            $table,
            ['status' => 'trash'],
            ['id' => $form_id],
            ['%s'],
            ['%d']
        );

        if ($result === false) {
            wp_send_json_error(__('Failed to trash form', 'syntekpro-forms'));
        }

        wp_send_json_success(__('Form moved to trash', 'syntekpro-forms'));
    }

    /**
     * AJAX handler to get form preview HTML
     */
    public function ajax_get_form_preview() {
        check_ajax_referer('spf_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'syntekpro-forms'));
        }

        $form_id = isset($_POST['form_id']) ? sanitize_text_field($_POST['form_id']) : 0;
        $is_template = isset($_POST['is_template']) && ($_POST['is_template'] === 'true' || $_POST['is_template'] === true || $_POST['is_template'] === '1');

        if (!$form_id) {
            wp_send_json_error(__('Invalid form ID', 'syntekpro-forms'));
        }

        // If it's a template preview, generate preview from template
        if ($is_template) {
            if (!class_exists('SyntekPro_Forms_Templates')) {
                require_once plugin_dir_path(__FILE__) . 'admin/templates.php';
            }
            
            $templates = SyntekPro_Forms_Templates::get_templates();
            $template_id = $form_id; // In this case, form_id is actually template_id
            
            if (!isset($templates[$template_id])) {
                wp_send_json_error(__('Template not found', 'syntekpro-forms'));
            }
            
            $template = $templates[$template_id];
            
            // Generate preview HTML from template structure
            ob_start();
            ?>
            <div class="spf-form-preview-wrapper">
                <div class="spf-form-container spf-theme-<?php echo esc_attr($template['settings']['theme'] ?? 'modern'); ?>" style="max-width: 600px; margin: 0 auto;">
                    <form class="spf-form spf-preview-form" style="padding: 30px; background: <?php echo esc_attr($template['settings']['bg_color'] ?? '#ffffff'); ?>; border-radius: <?php echo esc_attr($template['settings']['border_radius'] ?? '8'); ?>px;">
                        <h2 style="margin-bottom: 20px; color: <?php echo esc_attr($template['settings']['primary_color'] ?? '#0073aa'); ?>;">
                            <?php echo esc_html($template['title']); ?>
                        </h2>
                        <p style="margin-bottom: 30px; color: #666;">
                            <?php echo esc_html($template['description']); ?>
                        </p>
                        
                        <?php foreach ($template['fields'] as $field): ?>
                            <div class="spf-field-group" style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #333;">
                                    <?php echo esc_html($field['label']); ?>
                                    <?php if (isset($field['required']) && $field['required']): ?>
                                        <span style="color: <?php echo esc_attr($template['settings']['primary_color'] ?? '#dc3545'); ?>;">*</span>
                                    <?php endif; ?>
                                </label>
                                
                                <?php
                                $field_style = 'width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;';
                                
                                switch ($field['type']):
                                    case 'textarea':
                                        ?>
                                        <textarea 
                                            name="<?php echo esc_attr($field['name']); ?>" 
                                            placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
                                            rows="4"
                                            style="<?php echo $field_style; ?>"
                                            disabled
                                        ></textarea>
                                        <?php
                                        break;
                                    
                                    case 'select':
                                        ?>
                                        <select name="<?php echo esc_attr($field['name']); ?>" style="<?php echo $field_style; ?>" disabled>
                                            <option value="">-- Select --</option>
                                            <?php if (isset($field['options'])): ?>
                                                <?php foreach ($field['options'] as $option): ?>
                                                    <option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                        <?php
                                        break;
                                    
                                    case 'radio':
                                        ?>
                                        <div style="display: flex; flex-direction: column; gap: 8px;">
                                            <?php if (isset($field['options'])): ?>
                                                <?php foreach ($field['options'] as $option): ?>
                                                    <label style="display: flex; align-items: center; font-weight: normal;">
                                                        <input 
                                                            type="radio" 
                                                            name="<?php echo esc_attr($field['name']); ?>" 
                                                            value="<?php echo esc_attr($option); ?>"
                                                            style="margin-right: 8px;"
                                                            disabled
                                                        >
                                                        <?php echo esc_html($option); ?>
                                                    </label>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                        break;
                                    
                                    case 'checkbox':
                                        ?>
                                        <div style="display: flex; flex-direction: column; gap: 8px;">
                                            <?php if (isset($field['options'])): ?>
                                                <?php foreach ($field['options'] as $option): ?>
                                                    <label style="display: flex; align-items: center; font-weight: normal;">
                                                        <input 
                                                            type="checkbox" 
                                                            name="<?php echo esc_attr($field['name']); ?>[]" 
                                                            value="<?php echo esc_attr($option); ?>"
                                                            style="margin-right: 8px;"
                                                            disabled
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
                                        <input 
                                            type="file" 
                                            name="<?php echo esc_attr($field['name']); ?>"
                                            style="<?php echo $field_style; ?>"
                                            disabled
                                        >
                                        <?php
                                        break;
                                    
                                    default:
                                        // text, email, number, date, tel, url
                                        ?>
                                        <input 
                                            type="<?php echo esc_attr($field['type']); ?>" 
                                            name="<?php echo esc_attr($field['name']); ?>" 
                                            placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
                                            style="<?php echo $field_style; ?>"
                                            disabled
                                        >
                                        <?php
                                        break;
                                endswitch;
                                ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <div style="text-align: <?php echo esc_attr($template['settings']['submit_align'] ?? 'left'); ?>; margin-top: 30px;">
                            <button 
                                type="button" 
                                class="spf-submit-btn"
                                style="padding: 12px 30px; 
                                       background: <?php echo esc_attr($template['settings']['primary_color'] ?? '#0073aa'); ?>; 
                                       color: white; 
                                       border: none; 
                                       border-radius: 4px; 
                                       font-size: 16px; 
                                       font-weight: 500;
                                       cursor: not-allowed;
                                       opacity: 0.8;"
                                disabled
                            >
                                <?php echo esc_html($template['settings']['submit_button_text'] ?? 'Submit'); ?>
                            </button>
                        </div>
                        
                        <p style="margin-top: 15px; text-align: center; font-size: 13px; color: #999; font-style: italic;">
                            <?php _e('This is a preview. The form is not functional.', 'syntekpro-forms'); ?>
                        </p>
                    </form>
                </div>
            </div>
            <?php
            $html = ob_get_clean();
            
            wp_send_json_success(array('html' => $html));
        }

        // Regular form preview
        $form_id = intval($form_id);
        global $wpdb;
        $table = $wpdb->prefix . 'spf_forms';
        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $form_id));

        if (!$form) {
            wp_send_json_error(__('Form not found', 'syntekpro-forms'));
        }

        // Capture the form output
        ob_start();
        echo do_shortcode('[syntekpro_form id="' . $form_id . '"]');
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }

    /**
     * NEW: Get recent forms for dropdown
     */
    public function ajax_get_recent_forms() {
        check_ajax_referer('spf_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'syntekpro-forms'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'spf_forms';
        
        $forms = $wpdb->get_results($wpdb->prepare(
            "SELECT id, title FROM {$table} WHERE status IN (%s, %s) ORDER BY updated_at DESC LIMIT %d",
            'active',
            'inactive',
            15
        ));

        wp_send_json_success($forms ?: array());
    }

    /**
     * NEW: Insert form into existing post/page
     */
    public function ajax_insert_form_to_post() {
        check_ajax_referer('spf_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'syntekpro-forms'));
        }

        $post_id = intval($_POST['post_id']);
        $form_id = intval($_POST['form_id']);

        if (!$post_id || !$form_id) {
            wp_send_json_error(__('Missing required fields', 'syntekpro-forms'));
        }

        // Get the post
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(__('Post not found', 'syntekpro-forms'));
        }

        // Append shortcode to post content
        $shortcode = '[syntekpro_form id="' . $form_id . '"]';
        $new_content = $post->post_content . "\n\n" . $shortcode;

        // Update post
        $update_result = wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $new_content
        ));

        if (is_wp_error($update_result)) {
            wp_send_json_error(__('Error updating post', 'syntekpro-forms'));
        }

        $edit_url = get_edit_post_link($post_id, 'raw');
        wp_send_json_success(array('edit_url' => $edit_url));
    }

    /**
     * NEW: Get posts/pages for embed dropdown
     */
    public function ajax_get_posts_for_embed() {
        check_ajax_referer('spf_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'syntekpro-forms'));
        }

        $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 'page';
        
        if (!in_array($post_type, array('post', 'page'))) {
            wp_send_json_error(__('Invalid post type', 'syntekpro-forms'));
        }

        $args = array(
            'post_type' => $post_type,
            'post_status' => array('publish', 'draft', 'pending', 'private'),
            'posts_per_page' => 100,
            'orderby' => 'modified',
            'order' => 'DESC'
        );

        $posts = get_posts($args);
        
        if (empty($posts)) {
            wp_send_json_success(array());
        }

        $results = array();
        foreach ($posts as $post) {
            $results[] = array(
                'ID' => $post->ID,
                'post_title' => $post->post_title ? $post->post_title : __('(no title)', 'syntekpro-forms'),
                'post_status' => $post->post_status
            );
        }

        wp_send_json_success($results);
    }

    /**
     * NEW: Create new post/page and insert form
     */
    public function ajax_create_post_with_form() {
        check_ajax_referer('spf_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'syntekpro-forms'));
        }

        $post_type = sanitize_text_field($_POST['post_type']);
        $title = sanitize_text_field($_POST['title']);
        $form_id = intval($_POST['form_id']);

        if (!$title || !$form_id || !in_array($post_type, array('post', 'page'))) {
            wp_send_json_error(__('Missing or invalid fields', 'syntekpro-forms'));
        }

        // Create new post
        $shortcode = '[syntekpro_form id="' . $form_id . '"]';
        $post_data = array(
            'post_title' => $title,
            'post_content' => $shortcode,
            'post_type' => $post_type,
            'post_status' => 'draft',
            'post_author' => get_current_user_id()
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            wp_send_json_error(__('Error creating post', 'syntekpro-forms'));
        }

        $edit_url = get_edit_post_link($post_id, 'raw');
        wp_send_json_success(array('edit_url' => $edit_url));
    }

    public function ajax_save_draft() {
        check_ajax_referer('spf_frontend_nonce', 'nonce');

        if (!$this->growth_services) {
            wp_send_json_error(__('Draft service unavailable.', 'syntekpro-forms'));
        }

        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
        $resume_token = isset($_POST['resume_token']) ? sanitize_text_field(wp_unslash((string) $_POST['resume_token'])) : '';
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash((string) $_POST['email'])) : '';
        $draft_data = isset($_POST['draft_data']) ? json_decode(wp_unslash((string) $_POST['draft_data']), true) : array();

        $saved = $this->growth_services->save_draft($form_id, is_array($draft_data) ? $draft_data : array(), $resume_token, $email);
        if (is_wp_error($saved)) {
            wp_send_json_error($saved->get_error_message());
        }

        wp_send_json_success($saved);
    }

    public function ajax_get_draft() {
        check_ajax_referer('spf_frontend_nonce', 'nonce');

        if (!$this->growth_services) {
            wp_send_json_error(__('Draft service unavailable.', 'syntekpro-forms'));
        }

        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
        $resume_token = isset($_POST['resume_token']) ? sanitize_text_field(wp_unslash((string) $_POST['resume_token'])) : '';

        $draft = $this->growth_services->get_draft($resume_token, $form_id);
        if (!$draft) {
            wp_send_json_error(__('Draft not found.', 'syntekpro-forms'));
        }

        wp_send_json_success($draft);
    }

    public function ajax_track_analytics() {
        check_ajax_referer('spf_frontend_nonce', 'nonce');

        if (!$this->growth_services) {
            wp_send_json_error(__('Analytics service unavailable.', 'syntekpro-forms'));
        }

        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
        $event_type = isset($_POST['event_type']) ? sanitize_key(wp_unslash((string) $_POST['event_type'])) : '';
        $field_name = isset($_POST['field_name']) ? sanitize_key(wp_unslash((string) $_POST['field_name'])) : '';
        $session_id = isset($_POST['session_id']) ? sanitize_text_field(wp_unslash((string) $_POST['session_id'])) : '';

        $this->growth_services->track_event($form_id, $event_type, $field_name, $session_id);
        wp_send_json_success();
    }
}
