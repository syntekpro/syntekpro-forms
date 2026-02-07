<?php
/**
 * Forms List View - SyntekPro Forms
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get filter parameters
$current_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
$search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_at';
$order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

// Build query
$where = "1=1";
if ($current_status !== 'all' && in_array($current_status, ['active', 'inactive', 'trash'])) {
    $where .= $wpdb->prepare(" AND status = %s", $current_status);
}
if (!empty($search_query)) {
    $where .= $wpdb->prepare(" AND title LIKE %s", '%' . $wpdb->esc_like($search_query) . '%');
}

// Get valid orderby columns
$valid_orderby = ['id', 'title', 'status', 'created_at', 'views', 'conversion'];
if (!in_array($orderby, $valid_orderby)) {
    $orderby = 'created_at';
}
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// Get forms
$sql_orderby = ($orderby === 'conversion') ? 'created_at' : $orderby;
$forms = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}spf_forms WHERE {$where} ORDER BY {$sql_orderby} {$order}");

// Sort by conversion in PHP since it isn't a DB column
if ($orderby === 'conversion' && !empty($forms)) {
    $conversion_map = [];
    foreach ($forms as $form) {
        $entries_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}spf_entries WHERE form_id = %d",
            $form->id
        ));
        $views = isset($form->views) ? intval($form->views) : 0;
        $conversion_map[$form->id] = ($views > 0) ? ($entries_count / $views) : 0;
    }

    usort($forms, function($a, $b) use ($conversion_map, $order) {
        $a_val = $conversion_map[$a->id] ?? 0;
        $b_val = $conversion_map[$b->id] ?? 0;
        if ($a_val == $b_val) {
            return 0;
        }
        if ($order === 'ASC') {
            return ($a_val < $b_val) ? -1 : 1;
        }
        return ($a_val > $b_val) ? -1 : 1;
    });
}

// Get counts for status tabs
$all_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spf_forms");
$active_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spf_forms WHERE status = 'active'");
$inactive_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spf_forms WHERE status = 'inactive'");
$trash_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spf_forms WHERE status = 'trash'");
?>

<div class="wrap spf-admin-list-wrap">
    <div class="spf-admin-header">
        <div class="spf-admin-header-left">
        </div>
        <div class="spf-admin-header-center">
            <img src="<?php echo SPF_PLUGIN_URL; ?>assets/images/Syntekpro%20Forms%20Logo.png" class="spf-admin-logo" alt="SyntekPro">
        </div>
        <div class="spf-admin-header-right">
        </div>
    </div>

    <div class="spf-admin-page-title-wrap" style="margin-bottom: 20px;">
        <h1 class="wp-heading-inline" style="display: inline-block; margin-right: 15px;"><?php _e('Forms', 'syntekpro-forms'); ?></h1>
        <button type="button" class="button spf-add-new-btn" id="spf-add-new-form">
            <?php _e('Add New', 'syntekpro-forms'); ?>
        </button>
    </div>

    <!-- Status Tabs and Search Bar -->
    <div class="spf-tablenav-top" style="margin-bottom: 15px;">
        <ul class="subsubsub" style="float: left; margin: 0;">
            <li><a href="<?php echo admin_url('admin.php?page=syntekpro-forms'); ?>" class="<?php echo $current_status === 'all' ? 'current' : ''; ?>">
                <?php _e('All', 'syntekpro-forms'); ?> <span class="count">(<?php echo $all_count; ?>)</span>
            </a> |</li>
            <li><a href="<?php echo add_query_arg('status', 'active'); ?>" class="<?php echo $current_status === 'active' ? 'current' : ''; ?>">
                <?php _e('Active', 'syntekpro-forms'); ?> <span class="count">(<?php echo $active_count; ?>)</span>
            </a> |</li>
            <li><a href="<?php echo add_query_arg('status', 'inactive'); ?>" class="<?php echo $current_status === 'inactive' ? 'current' : ''; ?>">
                <?php _e('Inactive', 'syntekpro-forms'); ?> <span class="count">(<?php echo $inactive_count; ?>)</span>
            </a> |</li>
            <li><a href="<?php echo add_query_arg('status', 'trash'); ?>" class="<?php echo $current_status === 'trash' ? 'current' : ''; ?>">
                <?php _e('Trash', 'syntekpro-forms'); ?> <span class="count">(<?php echo $trash_count; ?>)</span>
            </a></li>
        </ul>

        <div class="spf-search-box" style="float: right;">
            <form method="get" action="<?php echo admin_url('admin.php'); ?>">
                <input type="hidden" name="page" value="syntekpro-forms">
                <?php if ($current_status !== 'all'): ?>
                    <input type="hidden" name="status" value="<?php echo esc_attr($current_status); ?>">
                <?php endif; ?>
                <input type="search" name="s" value="<?php echo esc_attr($search_query); ?>" placeholder="<?php _e('Search forms...', 'syntekpro-forms'); ?>" style="width: 250px;">
                <button type="submit" class="button"><?php _e('Search Form', 'syntekpro-forms'); ?></button>
            </form>
        </div>
        <div style="clear: both;"></div>
    </div>
    
    <div class="spf-admin-content-card">
        <?php if (empty($forms)): ?>
        <div class="spf-empty-state">
            <p><?php _e('No forms found. Create your first form!', 'syntekpro-forms'); ?></p>
            <button type="button" class="button button-primary" id="spf-add-new-form-empty">
                <?php _e('Create Form', 'syntekpro-forms'); ?>
            </button>
        </div>
        <?php else: ?>
            <!-- Bulk Actions -->
            <div class="tablenav top" style="margin-bottom: 10px;">
                <div class="alignleft actions bulkactions">
                    <select name="action" id="spf-bulk-action">
                        <option value="-1"><?php _e('Bulk Actions', 'syntekpro-forms'); ?></option>
                        <option value="mark_active"><?php _e('Mark As Active', 'syntekpro-forms'); ?></option>
                        <option value="mark_inactive"><?php _e('Mark As Inactive', 'syntekpro-forms'); ?></option>
                        <option value="reset_views"><?php _e('Reset Views', 'syntekpro-forms'); ?></option>
                        <option value="delete_entries"><?php _e('Permanently Delete Entries', 'syntekpro-forms'); ?></option>
                        <option value="delete_forms"><?php _e('Delete Forms Permanently', 'syntekpro-forms'); ?></option>
                        <option value="trash"><?php _e('Move to Trash', 'syntekpro-forms'); ?></option>
                    </select>
                    <button type="button" id="spf-bulk-apply" class="button action"><?php _e('Apply', 'syntekpro-forms'); ?></button>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="spf-select-all-forms">
                    </td>
                    <?php
                    $columns = [
                        'status' => __('Status', 'syntekpro-forms'),
                        'title' => __('Title', 'syntekpro-forms'),
                        'id' => __('ID', 'syntekpro-forms'),
                        'entries' => __('Entries', 'syntekpro-forms'),
                        'views' => __('Views', 'syntekpro-forms'),
                        'conversion' => __('Conversion', 'syntekpro-forms')
                    ];
                    foreach ($columns as $col_key => $col_label):
                        $column_orderby = ($col_key === 'entries') ? 'created_at' : $col_key;
                        $is_sorted = ($orderby === $column_orderby);
                        $sort_order = ($is_sorted && $order === 'ASC') ? 'DESC' : 'ASC';
                        $sort_url = add_query_arg(['orderby' => $column_orderby, 'order' => $sort_order]);
                    ?>
                        <th scope="col" class="manage-column sortable <?php echo $is_sorted ? 'sorted' : ''; ?> <?php echo $is_sorted ? strtolower($order) : ''; ?>">
                            <a href="<?php echo esc_url($sort_url); ?>">
                                <span><?php echo $col_label; ?></span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($forms as $form): 
                    $entries_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}spf_entries WHERE form_id = %d",
                        $form->id
                    ));
                    $views = isset($form->views) ? intval($form->views) : 0;
                    $conversion = ($views > 0) ? round(($entries_count / $views) * 100, 2) : 0;
                ?>
                    <tr data-form-id="<?php echo $form->id; ?>">
                        <th scope="row" class="check-column">
                            <input type="checkbox" class="spf-form-checkbox" value="<?php echo $form->id; ?>">
                        </th>
                        <td>
                            <span class="spf-status-indicator spf-status-<?php echo esc_attr($form->status); ?>">
                                <span class="spf-status-dot"></span>
                                <span class="spf-status-text"><?php echo esc_html(ucfirst((string)$form->status)); ?></span>
                            </span>
                        </td>
                        <td class="title column-title has-row-actions">
                            <strong>
                                <a href="<?php echo admin_url('admin.php?page=syntekpro-forms-new&form_id=' . $form->id); ?>" class="row-title">
                                    <?php echo esc_html($form->title); ?>
                                </a>
                            </strong>
                            <div class="row-actions">
                                <span class="edit"><a href="<?php echo admin_url('admin.php?page=syntekpro-forms-new&form_id=' . $form->id); ?>"><?php _e('Edit', 'syntekpro-forms'); ?></a> | </span>
                                <span class="settings spf-settings-submenu-wrap">
                                    <a href="#" class="spf-show-settings" data-form-id="<?php echo $form->id; ?>"><?php _e('Settings', 'syntekpro-forms'); ?></a>
                                    <div class="spf-settings-submenu">
                                        <a href="#" class="spf-show-form-settings" data-form-id="<?php echo $form->id; ?>"><?php _e('Form Settings', 'syntekpro-forms'); ?></a>
                                        <a href="#" class="spf-show-confirmations" data-form-id="<?php echo $form->id; ?>"><?php _e('Confirmations', 'syntekpro-forms'); ?></a>
                                    </div>
                                     | 
                                </span>
                                <span class="entries"><a href="<?php echo admin_url('admin.php?page=syntekpro-forms-entries&form_id=' . $form->id); ?>"><?php _e('Entries', 'syntekpro-forms'); ?></a> | </span>
                                <span class="view"><a href="#" class="spf-preview-form" data-form-id="<?php echo $form->id; ?>"><?php _e('Preview', 'syntekpro-forms'); ?></a> | </span>
                                <span class="duplicate"><a href="#" class="spf-duplicate-form" data-form-id="<?php echo $form->id; ?>"><?php _e('Duplicate', 'syntekpro-forms'); ?></a> | </span>
                                <?php if ($form->status === 'trash'): ?>
                                    <span class="delete"><a href="#" class="spf-delete-form" data-form-id="<?php echo $form->id; ?>"><?php _e('Delete Permanently', 'syntekpro-forms'); ?></a></span>
                                <?php else: ?>
                                    <span class="trash"><a href="#" class="spf-trash-form" data-form-id="<?php echo $form->id; ?>"><?php _e('Trash', 'syntekpro-forms'); ?></a></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?php echo $form->id; ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=syntekpro-forms-entries&form_id=' . $form->id); ?>">
                                <?php echo $entries_count; ?>
                            </a>
                        </td>
                        <td><?php echo $views; ?></td>
                        <td><?php echo $conversion; ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="spf-select-all-forms-bottom">
                    </td>
                    <?php
                    foreach ($columns as $col_key => $col_label):
                        $column_orderby = ($col_key === 'entries') ? 'created_at' : $col_key;
                        $is_sorted = ($orderby === $column_orderby);
                        $sort_order = ($is_sorted && $order === 'ASC') ? 'DESC' : 'ASC';
                        $sort_url = add_query_arg(['orderby' => $column_orderby, 'order' => $sort_order]);
                    ?>
                        <th scope="col" class="manage-column sortable <?php echo $is_sorted ? 'sorted' : ''; ?> <?php echo $is_sorted ? strtolower($order) : ''; ?>">
                            <a href="<?php echo esc_url($sort_url); ?>">
                                <span><?php echo $col_label; ?></span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>
    </div>

    <div class="spf-admin-footer" style="background:#f8ebb4;border:1px solid #ccd0d4;border-radius:4px;padding:10px 20px;margin-top:20px;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 1px rgba(0,0,0,0.04);">
        <a class="spf-footer-brand" href="https://syntekpro.com" target="_blank" rel="noopener noreferrer">
            <span><?php _e('Powered by', 'syntekpro-forms'); ?></span>
            <img src="<?php echo SPF_PLUGIN_URL; ?>assets/images/SYNTEK%20PRO%20LOGO%20Transparent%20Icon%20500x150.png" class="spf-footer-icon" alt="SyntekPro" style="height:45px !important;width:auto !important;max-height:45px !important;max-width:150px !important;object-fit:contain;display:inline-block;vertical-align:middle;">
        </a>
    </div>
</div>

<!-- Settings Modal -->
<div id="spf-settings-modal" class="spf-modal" style="display: none;">
    <div class="spf-modal-content" style="max-width: 800px;">
        <div class="spf-modal-header">
            <h2><?php _e('Form Settings', 'syntekpro-forms'); ?></h2>
            <button type="button" class="spf-modal-close" aria-label="<?php echo esc_attr__('Close', 'syntekpro-forms'); ?>">
                &times;
            </button>
        </div>
        <div class="spf-modal-body" id="spf-settings-content">
            <div style="text-align:center;padding:40px;">
                <span class="dashicons dashicons-update spin"></span> <?php _e('Loading...', 'syntekpro-forms'); ?>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div id="spf-preview-modal" class="spf-modal" style="display: none;">
    <div class="spf-modal-content" style="max-width: 900px;">
        <div class="spf-modal-header">
            <h2><?php _e('Form Preview', 'syntekpro-forms'); ?></h2>
            <button type="button" class="spf-modal-close" aria-label="<?php echo esc_attr__('Close', 'syntekpro-forms'); ?>">
                &times;
            </button>
        </div>
        <div class="spf-modal-body" id="spf-preview-content">
            <div style="text-align:center;padding:40px;">
                <span class="dashicons dashicons-update spin"></span> <?php _e('Loading...', 'syntekpro-forms'); ?>
            </div>
        </div>
    </div>
</div>

<!-- Template Selection Modal -->
<div id="spf-template-modal" class="spf-modal" style="display: none;">
    <div class="spf-modal-content spf-template-modal-content">
        <div class="spf-modal-header">
            <h2><?php _e('Explore Form Templates', 'syntekpro-forms'); ?></h2>
            <button type="button" class="spf-modal-close" aria-label="<?php echo esc_attr__('Close', 'syntekpro-forms'); ?>">
                &times;
            </button>
        </div>
        
        <div class="spf-modal-body" style="padding: 0;">
            <div style="padding: 20px 30px; background: #fff; border-bottom: 1px solid #f0f0f1;">
                <p style="margin: 0; color: #646970; font-size: 15px;"><?php _e('Quickly create an amazing form by using a pre-made template, or start from scratch to tailor your form to your specific needs.', 'syntekpro-forms'); ?></p>
            </div>
            
            <div class="spf-template-grid">
                <?php 
                $template_colors = ['#2271b1', '#0073aa', '#dc3232', '#28a745', '#ff9800', '#9c27b0', '#00bcd4', '#f44336'];
                $color_index = 0;
                
                if (class_exists('SyntekPro_Forms_Templates')) {
                    $templates = SyntekPro_Forms_Templates::get_templates();
                    if (!empty($templates)) {
                        foreach ($templates as $id => $template): 
                            $primary_color = isset($template['settings']['primary_color']) ? $template['settings']['primary_color'] : $template_colors[$color_index % count($template_colors)];
                            $bg_color = $template_colors[$color_index % count($template_colors)];
                            $color_index++;
                        ?>
                            <div class="spf-template-item" data-template-id="<?php echo esc_attr($id); ?>">
                                <div class="spf-template-preview" style="background: linear-gradient(135deg, <?php echo esc_attr($bg_color); ?> 0%, <?php echo esc_attr($bg_color); ?>CC 100%);">
                                    <!-- Mock Form Display -->
                                    <div class="spf-template-mock-form">
                                        <div class="spf-mock-input" style="background: rgba(255,255,255,0.15);"></div>
                                        <div class="spf-mock-input" style="background: rgba(255,255,255,0.15);"></div>
                                        <div class="spf-mock-button" style="background: <?php echo esc_attr($primary_color); ?>; border-color: <?php echo esc_attr($primary_color); ?>;"></div>
                                    </div>
                                </div>
                                <div class="spf-template-info">
                                    <h3><?php echo esc_html($template['title']); ?></h3>
                                    <p><?php echo esc_html($template['description']); ?></p>
                                </div>
                                <div class="spf-template-overlay">
                                    <button type="button" class="spf-btn-use-template" style="background: #dc3232;"><?php _e('Use Template', 'syntekpro-forms'); ?></button>
                                    <button type="button" class="spf-btn-preview-template" data-template-id="<?php echo esc_attr($id); ?>"><?php _e('Preview', 'syntekpro-forms'); ?> →</button>
                                </div>
                            </div>
                        <?php 
                        endforeach;
                    } else {
                        echo '<p style="padding: 30px;">' . __('No templates found in class.', 'syntekpro-forms') . '</p>';
                    }
                } else {
                    echo '<p style="padding: 30px;">' . __('Template class not found.', 'syntekpro-forms') . '</p>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<style>
.spf-add-new-btn {
    background: #dc3232 !important;
    color: #fff !important;
    border: 1px solid #c82333 !important;
    border-radius: 4px !important;
    padding: 8px 18px !important;
    font-weight: 600 !important;
    font-size: 14px !important;
    cursor: pointer !important;
    box-shadow: 0 2px 4px rgba(220, 50, 50, 0.2) !important;
    transition: all 0.3s ease !important;
}
.spf-add-new-btn:hover {
    background: #e74c3c !important;
    box-shadow: 0 4px 12px rgba(220, 50, 50, 0.3) !important;
    transform: translateY(-2px) !important;
    border-color: #b81a23 !important;
}
.spf-add-new-btn:active {
    box-shadow: 0 1px 2px rgba(220, 50, 50, 0.2) !important;
    transform: translateY(0) !important;
}
.spf-admin-content-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
    padding: 20px;
}
.spf-empty-state {
    text-align: center;
    padding: 50px 20px;
}
.spf-status {
    padding: 4px 10px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}
.spf-status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 5px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}
.spf-status-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    animation: pulse 2s infinite;
}
.spf-status-indicator.spf-status-active {
    background: #e8f5e9;
    color: #2e7d32;
}
.spf-status-indicator.spf-status-active .spf-status-dot {
    background: #4caf50;
    box-shadow: 0 0 8px rgba(76, 175, 80, 0.4);
}
.spf-status-indicator.spf-status-inactive {
    background: #ffebee;
    color: #c62828;
}
.spf-status-indicator.spf-status-inactive .spf-status-dot {
    background: #f44336;
    box-shadow: 0 0 8px rgba(244, 67, 54, 0.4);
}
.spf-status-indicator.spf-status-trash {
    background: #f5f5f5;
    color: #424242;
}
.spf-status-indicator.spf-status-trash .spf-status-dot {
    background: #9e9e9e;
}
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}
.spf-status-active {
    background: #d4edda;
    color: #155724;
}
.spf-status-inactive {
    background: #f8d7da;
    color: #721c24;
}
.spf-status-trash {
    background: #f0f0f1;
    color: #646970;
}
.spf-stat-item {
    font-size: 14px;
    color: #646970;
}
.subsubsub {
    margin: 0 0 15px;
}
.subsubsub li {
    margin: 0;
    padding: 0;
    white-space: nowrap;
}
.subsubsub a {
    text-decoration: none;
    padding: 0 5px;
}
.subsubsub a.current {
    font-weight: 600;
    color: #000;
}
.wp-list-table th.sortable a,
.wp-list-table th.sorted a {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.sorting-indicator {
    margin-left: 5px;
    display: inline-flex;
    flex-direction: column;
    font-size: 8px;
    line-height: 5px;
    height: 12px;
    justify-content: center;
}
.sorting-indicator:before {
    content: "▲";
    opacity: 0.3;
    font-size: 8px;
}
.sorting-indicator:after {
    content: "▼";
    opacity: 0.3;
    font-size: 8px;
}
.wp-list-table th.sorted.asc .sorting-indicator:before {
    opacity: 1;
}
.wp-list-table th.sorted.desc .sorting-indicator:after {
    opacity: 1;
}
.row-actions {
    color: #646970;
}
.row-actions span {
    display: inline;
}
.row-actions a {
    color: #dc3232;
    text-decoration: none;
}
.row-actions a:hover {
    color: #b81a23;
}
.spf-settings-submenu-wrap {
    position: relative;
    display: inline-block;
}
.spf-settings-submenu {
    display: none;
    position: absolute;
    left: 0;
    top: 100%;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 3px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    min-width: 150px;
    z-index: 1000;
    margin-top: 5px;
    padding: 5px 0;
}
.spf-settings-submenu a {
    display: block;
    padding: 8px 15px;
    color: #dc3232;
    text-decoration: none;
    white-space: nowrap;
}
.spf-settings-submenu a:hover {
    background: #fff8f8;
    color: #b81a23;
}
.spf-settings-submenu-wrap:hover .spf-settings-submenu {
    display: block;
}
.spf-settings-tabs {
    margin-top: 20px;
}
.spf-tabs-nav {
    list-style: none;
    margin: 0;
    padding: 0;
    border-bottom: 1px solid #ccd0d4;
    display: flex;
}
.spf-tabs-nav li {
    margin: 0 5px -1px 0;
}
.spf-tabs-nav a {
    display: block;
    padding: 10px 15px;
    text-decoration: none;
    border: 1px solid transparent;
    background: #f0f0f1;
    color: #2c3338;
}
.spf-tabs-nav a.active {
    background: #fff;
    border-color: #ccd0d4 #ccd0d4 #fff;
    color: #000;
}
.spf-tab-content {
    display: none;
    padding: 20px 0;
}
.spf-tab-content.active {
    display: block;
}
.form-table th {
    width: 200px;
}
/* Column width adjustments */
.wp-list-table th:nth-child(1) {
    width: 30px; /* Checkbox */
}
.wp-list-table th:nth-child(2) {
    width: 80px; /* Status */
}
.wp-list-table th:nth-child(3) {
    width: 45%; /* Title - expanded */
    min-width: 250px;
}
.wp-list-table th:nth-child(4) {
    width: 60px; /* ID */
}
.wp-list-table th:nth-child(5) {
    width: 80px; /* Entries */
}
.wp-list-table th:nth-child(6) {
    width: 70px; /* Views */
}
.wp-list-table th:nth-child(7) {
    width: 100px; /* Conversion */
}
.wp-list-table td:nth-child(1) {
    width: 30px;
}
.wp-list-table td:nth-child(2) {
    width: 80px;
}
.wp-list-table td:nth-child(3) {
    width: 45%;
    min-width: 250px;
}
.wp-list-table td:nth-child(4) {
    width: 60px;
}
.wp-list-table td:nth-child(5) {
    width: 80px;
}
.wp-list-table td:nth-child(6) {
    width: 70px;
}
.wp-list-table td:nth-child(7) {
    width: 100px;
}

