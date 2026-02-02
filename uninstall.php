<?php
/**
 * SyntekPro Forms Uninstall
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$settings = get_option('spf_settings');

// Only delete data if the "Delete on Uninstall" option is enabled
if (isset($settings['delete_entries_on_uninstall']) && $settings['delete_entries_on_uninstall']) {
    global $wpdb;

    // Delete tables
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spf_forms");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spf_entries");

    // Delete options
    delete_option('spf_settings');
    delete_option('spf_version');
    
    // Delete custom upload directory if it exists
    $upload_dir = wp_upload_dir();
    $custom_dir = $upload_dir['basedir'] . '/advanced-forms';
    
    if (file_exists($custom_dir)) {
        // Simple recursive delete
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($custom_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }

        rmdir($custom_dir);
    }
}
