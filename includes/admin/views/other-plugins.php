<?php
/**
 * Other Plugins Page View
 */

if (!defined('ABSPATH')) {
    exit;
}

$extra_plugins = array(
    array(
        'name' => __('SyntekPro Animations', 'syntekpro-forms'),
        'url' => 'https://plugins.syntekpro.com/animations',
        'description' => __('Bring your site to life with scroll, hover, and entrance animations that can be applied to any block or form.', 'syntekpro-forms'),
    ),
    array(
        'name' => __('SyntekPro Toggle', 'syntekpro-forms'),
        'url' => 'https://plugins.syntekpro.com/toggle',
        'description' => __('Display content in tabs, accordions, and toggles so you can present choices without crowding the page.', 'syntekpro-forms'),
    ),
    array(
        'name' => __('SyntekPro License Server', 'syntekpro-forms'),
        'url' => 'https://plugins.syntekpro.com/license-server',
        'description' => __('Manage your premium licenses from a single dashboard and control activation limits for purchased plugins.', 'syntekpro-forms'),
    ),
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
            <span class="dashicons dashicons-products"></span>
            <?php esc_html_e('Other SyntekPro Plugins', 'syntekpro-forms'); ?>
        </h1>
        <a class="button" href="https://plugins.syntekpro.com" target="_blank" rel="noopener noreferrer"><?php esc_html_e('All Plugins', 'syntekpro-forms'); ?></a>
    </div>

    <p class="description spf-about-intro">
        <?php esc_html_e('Explore additional tools from SyntekPro that complement form building with motion, toggles, and license management.', 'syntekpro-forms'); ?>
    </p>

    <div class="spf-other-plugins-grid">
        <?php foreach ($extra_plugins as $plugin) : ?>
            <div class="spf-other-plugin-card">
                <h3><?php echo esc_html($plugin['name']); ?></h3>
                <p><?php echo esc_html($plugin['description']); ?></p>
                <a class="button button-secondary" href="<?php echo esc_url($plugin['url']); ?>" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e('Visit Plugin', 'syntekpro-forms'); ?>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
