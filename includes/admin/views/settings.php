<?php
/**
 * Settings Page View - SyntekPro Forms v1.2
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['spf_save_settings']) && check_admin_referer('spf_settings_nonce')) {
    $settings = array(
        'license_key' => sanitize_text_field($_POST['license_key']),
        'currency' => sanitize_text_field($_POST['currency']),
        'enable_logging' => isset($_POST['enable_logging']) ? 1 : 0,
        'default_theme' => sanitize_text_field($_POST['default_theme']),
        'enable_toolbar_menu' => isset($_POST['enable_toolbar_menu']) ? 1 : 0,
        'enable_dashboard_widget' => isset($_POST['enable_dashboard_widget']) ? 1 : 0,
        'enable_background_updates' => isset($_POST['enable_background_updates']) ? 1 : 0,
        'no_conflict_mode' => isset($_POST['no_conflict_mode']) ? 1 : 0,
        'enable_akismet' => isset($_POST['enable_akismet']) ? 1 : 0,
        'enable_data_collection' => isset($_POST['enable_data_collection']) ? 1 : 0,
        
        'recaptcha_site_key' => sanitize_text_field($_POST['recaptcha_site_key']),
        'recaptcha_secret_key' => sanitize_text_field($_POST['recaptcha_secret_key']),
        'recaptcha_invisible' => isset($_POST['recaptcha_invisible']) ? 1 : 0,
        'enable_honeypot' => isset($_POST['enable_honeypot']) ? 1 : 0,
        
        'from_email' => sanitize_email($_POST['from_email']),
        'from_name' => sanitize_text_field($_POST['from_name']),
        'delete_entries_on_uninstall' => isset($_POST['delete_entries_on_uninstall']) ? 1 : 0,
        'enable_ip_logging' => isset($_POST['enable_ip_logging']) ? 1 : 0,
        'anonymize_ip' => isset($_POST['anonymize_ip']) ? 1 : 0,
        'data_retention_days' => isset($_POST['data_retention_days']) ? absint($_POST['data_retention_days']) : 0,
        'trash_retention_days' => isset($_POST['trash_retention_days']) ? absint($_POST['trash_retention_days']) : 40,
        'rate_limit_enabled' => isset($_POST['rate_limit_enabled']) ? 1 : 0,
        'rate_limit_seconds' => isset($_POST['rate_limit_seconds']) ? absint($_POST['rate_limit_seconds']) : 0
    );
    
    update_option('spf_settings', $settings);
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'syntekpro-forms') . '</p></div>';
}

$settings = get_option('spf_settings', array());
$defaults = array(
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
    'rate_limit_seconds' => 30
);
$settings = wp_parse_args($settings, $defaults);
$tutorial_url = esc_url(plugins_url('docs/TUTORIAL.md', SPF_PLUGIN_FILE));
?>

<div class="wrap spf-settings-page">
    <div class="spf-admin-header">
        <div class="spf-admin-header-left">
        </div>
        <div class="spf-admin-header-center">
            <img src="<?php echo SPF_PLUGIN_URL; ?>assets/images/Syntekpro%20Forms%20Logo.png" class="spf-admin-logo" alt="SyntekPro">
        </div>
        <div class="spf-admin-header-right">
            <a href="<?php echo $tutorial_url; ?>" target="_blank" class="button button-primary" style="display:inline-flex;align-items:center;gap:6px;">
                <span class="dashicons dashicons-book"></span>
                <?php _e('Open Full Tutorial', 'syntekpro-forms'); ?>
            </a>
        </div>
    </div>

    <div class="spf-admin-page-title-wrap" style="display: flex; justify-content: space-between; align-items: center;">
        <h2 class="spf-admin-page-title"><?php _e('SyntekPro Forms Settings', 'syntekpro-forms'); ?></h2>
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
                <button type="button" class="spf-settings-tab-btn" data-tab="uninstall">
                    <span class="dashicons dashicons-trash"></span>
                    <?php _e('Uninstall', 'syntekpro-forms'); ?>
                </button>
                <button type="button" class="spf-settings-tab-btn" data-tab="pro">
                    <span class="dashicons dashicons-awards"></span>
                    <?php _e('Pro', 'syntekpro-forms'); ?>
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
                        <p class="spf-setting-description"><?php _e('Enable to allow SyntekPro Forms to download and install bug fixes and security updates automatically in the background. Requires a valid license key.', 'syntekpro-forms'); ?></p>
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

                    <div class="spf-settings-footer">
                        <button type="submit" name="spf_save_settings" class="button button-primary button-large">
                            <?php _e('Save Settings', 'syntekpro-forms'); ?> &rarr;
                        </button>
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

                <!-- Uninstall Tab -->
                <div id="spf-settings-tab-uninstall" class="spf-settings-tab-pane">
                    <h2><span class="dashicons dashicons-trash"></span> <?php _e('Uninstall SyntekPro Forms', 'syntekpro-forms'); ?></h2>
                    
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
                    <h2><span class="dashicons dashicons-info"></span> <?php _e('About SyntekPro Forms', 'syntekpro-forms'); ?></h2>
                    
                    <div class="spf-about-card">
                        <div class="spf-about-logo" style="margin-bottom: 25px; text-align: center; background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #eee; display: inline-block; width: 100%; box-sizing: border-box;">
                            <img src="<?php echo SPF_PLUGIN_URL; ?>assets/images/Syntekpro%20Forms%20Logo.png" alt="SyntekPro Forms Logo" style="max-width: 300px; height: auto;">
                        </div>
                        <h3><?php _e('Professional Form Builder for WordPress', 'syntekpro-forms'); ?></h3>
                        <p><?php _e('Thank you for choosing SyntekPro Forms. We are dedicated to providing the best form building experience for WordPress users.', 'syntekpro-forms'); ?></p>
                        
                        <div class="spf-about-links">
                            <a href="https://syntekpro.com/wpplugins" target="_blank" class="button button-secondary">
                                <span class="dashicons dashicons-external"></span> <?php _e('Check Other Products', 'syntekpro-forms'); ?>
                            </a>
                            <a href="https://syntekpro.com/support" target="_blank" class="button button-secondary">
                                <span class="dashicons dashicons-sos"></span> <?php _e('Get Support', 'syntekpro-forms'); ?>
                            </a>
                        </div>

                        <div style="margin-top: 50px; color: #646970; font-size: 13px;">
                            <p>&copy; 2026 <a href="https://syntekpro.com" target="_blank" class="spf-footer-link"><img src="<?php echo SPF_PLUGIN_URL; ?>assets/images/company-logo.png" alt="SyntekPro" class="spf-footer-logo"> SyntekProSyntekPro</a>. All rights reserved.</p>
                        </div>
                    </div>
                </div>

                <!-- Pro Tab -->
                <div id="spf-settings-tab-pro" class="spf-settings-tab-pane">
                    <h2><span class="dashicons dashicons-awards"></span> <?php _e('Go Pro', 'syntekpro-forms'); ?></h2>
                    <div class="spf-about-card" style="display:grid;grid-template-columns:1fr;gap:20px;max-width:900px;">
                        <div>
                            <p style="font-size:15px;"><?php _e('Unlock advanced capabilities to supercharge your forms and workflows.', 'syntekpro-forms'); ?></p>
                            <ul style="list-style:disc;padding-left:20px;line-height:1.7;">
                                <li><?php _e('Multi-step forms with progress bars and per-step validation', 'syntekpro-forms'); ?></li>
                                <li><?php _e('Payments with Stripe/PayPal, coupons, and conditional totals', 'syntekpro-forms'); ?></li>
                                <li><?php _e('Save & Resume plus draft links for partially completed forms', 'syntekpro-forms'); ?></li>
                                <li><?php _e('Advanced conditional logic and calculations across fields', 'syntekpro-forms'); ?></li>
                                <li><?php _e('Webhooks, Zapier/Make connectors, and CRM/email integrations', 'syntekpro-forms'); ?></li>
                            </ul>
                        </div>
                        <div style="background:#f6f7fb;border:1px solid #e5e8f0;border-radius:12px;padding:20px;">
                            <h3 style="margin-top:0;display:flex;align-items:center;gap:8px;"><span class="dashicons dashicons-star-filled" style="color:#eab308;"></span><?php _e('Upgrade to SyntekPro Forms Pro', 'syntekpro-forms'); ?></h3>
                            <p style="margin:10px 0 16px;"><?php _e('Get priority support, premium templates, and automation add-ons.', 'syntekpro-forms'); ?></p>
                            <a href="https://syntekpro.com/forms-pro" target="_blank" class="button button-primary button-hero" style="background:#7c5bff;border-color:#7c5bff;">
                                <?php _e('Get Pro', 'syntekpro-forms'); ?>
                            </a>
                            <p style="margin:12px 0 0;font-size:13px;color:#555;"><?php _e('30-day money-back guarantee.', 'syntekpro-forms'); ?></p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

<div class="spf-admin-footer" style="background:#f8ebb4;border:1px solid #ccd0d4;border-radius:4px;padding:10px 20px;margin-top:20px;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 1px rgba(0,0,0,0.04);">
    <a class="spf-footer-brand" href="https://syntekpro.com" target="_blank" rel="noopener noreferrer">
        <span><?php _e('Powered by', 'syntekpro-forms'); ?></span>
        <img src="<?php echo SPF_PLUGIN_URL; ?>assets/images/SYNTEK%20PRO%20LOGO%20Transparent%20Icon%20500x150.png" class="spf-footer-icon" alt="SyntekPro" style="height:45px !important;width:auto !important;max-height:45px !important;max-width:150px !important;object-fit:contain;display:inline-block;vertical-align:middle;">
    </a>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab switching logic
    $('.spf-settings-tab-btn').on('click', function() {
        var tabId = $(this).data('tab');
        
        $('.spf-settings-tab-btn').removeClass('active');
        $(this).addClass('active');
        
        $('.spf-settings-tab-pane').removeClass('active');
        $('#spf-settings-tab-' + tabId).addClass('active');
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
