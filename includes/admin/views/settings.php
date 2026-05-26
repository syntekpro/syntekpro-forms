<?php
/**
 * Settings Page View - SyntekPro Forms v1.2
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$smtp_notice = '';
$smtp_notice_type = 'success';

// Handle form submission
if ((isset($_POST['spf_save_settings']) || isset($_POST['spf_send_test_email'])) && check_admin_referer('spf_settings_nonce')) {
    $post_data = wp_unslash($_POST);
    $settings = array(
        'license_key' => sanitize_text_field($post_data['license_key'] ?? ''),
        'currency' => sanitize_text_field($post_data['currency'] ?? ''),
        'enable_logging' => !empty($post_data['enable_logging']) ? 1 : 0,
        'default_theme' => sanitize_text_field($post_data['default_theme'] ?? ''),
        'enable_toolbar_menu' => !empty($post_data['enable_toolbar_menu']) ? 1 : 0,
        'enable_dashboard_widget' => !empty($post_data['enable_dashboard_widget']) ? 1 : 0,
        'enable_background_updates' => !empty($post_data['enable_background_updates']) ? 1 : 0,
        'no_conflict_mode' => !empty($post_data['no_conflict_mode']) ? 1 : 0,
        'enable_akismet' => !empty($post_data['enable_akismet']) ? 1 : 0,
        'enable_data_collection' => !empty($post_data['enable_data_collection']) ? 1 : 0,
        
        'recaptcha_site_key' => sanitize_text_field($post_data['recaptcha_site_key'] ?? ''),
        'recaptcha_secret_key' => sanitize_text_field($post_data['recaptcha_secret_key'] ?? ''),
        'recaptcha_invisible' => !empty($post_data['recaptcha_invisible']) ? 1 : 0,
        'enable_honeypot' => !empty($post_data['enable_honeypot']) ? 1 : 0,
        
        'from_email' => sanitize_email($post_data['from_email'] ?? ''),
        'from_name' => sanitize_text_field($post_data['from_name'] ?? ''),
        'delete_entries_on_uninstall' => !empty($post_data['delete_entries_on_uninstall']) ? 1 : 0,
        'enable_ip_logging' => !empty($post_data['enable_ip_logging']) ? 1 : 0,
        'anonymize_ip' => !empty($post_data['anonymize_ip']) ? 1 : 0,
        'data_retention_days' => isset($post_data['data_retention_days']) ? absint($post_data['data_retention_days']) : 0,
        'trash_retention_days' => isset($post_data['trash_retention_days']) ? absint($post_data['trash_retention_days']) : 40,
        'rate_limit_enabled' => !empty($post_data['rate_limit_enabled']) ? 1 : 0,
        'rate_limit_seconds' => isset($post_data['rate_limit_seconds']) ? absint($post_data['rate_limit_seconds']) : 0,
        'rest_api_enabled' => !empty($post_data['rest_api_enabled']) ? 1 : 0,
        'salesforce_instance_url' => esc_url_raw($post_data['salesforce_instance_url'] ?? ''),
        'salesforce_access_token' => sanitize_text_field($post_data['salesforce_access_token'] ?? ''),
        'activecampaign_api_url' => esc_url_raw($post_data['activecampaign_api_url'] ?? ''),
        'activecampaign_api_key' => sanitize_text_field($post_data['activecampaign_api_key'] ?? ''),
        'brevo_api_key' => sanitize_text_field($post_data['brevo_api_key'] ?? ''),
        'brevo_list_id' => isset($post_data['brevo_list_id']) ? absint($post_data['brevo_list_id']) : 0,
        'smtp_enabled' => !empty($post_data['smtp_enabled']) ? 1 : 0,
        'smtp_provider' => sanitize_key($post_data['smtp_provider'] ?? 'custom'),
        'smtp_host' => sanitize_text_field($post_data['smtp_host'] ?? ''),
        'smtp_port' => isset($post_data['smtp_port']) ? absint($post_data['smtp_port']) : 587,
        'smtp_encryption' => sanitize_key($post_data['smtp_encryption'] ?? 'tls'),
        'smtp_auth_type' => sanitize_key($post_data['smtp_auth_type'] ?? 'password'),
        'smtp_username' => sanitize_text_field($post_data['smtp_username'] ?? ''),
        'smtp_oauth_provider' => sanitize_key($post_data['smtp_oauth_provider'] ?? 'google'),
        'smtp_oauth_client_id' => sanitize_text_field($post_data['smtp_oauth_client_id'] ?? ''),
        'smtp_oauth_tenant_id' => sanitize_text_field($post_data['smtp_oauth_tenant_id'] ?? 'common')
    );

    if ($settings['smtp_provider'] === '') {
        $settings['smtp_provider'] = 'custom';
    }
    if (!in_array($settings['smtp_encryption'], array('tls', 'ssl', 'none'), true)) {
        $settings['smtp_encryption'] = 'tls';
    }
    if (!in_array($settings['smtp_auth_type'], array('password', 'oauth2'), true)) {
        $settings['smtp_auth_type'] = 'password';
    }
    if (!in_array($settings['smtp_oauth_provider'], array('google', 'microsoft'), true)) {
        $settings['smtp_oauth_provider'] = 'google';
    }
    if ($settings['smtp_port'] < 1) {
        $settings['smtp_port'] = 587;
    }

    if (class_exists('SyntekPro_Forms_SMTP')) {
        if (array_key_exists('smtp_password', $post_data) && trim((string) ($post_data['smtp_password'] ?? '')) !== '') {
            SyntekPro_Forms_SMTP::save_secret('password', (string) ($post_data['smtp_password'] ?? ''));
        }
        if (array_key_exists('smtp_oauth_client_secret', $post_data) && trim((string) ($post_data['smtp_oauth_client_secret'] ?? '')) !== '') {
            SyntekPro_Forms_SMTP::save_secret('oauth_client_secret', (string) ($post_data['smtp_oauth_client_secret'] ?? ''));
        }
        if (array_key_exists('smtp_oauth_refresh_token', $post_data) && trim((string) ($post_data['smtp_oauth_refresh_token'] ?? '')) !== '') {
            SyntekPro_Forms_SMTP::save_secret('oauth_refresh_token', (string) ($post_data['smtp_oauth_refresh_token'] ?? ''));
        }
    }
    
    $existing_settings = get_option('spf_settings', array());
    if (!is_array($existing_settings)) {
        $existing_settings = array();
    }
    update_option('spf_settings', array_merge($existing_settings, $settings));

    if (isset($_POST['spf_send_test_email'])) {
        $test_email = sanitize_email($post_data['smtp_test_email'] ?? get_option('admin_email'));
        if (!is_email($test_email)) {
            $smtp_notice = __('Please enter a valid email address for the SMTP test.', 'syntekpro-forms');
            $smtp_notice_type = 'error';
        } else {
            $subject = __('SyntekPro Forms SMTP Test Email', 'syntekpro-forms');
            $message = sprintf(
                /* translators: 1: site name, 2: date/time */
                __('This is a test email from %1$s sent at %2$s.', 'syntekpro-forms'),
                get_bloginfo('name'),
                wp_date(get_option('date_format') . ' ' . get_option('time_format'))
            );

            $sent = wp_mail($test_email, $subject, $message);
            if ($sent) {
                $smtp_notice = sprintf(
                    /* translators: %s: recipient email */
                    __('SMTP test email sent successfully to %s.', 'syntekpro-forms'),
                    $test_email
                );
                $smtp_notice_type = 'success';
            } else {
                $smtp_notice = __('SMTP test failed. Please verify SMTP host, credentials, and OAuth2 settings.', 'syntekpro-forms');
                $smtp_notice_type = 'error';
            }
        }
    } else {
        $smtp_notice = __('Settings saved successfully!', 'syntekpro-forms');
        $smtp_notice_type = 'success';
    }
}

