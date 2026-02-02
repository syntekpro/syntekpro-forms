<?php
/**
 * Add-ons Page View
 */

if (!defined('ABSPATH')) {
    exit;
}

$addons_dir = SPF_ADDONS_DIR;
$available_files = glob(trailingslashit($addons_dir) . '*.php');
$loaded_files = isset($loaded_addons) && is_array($loaded_addons) ? $loaded_addons : array();
?>
<div class="wrap spf-admin-page spf-settings-page">
    <div class="spf-admin-header">
        <div class="spf-admin-header-left"></div>
        <div class="spf-admin-header-center">
            <img src="<?php echo SPF_PLUGIN_URL; ?>assets/images/Syntekpro%20Forms%20Logo.png" class="spf-admin-logo" alt="SyntekPro">
        </div>
        <div class="spf-admin-header-right"></div>
    </div>

    <div class="spf-admin-page-title-wrap" style="display:flex;justify-content:space-between;align-items:center;">
        <h1 class="spf-admin-page-title" style="display:flex;align-items:center;gap:10px;">
            <span class="dashicons dashicons-admin-plugins"></span>
            <?php _e('SyntekPro Forms Add-ons', 'syntekpro-forms'); ?>
        </h1>
    </div>

    <p class="description"><?php _e('Drop PHP add-on files into the addons folder or register additional paths via the filter syntekpro_forms_addons_paths.', 'syntekpro-forms'); ?></p>

    <h2><?php _e('Add-ons Directory', 'syntekpro-forms'); ?></h2>
    <p><code><?php echo esc_html($addons_dir); ?></code></p>

    <h2><?php _e('Loaded Add-ons', 'syntekpro-forms'); ?></h2>
    <?php if (!empty($loaded_files)): ?>
        <table class="widefat striped" style="max-width:900px;">
            <thead>
                <tr>
                    <th><?php _e('File', 'syntekpro-forms'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($loaded_files as $file): ?>
                    <tr>
                        <td><?php echo esc_html($file); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p><?php _e('No add-ons loaded yet. Place a .php file in the addons directory to load it automatically.', 'syntekpro-forms'); ?></p>
    <?php endif; ?>

    <h2><?php _e('Available Files in Add-ons Folder', 'syntekpro-forms'); ?></h2>
    <?php if (!empty($available_files)): ?>
        <ul style="list-style:disc;padding-left:20px;">
            <?php foreach ($available_files as $file): ?>
                <li><?php echo esc_html($file); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p><?php _e('No .php files found in the addons folder.', 'syntekpro-forms'); ?></p>
    <?php endif; ?>

    <div class="spf-admin-footer" style="background:#f8ebb4;border:1px solid #ccd0d4;border-radius:4px;padding:10px 20px;margin-top:20px;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 1px rgba(0,0,0,0.04);">
        <a class="spf-footer-brand" href="https://syntekpro.com" target="_blank" rel="noopener noreferrer">
            <span><?php _e('Powered by', 'syntekpro-forms'); ?></span>
            <img src="<?php echo SPF_PLUGIN_URL; ?>assets/images/SYNTEK%20PRO%20LOGO%20Transparent%20Icon%20500x150.png" class="spf-footer-icon" alt="SyntekPro" style="height:32px !important;width:32px !important;max-height:32px !important;max-width:32px !important;object-fit:contain;display:inline-block;vertical-align:middle;">
        </a>
    </div>
</div>
