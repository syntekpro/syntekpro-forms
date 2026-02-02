<?php
/**
 * Entry management helpers for SyntekPro Forms.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SyntekPro_Forms_Entries {

    /**
     * Reference to the main plugin bootstrap.
     *
     * @var SyntekPro_Forms_Builder
     */
    private $builder;

    public function __construct($builder) {
        $this->builder = $builder;
    }

    /**
     * Register WordPress AJAX hooks for entry actions.
     */
    public function register_hooks() {
        add_action('wp_ajax_spf_get_entry', array($this, 'ajax_get_entry'));
        add_action('wp_ajax_spf_mark_entry_read', array($this, 'ajax_mark_entry_read'));
        add_action('wp_ajax_spf_delete_entry', array($this, 'ajax_delete_entry'));
        add_action('wp_ajax_spf_bulk_delete_entries', array($this, 'ajax_bulk_delete_entries'));
        add_action('wp_ajax_spf_export_entries', array($this, 'ajax_export_entries'));
    }

    public function ajax_get_entry() {
        check_ajax_referer('spf_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $entry_id = intval($_POST['entry_id']);

        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spf_entries WHERE id = %d",
            $entry_id
        ));

        if (!$entry) {
            wp_send_json_error('Entry not found');
        }

        $form = $wpdb->get_var($wpdb->prepare(
            "SELECT title FROM {$wpdb->prefix}spf_forms WHERE id = %d",
            $entry->form_id
        ));

        wp_send_json_success(array(
            'entry_data' => json_decode($entry->entry_data, true),
            'form_title' => $form ? $form : 'Unknown Form',
            'created_at' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime((string) $entry->created_at)),
            'ip_address' => $entry->ip_address,
            'user_agent' => $entry->user_agent,
        ));
    }

    public function ajax_mark_entry_read() {
        check_ajax_referer('spf_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $entry_id = intval($_POST['entry_id']);

        $wpdb->update(
            $wpdb->prefix . 'spf_entries',
            array('status' => 'read'),
            array('id' => $entry_id),
            array('%s'),
            array('%d')
        );

        wp_send_json_success();
    }

    public function ajax_delete_entry() {
        check_ajax_referer('spf_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $entry_id = intval($_POST['entry_id']);

        $result = $wpdb->delete(
            $wpdb->prefix . 'spf_entries',
            array('id' => $entry_id),
            array('%d')
        );

        if ($result) {
            wp_send_json_success('Entry deleted successfully');
        } else {
            wp_send_json_error('Failed to delete entry');
        }
    }

    public function ajax_bulk_delete_entries() {
        check_ajax_referer('spf_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $entry_ids = isset($_POST['entry_ids']) ? $_POST['entry_ids'] : array();

        if (empty($entry_ids) || !is_array($entry_ids)) {
            wp_send_json_error('No entries selected');
        }

        $entry_ids = array_map('intval', $entry_ids);
        $ids_placeholder = implode(',', array_fill(0, count($entry_ids), '%d'));

        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}spf_entries WHERE id IN ($ids_placeholder)",
            $entry_ids
        ));

        if ($result !== false) {
            wp_send_json_success(sprintf(__('%d entries deleted successfully', 'syntekpro-forms'), $result));
        } else {
            wp_send_json_error('Failed to delete entries');
        }
    }

    public function ajax_export_entries() {
        check_ajax_referer('spf_export_entries', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        global $wpdb;

        $form_id = isset($_REQUEST['form_id']) ? intval($_REQUEST['form_id']) : 0;
        $status = isset($_REQUEST['status']) ? sanitize_text_field(wp_unslash((string) $_REQUEST['status'])) : '';
        $search = isset($_REQUEST['s']) ? sanitize_text_field(wp_unslash((string) $_REQUEST['s'])) : '';
        $date_from = isset($_REQUEST['date_from']) ? sanitize_text_field(wp_unslash((string) $_REQUEST['date_from'])) : '';
        $date_to = isset($_REQUEST['date_to']) ? sanitize_text_field(wp_unslash((string) $_REQUEST['date_to'])) : '';

        $where  = 'WHERE 1=1';
        $params = array();
        if ($form_id > 0) {
            $where  .= ' AND e.form_id = %d';
            $params[] = $form_id;
        }

        if (!empty($status)) {
            $where .= ' AND e.status = %s';
            $params[] = $status;
        }

        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where .= ' AND (e.entry_data LIKE %s OR e.ip_address LIKE %s OR f.title LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($date_from)) {
            $where .= ' AND DATE(e.created_at) >= %s';
            $params[] = $date_from;
        }

        if (!empty($date_to)) {
            $where .= ' AND DATE(e.created_at) <= %s';
            $params[] = $date_to;
        }

        $sql = "SELECT e.*, f.title AS form_title FROM {$wpdb->prefix}spf_entries e LEFT JOIN {$wpdb->prefix}spf_forms f ON e.form_id = f.id {$where} ORDER BY e.created_at DESC";
        $entries = empty($params) ? $wpdb->get_results($sql) : $wpdb->get_results($wpdb->prepare($sql, $params));

        $all_keys = array();
        foreach ((array) $entries as $entry) {
            $data = json_decode((string) $entry->entry_data, true);
            if (is_array($data)) {
                $all_keys = array_values(array_unique(array_merge($all_keys, array_keys($data))));
            }
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="syntekpro-entries-' . gmdate('Ymd-His') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        if ($output === false) {
            wp_die('Failed to open output stream');
        }

        $columns = array('Entry ID', 'Form ID', 'Form Title', 'Created At', 'IP Address', 'User Agent');
        $columns = array_merge($columns, $all_keys);
        fputcsv($output, $columns);

        foreach ((array) $entries as $entry) {
            $data = json_decode((string) $entry->entry_data, true);
            if (!is_array($data)) {
                $data = array();
            }

            $row = array(
                (int) $entry->id,
                (int) $entry->form_id,
                (string) $entry->form_title,
                (string) $entry->created_at,
                (string) $entry->ip_address,
                (string) $entry->user_agent,
            );

            foreach ($all_keys as $key) {
                $val = $data[$key] ?? '';
                if (is_array($val)) {
                    $val = implode(', ', array_map('sanitize_text_field', $val));
                } else {
                    $val = sanitize_text_field((string) $val);
                }
                $row[] = $val;
            }

            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }
}