if ($smtp_notice !== '') {
    echo '<div class="notice notice-' . esc_attr($smtp_notice_type) . ' is-dismissible"><p>' . esc_html($smtp_notice) . '</p></div>';
}

$settings = get_option('spf_settings', array());
$defaults = array(
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
    'salesforce_instance_url' => '',
    'salesforce_access_token' => '',
    'activecampaign_api_url' => '',
    'activecampaign_api_key' => '',
    'brevo_api_key' => '',
    'brevo_list_id' => 0,
    'smtp_enabled' => 0,
    'smtp_provider' => 'custom',
    'smtp_host' => '',
    'smtp_port' => 587,
    'smtp_encryption' => 'tls',
    'smtp_auth_type' => 'password',
    'smtp_username' => '',
    'smtp_oauth_provider' => 'google',
    'smtp_oauth_client_id' => '',
    'smtp_oauth_tenant_id' => 'common'
);
$settings = wp_parse_args($settings, $defaults);
$tutorial_url = esc_url(plugins_url('docs/TUTORIAL.md', SPF_PLUGIN_FILE));

$smtp_presets = class_exists('SyntekPro_Forms_SMTP')
    ? SyntekPro_Forms_SMTP::get_provider_presets()
    : array();
$smtp_password_saved = class_exists('SyntekPro_Forms_SMTP') ? SyntekPro_Forms_SMTP::has_secret('password') : false;
$smtp_oauth_secret_saved = class_exists('SyntekPro_Forms_SMTP') ? SyntekPro_Forms_SMTP::has_secret('oauth_client_secret') : false;
$smtp_oauth_refresh_saved = class_exists('SyntekPro_Forms_SMTP') ? SyntekPro_Forms_SMTP::has_secret('oauth_refresh_token') : false;
$smtp_recent_logs = class_exists('SyntekPro_Forms_SMTP') ? SyntekPro_Forms_SMTP::get_recent_logs(20) : array();

$settings_theme = wp_get_theme();
$settings_timezone = wp_timezone_string();
if ($settings_timezone === '') {
    $settings_timezone = sprintf('UTC%s', get_option('gmt_offset') ? get_option('gmt_offset') : '+0');
}

$settings_system_info = array(
    __('Plugin Version', 'syntekpro-forms') => defined('SPF_VERSION') ? SPF_VERSION : __('N/A', 'syntekpro-forms'),
    __('WordPress Version', 'syntekpro-forms') => get_bloginfo('version'),
    __('PHP Version', 'syntekpro-forms') => phpversion(),
    __('Database Version', 'syntekpro-forms') => method_exists($wpdb, 'db_version') ? $wpdb->db_version() : __('N/A', 'syntekpro-forms'),
    __('Active Theme', 'syntekpro-forms') => $settings_theme->get('Name') . ' ' . $settings_theme->get('Version'),
    __('Timezone', 'syntekpro-forms') => $settings_timezone,
    __('Memory Limit', 'syntekpro-forms') => defined('WP_MEMORY_LIMIT') ? WP_MEMORY_LIMIT : ini_get('memory_limit'),
    __('WP Debug', 'syntekpro-forms') => (defined('WP_DEBUG') && WP_DEBUG) ? __('Enabled', 'syntekpro-forms') : __('Disabled', 'syntekpro-forms'),
);

$analytics_days = isset($_GET['days']) ? absint($_GET['days']) : 30;
if ($analytics_days < 1) {
    $analytics_days = 30;
}
$analytics_summary = array();
$field_dropoff = array();
if (class_exists('SyntekPro_Forms_Builder')) {
    $builder_instance = SyntekPro_Forms_Builder::get_instance();
    if (method_exists($builder_instance, 'get_growth_services')) {
        $growth_service = $builder_instance->get_growth_services();
        if ($growth_service) {
            $analytics_summary = $growth_service->get_analytics_summary($analytics_days);
            $field_dropoff = $growth_service->get_field_dropoff($analytics_days);
        }
    }
}

$plugin_cards = array(
    array(
        'title' => __('SyntekPro Animation', 'syntekpro-forms'),
        'description' => __('Create smooth, branded animation effects for your website components and UI sections.', 'syntekpro-forms'),
        'url' => 'https://plugins.syntekpro.com/animations',
    ),
    array(
        'title' => __('SyntekPro Toggle', 'syntekpro-forms'),
        'description' => __('Control feature switches and interactive toggles with lightweight, reliable behavior.', 'syntekpro-forms'),
        'url' => 'https://plugins.syntekpro.com/animations/toggle',
    ),
    array(
        'title' => __('SyntekPro License Server', 'syntekpro-forms'),
        'description' => __('Handle license activation, verification, and entitlement checks for SyntekPro products.', 'syntekpro-forms'),
        'url' => 'https://plugins.syntekpro.com/animations/license',
    ),
);
?>

