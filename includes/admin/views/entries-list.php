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

// Build query
$where = array('1=1');
$query_params = array();

if ($form_id > 0) {
    $where[] = 'e.form_id = %d';
    $query_params[] = $form_id;
}

if (!empty($status)) {
    $where[] = 'e.status = %s';
    $query_params[] = $status;
}

if (!empty($search)) {
    $like = '%' . $wpdb->esc_like($search) . '%';
    $where[] = '(e.entry_data LIKE %s OR e.ip_address LIKE %s OR f.title LIKE %s)';
    $query_params[] = $like;
    $query_params[] = $like;
    $query_params[] = $like;
}

if (!empty($date_from)) {
    $where[] = 'DATE(e.created_at) >= %s';
    $query_params[] = $date_from;
}

if (!empty($date_to)) {
    $where[] = 'DATE(e.created_at) <= %s';
    $query_params[] = $date_to;
}

$where_clause = implode(' AND ', $where);

// Get entries
if (!empty($query_params)) {
    $entries = $wpdb->get_results($wpdb->prepare(
        "SELECT e.*, f.title as form_title 
         FROM {$wpdb->prefix}spf_entries e 
         LEFT JOIN {$wpdb->prefix}spf_forms f ON e.form_id = f.id 
         WHERE $where_clause 
         ORDER BY e.created_at DESC",
        $query_params
    ));
} else {
    $entries = $wpdb->get_results(
        "SELECT e.*, f.title as form_title 
         FROM {$wpdb->prefix}spf_entries e 
         LEFT JOIN {$wpdb->prefix}spf_forms f ON e.form_id = f.id 
         WHERE $where_clause 
         ORDER BY e.created_at DESC"
    );
}

// Get all forms for filter
$forms = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}spf_forms ORDER BY title ASC");

// Get counts
$total_entries = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spf_entries");
$unread_entries = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spf_entries WHERE status = 'unread'");
?>