/* Template Modal Styles */
.spf-template-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    padding: 30px;
    background: #fafbfc;
}

.spf-template-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
    cursor: pointer;
}

.spf-template-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.spf-template-preview {
    width: 100%;
    height: 200px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: filter 0.3s ease;
}

.spf-template-item:hover .spf-template-preview {
    filter: blur(3px);
}

.spf-template-mock-form {
    width: 100%;
    height: 100%;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    justify-content: center;
}

.spf-mock-input {
    height: 12px;
    border-radius: 4px;
    animation: loading-pulse 1.5s ease-in-out infinite;
}

.spf-mock-button {
    height: 32px;
    border-radius: 4px;
    margin-top: 8px;
    animation: loading-pulse 1.5s ease-in-out infinite;
}

@keyframes loading-pulse {
    0%, 100% {
        opacity: 0.4;
    }
    50% {
        opacity: 0.7;
    }
}

.spf-template-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 10;
}

.spf-template-item:hover .spf-template-overlay {
    opacity: 1;
}

.spf-btn-use-template,
.spf-btn-preview-template {
    padding: 8px 16px !important;
    border: none !important;
    border-radius: 4px !important;
    font-size: 13px !important;
    font-weight: 500 !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    white-space: nowrap;
}

.spf-btn-use-template {
    background: #dc3232 !important;
    color: #fff !important;
}

