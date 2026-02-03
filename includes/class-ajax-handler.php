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

    public function __construct($builder, $spam_filter) {
        $this->builder = $builder;
        $this->spam_filter = $spam_filter;
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

        $availability = $this->builder->get_form_availability_state($form_settings, $form_id);
        if ($availability['status'] !== 'open') {
            $this->send_submission_error($availability['message']);
        }

        $fields = json_decode($form->fields, true);
        if (!is_array($fields)) {
            $this->send_submission_error(__('Form configuration invalid', 'syntekpro-forms'));
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

        if ($result) {
            $entry_id = $wpdb->insert_id;

            if (!empty($lock_key) && !empty($settings['rate_limit_enabled']) && $rate_limit_seconds > 0) {
                $this->spam_filter->acquire_rate_limit_lock($lock_key, $rate_limit_seconds);
            }

            delete_transient('spf_count_' . $form_id);

            SPF_email_templates()->send_admin_notification($form, $sanitized_data, $entry_id);
            SPF_email_templates()->send_user_confirmation($form, $sanitized_data);

            $this->builder->trigger_form_webhooks($form, $sanitized_data, $entry_id, $form_settings);

            wp_send_json_success($this->builder->build_success_payload($form_settings));
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
            'rate_limit_enabled'          => 'bool',
            'rate_limit_seconds'          => 'int',
            'enable_honeypot'             => 'bool',
            'enable_akismet'              => 'bool',
            'enable_dashboard_widget'     => 'bool',
            'enable_toolbar_menu'         => 'bool',
            'no_conflict_mode'            => 'bool',
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
}
