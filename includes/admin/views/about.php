<?php
/**
 * About Page View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap spf-admin-page spf-settings-page">
    <div class="spf-admin-header">
        <div class="spf-admin-header-left"></div>
        <div class="spf-admin-header-center">
            <img src="<?php echo SPF_PLUGIN_URL; ?>assets/images/Syntekpro%20Forms%20Logo.png" class="spf-admin-logo" alt="SyntekPro">
        </div>
        <div class="spf-admin-header-right"></div>
    </div>

    <div class="spf-admin-page-title-wrap" style="margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;">
        <h1 class="spf-admin-page-title" style="display:flex;align-items:center;gap:10px;">
            <span class="dashicons dashicons-info"></span>
            <?php esc_html_e('About SyntekPro Forms', 'syntekpro-forms'); ?>
        </h1>
        <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=syntekpro-forms-add-new')); ?>">
            <?php esc_html_e('Create a Form', 'syntekpro-forms'); ?>
        </a>
    </div>

    <p class="description" style="max-width:820px;">
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
    </div>
</div>