<div class="wrap spf-admin-list-wrap">
    <div class="spf-admin-header">
        <div class="spf-admin-header-left">
        </div>
        <div class="spf-admin-header-center">
            <img src="<?php echo SPF_PLUGIN_URL; ?>assets/images/Syntekpro%20Forms%20Logo.png" class="spf-admin-logo" alt="SyntekPro Logo">
        </div>
        <div class="spf-admin-header-right">
        </div>
    </div>

    <div class="spf-admin-page-title-wrap" style="display: flex; justify-content: space-between; align-items: center;">
        <h2 class="spf-admin-page-title"><?php _e('Form Entries', 'syntekpro-forms'); ?></h2>
        <div class="spf-header-stats">
            <span class="spf-stat-item"><strong><?php echo $total_entries; ?></strong> <?php _e('Total', 'syntekpro-forms'); ?></span>
            <span class="spf-stat-item"><strong><?php echo $unread_entries; ?></strong> <?php _e('Unread', 'syntekpro-forms'); ?></span>
        </div>
    </div>
    
    <div class="spf-admin-content-card">
        <!-- Filters & Bulk Actions -->
        <div class="spf-list-table-nav">
            <div class="spf-nav-left">
                <form method="get" class="spf-filters-form">
                    <input type="hidden" name="page" value="syntekpro-forms-entries">
                    
                    <select name="form_id">
                        <option value=""><?php _e('All Forms', 'syntekpro-forms'); ?></option>
                        <?php foreach ($forms as $form): ?>
                            <option value="<?php echo $form->id; ?>" <?php selected($form_id, $form->id); ?>>
                                <?php echo esc_html($form->title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="status">
                        <option value=""><?php _e('All Statuses', 'syntekpro-forms'); ?></option>
                        <option value="unread" <?php selected($status, 'unread'); ?>><?php _e('Unread', 'syntekpro-forms'); ?></option>
                        <option value="read" <?php selected($status, 'read'); ?>><?php _e('Read', 'syntekpro-forms'); ?></option>
                    </select>

                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search entries', 'syntekpro-forms'); ?>">
                    <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" placeholder="<?php esc_attr_e('From date', 'syntekpro-forms'); ?>">
                    <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" placeholder="<?php esc_attr_e('To date', 'syntekpro-forms'); ?>">
                    
                    <button type="submit" class="button button-primary"><?php _e('Filter', 'syntekpro-forms'); ?></button>
                    <a href="<?php echo admin_url('admin.php?page=syntekpro-forms-entries'); ?>" class="button">
                        <?php _e('Reset', 'syntekpro-forms'); ?>
                    </a>
                </form>
            </div>
            <div class="spf-nav-right">
                <?php if (!empty($entries)): ?>
                    <div class="spf-bulk-actions">
                        <select id="spf-bulk-action-selector">
                            <option value=""><?php _e('Bulk Actions', 'syntekpro-forms'); ?></option>
                            <option value="delete"><?php _e('Delete Permanently', 'syntekpro-forms'); ?></option>
                            <option value="mark_read"><?php _e('Mark as Read', 'syntekpro-forms'); ?></option>
                        </select>
                        <button type="button" id="spf-apply-bulk-action" class="button"><?php _e('Apply', 'syntekpro-forms'); ?></button>
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
                    $export_url = wp_nonce_url(add_query_arg($export_args, admin_url('admin.php')), 'spf_export_entries');
                    ?>
                    <a href="<?php echo esc_url($export_url); ?>" 
                       class="button button-secondary">
                        <span class="dashicons dashicons-download"></span> <?php _e('Export CSV', 'syntekpro-forms'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    
    <!-- Entries Table -->
    <?php if (empty($entries)): ?>
        <div class="spf-empty-state">
            <div class="spf-empty-icon"><span class="dashicons dashicons-database"></span></div>
            <p><?php _e('No entries found matching your criteria.', 'syntekpro-forms'); ?></p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped spf-entries-table">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1"><?php _e('Select All', 'syntekpro-forms'); ?></label>
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th class="column-id"><?php _e('ID', 'syntekpro-forms'); ?></th>
                    <th><?php _e('Form Name', 'syntekpro-forms'); ?></th>
                    <th><?php _e('Entry Data Preview', 'syntekpro-forms'); ?></th>
                    <th><?php _e('Date Submitted', 'syntekpro-forms'); ?></th>
                    <th class="column-status"><?php _e('Status', 'syntekpro-forms'); ?></th>
                    <th class="column-actions"><?php _e('Actions', 'syntekpro-forms'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $entry): 
                    $entry_data = json_decode($entry->entry_data, true);
                    $preview = '';
                    $count = 0;
                    if (is_array($entry_data)) {
                        foreach ($entry_data as $key => $value) {
                            if ($count >= 2) break;
                            $preview .= '<span class="spf-preview-item"><strong>' . esc_html(ucfirst(str_replace('_', ' ', (string)$key))) . ':</strong> ';
                            $preview .= esc_html(is_array($value) ? implode(', ', $value) : substr((string)$value, 0, 50)) . '</span>';
                            $count++;
                        }
                    }
                ?>
                    <tr class="<?php echo $entry->status === 'unread' ? 'spf-unread-row' : ''; ?>" data-entry-id="<?php echo $entry->id; ?>">
                        <th scope="row" class="check-column">
                            <input type="checkbox" name="entry[]" value="<?php echo $entry->id; ?>" class="spf-entry-checkbox">
                        </th>
                        <td>#<?php echo $entry->id; ?></td>
                        <td><strong><?php echo esc_html($entry->form_title); ?></strong><br><small><?php echo esc_html($entry->ip_address); ?></small></td>
                        <td><div class="spf-entry-preview"><?php echo $preview; ?></div></td>
                        <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime((string)$entry->created_at)); ?></td>
                        <td>
                            <span class="spf-status-badge spf-status-<?php echo esc_attr($entry->status); ?>">
                                <?php echo esc_html(ucfirst((string)$entry->status)); ?>
                            </span>
                        </td>
                        <td class="spf-row-actions">
                            <button class="button button-small spf-view-entry spf-tooltip" title="<?php _e('View full details', 'syntekpro-forms'); ?>" data-entry-id="<?php echo $entry->id; ?>">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                            <button class="button button-small spf-delete-entry spf-tooltip" title="<?php _e('Delete entry', 'syntekpro-forms'); ?>" data-entry-id="<?php echo $entry->id; ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    </div>
</div>

<div class="spf-admin-footer" style="background:#f8ebb4;border:1px solid #ccd0d4;border-radius:4px;padding:10px 20px;margin-top:20px;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 1px rgba(0,0,0,0.04);">
    <a class="spf-footer-brand" href="https://syntekpro.com" target="_blank" rel="noopener noreferrer">
        <span><?php _e('Powered by', 'syntekpro-forms'); ?></span>
        <img src="<?php echo SPF_PLUGIN_URL; ?>assets/images/SYNTEK%20PRO%20LOGO%20Transparent%20Icon%20500x150.png" class="spf-footer-icon" alt="SyntekPro" style="height:32px !important;width:32px !important;max-height:32px !important;max-width:32px !important;object-fit:contain;display:inline-block;vertical-align:middle;">
    </a>
</div>

<style>
.spf-header-stats { display: flex; gap: 20px; }
.spf-stat-item { background: #f0f0f1; padding: 5px 15px; border-radius: 20px; font-size: 13px; }

.spf-admin-content-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 0;
    overflow: hidden;
}

.spf-list-table-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: #fcfcfc;
    border-bottom: 1px solid #f0f0f1;
}

.spf-filters-form { display: flex; gap: 10px; align-items: center; }
.spf-bulk-actions { display: inline-flex; gap: 5px; margin-right: 15px; }

.spf-entries-table thead th { background: #f9f9f9; padding: 15px 10px; font-weight: 600; }
.spf-entries-table tbody td { padding: 15px 10px; vertical-align: middle; }

.spf-unread-row { background-color: #f0f7ff !important; }
.spf-unread-row td { font-weight: 500; }

.spf-status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}
.spf-status-read { background: #e7f5fe; color: #2271b1; }
.spf-status-unread { background: #fcf0ad; color: #856404; }

.spf-entry-preview { display: flex; flex-direction: column; gap: 4px; font-size: 12px; color: #646970; }
.spf-preview-item { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 300px; }

.spf-row-actions { display: flex; gap: 5px; }
.spf-row-actions .button { display: inline-flex; align-items: center; justify-content: center; }
.spf-delete-entry:hover { color: #d63638; border-color: #d63638; }

.spf-empty-state { text-align: center; padding: 80px 20px; color: #646970; }
.spf-empty-icon .dashicons { font-size: 50px; width: 50px; height: 50px; color: #ccd0d4; margin-bottom: 15px; }
</style>

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
