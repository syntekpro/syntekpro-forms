<?php
/**
 * About Page View
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$theme = wp_get_theme();
$timezone = wp_timezone_string();
if ($timezone === '') {
    $timezone = sprintf('UTC%s', get_option('gmt_offset') ? get_option('gmt_offset') : '+0');
}

$system_info = array(
    __('Plugin Version', 'syntekpro-forms') => defined('SPF_VERSION') ? SPF_VERSION : __('N/A', 'syntekpro-forms'),
    __('WordPress Version', 'syntekpro-forms') => get_bloginfo('version'),
    __('PHP Version', 'syntekpro-forms') => phpversion(),
    __('Database Version', 'syntekpro-forms') => method_exists($wpdb, 'db_version') ? $wpdb->db_version() : __('N/A', 'syntekpro-forms'),
    __('Active Theme', 'syntekpro-forms') => $theme->get('Name') . ' ' . $theme->get('Version'),
    __('Site URL', 'syntekpro-forms') => home_url(),
    __('Timezone', 'syntekpro-forms') => $timezone,
    __('Memory Limit', 'syntekpro-forms') => defined('WP_MEMORY_LIMIT') ? WP_MEMORY_LIMIT : ini_get('memory_limit'),
    __('WP Debug', 'syntekpro-forms') => (defined('WP_DEBUG') && WP_DEBUG) ? __('Enabled', 'syntekpro-forms') : __('Disabled', 'syntekpro-forms'),
    __('Multisite', 'syntekpro-forms') => is_multisite() ? __('Yes', 'syntekpro-forms') : __('No', 'syntekpro-forms'),
);
?>
<div class="wrap spf-admin-page spf-settings-page">
    <div class="spf-admin-header">
        <div class="spf-admin-header-left"></div>
        <div class="spf-admin-header-center">
            <img src="<?php echo SPF_PLUGIN_URL; ?>assets/images/Syntekpro%20Forms%20Logo.png" class="spf-admin-logo" alt="SyntekPro">
        </div>
        <div class="spf-admin-header-right"></div>
    </div>

    <div class="spf-admin-page-title-wrap spf-page-toolbar">
        <h1 class="spf-admin-page-title spf-title-with-icon">
            <span class="dashicons dashicons-info"></span>
            <?php esc_html_e('About SyntekPro Forms', 'syntekpro-forms'); ?>
        </h1>
        <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=syntekpro-forms-add-new')); ?>">
            <?php esc_html_e('Create a Form', 'syntekpro-forms'); ?>
        </a>
    </div>

    <p class="description spf-about-intro">
        <?php esc_html_e('SyntekPro Forms is a professional form builder for WordPress that blends drag & drop ease with the flexibility of Gutenberg blocks and shortcodes.', 'syntekpro-forms'); ?>
    </p>

    <div class="spf-about-grid">
        <div class="spf-about-card">
            <h2><?php esc_html_e('What This Plugin Provides', 'syntekpro-forms'); ?></h2>
            <ul>
                <li><?php esc_html_e('A responsive form builder with workflow-friendly entry management and export tools.', 'syntekpro-forms'); ?></li>
                <li><?php esc_html_e('Built-in conditional logic, validation, and spam defenses to keep submissions reliable.', 'syntekpro-forms'); ?></li>
                <li><?php esc_html_e('Seamless Gutenberg blocks, shortcodes, and global settings so forms can be reused across pages.', 'syntekpro-forms'); ?></li>
                <li><?php esc_html_e('Action hooks for notifications, webhooks, and integrations so you can connect to your favorite services.', 'syntekpro-forms'); ?></li>
            </ul>
        </div>

        <div class="spf-about-card">
            <h2><?php esc_html_e('Why It Matters', 'syntekpro-forms'); ?></h2>
            <p><?php esc_html_e('Every submission is stored, timestamped, and searchable so you never miss a lead or support request, and the plugin is tuned for performance even on high-traffic sites.', 'syntekpro-forms'); ?></p>

            <h2><?php esc_html_e('Stay in Touch', 'syntekpro-forms'); ?></h2>
            <p>
                <?php
                $spf_site_url = esc_url('https://syntekpro.com');
                printf(
                    esc_html__('Visit %s for documentation, release notes, and updates.', 'syntekpro-forms'),
                    '<a href="' . $spf_site_url . '" target="_blank" rel="noopener noreferrer">' . esc_html__('syntekpro.com', 'syntekpro-forms') . '</a>'
                );
                ?>
            </p>
        </div>

        <div class="spf-about-card spf-system-info-card">
            <h2><?php esc_html_e('System Information', 'syntekpro-forms'); ?></h2>
            <table class="spf-system-info-table" role="table">
                <tbody>
                <?php foreach ($system_info as $label => $value): ?>
                    <tr>
                        <th scope="row"><?php echo esc_html($label); ?></th>
                        <td><?php echo esc_html((string) $value); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
