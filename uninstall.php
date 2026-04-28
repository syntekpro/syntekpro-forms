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
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spf_webhook_queue");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spf_drafts");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spf_analytics");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spf_audit_log");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spf_form_backups");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spf_form_versions");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spf_email_templates");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spf_ab_variants");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spf_ab_events");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spf_dashboards");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spf_fraud_settings");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spf_fraud_events");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spf_integrations");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spf_integration_logs");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spf_webhook_logs");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spf_preview_links");

    // Delete options
    delete_option('spf_settings');
    delete_option('spf_version');
    
    // Delete custom upload directories (current and legacy name)
    $upload_dir = wp_upload_dir();
    foreach (array('syntekpro-forms', 'advanced-forms') as $spf_dir_name) {
        $custom_dir = $upload_dir['basedir'] . '/' . $spf_dir_name;

        if (file_exists($custom_dir)) {
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
}
