<?php
/**
 * Entries List View - SyntekPro Forms
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get filter parameters
$form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
$status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
$search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
$date_from = isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '';

// Get all forms for filter
$forms = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}spf_forms ORDER BY title ASC");
$entries = array();

// Get counts
$total_entries = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spf_entries");
$unread_entries = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spf_entries WHERE status = 'unread'");
$read_entries = max(0, (int) $total_entries - (int) $unread_entries);

$status_base_url = admin_url('admin.php?page=syntekpro-forms-entries');
$status_all_url = add_query_arg(
    array(
        'form_id' => $form_id,
        's' => $search,
        'date_from' => $date_from,
        'date_to' => $date_to,
    ),
    $status_base_url
);

$status_unread_url = add_query_arg(
    array(
        'status' => 'unread',
        'form_id' => $form_id,
        's' => $search,
        'date_from' => $date_from,
        'date_to' => $date_to,
    ),
    $status_base_url
);

$status_read_url = add_query_arg(
    array(
        'status' => 'read',
        'form_id' => $form_id,
        's' => $search,
        'date_from' => $date_from,
        'date_to' => $date_to,
    ),
    $status_base_url
);
?>

<div class="wrap spf-admin-list-wrap spf-entries-shell">
    <div class="spf-admin-header">
        <div class="spf-admin-header-left">
        </div>
        <div class="spf-admin-header-center">
            <img src="<?php echo SPF_PLUGIN_URL; ?>assets/images/Syntekpro%20Forms%20Logo.png" class="spf-admin-logo" alt="SyntekPro Logo">
        </div>
        <div class="spf-admin-header-right">
        </div>
    </div>

    <div class="spf-admin-page-title-wrap spf-page-toolbar">
        <h1 class="spf-admin-page-title spf-title-with-icon">
            <span class="dashicons dashicons-feedback"></span>
            <?php _e('Entries', 'syntekpro-forms'); ?>
        </h1>
        <div class="spf-page-stats" aria-label="<?php esc_attr_e('Entries overview', 'syntekpro-forms'); ?>">
            <div class="spf-stat-pill">
                <strong><?php echo (int) $total_entries; ?></strong>
                <span><?php _e('Total', 'syntekpro-forms'); ?></span>
            </div>
            <div class="spf-stat-pill">
                <strong><?php echo (int) $unread_entries; ?></strong>
                <span><?php _e('Unread', 'syntekpro-forms'); ?></span>
            </div>
        </div>
    </div>

    <p class="description spf-about-intro">
        <?php esc_html_e('Browse submissions, review details, and manage entries with filters, bulk actions, and CSV export.', 'syntekpro-forms'); ?>
    </p>

    <div class="spf-tablenav-top spf-forms-top-nav spf-entries-status-nav">
        <ul class="subsubsub spf-forms-status-tabs">
            <li>
                <a href="<?php echo esc_url($status_all_url); ?>" class="<?php echo $status === '' ? 'current' : ''; ?>">
                    <?php _e('All', 'syntekpro-forms'); ?> <span class="count">(<?php echo (int) $total_entries; ?>)</span>
                </a> |
            </li>
            <li>
                <a href="<?php echo esc_url($status_unread_url); ?>" class="<?php echo $status === 'unread' ? 'current' : ''; ?>">
                    <?php _e('Unread', 'syntekpro-forms'); ?> <span class="count">(<?php echo (int) $unread_entries; ?>)</span>
                </a> |
            </li>
            <li>
                <a href="<?php echo esc_url($status_read_url); ?>" class="<?php echo $status === 'read' ? 'current' : ''; ?>">
                    <?php _e('Read', 'syntekpro-forms'); ?> <span class="count">(<?php echo (int) $read_entries; ?>)</span>
                </a>
            </li>
        </ul>
    </div>
    
    <div class="spf-admin-content-card spf-entries-card">
        <!-- Filters & Bulk Actions -->
        <div class="spf-list-table-nav spf-entries-toolbar-panel">
            <div class="spf-nav-left spf-toolbar-block spf-toolbar-block-filters">
                <div class="spf-toolbar-label"><?php _e('Filters', 'syntekpro-forms'); ?></div>
                <form method="get" class="spf-filters-form">
                    <input type="hidden" name="page" value="syntekpro-forms-entries">

                    <div class="spf-filter-group">
                    <select name="form_id" id="spf-entries-form" class="spf-filter-select">
                        <option value=""><?php _e('All Forms', 'syntekpro-forms'); ?></option>
                        <?php foreach ($forms as $form): ?>
                            <option value="<?php echo $form->id; ?>" <?php selected($form_id, $form->id); ?>>
                                <?php echo esc_html($form->title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="status" id="spf-entries-status" class="spf-filter-select">
                        <option value=""><?php _e('All Statuses', 'syntekpro-forms'); ?></option>
                        <option value="unread" <?php selected($status, 'unread'); ?>><?php _e('Unread', 'syntekpro-forms'); ?></option>
                        <option value="read" <?php selected($status, 'read'); ?>><?php _e('Read', 'syntekpro-forms'); ?></option>
                    </select>
                    </div>

                    <div class="spf-filter-group spf-filter-group-grow">
                    <input type="search" name="s" id="spf-entries-search" class="spf-filter-search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search entries', 'syntekpro-forms'); ?>">
                    <input type="date" name="date_from" id="spf-entries-date-from" class="spf-filter-date" value="<?php echo esc_attr($date_from); ?>" placeholder="<?php esc_attr_e('From date', 'syntekpro-forms'); ?>">
                    <input type="date" name="date_to" id="spf-entries-date-to" class="spf-filter-date" value="<?php echo esc_attr($date_to); ?>" placeholder="<?php esc_attr_e('To date', 'syntekpro-forms'); ?>">
                    <input type="hidden" id="spf-entries-per-page" value="20">
                    </div>

                    <div class="spf-filter-group">
                    <button type="submit" class="button button-primary spf-btn-filter"><?php _e('Filter', 'syntekpro-forms'); ?></button>
                    <a href="<?php echo admin_url('admin.php?page=syntekpro-forms-entries'); ?>" class="button spf-btn-reset">
                        <?php _e('Reset', 'syntekpro-forms'); ?>
                    </a>
                    </div>
                </form>
            </div>

            <div class="spf-nav-right spf-toolbar-block spf-toolbar-actions">
                <div class="spf-toolbar-label"><?php _e('Actions', 'syntekpro-forms'); ?></div>
                <div class="spf-bulk-actions">
                    <select id="spf-bulk-action-selector" class="spf-bulk-select">
                        <option value=""><?php _e('Bulk Actions', 'syntekpro-forms'); ?></option>
                        <option value="delete"><?php _e('Delete Permanently', 'syntekpro-forms'); ?></option>
                        <option value="mark_read"><?php _e('Mark as Read', 'syntekpro-forms'); ?></option>
                    </select>
                    <button type="button" id="spf-apply-bulk-action" class="button spf-btn-apply"><?php _e('Apply', 'syntekpro-forms'); ?></button>
                </div>
                <?php
                $export_args = array(
                    'page' => 'syntekpro-forms-entries',
                    'action' => 'export',
                    'form_id' => $form_id,
                    'status' => $status,
                    's' => $search,
                    'date_from' => $date_from,
                    'date_to' => $date_to,
                );
                $export_url = wp_nonce_url(add_query_arg($export_args, admin_url('admin.php')), 'spf_export_entries', 'nonce');
                ?>
                <a href="<?php echo esc_url($export_url); ?>" class="button button-secondary spf-export-btn">
                    <span class="dashicons dashicons-download"></span> <?php _e('Export CSV', 'syntekpro-forms'); ?>
                </a>
            </div>
        </div>
    
    <!-- Entries Table -->
    <div id="spf-entries-table-wrap" class="spf-entries-table-section">
        <div class="spf-empty-state">
            <div class="spf-empty-icon"><span class="dashicons dashicons-database"></span></div>
            <p><?php _e('Loading entries…', 'syntekpro-forms'); ?></p>
        </div>
    </div>
    <div id="spf-entries-pagination"></div>
    </div>
</div>

<!-- Entry Detail Modal -->
<div id="spf-entry-modal" class="spf-modal">
    <div class="spf-modal-content">
        <div class="spf-modal-header">
            <h2><?php _e('Entry Details', 'syntekpro-forms'); ?></h2>
            <span class="spf-modal-close">&times;</span>
        </div>
        <div class="spf-modal-body">
            <div id="spf-entry-details-content"></div>
        </div>
    </div>
</div>
