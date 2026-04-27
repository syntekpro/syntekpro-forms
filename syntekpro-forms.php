<?php
/**
 * Plugin Name: SyntekPro Forms
 * Plugin URI: https://syntekpro.com
 * Description: Professional WordPress form builder with drag & drop interface, Gutenberg support, and advanced entry management
 * Version: 1.6.2
 * Update URI: https://github.com/syntekpro/syntekpro-forms
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
define('SPF_VERSION', '1.6.2');
define('SPF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SPF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SPF_PLUGIN_FILE', __FILE__);
define('SPF_ADDONS_DIR', SPF_PLUGIN_DIR . 'addons/');
// GitHub updater (set this to "owner/repo")
if (!defined('SPF_GITHUB_REPO')) {
    define('SPF_GITHUB_REPO', 'syntekpro/syntekpro-forms');
}
if (!defined('SPF_GITHUB_API')) {
    define('SPF_GITHUB_API', SPF_GITHUB_REPO ? 'https://api.github.com/repos/' . SPF_GITHUB_REPO . '/releases/latest' : '');
}
if (!defined('SPF_GITHUB_REPO_URL')) {
    define('SPF_GITHUB_REPO_URL', SPF_GITHUB_REPO ? 'https://github.com/' . SPF_GITHUB_REPO : '');
}

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
if (file_exists(SPF_PLUGIN_DIR . 'includes/class-growth-services.php')) {
    require_once SPF_PLUGIN_DIR . 'includes/class-growth-services.php';
}

// REST API support
if (file_exists(SPF_PLUGIN_DIR . 'includes/class-rest-api.php')) {
    require_once SPF_PLUGIN_DIR . 'includes/class-rest-api.php';
}

// GDPR compliance
if (file_exists(SPF_PLUGIN_DIR . 'includes/class-gdpr.php')) {
    require_once SPF_PLUGIN_DIR . 'includes/class-gdpr.php';
}

// PDF export
if (file_exists(SPF_PLUGIN_DIR . 'includes/class-pdf-export.php')) {
    require_once SPF_PLUGIN_DIR . 'includes/class-pdf-export.php';
}

// WP-CLI commands
if (defined('WP_CLI') && WP_CLI && file_exists(SPF_PLUGIN_DIR . 'includes/class-wpcli.php')) {
    require_once SPF_PLUGIN_DIR . 'includes/class-wpcli.php';
}

class SyntekPro_Forms_Builder {

    private static $instance = null;
    private $loaded_addons = array();
    private $ajax_handler;
    private $entries;
    private $spam_filter;
    private $growth_services;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->spam_filter = new SyntekPro_Forms_Spam_Filter();
        $this->growth_services = new SyntekPro_Forms_Growth_Services();
        $this->entries = new SyntekPro_Forms_Entries($this);
        $this->ajax_handler = new SyntekPro_Forms_Ajax_Handler($this, $this->spam_filter, $this->growth_services);
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
        add_action('init', array($this, 'register_consent_type'), 5);
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('admin_bar_menu', array($this, 'add_admin_bar_menu'), 100);
        add_action('admin_init', array($this, 'handle_no_conflict_mode'));
        add_action('admin_head', array($this, 'admin_styles_fix'));
        add_action('admin_notices', array($this, 'maybe_show_duplicate_install_notice'));
        add_action('admin_notices', array($this, 'show_admin_notifications'));
        add_filter('cron_schedules', array($this, 'register_cron_schedules'));
        add_action('spf_apply_data_retention', array($this, 'run_data_retention_cron'));
        add_action('spf_fire_webhook', array($this, 'fire_webhook'), 10, 2);
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_github_update'));
        add_filter('plugins_api', array($this, 'github_plugin_info'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'github_upgrader_post_install'), 10, 3);
        add_filter('auto_update_plugin', array($this, 'maybe_enable_auto_update_for_plugin'), 10, 2);
        add_action('spf_process_webhook_queue', array($this, 'process_webhook_queue'));

        // REST API initialization
        add_action('rest_api_init', array($this, 'init_rest_api'));

        // GDPR privacy hooks
        add_filter('wp_privacy_personal_data_exporters', array($this, 'register_gdpr_exporter'));
        add_filter('wp_privacy_personal_data_erasers', array($this, 'register_gdpr_eraser'));

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
            starred tinyint(1) DEFAULT 0,
            notes text,
            PRIMARY KEY  (id),
            KEY form_id  (form_id)
        ) $charset_collate;";

        $webhook_queue_table = $wpdb->prefix . 'spf_webhook_queue';
        $webhook_queue_sql = "CREATE TABLE $webhook_queue_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            form_id bigint(20) DEFAULT 0,
            entry_id bigint(20) DEFAULT 0,
            webhook_url text NOT NULL,
            payload longtext NOT NULL,
            status varchar(20) DEFAULT 'pending',
            attempts int(11) DEFAULT 0,
            max_attempts int(11) DEFAULT 5,
            next_attempt_at datetime DEFAULT NULL,
            last_attempt_at datetime DEFAULT NULL,
            response_code int(11) DEFAULT NULL,
            response_body text,
            error_message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status_next_attempt (status, next_attempt_at),
            KEY form_id (form_id),
            KEY entry_id (entry_id)
        ) $charset_collate;";

        $drafts_table = $wpdb->prefix . 'spf_drafts';
        $drafts_sql = "CREATE TABLE $drafts_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            form_id bigint(20) NOT NULL,
            resume_token varchar(64) NOT NULL,
            draft_data longtext NOT NULL,
            email varchar(255) DEFAULT NULL,
            user_id bigint(20) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY resume_token (resume_token),
            KEY form_id (form_id),
            KEY updated_at (updated_at)
        ) $charset_collate;";

        $analytics_table = $wpdb->prefix . 'spf_analytics';
        $analytics_sql = "CREATE TABLE $analytics_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            form_id bigint(20) NOT NULL,
            event_type varchar(40) NOT NULL,
            field_name varchar(120) DEFAULT NULL,
            session_id varchar(64) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_event (form_id, event_type),
            KEY created_at (created_at),
            KEY field_name (field_name)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($forms_sql);
        dbDelta($entries_sql);
        dbDelta($webhook_queue_sql);
        dbDelta($drafts_sql);
        dbDelta($analytics_sql);

        update_option('spf_version', SPF_VERSION);
        add_option('spf_settings', $this->get_default_settings());

        if (!wp_next_scheduled('spf_apply_data_retention')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'spf_six_hours', 'spf_apply_data_retention');
        }

        if (!wp_next_scheduled('spf_process_webhook_queue')) {
            wp_schedule_event(time() + MINUTE_IN_SECONDS, 'spf_five_minutes', 'spf_process_webhook_queue');
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
            'enable_background_updates' => 1,
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
            'rest_api_enabled' => 1,
            'stripe_publishable_key' => '',
            'stripe_secret_key' => '',
            'payment_currency' => 'USD',
            'automation_zapier_url' => '',
            'automation_make_url' => '',
            'mailchimp_api_key' => '',
            'mailchimp_audience_id' => '',
            'hubspot_private_token' => '',
            'hubspot_default_list_id' => '',
        );
    }

    public function get_growth_services() {
        return $this->growth_services;
    }

    public function deactivate() {
        wp_clear_scheduled_hook('spf_apply_data_retention');
        wp_clear_scheduled_hook('spf_process_webhook_queue');
    }

    public function load_textdomain() {
        load_plugin_textdomain('syntekpro-forms', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    private function get_github_repo() {
        $repo = SPF_GITHUB_REPO;
        $repo = apply_filters('spf_github_repo', $repo);
        return is_string($repo) ? trim($repo) : '';
    }

    private function get_github_release() {
        $repo = $this->get_github_repo();
        if (empty($repo)) {
            return null;
        }

        $cache_key = 'spf_github_release_' . md5($repo);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $api_url = 'https://api.github.com/repos/' . $repo . '/releases/latest';
        $response = wp_remote_get($api_url, array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/vnd.github+json',
                'User-Agent' => 'SyntekPro-Forms-Updater'
            )
        ));

        if (is_wp_error($response)) {
            set_transient($cache_key, null, MINUTE_IN_SECONDS * 10);
            return null;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ((int) $code !== 200) {
            set_transient($cache_key, null, MINUTE_IN_SECONDS * 10);
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        if (empty($data) || empty($data->tag_name)) {
            set_transient($cache_key, null, MINUTE_IN_SECONDS * 10);
            return null;
        }

        set_transient($cache_key, $data, HOUR_IN_SECONDS);
        return $data;
    }

    private function get_github_package_url($release) {
        if (empty($release)) {
            return '';
        }

        if (!empty($release->assets) && is_array($release->assets)) {
            foreach ($release->assets as $asset) {
                if (empty($asset) || empty($asset->name) || empty($asset->browser_download_url)) {
                    continue;
                }

                $name = strtolower((string) $asset->name);
                if (substr($name, -4) === '.zip' && strpos($name, 'syntekpro-forms') !== false) {
                    return (string) $asset->browser_download_url;
                }
            }

            foreach ($release->assets as $asset) {
                if (empty($asset) || empty($asset->name) || empty($asset->browser_download_url)) {
                    continue;
                }
                $name = strtolower((string) $asset->name);
                if (substr($name, -4) === '.zip') {
                    return (string) $asset->browser_download_url;
                }
            }
        }

        return !empty($release->zipball_url) ? (string) $release->zipball_url : '';
    }

    public function check_github_update($transient) {
        if (!is_object($transient)) {
            return $transient;
        }

        $repo = $this->get_github_repo();
        if (empty($repo)) {
            return $transient;
        }

        $release = $this->get_github_release();
        if (empty($release)) {
            return $transient;
        }

        $current_version = SPF_VERSION;
        $latest_version = ltrim((string) $release->tag_name, 'v');

        if (version_compare($latest_version, $current_version, '<=')) {
            return $transient;
        }

        $plugin_basename = plugin_basename(SPF_PLUGIN_FILE);
        $package = $this->get_github_package_url($release);

        $transient->response[$plugin_basename] = (object) array(
            'slug' => dirname($plugin_basename),
            'plugin' => $plugin_basename,
            'new_version' => $latest_version,
            'url' => !empty($release->html_url) ? $release->html_url : SPF_GITHUB_REPO_URL,
            'package' => $package
        );

        return $transient;
    }

    public function github_plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        $plugin_basename = plugin_basename(SPF_PLUGIN_FILE);
        if (empty($args->slug) || $args->slug !== dirname($plugin_basename)) {
            return $result;
        }

        $repo = $this->get_github_repo();
        if (empty($repo)) {
            return $result;
        }

        $release = $this->get_github_release();
        if (empty($release)) {
            return $result;
        }

        $latest_version = ltrim((string) $release->tag_name, 'v');

        return (object) array(
            'name' => 'SyntekPro Forms',
            'slug' => dirname($plugin_basename),
            'version' => $latest_version,
            'author' => '<a href="https://syntekpro.com">SyntekPro</a>',
            'homepage' => !empty($release->html_url) ? $release->html_url : SPF_GITHUB_REPO_URL,
            'requires' => '5.8',
            'tested' => get_bloginfo('version'),
            'sections' => array(
                'description' => !empty($release->body) ? wp_kses_post(wpautop($release->body)) : 'GitHub release update.',
                'changelog' => !empty($release->body) ? wp_kses_post(wpautop($release->body)) : ''
            ),
            'download_link' => $this->get_github_package_url($release)
        );
    }

    public function github_upgrader_post_install($response, $hook_extra, $result) {
        if (empty($hook_extra['plugin']) || $hook_extra['plugin'] !== plugin_basename(SPF_PLUGIN_FILE)) {
            return $response;
        }

        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            return $response;
        }

        $plugin_folder = WP_PLUGIN_DIR . '/' . dirname(plugin_basename(SPF_PLUGIN_FILE));
        if (!isset($result['destination'])) {
            return $response;
        }

        $destination = untrailingslashit(wp_normalize_path($result['destination']));
        $plugin_folder_normalized = untrailingslashit(wp_normalize_path($plugin_folder));
        if ($destination === $plugin_folder_normalized) {
            return $response;
        }

        if ($wp_filesystem->is_dir($plugin_folder)) {
            $deleted = $wp_filesystem->delete($plugin_folder, true);
            if (!$deleted && $wp_filesystem->is_dir($plugin_folder)) {
                return new WP_Error('spf_update_delete_failed', __('SyntekPro Forms update failed while removing old plugin directory. Please check file permissions.', 'syntekpro-forms'));
            }
        }

        $moved = $wp_filesystem->move($result['destination'], $plugin_folder, true);
        if (!$moved) {
            if (!function_exists('copy_dir')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }

            if (!$wp_filesystem->is_dir($plugin_folder)) {
                $wp_filesystem->mkdir($plugin_folder);
            }

            $copied = copy_dir($result['destination'], $plugin_folder);
            if (!is_wp_error($copied)) {
                $wp_filesystem->delete($result['destination'], true);
                $moved = true;
            }
        }

        if (!$moved) {
            return new WP_Error('spf_update_move_failed', __('SyntekPro Forms update failed while replacing plugin directory.', 'syntekpro-forms'));
        }

        $result['destination'] = $plugin_folder;

        return $result;
    }

    public function maybe_enable_auto_update_for_plugin($update, $item) {
        if (empty($item) || empty($item->plugin)) {
            return $update;
        }

        if ($item->plugin !== plugin_basename(SPF_PLUGIN_FILE)) {
            return $update;
        }

        $settings = get_option('spf_settings', array());
        $enabled = !empty($settings['enable_background_updates']);

        return $enabled ? true : $update;
    }

    public function init_rest_api() {
        $settings = get_option('spf_settings', array());
        $enabled = isset($settings['rest_api_enabled']) ? (int)$settings['rest_api_enabled'] : 0;
        if ( ! $enabled ) {
            return;
        }
        if (class_exists('SyntekPro_Forms_REST_API')) {
            new SyntekPro_Forms_REST_API($this);
        }
    }

    public function maybe_show_duplicate_install_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();
        if (empty($plugins) || !is_array($plugins)) {
            return;
        }

        $current = plugin_basename(SPF_PLUGIN_FILE);
        $duplicates = array();

        foreach ($plugins as $file => $data) {
            if ($file === $current) {
                continue;
            }

            $text_domain = isset($data['TextDomain']) ? (string) $data['TextDomain'] : '';
            $name = isset($data['Name']) ? (string) $data['Name'] : '';

            if ($text_domain === 'syntekpro-forms' || stripos($name, 'SyntekPro Forms') !== false) {
                $duplicates[] = $file;
            }
        }

        if (empty($duplicates)) {
            return;
        }

        echo '<div class="notice notice-warning"><p>'
            . esc_html__('Multiple SyntekPro Forms plugin folders were detected. Keep only one plugin install (syntekpro-forms) to ensure updates replace the existing version.', 'syntekpro-forms')
            . '</p></div>';
    }

    /**
     * Show admin notifications from the plugin's notification queue.
     */
    public function show_admin_notifications() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $notices = get_option( 'spf_admin_notices', array() );
        if ( empty( $notices ) || ! is_array( $notices ) ) {
            return;
        }

        foreach ( $notices as $notice ) {
            $type    = isset( $notice['type'] ) ? sanitize_html_class( $notice['type'] ) : 'info';
            $message = isset( $notice['message'] ) ? wp_kses_post( $notice['message'] ) : '';
            if ( $message ) {
                printf( '<div class="notice notice-%s is-dismissible"><p><strong>SyntekPro Forms:</strong> %s</p></div>', $type, $message );
            }
        }

        // Clear after display
        delete_option( 'spf_admin_notices' );
    }

    /**
     * Queue an admin notification. Type: success, warning, error, info
     */
    public static function add_admin_notice( $message, $type = 'info' ) {
        $notices = get_option( 'spf_admin_notices', array() );
        if ( ! is_array( $notices ) ) {
            $notices = array();
        }
        $notices[] = array(
            'type'    => $type,
            'message' => $message,
            'time'    => current_time( 'mysql' ),
        );
        // Keep max 20 notices
        $notices = array_slice( $notices, -20 );
        update_option( 'spf_admin_notices', $notices );
    }

    /**
     * Log a plugin error with optional admin notification.
     */
    public static function log_error( $message, $context = '', $notify_admin = false ) {
        if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            error_log( 'SyntekPro Forms' . ( $context ? ' [' . $context . ']' : '' ) . ': ' . $message );
        }
        if ( $notify_admin ) {
            self::add_admin_notice( $message, 'error' );
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            __('SyntekPro Forms', 'syntekpro-forms'),
            __('SyntekPro Forms', 'syntekpro-forms'),
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
            __('Add-ons', 'syntekpro-forms'),
            __('Add-ons', 'syntekpro-forms'),
            'manage_options',
            'syntekpro-forms-addons',
            array($this, 'render_addons_page')
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
            __('Help', 'syntekpro-forms'),
            __('Help', 'syntekpro-forms'),
            'manage_options',
            'syntekpro-forms-help',
            array($this, 'render_help_page')
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
            SPF_VERSION
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
            SPF_VERSION,
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
        // Register scripts/styles (only enqueued when a form is on the page)
        wp_register_style('spf-frontend-css', SPF_PLUGIN_URL . 'assets/css/frontend.css', array(), SPF_VERSION);

        wp_register_script(
            'spf-conditional-logic',
            SPF_PLUGIN_URL . 'assets/js/conditional-logic.js',
            array('jquery'),
            SPF_VERSION,
            true
        );

        wp_register_script('spf-frontend-js', SPF_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), SPF_VERSION, true);

        wp_localize_script('spf-frontend-js', 'spfFrontend', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('spf_frontend_nonce')
        ));

        // Only enqueue on pages that contain a form
        if ($this->page_has_form()) {
            wp_enqueue_style('spf-frontend-css');
            wp_enqueue_script('spf-frontend-js');
            if ($this->should_enqueue_conditional_logic()) {
                wp_enqueue_script('spf-conditional-logic');
            }
        }
    }

    /**
     * Detect whether the current page contains a SyntekPro form.
     */
    private function page_has_form() {
        $settings = get_option('spf_settings', array());
        if (!empty($settings['force_enqueue_conditional_logic'])) {
            return true;
        }
        if (apply_filters('syntekpro_forms_force_enqueue_scripts', false)) {
            return true;
        }
        if (is_singular()) {
            $post = get_post();
            if ($post) {
                if (has_shortcode($post->post_content, 'syntekpro_form')) {
                    return true;
                }
                if (function_exists('has_block') && has_block('syntekpro-forms/form-selector', $post)) {
                    return true;
                }
            }
        } else {
            global $wp_query;
            if (!empty($wp_query->posts)) {
                foreach ((array) $wp_query->posts as $p) {
                    if (!isset($p->post_content)) continue;
                    if (has_shortcode($p->post_content, 'syntekpro_form')) return true;
                    if (function_exists('has_block') && has_block('syntekpro-forms/form-selector', $p)) return true;
                }
            }
        }
        return false;
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

    public function register_consent_type() {
        if (!function_exists('wp_set_consent_type')) {
            return;
        }

        wp_set_consent_type('syntekpro_forms', array(
            'label'       => __('SyntekPro Forms submissions', 'syntekpro-forms'),
            'description' => __('Allow SyntekPro Forms to store submissions, trigger webhooks, and send notifications once the visitor grants consent.', 'syntekpro-forms'),
            'context'     => 'plugin',
            'has_ajax'    => true,
        ));
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

    public function render_analytics_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'syntekpro-forms'));
        }

        $days = isset($_GET['days']) ? absint($_GET['days']) : 30;
        if ($days < 1) {
            $days = 30;
        }

        $analytics_summary = array();
        $field_dropoff = array();
        if ($this->growth_services) {
            $analytics_summary = $this->growth_services->get_analytics_summary($days);
            $field_dropoff = $this->growth_services->get_field_dropoff($days);
        }

        $view_file = SPF_PLUGIN_DIR . 'includes/admin/views/analytics.php';
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

    public function render_help_page() {
        $view_file = SPF_PLUGIN_DIR . 'includes/admin/views/help.php';
        if (file_exists($view_file)) {
            include $view_file;
        }
    }

    public function render_addons_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'syntekpro-forms'));
        }

        global $wpdb;
        $table = $this->get_webhook_queue_table();

        if (isset($_POST['spf_webhook_queue_action'])) {
            check_admin_referer('spf_webhook_queue_action', 'spf_webhook_queue_nonce');
            $action = sanitize_text_field(wp_unslash($_POST['spf_webhook_queue_action']));

            if ($action === 'retry_item' && !empty($_POST['item_id'])) {
                $item_id = absint($_POST['item_id']);
                if ($item_id > 0) {
                    $wpdb->update(
                        $table,
                        array(
                            'status' => 'pending',
                            'attempts' => 0,
                            'next_attempt_at' => current_time('mysql'),
                            'error_message' => null,
                            'response_code' => null,
                            'response_body' => null,
                        ),
                        array('id' => $item_id),
                        array('%s', '%d', '%s', '%s', '%s', '%s'),
                        array('%d')
                    );
                    $this->schedule_webhook_queue_processor();
                }
            }

            if ($action === 'retry_failed') {
                $wpdb->query(
                    "UPDATE {$table}
                     SET status = 'pending', attempts = 0, next_attempt_at = NOW(), error_message = NULL
                     WHERE status = 'failed'"
                );
                $this->schedule_webhook_queue_processor();
            }

            if ($action === 'clear_success') {
                $wpdb->query("DELETE FROM {$table} WHERE status = 'success'");
            }

            if ($action === 'run_processor_now') {
                $this->process_webhook_queue();
            }
        }

        $status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : 'failed';
        $allowed = array('all', 'failed', 'pending', 'success');
        if (!in_array($status, $allowed, true)) {
            $status = 'failed';
        }

        $where = '';
        $params = array();
        if ($status !== 'all') {
            $where = 'WHERE status = %s';
            $params[] = $status;
        }

        $items_sql = "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT 200";
        $items = empty($params) ? $wpdb->get_results($items_sql) : $wpdb->get_results($wpdb->prepare($items_sql, $params));

        $stats = array(
            'all' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}"),
            'failed' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status = %s", 'failed')),
            'pending' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status = %s", 'pending')),
            'success' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status = %s", 'success')),
        );

        $view_file = SPF_PLUGIN_DIR . 'includes/admin/views/addons.php';
        if (file_exists($view_file)) {
            $loaded_addons = $this->loaded_addons;
            include $view_file;
        }
    }

    public function render_webhook_queue_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'syntekpro-forms'));
        }

        global $wpdb;
        $table = $this->get_webhook_queue_table();

        if (isset($_POST['spf_webhook_queue_action'])) {
            check_admin_referer('spf_webhook_queue_action', 'spf_webhook_queue_nonce');
            $action = sanitize_text_field(wp_unslash($_POST['spf_webhook_queue_action']));

            if ($action === 'retry_item' && !empty($_POST['item_id'])) {
                $item_id = absint($_POST['item_id']);
                if ($item_id > 0) {
                    $wpdb->update(
                        $table,
                        array(
                            'status' => 'pending',
                            'attempts' => 0,
                            'next_attempt_at' => current_time('mysql'),
                            'error_message' => null,
                            'response_code' => null,
                            'response_body' => null,
                        ),
                        array('id' => $item_id),
                        array('%s', '%d', '%s', '%s', '%s', '%s'),
                        array('%d')
                    );
                    $this->schedule_webhook_queue_processor();
                }
            }

            if ($action === 'retry_failed') {
                $wpdb->query(
                    "UPDATE {$table}
                     SET status = 'pending', attempts = 0, next_attempt_at = NOW(), error_message = NULL
                     WHERE status = 'failed'"
                );
                $this->schedule_webhook_queue_processor();
            }

            if ($action === 'clear_success') {
                $wpdb->query("DELETE FROM {$table} WHERE status = 'success'");
            }

            if ($action === 'run_processor_now') {
                $this->process_webhook_queue();
            }
        }

        $status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : 'failed';
        $allowed = array('all', 'failed', 'pending', 'success');
        if (!in_array($status, $allowed, true)) {
            $status = 'failed';
        }

        $where = '';
        $params = array();
        if ($status !== 'all') {
            $where = 'WHERE status = %s';
            $params[] = $status;
        }

        $items_sql = "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT 200";
        $items = empty($params) ? $wpdb->get_results($items_sql) : $wpdb->get_results($wpdb->prepare($items_sql, $params));

        $stats = array(
            'all' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}"),
            'failed' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status = %s", 'failed')),
            'pending' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status = %s", 'pending')),
            'success' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status = %s", 'success')),
        );

        $view_file = SPF_PLUGIN_DIR . 'includes/admin/views/webhook-queue.php';
        if (file_exists($view_file)) {
            include $view_file;
        }
    }

    public function render_about_page() {
        $view_file = SPF_PLUGIN_DIR . 'includes/admin/views/about.php';
        if (file_exists($view_file)) {
            include $view_file;
        }
    }

    public function render_other_plugins_page() {
        $view_file = SPF_PLUGIN_DIR . 'includes/admin/views/other-plugins.php';
        if (file_exists($view_file)) {
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

        // Ensure frontend assets are enqueued when shortcode renders
        wp_enqueue_style('spf-frontend-css');
        wp_enqueue_script('spf-frontend-js');
        wp_enqueue_script('spf-conditional-logic');

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

            $this->enqueue_webhook_request((int) $form->id, (int) $entry_id, $url, $payload);
        }

        $this->schedule_webhook_queue_processor();
    }

    public function fire_webhook($url, $payload) {
        if (empty($url) || empty($payload)) {
            return;
        }

        $this->enqueue_webhook_request(0, 0, $url, $payload);
        $this->schedule_webhook_queue_processor();
        $this->process_webhook_queue(1);
    }

    private function get_webhook_queue_table() {
        global $wpdb;
        return $wpdb->prefix . 'spf_webhook_queue';
    }

    private function enqueue_webhook_request($form_id, $entry_id, $url, $payload) {
        global $wpdb;

        $table = $this->get_webhook_queue_table();
        $max_attempts = (int) apply_filters('syntekpro_forms_webhook_max_attempts', 5);
        if ($max_attempts < 1) {
            $max_attempts = 1;
        }

        $wpdb->insert(
            $table,
            array(
                'form_id' => absint($form_id),
                'entry_id' => absint($entry_id),
                'webhook_url' => esc_url_raw((string) $url),
                'payload' => wp_json_encode($payload),
                'status' => 'pending',
                'attempts' => 0,
                'max_attempts' => $max_attempts,
                'next_attempt_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s')
        );
    }

    public function process_webhook_queue($limit = 10) {
        global $wpdb;

        $limit = max(1, absint($limit));
        $table = $this->get_webhook_queue_table();

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT *
             FROM {$table}
             WHERE status = %s
               AND (next_attempt_at IS NULL OR next_attempt_at <= NOW())
             ORDER BY created_at ASC
             LIMIT %d",
            'pending',
            $limit
        ));

        if (empty($items)) {
            return;
        }

        foreach ($items as $item) {
            $this->process_webhook_queue_item($item);
        }
    }

    private function process_webhook_queue_item($item) {
        global $wpdb;

        $table = $this->get_webhook_queue_table();
        $attempt = (int) $item->attempts + 1;

        $wpdb->update(
            $table,
            array(
                'status' => 'processing',
                'attempts' => $attempt,
                'last_attempt_at' => current_time('mysql'),
            ),
            array('id' => (int) $item->id),
            array('%s', '%d', '%s'),
            array('%d')
        );

        $timeout = (int) apply_filters('syntekpro_forms_webhook_timeout', 8, $item);
        if ($timeout < 1) {
            $timeout = 8;
        }

        $response = wp_remote_post((string) $item->webhook_url, array(
            'timeout' => $timeout,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => (string) $item->payload,
        ));

        $is_success = false;
        $response_code = null;
        $response_body = '';
        $error_message = '';

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
        } else {
            $response_code = (int) wp_remote_retrieve_response_code($response);
            $response_body = (string) wp_remote_retrieve_body($response);
            $is_success = $response_code >= 200 && $response_code < 300;

            if (!$is_success) {
                $error_message = sprintf(__('HTTP %d returned by webhook endpoint.', 'syntekpro-forms'), $response_code);
            }
        }

        if ($is_success) {
            $wpdb->update(
                $table,
                array(
                    'status' => 'success',
                    'response_code' => $response_code,
                    'response_body' => wp_trim_words(wp_strip_all_tags($response_body), 120, '...'),
                    'error_message' => null,
                    'next_attempt_at' => null,
                ),
                array('id' => (int) $item->id),
                array('%s', '%d', '%s', '%s', '%s'),
                array('%d')
            );
            return;
        }

        $max_attempts = max(1, (int) $item->max_attempts);
        if ($attempt >= $max_attempts) {
            self::log_error(
                sprintf( 'Webhook #%d failed permanently after %d attempts: %s (URL: %s)', $item->id, $attempt, $error_message, $item->webhook_url ),
                'webhook',
                true
            );
            $wpdb->update(
                $table,
                array(
                    'status' => 'failed',
                    'response_code' => $response_code,
                    'response_body' => wp_trim_words(wp_strip_all_tags($response_body), 120, '...'),
                    'error_message' => $error_message,
                    'next_attempt_at' => null,
                ),
                array('id' => (int) $item->id),
                array('%s', '%d', '%s', '%s', '%s'),
                array('%d')
            );
            return;
        }

        $delay_seconds = $this->get_webhook_retry_delay($attempt);
        $next_attempt = gmdate('Y-m-d H:i:s', current_time('timestamp') + $delay_seconds);

        $wpdb->update(
            $table,
            array(
                'status' => 'pending',
                'response_code' => $response_code,
                'response_body' => wp_trim_words(wp_strip_all_tags($response_body), 120, '...'),
                'error_message' => $error_message,
                'next_attempt_at' => $next_attempt,
            ),
            array('id' => (int) $item->id),
            array('%s', '%d', '%s', '%s', '%s'),
            array('%d')
        );
    }

    private function get_webhook_retry_delay($attempt) {
        $attempt = max(1, absint($attempt));
        $delays = array(60, 300, 900, 3600, 7200);
        $delay = isset($delays[$attempt - 1]) ? $delays[$attempt - 1] : end($delays);

        return (int) apply_filters('syntekpro_forms_webhook_retry_delay', $delay, $attempt);
    }

    private function schedule_webhook_queue_processor() {
        if (!wp_next_scheduled('spf_process_webhook_queue')) {
            wp_schedule_event(time() + MINUTE_IN_SECONDS, 'spf_five_minutes', 'spf_process_webhook_queue');
        }

        $next_run = wp_next_scheduled('spf_process_webhook_queue');
        if (!$next_run || $next_run > (time() + 60)) {
            wp_schedule_single_event(time() + 15, 'spf_process_webhook_queue');
        }
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

        $disabled_addons = get_option('spf_disabled_addons', array());
        if (!is_array($disabled_addons)) {
            $disabled_addons = array();
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
                $resolved_file = realpath($file);
                if (!$resolved_file) {
                    continue;
                }

                if ($resolved_file === realpath(SPF_PLUGIN_FILE)) {
                    continue;
                }

                $file_hash = md5($resolved_file);
                if (in_array($file_hash, $disabled_addons, true)) {
                    continue;
                }

                require_once $resolved_file;
                $this->loaded_addons[] = $resolved_file;
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

        if (!isset($schedules['spf_five_minutes'])) {
            $schedules['spf_five_minutes'] = array(
                'interval' => 5 * MINUTE_IN_SECONDS,
                'display'  => __('Every 5 Minutes', 'syntekpro-forms'),
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
            array($this, 'render_dashboard_widget_v2')
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

    public function render_dashboard_widget_v2() {
        global $wpdb;

        $forms_table = $wpdb->prefix . 'spf_forms';
        $entries_table = $wpdb->prefix . 'spf_entries';

        $forms_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$forms_table} WHERE status != 'trash'");
        $entries_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$entries_table}");
        $unread_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$entries_table} WHERE status = %s", 'unread'));
        $read_count = max(0, $entries_count - $unread_count);

        $entries = $wpdb->get_results("\n            SELECT e.*, f.title as form_title \n            FROM {$entries_table} e \n            LEFT JOIN {$forms_table} f ON e.form_id = f.id \n            ORDER BY e.created_at DESC \n            LIMIT 5\n        ");

        $forms_url = esc_url(admin_url('admin.php?page=syntekpro-forms'));
        $entries_url = esc_url(admin_url('admin.php?page=syntekpro-forms-entries'));

        echo '<style>
            .spf-dashboard-summary a {
                transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
            }
            .spf-dashboard-summary a:hover,
            .spf-dashboard-summary a:focus {
                transform: translateY(-1px);
                box-shadow: 0 2px 8px rgba(0,0,0,.08);
                border-color: #a7aaad !important;
                outline: none;
            }
        </style>';

        echo '<div class="spf-dashboard-summary" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin:0 0 14px;">';
        echo '<a href="' . $forms_url . '" style="background:#f6f7f7;border:1px solid #dcdcde;border-radius:6px;padding:10px;text-decoration:none;color:inherit;display:block;"><div style="font-size:11px;color:#646970;text-transform:uppercase;letter-spacing:.4px;">' . esc_html__('Forms', 'syntekpro-forms') . '</div><div style="font-size:22px;font-weight:700;line-height:1.2;">' . esc_html((string) $forms_count) . '</div></a>';
        echo '<a href="' . $entries_url . '" style="background:#f6f7f7;border:1px solid #dcdcde;border-radius:6px;padding:10px;text-decoration:none;color:inherit;display:block;"><div style="font-size:11px;color:#646970;text-transform:uppercase;letter-spacing:.4px;">' . esc_html__('Entries', 'syntekpro-forms') . '</div><div style="font-size:22px;font-weight:700;line-height:1.2;">' . esc_html((string) $entries_count) . '</div></a>';
        echo '<a href="' . $entries_url . '" style="background:#f0f6ff;border:1px solid #bfdbfe;border-radius:6px;padding:10px;text-decoration:none;color:inherit;display:block;"><div style="font-size:11px;color:#1e40af;text-transform:uppercase;letter-spacing:.4px;">' . esc_html__('Read', 'syntekpro-forms') . '</div><div style="font-size:22px;font-weight:700;line-height:1.2;color:#1e3a8a;">' . esc_html((string) $read_count) . '</div></a>';
        echo '<a href="' . $entries_url . '" style="background:#fff7ed;border:1px solid #fed7aa;border-radius:6px;padding:10px;text-decoration:none;color:inherit;display:block;"><div style="font-size:11px;color:#9a3412;text-transform:uppercase;letter-spacing:.4px;">' . esc_html__('Unread', 'syntekpro-forms') . '</div><div style="font-size:22px;font-weight:700;line-height:1.2;color:#c2410c;">' . esc_html((string) $unread_count) . '</div></a>';
        echo '</div>';

        if ($forms_count === 0 && $entries_count === 0) {
            echo '<div style="padding:14px;border:1px dashed #c3c4c7;border-radius:6px;background:#fff;">';
            echo '<p style="margin:0 0 10px;font-size:13px;">' . esc_html__("Don't have a form yet. Add one to start collecting entries.", 'syntekpro-forms') . '</p>';
            echo '<p style="margin:0 0 10px;">';
            echo '<a href="' . esc_url(admin_url('admin.php?page=syntekpro-forms-add-new')) . '" class="button button-primary" style="margin-right: 8px;">' . esc_html__('Add Form', 'syntekpro-forms') . '</a>';
            echo '<a href="' . esc_url(admin_url('admin.php?page=syntekpro-forms')) . '" class="button button-secondary" style="margin-right: 8px;">' . esc_html__('View All Forms', 'syntekpro-forms') . '</a>';
            echo '<a href="' . esc_url(admin_url('admin.php?page=syntekpro-forms-entries')) . '" class="button button-secondary">' . esc_html__('View All Entries', 'syntekpro-forms') . '</a>';
            echo '</p>';
            echo '</div>';
            return;
        }

        if (empty($entries)) {
            echo '<div style="padding:12px;border:1px solid #dcdcde;border-radius:6px;background:#fff;">';
            echo '<p style="margin:0 0 10px;">' . esc_html__('No entries yet. Your forms are ready to collect submissions.', 'syntekpro-forms') . '</p>';
            echo '<a href="' . esc_url(admin_url('admin.php?page=syntekpro-forms')) . '" class="button button-secondary" style="margin-right: 8px;">' . esc_html__('View All Forms', 'syntekpro-forms') . '</a>';
            echo '<a href="' . esc_url(admin_url('admin.php?page=syntekpro-forms-entries')) . '" class="button button-secondary">' . esc_html__('View All Entries', 'syntekpro-forms') . '</a>';
            echo '</div>';
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

            $form_title = !empty($entry->form_title) ? $entry->form_title : __('Untitled Form', 'syntekpro-forms');
            echo '<li style="padding: 10px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">';
            echo '<div>';
            echo '<div style="font-weight: 600; font-size: 14px;">' . esc_html($form_title) . '</div>';
            echo '<div style="font-size: 12px; color: #646970;">' . esc_html($preview) . '</div>';
            echo '</div>';
            echo '<div style="text-align: right;">';
            echo '<div style="font-size: 11px; color: #999;">' . esc_html(human_time_diff(strtotime((string) $entry->created_at), current_time('timestamp')) . ' ' . __('ago', 'syntekpro-forms')) . '</div>';
            if ($entry->status === 'unread') {
                echo '<span style="display: inline-block; width: 8px; height: 8px; background: #ffb900; border-radius: 50%; margin-left: 5px;" title="' . esc_attr__('Unread', 'syntekpro-forms') . '"></span>';
            }
            echo '</div>';
            echo '</li>';
        }
        echo '</ul>';
        echo '<p style="margin-top: 15px; text-align: right;">';
        echo '<a href="' . esc_url(admin_url('admin.php?page=syntekpro-forms')) . '" class="button button-secondary" style="margin-right: 8px;">' . esc_html__('View All Forms', 'syntekpro-forms') . '</a>';
        echo '<a href="' . esc_url(admin_url('admin.php?page=syntekpro-forms-entries')) . '" class="button button-secondary">' . esc_html__('View All Entries', 'syntekpro-forms') . '</a>';
        echo '</p>';
    }

    /**
     * Register GDPR personal data exporter.
     */
    public function register_gdpr_exporter($exporters) {
        if (class_exists('SyntekPro_Forms_GDPR')) {
            $exporters['syntekpro-forms'] = array(
                'exporter_friendly_name' => __('SyntekPro Forms Entries', 'syntekpro-forms'),
                'callback'               => array('SyntekPro_Forms_GDPR', 'export_personal_data'),
            );
        }
        return $exporters;
    }

    /**
     * Register GDPR personal data eraser.
     */
    public function register_gdpr_eraser($erasers) {
        if (class_exists('SyntekPro_Forms_GDPR')) {
            $erasers['syntekpro-forms'] = array(
                'eraser_friendly_name' => __('SyntekPro Forms Entries', 'syntekpro-forms'),
                'callback'             => array('SyntekPro_Forms_GDPR', 'erase_personal_data'),
            );
        }
        return $erasers;
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