.spf-btn-use-template:hover {
    background: #c82333 !important;
}

.spf-btn-use-template:active {
    transform: scale(0.95) !important;
}

.spf-btn-preview-template {
    background: #28a745 !important;
    color: #fff !important;
}

.spf-btn-preview-template:hover {
    background: #218838 !important;
}

.spf-btn-preview-template:active {
    transform: scale(0.95) !important;
}

.spf-template-info {
    padding: 15px;
    border-top: 1px solid #e2e8f0;
}

.spf-template-info h3 {
    margin: 0 0 8px;
    font-size: 14px;
    font-weight: 600;
    color: #2c3338;
}

.spf-template-info p {
    margin: 0;
    font-size: 12px;
    color: #646970;
    line-height: 1.4;
}

/* Modal Base Styles */
.spf-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.spf-modal-content {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-height: 90vh;
    overflow-y: auto;
}

.spf-template-modal-content {
    width: 90%;
    max-width: 1000px;
}

.spf-modal-header {
    padding: 20px 30px;
    border-bottom: 1px solid #f0f0f1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.spf-modal-header h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #2c3338;
}

.spf-modal-close {
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: 600;
    line-height: 1;
    color: #fff;
    background: #dc3232;
    border: 1px solid #c82333;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(220, 50, 50, 0.2);
    padding: 0;
}

