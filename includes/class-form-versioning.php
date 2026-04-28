<?php
/**
 * SyntekPro Forms - Form Versioning (Phase 2)
 * 
 * This class provides advanced form versioning with full version history,
 * comparison, and rollback capabilities. Phase 2 architectural stub.
 * 
 * @package SyntekPro_Forms
 * @subpackage Form_Versioning
 */

if (!defined('ABSPATH')) {
    exit;
}

class SyntekPro_Forms_Versioning {

    private static function can_manage_forms() {
        return current_user_can('spf_manage_forms') || current_user_can('manage_options');
    }

    private static function get_snapshot_row($version_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spf_form_versions WHERE id = %d",
            absint($version_id)
        ));
    }

    private static function decode_snapshot($snapshot_json) {
        $snapshot = json_decode((string) $snapshot_json, true);
        return is_array($snapshot) ? $snapshot : array();
    }

    /**
     * Get all versions of a form
     * 
     * @param int $form_id Form ID
     * @param int $limit Limit results
     * @return array|WP_Error Array of versions with metadata
     */
    public static function get_form_versions($form_id, $limit = 50) {
        global $wpdb;

        if (!self::can_manage_forms()) {
            return new WP_Error('unauthorized', __('You do not have permission to view form versions.', 'syntekpro-forms'));
        }

        $form_id = absint($form_id);
        $limit = max(1, min(200, absint($limit)));

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, form_id, version_num, snapshot_data, created_by, created_at
             FROM {$wpdb->prefix}spf_form_versions
             WHERE form_id = %d
             ORDER BY version_num DESC
             LIMIT %d",
            $form_id,
            $limit
        ));

        if (!$rows) {
            return array();
        }

        $versions = array();
        foreach ($rows as $row) {
            $snapshot = self::decode_snapshot($row->snapshot_data);
            $user = !empty($row->created_by) ? get_userdata((int) $row->created_by) : false;

            $versions[] = array(
                'id' => (int) $row->id,
                'form_id' => (int) $row->form_id,
                'version_num' => (int) $row->version_num,
                'description' => isset($snapshot['description']) ? (string) $snapshot['description'] : '',
                'snapshot' => $snapshot,
                'created_by' => (int) $row->created_by,
                'created_by_name' => $user ? $user->display_name : __('System', 'syntekpro-forms'),
                'created_at' => $row->created_at,
            );
        }

        return $versions;
    }

    /**
     * Compare two form versions
     * 
     * @param int $version_id_1 First version ID
     * @param int $version_id_2 Second version ID
     * @return array|WP_Error Diff array with added/modified/removed fields
     */
    public static function compare_versions($version_id_1, $version_id_2) {
        if (!self::can_manage_forms()) {
            return new WP_Error('unauthorized', __('You do not have permission to compare versions.', 'syntekpro-forms'));
        }

        $v1 = self::get_snapshot_row($version_id_1);
        $v2 = self::get_snapshot_row($version_id_2);

        if (!$v1 || !$v2) {
            return new WP_Error('not_found', __('One or both versions were not found.', 'syntekpro-forms'));
        }

        if ((int) $v1->form_id !== (int) $v2->form_id) {
            return new WP_Error('invalid_request', __('Versions must belong to the same form.', 'syntekpro-forms'));
        }

        $s1 = self::decode_snapshot($v1->snapshot_data);
        $s2 = self::decode_snapshot($v2->snapshot_data);

        $fields1 = isset($s1['fields']) && is_array($s1['fields']) ? $s1['fields'] : array();
        $fields2 = isset($s2['fields']) && is_array($s2['fields']) ? $s2['fields'] : array();

        $index1 = array();
        foreach ($fields1 as $field) {
            if (!is_array($field)) {
                continue;
            }
            $key = isset($field['id']) ? (string) $field['id'] : (isset($field['name']) ? (string) $field['name'] : md5(wp_json_encode($field)));
            $index1[$key] = $field;
        }

        $index2 = array();
        foreach ($fields2 as $field) {
            if (!is_array($field)) {
                continue;
            }
            $key = isset($field['id']) ? (string) $field['id'] : (isset($field['name']) ? (string) $field['name'] : md5(wp_json_encode($field)));
            $index2[$key] = $field;
        }

        $added = array();
        $removed = array();
        $modified = array();

        foreach ($index2 as $key => $field) {
            if (!isset($index1[$key])) {
                $added[] = $field;
            } elseif (wp_json_encode($index1[$key]) !== wp_json_encode($field)) {
                $modified[] = array(
                    'key' => $key,
                    'before' => $index1[$key],
                    'after' => $field,
                );
            }
        }

        foreach ($index1 as $key => $field) {
            if (!isset($index2[$key])) {
                $removed[] = $field;
            }
        }

        $settings_1 = isset($s1['settings']) && is_array($s1['settings']) ? $s1['settings'] : array();
        $settings_2 = isset($s2['settings']) && is_array($s2['settings']) ? $s2['settings'] : array();

        return array(
            'form_id' => (int) $v1->form_id,
            'version_1' => (int) $v1->version_num,
            'version_2' => (int) $v2->version_num,
            'title_changed' => (($s1['title'] ?? '') !== ($s2['title'] ?? '')),
            'description_changed' => (($s1['description'] ?? '') !== ($s2['description'] ?? '')),
            'added_fields' => $added,
            'removed_fields' => $removed,
            'modified_fields' => $modified,
            'settings_changed' => (wp_json_encode($settings_1) !== wp_json_encode($settings_2)),
        );
    }

    /**
     * Rollback form to specific version
     * 
     * @param int $form_id Form ID
     * @param int $version_id Version ID to restore
     * @return array|WP_Error Success/error with result
     */
    public static function rollback_to_version($form_id, $version_id) {
        global $wpdb;

        if (!self::can_manage_forms()) {
            return new WP_Error('unauthorized', __('You do not have permission to rollback forms.', 'syntekpro-forms'));
        }

        $form_id = absint($form_id);
        $version_id = absint($version_id);

        $version = self::get_snapshot_row($version_id);
        if (!$version || (int) $version->form_id !== $form_id) {
            return new WP_Error('not_found', __('Requested version does not belong to this form.', 'syntekpro-forms'));
        }

        $form = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spf_forms WHERE id = %d",
            $form_id
        ));

        if (!$form) {
            return new WP_Error('not_found', __('Form not found.', 'syntekpro-forms'));
        }

        $current_snapshot = array(
            'form_id' => (int) $form->id,
            'title' => (string) $form->title,
            'description' => (string) $form->description,
            'fields' => json_decode((string) $form->fields, true),
            'settings' => json_decode((string) $form->settings, true),
        );
        self::create_version_snapshot($form_id, $current_snapshot, sprintf(__('Auto backup before rollback to v%d', 'syntekpro-forms'), (int) $version->version_num));

        $snapshot = self::decode_snapshot($version->snapshot_data);
        if (empty($snapshot)) {
            return new WP_Error('invalid_snapshot', __('Version snapshot is invalid.', 'syntekpro-forms'));
        }

        $updated = $wpdb->update(
            $wpdb->prefix . 'spf_forms',
            array(
                'title' => isset($snapshot['title']) ? sanitize_text_field($snapshot['title']) : $form->title,
                'description' => isset($snapshot['description']) ? sanitize_textarea_field($snapshot['description']) : $form->description,
                'fields' => wp_json_encode(isset($snapshot['fields']) ? $snapshot['fields'] : array()),
                'settings' => wp_json_encode(isset($snapshot['settings']) ? $snapshot['settings'] : array()),
            ),
            array('id' => $form_id),
            array('%s', '%s', '%s', '%s'),
            array('%d')
        );

        if ($updated === false) {
            return new WP_Error('db_error', __('Rollback failed due to database error.', 'syntekpro-forms'));
        }

        spf_log_audit($form_id, 'form_rollback', array(
            'rollback_to_version' => (int) $version->version_num,
            'source_version_id' => (int) $version_id,
        ));

        return array(
            'success' => true,
            'form_id' => $form_id,
            'restored_version' => (int) $version->version_num,
        );
    }

    /**
     * Create version snapshot (called by backup system)
     * 
     * @param int $form_id Form ID
     * @param array $form_data Form data to snapshot
     * @param string $description Version description
     * @return int|WP_Error Version ID or error
     */
    public static function create_version_snapshot($form_id, $form_data, $description = '') {
        global $wpdb;

        $form_id = absint($form_id);
        if ($form_id <= 0) {
            return new WP_Error('invalid_form', __('Invalid form ID.', 'syntekpro-forms'));
        }

        if (!is_array($form_data)) {
            return new WP_Error('invalid_data', __('Snapshot data must be an array.', 'syntekpro-forms'));
        }

        $max_version = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(version_num) FROM {$wpdb->prefix}spf_form_versions WHERE form_id = %d",
            $form_id
        ));
        $next_version = $max_version + 1;

        $snapshot = array(
            'form_id' => $form_id,
            'title' => isset($form_data['title']) ? sanitize_text_field((string) $form_data['title']) : '',
            'description' => isset($form_data['description']) ? sanitize_textarea_field((string) $form_data['description']) : '',
            'fields' => isset($form_data['fields']) && is_array($form_data['fields']) ? $form_data['fields'] : array(),
            'settings' => isset($form_data['settings']) && is_array($form_data['settings']) ? $form_data['settings'] : array(),
            'description_label' => sanitize_text_field((string) $description),
            'saved_at' => current_time('mysql'),
        );

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'spf_form_versions',
            array(
                'form_id' => $form_id,
                'version_num' => $next_version,
                'snapshot_data' => wp_json_encode($snapshot),
                'created_by' => get_current_user_id(),
            ),
            array('%d', '%d', '%s', '%d')
        );

        if ($inserted === false) {
            return new WP_Error('db_error', __('Failed to save form snapshot.', 'syntekpro-forms'));
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Get version history for admin display
     * 
     * @param int $form_id Form ID
     * @param int $page Pagination page
     * @return array Version history formatted for admin UI
     */
    public static function get_version_history($form_id, $page = 1) {
        global $wpdb;

        if (!self::can_manage_forms()) {
            return array(
                'total' => 0,
                'page' => 1,
                'versions' => array(),
            );
        }

        $form_id = absint($form_id);
        $page = max(1, absint($page));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}spf_form_versions WHERE form_id = %d",
            $form_id
        ));

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, version_num, snapshot_data, created_by, created_at
             FROM {$wpdb->prefix}spf_form_versions
             WHERE form_id = %d
             ORDER BY version_num DESC
             LIMIT %d OFFSET %d",
            $form_id,
            $limit,
            $offset
        ));

        $history = array();
        foreach ($rows as $row) {
            $snapshot = self::decode_snapshot($row->snapshot_data);
            $fields = isset($snapshot['fields']) && is_array($snapshot['fields']) ? $snapshot['fields'] : array();
            $user = !empty($row->created_by) ? get_userdata((int) $row->created_by) : false;

            $history[] = array(
                'id' => (int) $row->id,
                'version_num' => (int) $row->version_num,
                'description' => isset($snapshot['description_label']) ? (string) $snapshot['description_label'] : '',
                'created_by' => (int) $row->created_by,
                'created_by_name' => $user ? $user->display_name : __('System', 'syntekpro-forms'),
                'created_at' => $row->created_at,
                'created_at_human' => human_time_diff(strtotime($row->created_at), current_time('timestamp')) . ' ' . __('ago', 'syntekpro-forms'),
                'field_count' => count($fields),
            );
        }

        return array(
            'total' => $total,
            'page' => $page,
            'versions' => $history,
        );
    }
}
