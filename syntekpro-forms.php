<?php
/**
 * Plugin Name: SyntekPro Forms
 * Plugin URI: https://syntekpro.com
 * Description: Professional WordPress form builder with drag & drop interface, Gutenberg support, and advanced entry management
 * Version: 1.3.1
 * Author: SyntekPro
 * Author URI: https://syntekpro.com
 * License: GPL v2 or later
 * Text Domain: syntekpro-forms
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SPF_VERSION', '1.3.1');
define('SPF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SPF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SPF_PLUGIN_FILE', __FILE__);
define('SPF_ADDONS_DIR', SPF_PLUGIN_DIR . 'addons/');

// Include dependencies (with error handling)
if (file_exists(SPF_PLUGIN_DIR . 'includes/admin/templates.php')) {
    require_once SPF_PLUGIN_DIR . 'includes/admin/templates.php';
}
if (file_exists(SPF_PLUGIN_DIR . 'includes/file-handler.php')) {
    require_once SPF_PLUGIN_DIR . 'includes/file-handler.php';
}
if (file_exists(SPF_PLUGIN_DIR . 'includes/email-templates.php')) {
    require_once SPF_PLUGIN_DIR . 'includes/email-templates.php';
}
if (file_exists(SPF_PLUGIN_DIR . 'includes/class-gutenberg-block.php')) {
    require_once SPF_PLUGIN_DIR . 'includes/class-gutenberg-block.php';
}
if (file_exists(SPF_PLUGIN_DIR . 'includes/class-spam-filter.php')) {
    require_once SPF_PLUGIN_DIR . 'includes/class-spam-filter.php';
}
if (file_exists(SPF_PLUGIN_DIR . 'includes/class-entries.php')) {
    require_once SPF_PLUGIN_DIR . 'includes/class-entries.php';
}
if (file_exists(SPF_PLUGIN_DIR . 'includes/class-ajax-handler.php')) {
    require_once SPF_PLUGIN_DIR . 'includes/class-ajax-handler.php';
}

class SyntekPro_Forms_Builder {

    private static $instance = null;
    private $loaded_addons = array();
    private $ajax_handler;
    private $entries;
    private $spam_filter;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->spam_filter = new SyntekPro_Forms_Spam_Filter();
        $this->entries = new SyntekPro_Forms_Entries($this);
        $this->ajax_handler = new SyntekPro_Forms_Ajax_Handler($this, $this->spam_filter);
        $this->init_hooks();
    }

    private function init_hooks() {
        register_activation_hook(SPF_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(SPF_PLUGIN_FILE, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('plugins_loaded', array($this, 'check_version'));
        add_action('plugins_loaded', array($this, 'load_addons'), 20);
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('admin_bar_menu', array($this, 'add_admin_bar_menu'), 100);
        add_action('admin_init', array($this, 'handle_no_conflict_mode'));
        add_action('admin_head', array($this, 'admin_styles_fix'));
        add_filter('cron_schedules', array($this, 'register_cron_schedules'));
        add_action('spf_apply_data_retention', array($this, 'run_data_retention_cron'));
        add_action('spf_fire_webhook', array($this, 'fire_webhook'), 10, 2);

        $this->ajax_handler->register_hooks();
        $this->entries->register_hooks();
    }

    public function activate() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $forms_table = $wpdb->prefix . 'spf_forms';
        $forms_sql = "CREATE TABLE $forms_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            fields longtext NOT NULL,
            settings longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'active',
            views bigint(20) DEFAULT 0,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $entries_table = $wpdb->prefix . 'spf_entries';
        $entries_sql = "CREATE TABLE $entries_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            form_id bigint(20) NOT NULL,
            entry_data longtext NOT NULL,
            user_id bigint(20),
            ip_address varchar(45),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'unread',
            PRIMARY KEY  (id),
            KEY form_id  (form_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($forms_sql);
        dbDelta($entries_sql);

        update_option('spf_version', SPF_VERSION);
        add_option('spf_settings', $this->get_default_settings());

        if (!wp_next_scheduled('spf_apply_data_retention')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'spf_six_hours', 'spf_apply_data_retention');
        }
    }

    public function check_version() {
        if (get_option('spf_version') !== SPF_VERSION) {
            $this->activate();

            $current_settings = get_option('spf_settings', array());
            if (!is_array($current_settings)) {
                $current_settings = array();
            }

            $defaults = $this->get_default_settings();
            $merged = wp_parse_args($current_settings, $defaults);

            if ($merged !== $current_settings) {
                update_option('spf_settings', $merged);
            }
        }
    }

    private function get_default_settings() {
        return array(
            'license_key' => '',
            'currency' => 'USD',
            'enable_logging' => 0,
            'default_theme' => 'classic',
            'enable_toolbar_menu' => 1,
            'enable_dashboard_widget' => 1,
            'enable_background_updates' => 0,
            'no_conflict_mode' => 0,
            'enable_akismet' => 0,
            'enable_data_collection' => 0,
            'recaptcha_site_key' => '',
            'recaptcha_secret_key' => '',
            'recaptcha_invisible' => 0,
            'enable_honeypot' => 1,
            'from_email' => get_option('admin_email'),
            'from_name' => get_option('blogname'),
            'delete_entries_on_uninstall' => 0,
            'enable_ip_logging' => 1,
            'anonymize_ip' => 0,
            'data_retention_days' => 0,
            'trash_retention_days' => 40,
            'rate_limit_enabled' => 0,
            'rate_limit_seconds' => 30,
            'force_enqueue_conditional_logic' => 0,
        );
    }

    public function deactivate() {
        wp_clear_scheduled_hook('spf_apply_data_retention');
    }

    public function load_textdomain() {
        load_plugin_textdomain('syntekpro-forms', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function add_admin_menu() {
        add_menu_page(
            __('SyntekPro Forms', 'syntekpro-forms'),
            __('Forms', 'syntekpro-forms'),
            'manage_options',
            'syntekpro-forms',
            array($this, 'render_forms_page'),
            SPF_PLUGIN_URL . 'assets/images/Syntekpro%20Forms%20Grey%20Favicons.png',
            30
        );

        remove_submenu_page('syntekpro-forms', 'syntekpro-forms');

        add_submenu_page(
            'syntekpro-forms',
            __('Add New', 'syntekpro-forms'),
            __('Add New', 'syntekpro-forms'),
            'manage_options',
            'syntekpro-forms-add-new',
            array($this, 'render_add_new_page')
        );

        add_submenu_page(
            null,
            __('Form Builder', 'syntekpro-forms'),
            __('Form Builder', 'syntekpro-forms'),
            'manage_options',
            'syntekpro-forms-new',
            array($this, 'render_form_builder')
        );

        add_submenu_page(
            'syntekpro-forms',
            __('Entries', 'syntekpro-forms'),
            __('Entries', 'syntekpro-forms'),
            'manage_options',
            'syntekpro-forms-entries',
            array($this, 'render_entries_page')
        );

        add_submenu_page(
            'syntekpro-forms',
            __('Settings', 'syntekpro-forms'),
            __('Settings', 'syntekpro-forms'),
            'manage_options',
            'syntekpro-forms-settings',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'syntekpro-forms',
            __('Add-ons', 'syntekpro-forms'),
            __('Add-ons', 'syntekpro-forms'),
            'manage_options',
            'syntekpro-forms-addons',
            array($this, 'render_addons_page')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (empty($hook) || strpos((string) $hook, 'syntekpro-forms') === false) {
            return;
        }

        wp_dequeue_script('spf-form-builder-js');

        wp_enqueue_style(
            'spf-admin-css',
            SPF_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SPF_VERSION . time()  // Force cache bust
        );

        // Add critical inline CSS to force red buttons - override WordPress defaults
        wp_add_inline_style('spf-admin-css', '
            .spf-admin-list-wrap .button-primary,
            .spf-form-builder-wrap .button-primary,
            .spf-settings-page .button-primary,
            .spf-admin-page .button-primary,
            .wrap .button-primary {
                background-color: #dc3232 !important;
                border-color: #c82333 !important;
                color: #fff !important;
            }
            
            .spf-admin-list-wrap .button-primary:hover,
            .spf-admin-list-wrap .button-primary:focus,
            .spf-form-builder-wrap .button-primary:hover,
            .spf-form-builder-wrap .button-primary:focus,
            .spf-settings-page .button-primary:hover,
            .spf-settings-page .button-primary:focus,
            .spf-admin-page .button-primary:hover,
            .spf-admin-page .button-primary:focus,
            .wrap .button-primary:hover,
            .wrap .button-primary:focus {
                background-color: #e74c3c !important;
                border-color: #b81a23 !important;
                box-shadow: 0 4px 12px rgba(220, 50, 50, 0.3) !important;
            }
            
            /* Badge styling */
            .spf-badge,
            .spf-version-badge {
                background-color: #dc3232 !important;
                color: #fff !important;
                border-color: #c82333 !important;
            }
            
            .spf-badge:hover,
            .spf-version-badge:hover {
                background-color: #e74c3c !important;
                border-color: #b81a23 !important;
            }
            
            /* All anchor buttons */
            a.button,
            a.button-primary {
                background-color: #dc3232 !important;
                border-color: #c82333 !important;
            }
            
            a.button:hover,
            a.button-primary:hover,
            a.button:focus,
            a.button-primary:focus {
                background-color: #e74c3c !important;
                border-color: #b81a23 !important;
            }
            
            /* Input buttons */
            input[type="submit"].button,
            input[type="submit"].button-primary,
            input[type="button"].button,
            input[type="button"].button-primary {
                background-color: #dc3232 !important;
                border-color: #c82333 !important;
            }
            
            input[type="submit"].button:hover,
            input[type="submit"].button-primary:hover,
            input[type="button"].button:hover,
            input[type="button"].button-primary:hover {
                background-color: #e74c3c !important;
                border-color: #b81a23 !important;
            }
        ');

        wp_enqueue_script(
            'spf-admin-js',
            SPF_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-sortable'),
            SPF_VERSION . time(),  // Force cache bust
            true
        );

        wp_localize_script('spf-admin-js', 'spfAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('spf_admin_nonce'),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this?', 'syntekpro-forms'),
                'saved'         => __('Saved successfully!', 'syntekpro-forms'),
                'error'         => __('An error occurred. Please try again.', 'syntekpro-forms')
            )
        ));
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_style('spf-frontend-css', SPF_PLUGIN_URL . 'assets/css/frontend.css', array(), SPF_VERSION);

        wp_register_script(
            'spf-conditional-logic',
            SPF_PLUGIN_URL . 'assets/js/conditional-logic.js',
            array('jquery'),
            SPF_VERSION,
            true
        );

        if ($this->should_enqueue_conditional_logic()) {
            wp_enqueue_script('spf-conditional-logic');
        }

        wp_enqueue_script('spf-frontend-js', SPF_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), SPF_VERSION, true);

        wp_localize_script('spf-frontend-js', 'spfFrontend', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('spf_frontend_nonce')
        ));
    }

    private function should_enqueue_conditional_logic() {
        $should_enqueue = false;
        $settings = get_option('spf_settings', array());
        if (!is_array($settings)) {
            $settings = array();
        }
        $force_enqueue_setting = !empty($settings['force_enqueue_conditional_logic']);
        $force_enqueue_filter = apply_filters('syntekpro_forms_force_enqueue_conditional_logic', false);

        if ($force_enqueue_setting || $force_enqueue_filter) {
            $should_enqueue = true;
        }

        if (is_singular()) {
            $post = get_post();
            if ($post) {
                if (has_shortcode($post->post_content, 'syntekpro_form')) {
                    $should_enqueue = true;
                } elseif (function_exists('has_block') && has_block('syntekpro-forms/form-selector', $post)) {
                    $should_enqueue = true;
                }
            }
        } elseif (!$should_enqueue) {
            global $wp_query;
            if (!empty($wp_query->posts)) {
                foreach ((array) $wp_query->posts as $post) {
                    if (!isset($post->post_content)) {
                        continue;
                    }

                    if (has_shortcode($post->post_content, 'syntekpro_form')) {
                        $should_enqueue = true;
                        break;
                    }

                    if (function_exists('has_block') && has_block('syntekpro-forms/form-selector', $post)) {
                        $should_enqueue = true;
                        break;
                    }
                }
            }
        }

        return (bool) apply_filters('syntekpro_forms_enqueue_conditional_logic', $should_enqueue);
    }

    public function register_shortcodes() {
        add_shortcode('syntekpro_form', array($this, 'render_form_shortcode'));
    }

    public function render_forms_page() {
        $view_file = SPF_PLUGIN_DIR . 'includes/admin/views/forms-list.php';
        if (file_exists($view_file)) {
            include $view_file;
        }
    }

    public function render_add_new_page() {
        $view_file = SPF_PLUGIN_DIR . 'includes/admin/views/forms-list.php';
        if (file_exists($view_file)) {
            include $view_file;
            // Auto-open template modal via inline script
            echo "<script type='text/javascript'>
            (function() {
                var showModal = function() {
                    if (typeof jQuery !== 'undefined') {
                        var $ = jQuery;
                        $('#spf-template-modal').css('display', 'flex').hide().fadeIn();
                    } else {
                        setTimeout(showModal, 100);
                    }
                };
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', showModal);
                } else {
                    showModal();
                }
            })();
            </script>";
        }
    }

    public function render_form_builder() {
        $view_file = SPF_PLUGIN_DIR . 'includes/admin/views/form-builder.php';
        if (file_exists($view_file)) {
            include $view_file;
        }
    }

    public function render_entries_page() {
        $view_file = SPF_PLUGIN_DIR . 'includes/admin/views/entries-list.php';
        if (file_exists($view_file)) {
            include $view_file;
        }
    }

    public function render_settings_page() {
        $view_file = SPF_PLUGIN_DIR . 'includes/admin/views/settings.php';
        if (file_exists($view_file)) {
            include $view_file;
        }
    }

    public function render_addons_page() {
        $view_file = SPF_PLUGIN_DIR . 'includes/admin/views/addons.php';
        if (file_exists($view_file)) {
            $loaded_addons = $this->loaded_addons;
            include $view_file;
        }
    }

    public function render_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts);

        if (empty($atts['id'])) {
            return '';
        }

        ob_start();
        $view_file = SPF_PLUGIN_DIR . 'includes/frontend/form-display.php';
        if (file_exists($view_file)) {
            include $view_file;
        }
        return ob_get_clean();
    }

    public function get_form_availability_state($form_settings, $form_id) {
        global $wpdb;

        $defaults = array(
            'submission_limit' => 0,
            'submission_limit_message' => __('This form is no longer accepting responses.', 'syntekpro-forms'),
            'schedule_not_started_message' => __('This form is not yet open for submissions.', 'syntekpro-forms'),
            'schedule_expired_message' => __('This form is no longer accepting submissions.', 'syntekpro-forms'),
        );

        $settings = wp_parse_args(is_array($form_settings) ? $form_settings : array(), $defaults);
        $now = current_time('timestamp');

        if (!empty($settings['schedule_start'])) {
            $start_ts = strtotime((string) $settings['schedule_start']);
            if ($start_ts && $now < $start_ts) {
                return array(
                    'status' => 'not_started',
                    'message' => $settings['schedule_not_started_message'],
                );
            }
        }

        if (!empty($settings['schedule_end'])) {
            $end_ts = strtotime((string) $settings['schedule_end']);
            if ($end_ts && $now > $end_ts) {
                return array(
                    'status' => 'expired',
                    'message' => $settings['schedule_expired_message'],
                );
            }
        }

        $limit = absint($settings['submission_limit']);
        if ($limit > 0) {
            $cache_key = 'spf_count_' . $form_id;
            $count = get_transient($cache_key);
            if ($count === false) {
                $count = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}spf_entries WHERE form_id = %d",
                    $form_id
                ));
                set_transient($cache_key, $count, 60);
            }

            if ($count >= $limit) {
                return array(
                    'status' => 'limit',
                    'message' => $settings['submission_limit_message'],
                );
            }
        }

        return array(
            'status' => 'open',
            'message' => '',
        );
    }

    public function trigger_form_webhooks($form, $data, $entry_id, $form_settings) {
        if (empty($form_settings['webhook_enabled'])) {
            return;
        }

        $raw_urls = isset($form_settings['webhook_urls']) ? (string) $form_settings['webhook_urls'] : '';
        if (empty($raw_urls)) {
            return;
        }

        $urls = preg_split('/[\r\n]+/', $raw_urls);
        if (empty($urls)) {
            return;
        }

        $payload = array(
            'form_id' => (int) $form->id,
            'form_title' => (string) $form->title,
            'entry_id' => (int) $entry_id,
            'submitted_at' => current_time('mysql'),
            'site' => home_url('/'),
            'data' => $data,
        );

        foreach ($urls as $url) {
            $url = trim($url);
            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            wp_schedule_single_event(time(), 'spf_fire_webhook', array($url, $payload));
        }
    }

    public function fire_webhook($url, $payload) {
        if (empty($url) || empty($payload)) {
            return;
        }

        wp_remote_post($url, array(
            'timeout' => 5,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($payload),
        ));
    }

    public function build_success_payload($form_settings) {
        $message = !empty($form_settings['success_message'])
            ? $form_settings['success_message']
            : __('Form submitted successfully', 'syntekpro-forms');

        $behavior = isset($form_settings['success_behavior']) && $form_settings['success_behavior'] === 'redirect'
            ? 'redirect'
            : 'message';

        $payload = array(
            'message' => $message,
            'behavior' => $behavior,
            'redirect' => '',
        );

        if ($behavior === 'redirect' && !empty($form_settings['success_redirect_url'])) {
            $payload['redirect'] = esc_url_raw($form_settings['success_redirect_url']);
        }

        return $payload;
    }

    public function load_addons() {
        if (!file_exists(SPF_ADDONS_DIR)) {
            wp_mkdir_p(SPF_ADDONS_DIR);
        }

        $paths = array(SPF_ADDONS_DIR);
        $paths = apply_filters('syntekpro_forms_addons_paths', $paths);

        foreach ((array) $paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $files = glob(trailingslashit($path) . '*.php');
            if (empty($files)) {
                continue;
            }

            foreach ($files as $file) {
                if (realpath($file) === realpath(SPF_PLUGIN_FILE)) {
                    continue;
                }

                require_once $file;
                $this->loaded_addons[] = $file;
            }
        }
    }

    public function anonymize_ip_address($ip) {
        if (empty($ip)) {
            return '';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                $parts[3] = '0';
            }
            return implode('.', $parts);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $segments = explode(':', $ip);
            $segments = array_slice($segments, 0, 4);
            return implode(':', $segments) . '::';
        }

        return '';
    }

    private function apply_data_retention($settings) {
        if (empty($settings['data_retention_days'])) {
            return;
        }

        global $wpdb;
        $days = absint($settings['data_retention_days']);
        if ($days === 0) {
            return;
        }

        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}spf_entries WHERE created_at < (NOW() - INTERVAL %d DAY)",
            $days
        ));
    }

    private function apply_trash_retention($settings) {
        if (!isset($settings['trash_retention_days'])) {
            return;
        }

        $days = absint($settings['trash_retention_days']);
        if ($days === 0) {
            return;
        }

        global $wpdb;
        $forms_table = $wpdb->prefix . 'spf_forms';
        $entries_table = $wpdb->prefix . 'spf_entries';

        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$entries_table} WHERE form_id IN (SELECT id FROM {$forms_table} WHERE status = 'trash' AND updated_at < (NOW() - INTERVAL %d DAY))",
            $days
        ));

        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$forms_table} WHERE status = 'trash' AND updated_at < (NOW() - INTERVAL %d DAY)",
            $days
        ));
    }

    public function run_data_retention_cron() {
        $settings = get_option('spf_settings', array());
        if (!is_array($settings)) {
            $settings = array();
        }

        $this->apply_data_retention($settings);
        $this->apply_trash_retention($settings);
    }

    public function register_cron_schedules($schedules) {
        if (!isset($schedules['spf_six_hours'])) {
            $schedules['spf_six_hours'] = array(
                'interval' => 6 * HOUR_IN_SECONDS,
                'display'  => __('Every 6 Hours', 'syntekpro-forms'),
            );
        }

        return $schedules;
    }

    public function add_dashboard_widget() {
        $settings = get_option('spf_settings');
        if (isset($settings['enable_dashboard_widget']) && !$settings['enable_dashboard_widget']) {
            return;
        }

        wp_add_dashboard_widget(
            'spf_entries_dashboard_widget',
            '<img src="' . SPF_PLUGIN_URL . 'assets/images/Syntekpro%20Forms%20Logo.png" style="height:20px;width:auto;vertical-align:middle;margin-right:8px;"> ' . __('Recent Form Entries', 'syntekpro-forms'),
            array($this, 'render_dashboard_widget')
        );
    }

    public function add_admin_bar_menu($wp_admin_bar) {
        $settings = get_option('spf_settings');
        if (isset($settings['enable_toolbar_menu']) && !$settings['enable_toolbar_menu']) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $wp_admin_bar->add_node(array(
            'id'    => 'syntekpro-forms',
            'title' => '<img src="' . SPF_PLUGIN_URL . 'assets/images/Syntekpro%20Forms%20Grey%20Favicons.png" style="height:25px; width:auto; vertical-align:middle; margin-right:5px; filter: drop-shadow(0 1px 1px rgba(0,0,0,0.2));"> Forms',
            'href'  => admin_url('admin.php?page=syntekpro-forms'),
            'meta'  => array('title' => __('SyntekPro Forms', 'syntekpro-forms')),
        ));

        $wp_admin_bar->add_node(array(
            'id'     => 'syntekpro-forms-all',
            'parent' => 'syntekpro-forms',
            'title'  => __('All Forms', 'syntekpro-forms'),
            'href'   => admin_url('admin.php?page=syntekpro-forms'),
        ));

        $wp_admin_bar->add_node(array(
            'id'     => 'syntekpro-forms-add',
            'parent' => 'syntekpro-forms',
            'title'  => __('Add New', 'syntekpro-forms'),
            'href'   => admin_url('admin.php?page=syntekpro-forms-all&add-new=1'),
        ));

        $wp_admin_bar->add_node(array(
            'id'     => 'syntekpro-forms-entries',
            'parent' => 'syntekpro-forms',
            'title'  => __('Entries', 'syntekpro-forms'),
            'href'   => admin_url('admin.php?page=syntekpro-forms-entries'),
        ));

        $wp_admin_bar->add_node(array(
            'id'     => 'syntekpro-forms-tutorial',
            'parent' => 'syntekpro-forms',
            'title'  => __('Open Tutorial', 'syntekpro-forms'),
            'href'   => plugins_url('docs/TUTORIAL.md', SPF_PLUGIN_FILE),
            'meta'   => array('target' => '_blank', 'title' => __('View the SyntekPro Forms tutorial', 'syntekpro-forms')),
        ));
    }

    public function handle_no_conflict_mode() {
        $settings = get_option('spf_settings');
        if (!isset($settings['no_conflict_mode']) || !$settings['no_conflict_mode']) {
            return;
        }

        $hook = isset($_GET['page']) ? $_GET['page'] : '';
        if (strpos((string) $hook, 'syntekpro-forms') === false) {
            return;
        }

        add_action('admin_enqueue_scripts', array($this, 'dequeue_extraneous_scripts'), 999);
    }

    public function dequeue_extraneous_scripts() {
        global $wp_scripts, $wp_styles;

        $whitelist = array(
            'jquery', 'jquery-core', 'jquery-migrate', 'jquery-ui-core', 'jquery-ui-sortable',
            'utils', 'common', 'admin-bar', 'heartbeat', 'wp-auth-check', 'media-views', 'media-editor',
            'spf-admin-js', 'spf-admin-css', 'dashicons', 'admin-menu', 'thickbox'
        );

        foreach ($wp_scripts->queue as $handle) {
            if (!in_array($handle, $whitelist) && strpos($handle, 'spf') === false) {
                wp_dequeue_script($handle);
            }
        }

        foreach ($wp_styles->queue as $handle) {
            if (!in_array($handle, $whitelist) && strpos($handle, 'spf') === false) {
                wp_dequeue_style($handle);
            }
        }
    }

    public function render_dashboard_widget() {
        global $wpdb;
        $entries = $wpdb->get_results("
            SELECT e.*, f.title as form_title 
            FROM {$wpdb->prefix}spf_entries e 
            LEFT JOIN {$wpdb->prefix}spf_forms f ON e.form_id = f.id 
            ORDER BY e.created_at DESC 
            LIMIT 5
        ");

        if (empty($entries)) {
            echo '<p>' . __('No entries yet.', 'syntekpro-forms') . '</p>';
            return;
        }

        echo '<ul class="spf-dashboard-entries-list" style="margin: 0; padding: 0; list-style: none;">';
        foreach ($entries as $entry) {
            $entry_data = json_decode($entry->entry_data, true);
            $preview = '';
            if (is_array($entry_data)) {
                $first_val = reset($entry_data);
                $preview = is_array($first_val) ? implode(', ', $first_val) : (string) $first_val;
                $preview = wp_trim_words($preview, 10);
            }

            echo '<li style="padding: 10px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">';
            echo '<div>';
            echo '<div style="font-weight: 600; font-size: 14px;">' . esc_html($entry->form_title) . '</div>';
            echo '<div style="font-size: 12px; color: #646970;">' . esc_html($preview) . '</div>';
            echo '</div>';
            echo '<div style="text-align: right;">';
            echo '<div style="font-size: 11px; color: #999;">' . human_time_diff(strtotime((string) $entry->created_at), current_time('timestamp')) . ' ' . __('ago', 'syntekpro-forms') . '</div>';
            if ($entry->status === 'unread') {
                echo '<span style="display: inline-block; width: 8px; height: 8px; background: #ffb900; border-radius: 50%; margin-left: 5px;" title="' . __('Unread', 'syntekpro-forms') . '"></span>';
            }
            echo '</div>';
            echo '</li>';
        }
        echo '</ul>';
        echo '<p style="margin-top: 15px; text-align: right;"><a href="' . admin_url('admin.php?page=syntekpro-forms-entries') . '" class="button button-secondary">' . __('View All Entries', 'syntekpro-forms') . '</a></p>';
    }

    public function admin_styles_fix() {
        echo '<style>
            #toplevel_page_syntekpro-forms .wp-menu-image,
            .folded #toplevel_page_syntekpro-forms .wp-menu-image {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            #toplevel_page_syntekpro-forms .wp-menu-image img,
            .folded #toplevel_page_syntekpro-forms .wp-menu-image img {
                padding: 0;
                width: 25px;
                height: 25px;
                max-width: 25px;
                max-height: 25px;
                object-fit: contain;
                filter: brightness(1.2);
                vertical-align: middle;
                margin: 0;
            }
            #wpadminbar #wp-admin-bar-syntekpro-forms .wp-admin-bar-item img,
            #wpadminbar #wp-admin-bar-syntekpro-forms .ab-icon img {
                max-width: 25px !important;
                max-height: 25px !important;
                height: auto !important;
                width: auto !important;
                vertical-align: middle;
                margin-top: -4px;
            }
            .spf-admin-list-wrap, .spf-form-builder-wrap, .spf-settings-page {
                background-color: #dcf5dc !important;
                min-height: 80vh;
            }
        </style>';
    }
}

SyntekPro_Forms_Builder::get_instance();