<div class="wrap spf-settings-page">
    <div class="spf-admin-header">
        <div class="spf-admin-header-left">
        </div>
        <div class="spf-admin-header-center">
            <img src="<?php echo SPF_PLUGIN_URL; ?>assets/images/Syntekpro%20Forms%20Logo.png" class="spf-admin-logo" alt="SyntekPro">
        </div>
        <div class="spf-admin-header-right">
            <a href="<?php echo $tutorial_url; ?>" target="_blank" class="button button-primary spf-inline-icon-btn">
                <span class="dashicons dashicons-book"></span>
                <?php _e('Open Full Tutorial', 'syntekpro-forms'); ?>
            </a>
        </div>
    </div>

    <div class="spf-admin-page-title-wrap spf-page-toolbar">
        <h1 class="spf-admin-page-title"><?php _e('Settings', 'syntekpro-forms'); ?></h1>
        <span class="spf-badge"><?php _e('v', 'syntekpro-forms'); ?><?php echo SPF_VERSION; ?></span>
    </div>

    <form method="post" action="">
        <?php wp_nonce_field('spf_settings_nonce'); ?>
        
        <div class="spf-settings-layout">
            <!-- Sidebar Tabs -->
            <div class="spf-settings-tabs">
                <button type="button" class="spf-settings-tab-btn active" data-tab="general">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php _e('General Settings', 'syntekpro-forms'); ?>
                </button>
                <button type="button" class="spf-settings-tab-btn" data-tab="protection">
                    <span class="dashicons dashicons-shield"></span>
                    <?php _e('ReCAPTCHA & Protection', 'syntekpro-forms'); ?>
                </button>
                <button type="button" class="spf-settings-tab-btn" data-tab="smtp">
                    <span class="dashicons dashicons-email-alt"></span>
                    <?php _e('SMTP & Email Delivery', 'syntekpro-forms'); ?>
                </button>
                <button type="button" class="spf-settings-tab-btn" data-tab="rest-api">
                    <span class="dashicons dashicons-rest-api"></span>
                    <?php _e('Rest Api', 'syntekpro-forms'); ?>
                </button>
                <button type="button" class="spf-settings-tab-btn" data-tab="analytics">
                    <span class="dashicons dashicons-chart-area"></span>
                    <?php _e('Analytics', 'syntekpro-forms'); ?>
                </button>
                <button type="button" class="spf-settings-tab-btn" data-tab="uninstall">
                    <span class="dashicons dashicons-trash"></span>
                    <?php _e('Uninstall', 'syntekpro-forms'); ?>
                </button>
                <button type="button" class="spf-settings-tab-btn" data-tab="about">
                    <span class="dashicons dashicons-info"></span>
                    <?php _e('About', 'syntekpro-forms'); ?>
                </button>
            </div>

            <!-- Content Area -->
            <div class="spf-settings-content">
                
                <!-- General Settings Tab -->
                <div id="spf-settings-tab-general" class="spf-settings-tab-pane active">
                    <h2><span class="dashicons dashicons-admin-settings"></span> <?php _e('General Settings', 'syntekpro-forms'); ?></h2>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label"><?php _e('License Key', 'syntekpro-forms'); ?></label>
                        <div class="spf-license-box">
                            <input type="text" name="license_key" value="<?php echo esc_attr($settings['license_key']); ?>" class="regular-text" placeholder="XXXX-XXXX-XXXX-XXXX">
                            <p class="spf-setting-description"><?php _e('Enter your license key to enable automatic updates and premium support.', 'syntekpro-forms'); ?></p>
                        </div>
                    </div>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label"><?php _e('Currency', 'syntekpro-forms'); ?></label>
                        <div class="spf-setting-input-wrap">
                            <select name="currency">
                                <option value="USD" <?php selected($settings['currency'], 'USD'); ?>><?php _e('U.S. Dollar ($)', 'syntekpro-forms'); ?></option>
                                <option value="EUR" <?php selected($settings['currency'], 'EUR'); ?>><?php _e('Euro (€)', 'syntekpro-forms'); ?></option>
                                <option value="GBP" <?php selected($settings['currency'], 'GBP'); ?>><?php _e('British Pound (£)', 'syntekpro-forms'); ?></option>
                            </select>
                        </div>
                        <p class="spf-setting-description"><?php _e('Select the default currency for your forms. This is used for product fields, credit card fields and others.', 'syntekpro-forms'); ?></p>
                    </div>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label"><?php _e('Logging', 'syntekpro-forms'); ?></label>
                        <div class="spf-setting-input-wrap">
                            <label>
                                <input type="checkbox" name="enable_logging" value="1" <?php checked($settings['enable_logging'], 1); ?>>
                                <?php _e('Enable Logging', 'syntekpro-forms'); ?>
                            </label>
                        </div>
                        <p class="spf-setting-description"><?php _e('Enable if you would like logging within SyntekPro Forms. Logging allows you to easily debug the inner workings of the plugin to solve any possible issues.', 'syntekpro-forms'); ?></p>
                    </div>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label"><?php _e('Default Form Theme', 'syntekpro-forms'); ?></label>
                        <div class="spf-setting-input-wrap">
                            <select name="default_theme">
                                <option value="inherit" <?php selected($settings['default_theme'], 'inherit'); ?>><?php _e('Use Site Theme', 'syntekpro-forms'); ?></option>
                                <option value="classic" <?php selected($settings['default_theme'], 'classic'); ?>><?php _e('Classic', 'syntekpro-forms'); ?></option>
                                <option value="modern" <?php selected($settings['default_theme'], 'modern'); ?>><?php _e('Modern', 'syntekpro-forms'); ?></option>
                                <option value="minimal" <?php selected($settings['default_theme'], 'minimal'); ?>><?php _e('Minimal', 'syntekpro-forms'); ?></option>
                                <option value="elegant" <?php selected($settings['default_theme'], 'elegant'); ?>><?php _e('Elegant', 'syntekpro-forms'); ?></option>
                                <option value="contrast" <?php selected($settings['default_theme'], 'contrast'); ?>><?php _e('High Contrast', 'syntekpro-forms'); ?></option>
                                <option value="pastel" <?php selected($settings['default_theme'], 'pastel'); ?>><?php _e('Pastel', 'syntekpro-forms'); ?></option>
                                <option value="outline" <?php selected($settings['default_theme'], 'outline'); ?>><?php _e('Outline', 'syntekpro-forms'); ?></option>
                                <option value="glass" <?php selected($settings['default_theme'], 'glass'); ?>><?php _e('Glassmorphism', 'syntekpro-forms'); ?></option>
                            </select>
                        </div>
                        <p class="spf-setting-description"><?php _e('This theme will be used by default everywhere forms are embedded on your site.', 'syntekpro-forms'); ?> <a href="#" class="spf-help-link"><?php _e('Learn more', 'syntekpro-forms'); ?></a></p>
                    </div>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label"><?php _e('Toolbar Menu', 'syntekpro-forms'); ?></label>
                        <div class="spf-setting-input-wrap">
                            <label>
                                <input type="checkbox" name="enable_toolbar_menu" value="1" <?php checked($settings['enable_toolbar_menu'], 1); ?>>
                                <?php _e('Enable Toolbar Menu', 'syntekpro-forms'); ?>
                            </label>
                        </div>
                        <p class="spf-setting-description"><?php _e('Enable to display the forms menu in the WordPress top toolbar. The forms menu will display the ten forms recently opened in the form editor.', 'syntekpro-forms'); ?></p>
                    </div>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label"><?php _e('Dashboard Widget', 'syntekpro-forms'); ?></label>
                        <div class="spf-setting-input-wrap">
                            <label>
                                <input type="checkbox" name="enable_dashboard_widget" value="1" <?php checked($settings['enable_dashboard_widget'], 1); ?>>
                                <?php _e('Enable Dashboard Widget', 'syntekpro-forms'); ?>
                            </label>
                        </div>
                        <p class="spf-setting-description"><?php _e('Turn on to enable the SyntekPro Forms dashboard widget. The dashboard widget displays a list of forms and the number of entries each form has.', 'syntekpro-forms'); ?></p>
                    </div>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label"><?php _e('Automatic Background Updates', 'syntekpro-forms'); ?></label>
                        <div class="spf-setting-input-wrap">
                            <label>
                                <input type="checkbox" name="enable_background_updates" value="1" <?php checked($settings['enable_background_updates'], 1); ?>>
                                <?php _e('Enable Automatic Background Updates', 'syntekpro-forms'); ?>
                            </label>
                        </div>
                        <p class="spf-setting-description"><?php _e('Enable to automatically install SyntekPro Forms updates on this site when a new version is available.', 'syntekpro-forms'); ?></p>
                    </div>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label"><?php _e('No Conflict Mode', 'syntekpro-forms'); ?></label>
                        <div class="spf-setting-input-wrap">
                            <label>
                                <input type="checkbox" name="no_conflict_mode" value="1" <?php checked($settings['no_conflict_mode'], 1); ?>>
                                <?php _e('Enable No Conflict Mode', 'syntekpro-forms'); ?>
                            </label>
                        </div>
                        <p class="spf-setting-description"><?php _e('Enable to prevent extraneous scripts and styles from being printed on SyntekPro Forms admin pages, reducing conflicts with other plugins and themes.', 'syntekpro-forms'); ?></p>
                    </div>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label"><?php _e('Akismet Integration', 'syntekpro-forms'); ?></label>
                        <div class="spf-setting-input-wrap">
                            <label>
                                <input type="checkbox" name="enable_akismet" value="1" <?php checked($settings['enable_akismet'], 1); ?>>
                                <?php _e('Enable Akismet Integration', 'syntekpro-forms'); ?>
                            </label>
                        </div>
                        <p class="spf-setting-description"><?php _e('Protect your form entries from spam using Akismet.', 'syntekpro-forms'); ?></p>
                    </div>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label"><?php _e('Data Collection', 'syntekpro-forms'); ?></label>
                        <div class="spf-setting-input-wrap">
                            <label>
                                <input type="checkbox" name="enable_data_collection" value="1" <?php checked($settings['enable_data_collection'], 1); ?>>
                                <?php _e('Enable Data Collection', 'syntekpro-forms'); ?>
                            </label>
                        </div>
                        <p class="spf-setting-description"><?php _e('We love improving the form building experience for everyone in our community. By enabling data collection, you can help us learn more about how our customers use SyntekPro Forms.', 'syntekpro-forms'); ?> <a href="#" class="spf-help-link"><?php _e('Learn more', 'syntekpro-forms'); ?></a></p>
                    </div>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label"><?php _e('Salesforce Instance URL', 'syntekpro-forms'); ?></label>
                        <div class="spf-setting-input-wrap">
                            <input type="url" name="salesforce_instance_url" value="<?php echo esc_attr($settings['salesforce_instance_url']); ?>" class="regular-text" placeholder="https://your-instance.my.salesforce.com">
                        </div>
                    </div>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label"><?php _e('Salesforce Access Token', 'syntekpro-forms'); ?></label>
                        <div class="spf-setting-input-wrap">
                            <input type="text" name="salesforce_access_token" value="<?php echo esc_attr($settings['salesforce_access_token']); ?>" class="regular-text" placeholder="Salesforce OAuth access token">
                        </div>
                    </div>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label"><?php _e('ActiveCampaign API URL', 'syntekpro-forms'); ?></label>
                        <div class="spf-setting-input-wrap">
                            <input type="url" name="activecampaign_api_url" value="<?php echo esc_attr($settings['activecampaign_api_url']); ?>" class="regular-text" placeholder="https://youraccount.api-us1.com">
                        </div>
                    </div>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label"><?php _e('ActiveCampaign API Key', 'syntekpro-forms'); ?></label>
                        <div class="spf-setting-input-wrap">
                            <input type="text" name="activecampaign_api_key" value="<?php echo esc_attr($settings['activecampaign_api_key']); ?>" class="regular-text" placeholder="ActiveCampaign API key">
                        </div>
                    </div>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label"><?php _e('Brevo API Key', 'syntekpro-forms'); ?></label>
                        <div class="spf-setting-input-wrap">
                            <input type="text" name="brevo_api_key" value="<?php echo esc_attr($settings['brevo_api_key']); ?>" class="regular-text" placeholder="Brevo API key">
                        </div>
                    </div>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label"><?php _e('Brevo List ID', 'syntekpro-forms'); ?></label>
                        <div class="spf-setting-input-wrap">
                            <input type="number" min="0" name="brevo_list_id" value="<?php echo esc_attr((string) $settings['brevo_list_id']); ?>" class="small-text">
                        </div>
                        <p class="spf-setting-description"><?php _e('Optional. If provided, new contacts are added to this Brevo list.', 'syntekpro-forms'); ?></p>
                    </div>

                    <div class="spf-settings-footer">
                        <button type="submit" name="spf_save_settings" class="button button-primary button-large">
                            <?php _e('Save Settings', 'syntekpro-forms'); ?> &rarr;
                        </button>
                    </div>
                </div>

                <!-- SMTP Tab -->
                <div id="spf-settings-tab-smtp" class="spf-settings-tab-pane">
                    <h2><span class="dashicons dashicons-email-alt"></span> <?php _e('SMTP & Email Delivery', 'syntekpro-forms'); ?></h2>

                    <div class="spf-setting-field">
                        <label>
                            <input type="checkbox" name="smtp_enabled" value="1" <?php checked($settings['smtp_enabled'], 1); ?>>
                            <strong><?php _e('Enable SMTP for outgoing mail', 'syntekpro-forms'); ?></strong>
                        </label>
                        <p class="spf-setting-description"><?php _e('When enabled, SyntekPro Forms uses your SMTP provider for all wp_mail notifications and confirmations.', 'syntekpro-forms'); ?></p>
                    </div>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label" for="spf-smtp-provider"><?php _e('Provider Preset', 'syntekpro-forms'); ?></label>
                        <div class="spf-setting-input-wrap">
                            <select id="spf-smtp-provider" name="smtp_provider">
                                <?php foreach ($smtp_presets as $preset_key => $preset): ?>
                                    <option
                                        value="<?php echo esc_attr($preset_key); ?>"
                                        data-host="<?php echo esc_attr($preset['host']); ?>"
                                        data-port="<?php echo esc_attr((string) $preset['port']); ?>"
                                        data-encryption="<?php echo esc_attr($preset['encryption']); ?>"
                                        data-auth-type="<?php echo esc_attr($preset['auth_type']); ?>"
                                        data-oauth-provider="<?php echo esc_attr($preset['oauth_provider']); ?>"
                                        <?php selected($settings['smtp_provider'], $preset_key); ?>
                                    >
                                        <?php echo esc_html($preset['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <p class="spf-setting-description"><?php _e('Select Gmail OAuth2, Outlook OAuth2, SendGrid, Mailgun, SES, or Custom. Presets auto-fill host, port, and auth mode.', 'syntekpro-forms'); ?></p>
                    </div>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label" for="spf-smtp-host"><?php _e('SMTP Host', 'syntekpro-forms'); ?></label>
                        <input id="spf-smtp-host" type="text" name="smtp_host" value="<?php echo esc_attr($settings['smtp_host']); ?>" class="regular-text" placeholder="smtp.example.com">
                    </div>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label" for="spf-smtp-port"><?php _e('SMTP Port', 'syntekpro-forms'); ?></label>
                        <input id="spf-smtp-port" type="number" min="1" name="smtp_port" value="<?php echo esc_attr((string) $settings['smtp_port']); ?>" class="small-text">
                    </div>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label" for="spf-smtp-encryption"><?php _e('Encryption', 'syntekpro-forms'); ?></label>
                        <div class="spf-setting-input-wrap">
                            <select id="spf-smtp-encryption" name="smtp_encryption">
                                <option value="tls" <?php selected($settings['smtp_encryption'], 'tls'); ?>><?php _e('TLS / STARTTLS', 'syntekpro-forms'); ?></option>
                                <option value="ssl" <?php selected($settings['smtp_encryption'], 'ssl'); ?>><?php _e('SSL', 'syntekpro-forms'); ?></option>
                                <option value="none" <?php selected($settings['smtp_encryption'], 'none'); ?>><?php _e('None', 'syntekpro-forms'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label" for="spf-smtp-auth-type"><?php _e('Authentication', 'syntekpro-forms'); ?></label>
                        <div class="spf-setting-input-wrap">
                            <select id="spf-smtp-auth-type" name="smtp_auth_type">
                                <option value="password" <?php selected($settings['smtp_auth_type'], 'password'); ?>><?php _e('Username + Password', 'syntekpro-forms'); ?></option>
                                <option value="oauth2" <?php selected($settings['smtp_auth_type'], 'oauth2'); ?>><?php _e('OAuth2 (Gmail / Outlook)', 'syntekpro-forms'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label" for="spf-smtp-username"><?php _e('SMTP Username / Email', 'syntekpro-forms'); ?></label>
                        <input id="spf-smtp-username" type="text" name="smtp_username" value="<?php echo esc_attr($settings['smtp_username']); ?>" class="regular-text" placeholder="notifications@example.com">
                    </div>

                    <div id="spf-smtp-password-wrap">
                        <div class="spf-setting-field">
                            <label class="spf-setting-label" for="spf-smtp-password"><?php _e('SMTP Password / API Key', 'syntekpro-forms'); ?></label>
                            <input id="spf-smtp-password" type="password" name="smtp_password" value="" class="regular-text" autocomplete="new-password" placeholder="<?php echo $smtp_password_saved ? esc_attr__('Saved (leave blank to keep current)', 'syntekpro-forms') : esc_attr__('Enter SMTP password or API key', 'syntekpro-forms'); ?>">
                            <p class="spf-setting-description"><?php _e('Stored encrypted with openssl_encrypt() in a non-autoloaded option and never rendered back into the DOM.', 'syntekpro-forms'); ?></p>
                        </div>
                    </div>

                    <div id="spf-smtp-oauth-wrap">
                        <div class="spf-setting-field">
                            <label class="spf-setting-label" for="spf-smtp-oauth-provider"><?php _e('OAuth Provider', 'syntekpro-forms'); ?></label>
                            <div class="spf-setting-input-wrap">
                                <select id="spf-smtp-oauth-provider" name="smtp_oauth_provider">
                                    <option value="google" <?php selected($settings['smtp_oauth_provider'], 'google'); ?>><?php _e('Google (Gmail)', 'syntekpro-forms'); ?></option>
                                    <option value="microsoft" <?php selected($settings['smtp_oauth_provider'], 'microsoft'); ?>><?php _e('Microsoft (Outlook / 365)', 'syntekpro-forms'); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="spf-setting-field">
                            <label class="spf-setting-label" for="spf-smtp-oauth-client-id"><?php _e('OAuth Client ID', 'syntekpro-forms'); ?></label>
                            <input id="spf-smtp-oauth-client-id" type="text" name="smtp_oauth_client_id" value="<?php echo esc_attr($settings['smtp_oauth_client_id']); ?>" class="regular-text" placeholder="OAuth App Client ID">
                        </div>

                        <div class="spf-setting-field">
                            <label class="spf-setting-label" for="spf-smtp-oauth-client-secret"><?php _e('OAuth Client Secret', 'syntekpro-forms'); ?></label>
                            <input id="spf-smtp-oauth-client-secret" type="password" name="smtp_oauth_client_secret" value="" class="regular-text" autocomplete="new-password" placeholder="<?php echo $smtp_oauth_secret_saved ? esc_attr__('Saved (leave blank to keep current)', 'syntekpro-forms') : esc_attr__('Enter OAuth client secret', 'syntekpro-forms'); ?>">
                        </div>

                        <div class="spf-setting-field">
                            <label class="spf-setting-label" for="spf-smtp-oauth-refresh-token"><?php _e('OAuth Refresh Token', 'syntekpro-forms'); ?></label>
                            <input id="spf-smtp-oauth-refresh-token" type="password" name="smtp_oauth_refresh_token" value="" class="regular-text" autocomplete="new-password" placeholder="<?php echo $smtp_oauth_refresh_saved ? esc_attr__('Saved (leave blank to keep current)', 'syntekpro-forms') : esc_attr__('Enter OAuth refresh token', 'syntekpro-forms'); ?>">
                        </div>

                        <div class="spf-setting-field">
                            <label class="spf-setting-label" for="spf-smtp-oauth-tenant-id"><?php _e('Microsoft Tenant ID', 'syntekpro-forms'); ?></label>
                            <input id="spf-smtp-oauth-tenant-id" type="text" name="smtp_oauth_tenant_id" value="<?php echo esc_attr($settings['smtp_oauth_tenant_id']); ?>" class="regular-text" placeholder="common">
                            <p class="spf-setting-description"><?php _e('Only used for Microsoft OAuth2. Keep as "common" unless your Azure app requires a specific tenant.', 'syntekpro-forms'); ?></p>
                        </div>
                    </div>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label" for="spf-smtp-test-email"><?php _e('Send Test Email To', 'syntekpro-forms'); ?></label>
                        <input id="spf-smtp-test-email" type="email" name="smtp_test_email" value="<?php echo esc_attr(get_option('admin_email')); ?>" class="regular-text" placeholder="admin@example.com">
                    </div>

                    <div class="spf-settings-footer" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                        <button type="submit" name="spf_save_settings" class="button button-primary button-large">
                            <?php _e('Save SMTP Settings', 'syntekpro-forms'); ?>
                        </button>
                        <button type="submit" name="spf_send_test_email" class="button button-secondary button-large">
                            <?php _e('Send Test Email', 'syntekpro-forms'); ?>
                        </button>
                    </div>

                    <div class="spf-admin-content-card" style="padding:16px;margin-top:16px;">
                        <h3><?php _e('Email Delivery Log', 'syntekpro-forms'); ?></h3>
                        <?php if (empty($smtp_recent_logs)): ?>
                            <p><?php _e('No email events logged yet. Send a test email to validate transport and populate this log.', 'syntekpro-forms'); ?></p>
                        <?php else: ?>
                            <table class="widefat striped">
                                <thead>
                                <tr>
                                    <th><?php _e('Status', 'syntekpro-forms'); ?></th>
                                    <th><?php _e('Timestamp', 'syntekpro-forms'); ?></th>
                                    <th><?php _e('Recipient', 'syntekpro-forms'); ?></th>
                                    <th><?php _e('Subject', 'syntekpro-forms'); ?></th>
                                    <th><?php _e('Error', 'syntekpro-forms'); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($smtp_recent_logs as $log_row): ?>
                                    <tr>
                                        <td>
                                            <?php if (($log_row['status'] ?? '') === 'sent'): ?>
                                                <span style="color:#007017;font-weight:600;"><?php _e('Sent', 'syntekpro-forms'); ?></span>
                                            <?php else: ?>
                                                <span style="color:#b42318;font-weight:600;"><?php _e('Failed', 'syntekpro-forms'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html((string) ($log_row['created_at'] ?? '')); ?></td>
                                        <td><?php echo esc_html((string) ($log_row['recipient'] ?? '')); ?></td>
                                        <td><?php echo esc_html((string) ($log_row['subject'] ?? '')); ?></td>
                                        <td><?php echo esc_html((string) ($log_row['error_message'] ?? '')); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ReCAPTCHA & Protection Tab -->
                <div id="spf-settings-tab-protection" class="spf-settings-tab-pane">
                    <h2><span class="dashicons dashicons-shield"></span> <?php _e('ReCAPTCHA & Protection', 'syntekpro-forms'); ?></h2>
                    
                    <div class="spf-security-page">
                        <div class="spf-security-card">
                            <h3><span class="dashicons dashicons-google"></span> <?php _e('Google ReCAPTCHA', 'syntekpro-forms'); ?></h3>
                            <div class="spf-setting-field">
                                <label class="spf-setting-label"><?php _e('Site Key', 'syntekpro-forms'); ?></label>
                                <input type="text" name="recaptcha_site_key" value="<?php echo esc_attr($settings['recaptcha_site_key']); ?>" class="regular-text">
                            </div>
                            <div class="spf-setting-field">
                                <label class="spf-setting-label"><?php _e('Secret Key', 'syntekpro-forms'); ?></label>
                                <input type="text" name="recaptcha_secret_key" value="<?php echo esc_attr($settings['recaptcha_secret_key']); ?>" class="regular-text">
                            </div>
                            <div class="spf-setting-field">
                                <label>
                                    <input type="checkbox" name="recaptcha_invisible" value="1" <?php checked($settings['recaptcha_invisible'], 1); ?>>
                                    <strong><?php _e('Enable Invisible reCAPTCHA', 'syntekpro-forms'); ?></strong>
                                </label>
                                <p class="spf-setting-description"><?php _e('When enabled, the reCAPTCHA badge will be hidden and verification will happen automatically.', 'syntekpro-forms'); ?></p>
                            </div>
                            <p class="spf-setting-description">
                                <?php _e('Get your reCAPTCHA keys from', 'syntekpro-forms'); ?> 
                                <a href="https://www.google.com/recaptcha/admin" target="_blank">Google reCAPTCHA</a>. 
                                <?php _e('Support for v2 and v3.', 'syntekpro-forms'); ?>
                            </p>
                        </div>

                        <div class="spf-security-card">
                            <h3><span class="dashicons dashicons-hidden"></span> <?php _e('Spam Protection', 'syntekpro-forms'); ?></h3>
                            <div class="spf-setting-field">
                                <label>
                                    <input type="checkbox" name="enable_honeypot" value="1" <?php checked($settings['enable_honeypot'], 1); ?>>
                                    <strong><?php _e('Enable Honeypot', 'syntekpro-forms'); ?></strong>
                                </label>
                                <p class="spf-setting-description"><?php _e('Adds a hidden field to your forms that only bots will fill out, allowing you to identify and block spam submissions.', 'syntekpro-forms'); ?></p>
                            </div>

                            <div class="spf-setting-field">
                                <label>
                                    <input type="checkbox" name="enable_ip_logging" value="1" <?php checked($settings['enable_ip_logging'], 1); ?>>
                                    <strong><?php _e('IP Address Logging', 'syntekpro-forms'); ?></strong>
                                </label>
                                <p class="spf-setting-description"><?php _e('When enabled, the IP address of form submitters will be recorded to help prevent abuse.', 'syntekpro-forms'); ?></p>
                            </div>

                            <div class="spf-setting-field">
                                <label>
                                    <input type="checkbox" name="anonymize_ip" value="1" <?php checked($settings['anonymize_ip'], 1); ?>>
                                    <strong><?php _e('Anonymize IP Addresses', 'syntekpro-forms'); ?></strong>
                                </label>
                                <p class="spf-setting-description"><?php _e('Mask the last octet of IPv4 and truncate IPv6 addresses to reduce personal data storage while still tracking abusive patterns.', 'syntekpro-forms'); ?></p>
                            </div>

                            <div class="spf-setting-field">
                                <label class="spf-setting-label"><?php _e('Data Retention (days)', 'syntekpro-forms'); ?></label>
                                <input type="number" min="0" name="data_retention_days" value="<?php echo esc_attr($settings['data_retention_days']); ?>" class="small-text">
                                <p class="spf-setting-description"><?php _e('Automatically purge entries older than this many days. Set to 0 to keep entries indefinitely.', 'syntekpro-forms'); ?></p>
                            </div>

                            <div class="spf-setting-field">
                                <label class="spf-setting-label"><?php _e('Auto-Clear Trash (days)', 'syntekpro-forms'); ?></label>
                                <input type="number" min="0" name="trash_retention_days" value="<?php echo esc_attr($settings['trash_retention_days']); ?>" class="small-text">
                                <p class="spf-setting-description"><?php _e('Permanently delete forms in Trash after this many days. Set to 0 to keep trashed forms indefinitely.', 'syntekpro-forms'); ?></p>
                            </div>

                            <div class="spf-setting-field">
                                <label>
                                    <input type="checkbox" name="rate_limit_enabled" value="1" <?php checked($settings['rate_limit_enabled'], 1); ?>>
                                    <strong><?php _e('Enable Rate Limiting', 'syntekpro-forms'); ?></strong>
                                </label>
                                <p class="spf-setting-description"><?php _e('Throttle repeat submissions from the same IP to reduce spam bursts.', 'syntekpro-forms'); ?></p>
                                <div style="margin-top:8px;">
                                    <label class="spf-setting-label" style="display:inline-block;margin-right:8px;"><?php _e('Cooldown (seconds)', 'syntekpro-forms'); ?></label>
                                    <input type="number" min="0" name="rate_limit_seconds" value="<?php echo esc_attr($settings['rate_limit_seconds']); ?>" class="small-text">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="spf-settings-footer">
                        <button type="submit" name="spf_save_settings" class="button button-primary button-large">
                            <?php _e('Save Protection Settings', 'syntekpro-forms'); ?>
                        </button>
                    </div>
                </div>

                <!-- Rest Api Tab -->
                <div id="spf-settings-tab-rest-api" class="spf-settings-tab-pane">
                    <h2><span class="dashicons dashicons-rest-api"></span> <?php _e('Rest Api', 'syntekpro-forms'); ?></h2>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label"><?php _e('Enable REST API', 'syntekpro-forms'); ?></label>
                        <div class="spf-setting-input-wrap">
                            <label>
                                <input type="checkbox" name="rest_api_enabled" value="1" <?php checked($settings['rest_api_enabled'], 1); ?>>
                                <?php _e('Allow external access via SyntekPro Forms REST endpoints', 'syntekpro-forms'); ?>
                            </label>
                        </div>
                        <p class="spf-setting-description"><?php _e('Disable this to stop registering custom routes under /wp-json/syntekpro-forms/v1.', 'syntekpro-forms'); ?></p>
                    </div>

                    <div class="spf-setting-field">
                        <label class="spf-setting-label"><?php _e('Available Base Endpoint', 'syntekpro-forms'); ?></label>
                        <div class="spf-setting-input-wrap">
                            <input type="text" class="regular-text" readonly value="<?php echo esc_attr(rest_url('syntekpro-forms/v1')); ?>">
                        </div>
                        <p class="spf-setting-description"><?php _e('Use this base URL for forms and entries API requests.', 'syntekpro-forms'); ?></p>
                    </div>

                    <div class="spf-settings-footer">
                        <button type="submit" name="spf_save_settings" class="button button-primary button-large">
                            <?php _e('Save REST API Settings', 'syntekpro-forms'); ?>
                        </button>
                    </div>
                </div>

                <!-- Analytics Tab -->
                <div id="spf-settings-tab-analytics" class="spf-settings-tab-pane">
                    <h2><span class="dashicons dashicons-chart-area"></span> <?php _e('Forms Analytics', 'syntekpro-forms'); ?></h2>

                    <div style="display:flex;gap:8px;align-items:center;margin:0 0 14px;">
                        <label for="spf-analytics-days"><?php _e('Date window', 'syntekpro-forms'); ?></label>
                        <select id="spf-analytics-days" name="days">
                            <option value="7" <?php selected($analytics_days, 7); ?>><?php _e('7 days', 'syntekpro-forms'); ?></option>
                            <option value="14" <?php selected($analytics_days, 14); ?>><?php _e('14 days', 'syntekpro-forms'); ?></option>
                            <option value="30" <?php selected($analytics_days, 30); ?>><?php _e('30 days', 'syntekpro-forms'); ?></option>
                            <option value="60" <?php selected($analytics_days, 60); ?>><?php _e('60 days', 'syntekpro-forms'); ?></option>
                            <option value="90" <?php selected($analytics_days, 90); ?>><?php _e('90 days', 'syntekpro-forms'); ?></option>
                        </select>
                        <button type="button" id="spf-apply-analytics-window" class="button button-primary"><?php _e('Apply', 'syntekpro-forms'); ?></button>
                    </div>

                    <div class="spf-admin-content-card" style="padding:16px;">
                        <h3><?php _e('Conversion Funnel by Form', 'syntekpro-forms'); ?></h3>
                        <?php if (empty($analytics_summary)): ?>
                            <p><?php _e('No analytics data available yet. Open a form and start submitting to populate this dashboard.', 'syntekpro-forms'); ?></p>
                        <?php else: ?>
                            <table class="widefat striped">
                                <thead>
                                <tr>
                                    <th><?php _e('Form', 'syntekpro-forms'); ?></th>
                                    <th><?php _e('Views', 'syntekpro-forms'); ?></th>
                                    <th><?php _e('Starts', 'syntekpro-forms'); ?></th>
                                    <th><?php _e('Completions', 'syntekpro-forms'); ?></th>
                                    <th><?php _e('Abandons', 'syntekpro-forms'); ?></th>
                                    <th><?php _e('Dropoff Events', 'syntekpro-forms'); ?></th>
                                    <th><?php _e('Completion Rate', 'syntekpro-forms'); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($analytics_summary as $row): ?>
                                    <?php
                                    $starts = (int) ($row['start'] ?? 0);
                                    $completions = (int) ($row['complete'] ?? 0);
                                    $completion_rate = $starts > 0 ? round(($completions / $starts) * 100, 2) : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($row['form_title'] !== '' ? $row['form_title'] : '#' . (int) $row['form_id']); ?></td>
                                        <td><?php echo (int) ($row['view'] ?? 0); ?></td>
                                        <td><?php echo $starts; ?></td>
                                        <td><?php echo $completions; ?></td>
                                        <td><?php echo (int) ($row['abandon'] ?? 0); ?></td>
                                        <td><?php echo (int) ($row['field_dropoff'] ?? 0); ?></td>
                                        <td><?php echo esc_html(number_format_i18n($completion_rate, 2)); ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>

                    <div class="spf-admin-content-card" style="padding:16px;margin-top:16px;">
                        <h3><?php _e('Top Field Dropoff', 'syntekpro-forms'); ?></h3>
                        <?php if (empty($field_dropoff)): ?>
                            <p><?php _e('No field dropoff data yet.', 'syntekpro-forms'); ?></p>
                        <?php else: ?>
                            <table class="widefat striped">
                                <thead>
                                <tr>
                                    <th><?php _e('Form ID', 'syntekpro-forms'); ?></th>
                                    <th><?php _e('Field', 'syntekpro-forms'); ?></th>
                                    <th><?php _e('Events', 'syntekpro-forms'); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($field_dropoff as $item): ?>
                                    <tr>
                                        <td><?php echo (int) $item->form_id; ?></td>
                                        <td><?php echo esc_html((string) $item->field_name); ?></td>
                                        <td><?php echo (int) $item->total; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Uninstall Tab -->
                <div id="spf-settings-tab-uninstall" class="spf-settings-tab-pane">
                    <h2><span class="dashicons dashicons-trash"></span> <?php _e('Uninstall', 'syntekpro-forms'); ?></h2>
                    
                    <div class="spf-danger-zone">
                        <h3><span class="dashicons dashicons-warning"></span> <?php _e('Danger Zone', 'syntekpro-forms'); ?></h3>
                        <p><?php _e('Use these settings to control what happens when you delete the plugin.', 'syntekpro-forms'); ?></p>
                        
                        <div class="spf-setting-field">
                            <label>
                                <input type="checkbox" name="delete_entries_on_uninstall" value="1" <?php checked($settings['delete_entries_on_uninstall'], 1); ?>>
                                <strong><?php _e('Delete all forms and entries when plugin is uninstalled', 'syntekpro-forms'); ?></strong>
                            </label>
                            <p class="spf-setting-description"><?php _e('Warning: Checking this will permanently delete all your forms and entry data from the database when you delete the plugin from the WordPress Plugins page.', 'syntekpro-forms'); ?></p>
                        </div>

                        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #d63638;">
                            <p><strong><?php _e('Need to completely reset?', 'syntekpro-forms'); ?></strong></p>
                            <button type="button" id="spf-reset-plugin" class="button button-link-delete" style="color: #d63638;">
                                <?php _e('Reset all plugin data now', 'syntekpro-forms'); ?>
                            </button>
                        </div>
                    </div>

                    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee;">
                        <h3><span class="dashicons dashicons-migrate"></span> <?php _e('Import/Export Settings', 'syntekpro-forms'); ?></h3>
                        <p><?php _e('Move your plugin settings between sites easily.', 'syntekpro-forms'); ?></p>
                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <button type="button" id="spf-export-settings" class="button button-secondary">
                                <span class="dashicons dashicons-download"></span> <?php _e('Export Settings', 'syntekpro-forms'); ?>
                            </button>
                            <button type="button" id="spf-import-settings-trigger" class="button button-secondary">
                                <span class="dashicons dashicons-upload"></span> <?php _e('Import Settings', 'syntekpro-forms'); ?>
                            </button>
                        </div>
                        <div id="spf-import-wrap" style="display:none; margin-top: 20px;">
                            <textarea id="spf-import-data" style="width:100%; height:100px;" placeholder="<?php _e('Paste export data here...', 'syntekpro-forms'); ?>"></textarea>
                            <button type="button" id="spf-confirm-import" class="button button-primary" style="margin-top: 10px;">
                                <?php _e('Confirm Import', 'syntekpro-forms'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- About Tab -->
                <div id="spf-settings-tab-about" class="spf-settings-tab-pane">
                    <h2><span class="dashicons dashicons-info"></span> <?php _e('About', 'syntekpro-forms'); ?></h2>

                    <p class="description spf-about-intro">
                        <?php esc_html_e('SyntekPro Forms is a professional form builder for WordPress that blends drag & drop ease with the flexibility of Gutenberg blocks and shortcodes.', 'syntekpro-forms'); ?>
                    </p>

                    <div class="spf-about-grid">
                        <div class="spf-about-card">
                            <div class="spf-about-logo">
                                <img src="<?php echo SPF_PLUGIN_URL; ?>assets/images/Syntekpro%20Forms%20Logo.png" alt="SyntekPro Forms Logo">
                            </div>
                            <h3><?php _e('What This Plugin Provides', 'syntekpro-forms'); ?></h3>
                            <ul>
                                <li><?php esc_html_e('A responsive form builder with workflow-friendly entry management and export tools.', 'syntekpro-forms'); ?></li>
                                <li><?php esc_html_e('Built-in conditional logic, validation, and spam defenses to keep submissions reliable.', 'syntekpro-forms'); ?></li>
                                <li><?php esc_html_e('Seamless Gutenberg blocks, shortcodes, and global settings so forms can be reused across pages.', 'syntekpro-forms'); ?></li>
                                <li><?php esc_html_e('Action hooks for notifications, webhooks, and integrations so you can connect to your favorite services.', 'syntekpro-forms'); ?></li>
                            </ul>
                        </div>

                        <div class="spf-about-card">
                            <h3><?php esc_html_e('Why It Matters', 'syntekpro-forms'); ?></h3>
                            <p><?php esc_html_e('Every submission is stored, timestamped, and searchable so you never miss a lead or support request, and the plugin is tuned for performance even on high-traffic sites.', 'syntekpro-forms'); ?></p>

                            <h3><?php esc_html_e('Stay in Touch', 'syntekpro-forms'); ?></h3>
                            <p><?php _e('Thank you for choosing SyntekPro Forms. Build high-converting forms, manage entries faster, and run your forms with dependable performance.', 'syntekpro-forms'); ?></p>
                            <p>
                                <a href="<?php echo esc_url('https://syntekpro.com'); ?>" target="_blank" rel="noopener noreferrer" class="button button-secondary"><?php _e('Visit syntekpro.com', 'syntekpro-forms'); ?></a>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=syntekpro-forms-add-new')); ?>" class="button button-primary" style="margin-left:8px;"><?php _e('Create a Form', 'syntekpro-forms'); ?></a>
                            </p>
                        </div>

                        <div class="spf-about-card spf-system-info-card">
                            <h3><?php _e('System Information', 'syntekpro-forms'); ?></h3>
                            <table class="spf-system-info-table" role="table">
                                <tbody>
                                <?php foreach ($settings_system_info as $label => $value): ?>
                                    <tr>
                                        <th scope="row"><?php echo esc_html($label); ?></th>
                                        <td><?php echo esc_html((string) $value); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="spf-about-card">
                            <h3><?php _e('Other Plugins', 'syntekpro-forms'); ?></h3>
                            <p><?php esc_html_e('Explore SyntekPro plugin services from one clean panel.', 'syntekpro-forms'); ?></p>

                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin:12px 0 18px;">
                                <?php foreach ($plugin_cards as $card): ?>
                                    <div style="border:1px solid #dcdcde;border-radius:8px;padding:12px;background:#fff;">
                                        <h4 style="margin:0 0 6px;font-size:14px;"><?php echo esc_html($card['title']); ?></h4>
                                        <p style="margin:0 0 10px;color:#646970;font-size:13px;"><?php echo esc_html($card['description']); ?></p>
                                        <a href="<?php echo esc_url($card['url']); ?>" target="_blank" rel="noopener noreferrer" class="button button-secondary"><?php _e('Open Plugin', 'syntekpro-forms'); ?></a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    var requestedTab = '<?php echo esc_js(isset($_GET['spf_settings_tab']) ? sanitize_key((string) $_GET['spf_settings_tab']) : ''); ?>';
    var smtpPresetWasApplied = false;

    // Tab switching logic
    $('.spf-settings-tab-btn').on('click', function() {
        var tabId = $(this).data('tab');
        
        $('.spf-settings-tab-btn').removeClass('active');
        $(this).addClass('active');
        
        $('.spf-settings-tab-pane').removeClass('active');
        $('#spf-settings-tab-' + tabId).addClass('active');
    });

    if (requestedTab && $('.spf-settings-tab-btn[data-tab="' + requestedTab + '"]').length) {
        $('.spf-settings-tab-btn[data-tab="' + requestedTab + '"]').trigger('click');
    }

    function toggleSmtpAuthSections() {
        var mode = $('#spf-smtp-auth-type').val() || 'password';
        if (mode === 'oauth2') {
            $('#spf-smtp-password-wrap').hide();
            $('#spf-smtp-oauth-wrap').show();
        } else {
            $('#spf-smtp-password-wrap').show();
            $('#spf-smtp-oauth-wrap').hide();
        }
    }

    function applySmtpPreset(forceApply) {
        var $selected = $('#spf-smtp-provider option:selected');
        if (!$selected.length) {
            return;
        }

        if (!forceApply && !smtpPresetWasApplied) {
            smtpPresetWasApplied = true;
            return;
        }

        var host = $selected.data('host');
        var port = $selected.data('port');
        var encryption = $selected.data('encryption');
        var authType = $selected.data('auth-type');
        var oauthProvider = $selected.data('oauth-provider');

        if (typeof host !== 'undefined') {
            $('#spf-smtp-host').val(host);
        }
        if (typeof port !== 'undefined') {
            $('#spf-smtp-port').val(port);
        }
        if (typeof encryption !== 'undefined') {
            $('#spf-smtp-encryption').val(encryption);
        }
        if (typeof authType !== 'undefined') {
            $('#spf-smtp-auth-type').val(authType);
        }
        if (typeof oauthProvider !== 'undefined') {
            $('#spf-smtp-oauth-provider').val(oauthProvider);
        }

        toggleSmtpAuthSections();
    }

    $('#spf-smtp-provider').on('change', function() {
        applySmtpPreset(true);
    });

    $('#spf-smtp-auth-type').on('change', function() {
        toggleSmtpAuthSections();
    });

    toggleSmtpAuthSections();
    applySmtpPreset(false);

    $('#spf-apply-analytics-window').on('click', function() {
        var days = $('#spf-analytics-days').val() || '30';
        var url = new URL(window.location.href);
        url.searchParams.set('page', 'syntekpro-forms-settings');
        url.searchParams.set('spf_settings_tab', 'analytics');
        url.searchParams.set('days', days);
        window.location.href = url.toString();
    });

    // Handle Reset Button
    $('#spf-reset-plugin').on('click', function() {
        if (confirm('<?php echo esc_js(__("WARNING: This will immediately delete all forms and entries. This action cannot be undone. Are you sure?", "syntekpro-forms")); ?>')) {
            // Implementation for immediate reset can be added via AJAX if needed
            alert('This feature will be available in the next minor update.');
        }
    });

    // Handle Export
    $('#spf-export-settings').on('click', function() {
        $.post(spfAdmin.ajaxurl, {
            action: 'spf_export_settings',
            nonce: spfAdmin.nonce
        }, function(response) {
            if (response.success) {
                var data = JSON.stringify(response.data);
                var blob = new Blob([data], {type: 'application/json'});
                var url = window.URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = 'spf-settings-export.json';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
            }
        });
    });

    // Handle Import Trigger
    $('#spf-import-settings-trigger').on('click', function() {
        $('#spf-import-wrap').slideToggle();
    });

    // Handle Confirm Import
    $('#spf-confirm-import').on('click', function() {
        var importData = $('#spf-import-data').val();
        if (!importData) return;

        if (confirm('<?php echo esc_js(__("This will overwrite your current settings. Continue?", "syntekpro-forms")); ?>')) {
            $.post(spfAdmin.ajaxurl, {
                action: 'spf_import_settings',
                nonce: spfAdmin.nonce,
                import_data: importData
            }, function(response) {
                if (response.success) {
                    alert(response.data);
                    window.location.reload();
                } else {
                    alert(response.data);
                }
            });
        }
    });
});
</script>