.spf-modal-close:hover {
    background: #e74c3c;
    border-color: #b81a23;
    box-shadow: 0 4px 12px rgba(220, 50, 50, 0.3);
    transform: translateY(-2px);
}

.spf-modal-close:active {
    box-shadow: 0 1px 2px rgba(220, 50, 50, 0.2);
    transform: translateY(0);
}

.spf-modal-close:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(220, 50, 50, 0.35);
}

.spf-modal-body {
    padding: 30px;
}

/* Loading Spinner Animation */
.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

/* Form Preview Styling */
.spf-form-preview-wrapper {
    padding: 20px;
}

.spf-form-preview-wrapper .spf-field-group {
    margin-bottom: 20px;
}
</style>

<script>
jQuery(document).ready(function($) {
    var templateModalOpenBeforePreview = false;
    // Select All Forms (top and bottom checkboxes)
    $('#spf-select-all-forms, #spf-select-all-forms-bottom').on('change', function() {
        var isChecked = $(this).prop('checked');
        $('.spf-form-checkbox').prop('checked', isChecked);
        $('#spf-select-all-forms, #spf-select-all-forms-bottom').prop('checked', isChecked);
    });

    // Bulk Actions
    $('#spf-bulk-apply').on('click', function() {
        var action = $('#spf-bulk-action').val();
        var selectedForms = [];
        
        $('.spf-form-checkbox:checked').each(function() {
            selectedForms.push($(this).val());
        });

        if (selectedForms.length === 0) {
            alert('<?php echo esc_js(__('Please select at least one form.', 'syntekpro-forms')); ?>');
            return;
        }

        if (action === '-1') {
            alert('<?php echo esc_js(__('Please select an action.', 'syntekpro-forms')); ?>');
            return;
        }

        // Confirm for destructive actions
        if (action === 'delete_entries' || action === 'trash' || action === 'delete_forms') {
            var confirmMsg = '';
            if (action === 'delete_entries') {
                confirmMsg = '<?php echo esc_js(__('Are you sure you want to permanently delete all entries for the selected forms? This cannot be undone.', 'syntekpro-forms')); ?>';
            } else if (action === 'delete_forms') {
                confirmMsg = '<?php echo esc_js(__('Are you sure you want to permanently delete the selected forms and all their entries? This cannot be undone.', 'syntekpro-forms')); ?>';
            } else {
                confirmMsg = '<?php echo esc_js(__('Are you sure you want to move the selected forms to trash?', 'syntekpro-forms')); ?>';
            }
            
            if (!confirm(confirmMsg)) {
                return;
            }
        }

        $.ajax({
            url: spfAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'spf_bulk_action_forms',
                nonce: spfAdmin.nonce,
                bulk_action: action,
                form_ids: selectedForms
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || '<?php echo esc_js(__('Action failed.', 'syntekpro-forms')); ?>');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('An error occurred. Please try again.', 'syntekpro-forms')); ?>');
            }
        });
    });

    // Show Settings Modal
    $(document).on('click', '.spf-show-settings', function(e) {
        e.preventDefault();
        var formId = $(this).data('form-id');
        
        $('#spf-settings-modal').css('display', 'flex').hide().fadeIn();
        
        $.ajax({
            url: spfAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'spf_get_form_settings',
                nonce: spfAdmin.nonce,
                form_id: formId
            },
            success: function(response) {
                if (response.success) {
                    var settings = response.data;
                    var html = '<div class="spf-settings-tabs">';
                    html += '<ul class="spf-tabs-nav">';
                    html += '<li><a href="#spf-tab-settings" class="active"><?php _e('Form Settings', 'syntekpro-forms'); ?></a></li>';
                    html += '<li><a href="#spf-tab-confirmations"><?php _e('Confirmations', 'syntekpro-forms'); ?></a></li>';
                    html += '</ul>';
                    
                    // Form Settings Tab
                    html += '<div id="spf-tab-settings" class="spf-tab-content active">';
                    html += '<table class="form-table">';
                    html += '<tr><th><?php _e('Status', 'syntekpro-forms'); ?></th><td>' + settings.status + '</td></tr>';
                    html += '<tr><th><?php _e('Submit Button Text', 'syntekpro-forms'); ?></th><td>' + (settings.submit_button_text || 'Submit') + '</td></tr>';
                    html += '<tr><th><?php _e('Enable AJAX', 'syntekpro-forms'); ?></th><td>' + (settings.enable_ajax ? 'Yes' : 'No') + '</td></tr>';
                    html += '<tr><th><?php _e('Enable reCAPTCHA', 'syntekpro-forms'); ?></th><td>' + (settings.enable_recaptcha ? 'Yes' : 'No') + '</td></tr>';
                    html += '</table>';
                    html += '<a href="' + '<?php echo admin_url('admin.php?page=syntekpro-forms-new&form_id='); ?>' + formId + '" class="button button-primary"><?php _e('Edit Settings', 'syntekpro-forms'); ?></a>';
                    html += '</div>';
                    
                    // Confirmations Tab
                    html += '<div id="spf-tab-confirmations" class="spf-tab-content">';
                    html += '<table class="form-table">';
                    html += '<tr><th><?php _e('Success Message', 'syntekpro-forms'); ?></th><td>' + (settings.success_message || 'Thank you for your submission!') + '</td></tr>';
                    html += '<tr><th><?php _e('Redirect URL', 'syntekpro-forms'); ?></th><td>' + (settings.redirect_url || 'N/A') + '</td></tr>';
                    html += '</table>';
                    html += '<a href="' + '<?php echo admin_url('admin.php?page=syntekpro-forms-new&form_id='); ?>' + formId + '" class="button button-primary"><?php _e('Edit Confirmations', 'syntekpro-forms'); ?></a>';
                    html += '</div>';
                    
                    html += '</div>';
                    
                    $('#spf-settings-content').html(html);
                    
                    // Tab switching
                    $('.spf-tabs-nav a').on('click', function(e) {
                        e.preventDefault();
                        var target = $(this).attr('href');
                        $('.spf-tabs-nav a').removeClass('active');
                        $(this).addClass('active');
                        $('.spf-tab-content').removeClass('active');
                        $(target).addClass('active');
                    });
                } else {
                    $('#spf-settings-content').html('<div class="error"><p>' + response.data + '</p></div>');
                }
            }
        });
    });

    // Preview Form
    $(document).on('click', '.spf-preview-form', function(e) {
        e.preventDefault();
        var formId = $(this).data('form-id');
        
        $('#spf-preview-modal').css('display', 'flex').hide().fadeIn();
        $('#spf-preview-content').html('<div style="text-align:center;padding:40px;"><span class="dashicons dashicons-update spin"></span> <?php _e('Loading...', 'syntekpro-forms'); ?></div>');
        
        $.ajax({
            url: spfAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'spf_get_form_preview',
                nonce: spfAdmin.nonce,
                form_id: formId
            },
            success: function(response) {
                if (response.success) {
                    $('#spf-preview-content').html(response.data.html);
                } else {
                    $('#spf-preview-content').html('<div class="error"><p>' + (response.data || 'Error loading preview') + '</p></div>');
                }
            },
            error: function() {
                $('#spf-preview-content').html('<div class="error"><p><?php _e('Error loading preview', 'syntekpro-forms'); ?></p></div>');
            }
        });
    });

    // Show Form Settings submenu item
    $(document).on('click', '.spf-show-form-settings', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var formId = $(this).data('form-id');
        
        $('#spf-settings-modal').css('display', 'flex').hide().fadeIn();
        
        $.ajax({
            url: spfAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'spf_get_form_settings',
                nonce: spfAdmin.nonce,
                form_id: formId
            },
            success: function(response) {
                if (response.success) {
                    var settings = response.data;
                    var html = '<h3><?php _e('Form Settings', 'syntekpro-forms'); ?></h3>';
                    html += '<table class="form-table">';
                    html += '<tr><th><?php _e('Status', 'syntekpro-forms'); ?></th><td>' + settings.status + '</td></tr>';
                    html += '<tr><th><?php _e('Submit Button Text', 'syntekpro-forms'); ?></th><td>' + (settings.submit_button_text || 'Submit') + '</td></tr>';
                    html += '<tr><th><?php _e('Enable AJAX', 'syntekpro-forms'); ?></th><td>' + (settings.enable_ajax ? 'Yes' : 'No') + '</td></tr>';
                    html += '<tr><th><?php _e('Enable reCAPTCHA', 'syntekpro-forms'); ?></th><td>' + (settings.enable_recaptcha ? 'Yes' : 'No') + '</td></tr>';
                    html += '</table>';
                    html += '<a href="' + '<?php echo admin_url('admin.php?page=syntekpro-forms-new&form_id='); ?>' + formId + '" class="button button-primary"><?php _e('Edit Settings', 'syntekpro-forms'); ?></a>';
                    
                    $('#spf-settings-content').html(html);
                } else {
                    $('#spf-settings-content').html('<div class="error"><p>' + response.data + '</p></div>');
                }
            }
        });
    });

    // Show Confirmations submenu item
    $(document).on('click', '.spf-show-confirmations', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var formId = $(this).data('form-id');
        
        $('#spf-settings-modal').css('display', 'flex').hide().fadeIn();
        
        $.ajax({
            url: spfAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'spf_get_form_settings',
                nonce: spfAdmin.nonce,
                form_id: formId
            },
            success: function(response) {
                if (response.success) {
                    var settings = response.data;
                    var html = '<h3><?php _e('Confirmations', 'syntekpro-forms'); ?></h3>';
                    html += '<table class="form-table">';
                    html += '<tr><th><?php _e('Success Message', 'syntekpro-forms'); ?></th><td>' + (settings.success_message || 'Thank you for your submission!') + '</td></tr>';
                    html += '<tr><th><?php _e('Redirect URL', 'syntekpro-forms'); ?></th><td>' + (settings.redirect_url || 'N/A') + '</td></tr>';
                    html += '</table>';
                    html += '<a href="' + '<?php echo admin_url('admin.php?page=syntekpro-forms-new&form_id='); ?>' + formId + '" class="button button-primary"><?php _e('Edit Confirmations', 'syntekpro-forms'); ?></a>';
                    
                    $('#spf-settings-content').html(html);
                } else {
                    $('#spf-settings-content').html('<div class="error"><p>' + response.data + '</p></div>');
                }
            }
        });
    });

    // Trash Form
    $(document).on('click', '.spf-trash-form', function(e) {
        e.preventDefault();
        
        if (!confirm('<?php echo esc_js(__('Are you sure you want to move this form to trash?', 'syntekpro-forms')); ?>')) {
            return;
        }
        
        var formId = $(this).data('form-id');
        var $row = $(this).closest('tr');
        
        $.ajax({
            url: spfAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'spf_trash_form',
                nonce: spfAdmin.nonce,
                form_id: formId
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(function() {
                        location.reload();
                    });
                } else {
                    alert('<?php echo esc_js(__('Error moving form to trash.', 'syntekpro-forms')); ?>');
                }
            }
        });
    });

    // Delete Form Permanently
    $(document).on('click', '.spf-delete-form', function(e) {
        e.preventDefault();
        
        if (!confirm('<?php echo esc_js(__('Are you sure you want to permanently delete this form and all its entries? This cannot be undone.', 'syntekpro-forms')); ?>')) {
            return;
        }
        
        var formId = $(this).data('form-id');
        var $row = $(this).closest('tr');
        
        $.ajax({
            url: spfAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'spf_delete_form',
                nonce: spfAdmin.nonce,
                form_id: formId
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(function() {
                        location.reload();
                    });
                } else {
                    alert(response.data || '<?php echo esc_js(__('Error deleting form.', 'syntekpro-forms')); ?>');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Error deleting form.', 'syntekpro-forms')); ?>');
            }
        });
    });

    // Add New Form Modal (keep existing functionality)
    $('#spf-add-new-form, #spf-add-new-form-empty').on('click', function() {
        $('#spf-template-modal').css('display', 'flex').hide().fadeIn();
    });

    $('.spf-modal-close, .spf-modal').on('click', function(e) {
        if (e.target === this || $(e.target).hasClass('spf-modal-close')) {
            var $modal = $(this).hasClass('spf-modal') ? $(this) : $(this).closest('.spf-modal');
            var isPreviewModal = $modal.attr('id') === 'spf-preview-modal';
            $modal.fadeOut(function() {
                if (isPreviewModal && templateModalOpenBeforePreview) {
                    $('#spf-template-modal').css('display', 'flex').hide().fadeIn();
                    templateModalOpenBeforePreview = false;
                }
            });
        }
    });

    // Use Template
    $(document).on('click', '.spf-btn-use-template', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var templateId = $(this).closest('.spf-template-item').data('template-id');
        var $btn = $(this);
        
        if (typeof spfAdmin === 'undefined') {
            alert('Error: spfAdmin is not defined');
            return;
        }

        $btn.prop('disabled', true).text('Creating...');
        
        $.ajax({
            url: spfAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'spf_create_form_from_template',
                nonce: spfAdmin.nonce,
                template_id: templateId
            },
            success: function(response) {
                if (response.success && response.data.redirect) {
                    window.location.href = response.data.redirect;
                } else {
                    alert(response.data || 'Error creating form');
                    $btn.prop('disabled', false).text('Use Template');
                }
            },
            error: function(xhr) {
                alert('An error occurred. Please try again.');
                $btn.prop('disabled', false).text('Use Template');
            }
        });
    });

    // Preview Template
    $(document).on('click', '.spf-btn-preview-template', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var templateId = $(this).data('template-id');
        console.log('Preview clicked for template:', templateId);
        templateModalOpenBeforePreview = $('#spf-template-modal').is(':visible');
        if (templateModalOpenBeforePreview) {
            $('#spf-template-modal').hide();
        }
        
        // Show preview modal
        $('#spf-preview-modal').css('display', 'flex').hide().fadeIn();
        $('#spf-preview-content').html('<div style="text-align:center;padding:40px;"><span class="dashicons dashicons-update spin"></span> Loading preview...</div>');
        
        // Load template preview using form preview endpoint
        $.ajax({
            url: spfAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'spf_get_form_preview',
                nonce: spfAdmin.nonce,
                form_id: templateId,
                is_template: '1'
            },
            success: function(response) {
                console.log('Preview response:', response);
                if (response.success) {
                    $('#spf-preview-content').html(response.data.html);
                } else {
                    console.error('Preview error:', response.data);
                    $('#spf-preview-content').html('<div class="error"><p>' + (response.data || 'Error loading preview') + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error, xhr.responseText);
                $('#spf-preview-content').html('<div class="error"><p>Error loading preview: ' + error + '</p></div>');
            }
        });
    });
    
    // Duplicate form
    $(document).on('click', '.spf-duplicate-form', function(e) {
        e.preventDefault();
        
        var formId = $(this).data('form-id');
        var $link = $(this);

        $.ajax({
            url: spfAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'spf_duplicate_form',
                nonce: spfAdmin.nonce,
                form_id: formId
            },
            beforeSend: function() {
                $link.text('<?php echo esc_js(__('Duplicating...', 'syntekpro-forms')); ?>');
            },
            success: function(response) {
                if (response.success && response.data && response.data.redirect) {
                    window.location.href = response.data.redirect;
                } else {
                    alert(response.data || '<?php echo esc_js(__('Failed to duplicate form.', 'syntekpro-forms')); ?>');
                    $link.text('<?php echo esc_js(__('Duplicate', 'syntekpro-forms')); ?>');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('An error occurred. Please try again.', 'syntekpro-forms')); ?>');
                $link.text('<?php echo esc_js(__('Duplicate', 'syntekpro-forms')); ?>');
            }
        });
    });
});
</script>