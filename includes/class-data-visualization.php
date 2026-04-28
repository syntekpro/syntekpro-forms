<?php
/**
 * SyntekPro Forms - Data Visualization (Phase 2)
 * 
 * Entry data visualization with charts, custom dashboards, and interactive
 * analytics. Phase 2 architectural stub.
 * 
 * @package SyntekPro_Forms
 * @subpackage Data_Visualization
 */

if (!defined('ABSPATH')) {
    exit;
}

class SyntekPro_Forms_Data_Visualization {

    private static function can_manage_forms() {
        return current_user_can('spf_manage_forms') || current_user_can('manage_options');
    }

    private static function can_view_entries() {
        return current_user_can('spf_view_entries') || current_user_can('manage_options');
    }

    private static function decode_json_array($value) {
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : array();
    }

    private static function get_entries($form_id) {
        global $wpdb;

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, entry_data, created_at FROM {$wpdb->prefix}spf_entries WHERE form_id = %d ORDER BY created_at ASC",
            absint($form_id)
        ));

        $entries = array();
        foreach ((array) $rows as $row) {
            $entries[] = array(
                'id' => (int) $row->id,
                'created_at' => (string) $row->created_at,
                'data' => self::decode_json_array($row->entry_data),
            );
        }

        return $entries;
    }

    private static function palette() {
        return array('#0F766E', '#F59E0B', '#2563EB', '#DC2626', '#7C3AED', '#16A34A', '#EA580C', '#0EA5E9');
    }

    /**
     * Get available chart types for form
     * 
     * @param int $form_id Form ID
     * @return array Chart types applicable to form fields
     */
    public static function get_available_charts($form_id) {
        global $wpdb;

        $form = $wpdb->get_row($wpdb->prepare(
            "SELECT fields FROM {$wpdb->prefix}spf_forms WHERE id = %d",
            absint($form_id)
        ));

        if (!$form) {
            return array('charts' => array(), 'status' => 'ok');
        }

        $fields = self::decode_json_array($form->fields);
        $charts = array();

        foreach ((array) $fields as $field) {
            if (!is_array($field) || empty($field['name'])) {
                continue;
            }

            $type = isset($field['type']) ? sanitize_key((string) $field['type']) : 'text';
            $supported = array('bar', 'pie');

            if (in_array($type, array('number', 'calculation'), true)) {
                $supported = array('bar', 'line', 'histogram');
            }
            if (in_array($type, array('date', 'datetime'), true)) {
                $supported = array('line', 'bar');
            }
            if (in_array($type, array('checkbox', 'multiselect'), true)) {
                $supported = array('bar', 'pie');
            }

            $charts[] = array(
                'field_name' => (string) $field['name'],
                'field_label' => isset($field['label']) ? (string) $field['label'] : (string) $field['name'],
                'field_type' => $type,
                'supported_charts' => $supported,
            );
        }

        return array(
            'charts' => $charts,
            'status' => 'ok',
        );
    }

    /**
     * Generate chart data for visualization
     * 
     * @param int $form_id Form ID
     * @param string $field_name Field name to visualize
     * @param string $chart_type Chart type (pie, bar, line, etc)
     * @param array $options Chart options (date_range, grouping, etc)
     * @return array|WP_Error Chart data formatted for Chart.js or D3.js
     */
    public static function get_chart_data($form_id, $field_name, $chart_type, $options = array()) {
        global $wpdb;

        if (!self::can_view_entries()) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        $form_id = absint($form_id);
        $field_name = sanitize_text_field((string) $field_name);
        $chart_type = sanitize_key((string) $chart_type);

        $entries = self::get_entries($form_id);
        if (empty($entries)) {
            return array('labels' => array(), 'datasets' => array(), 'status' => 'ok');
        }

        $buckets = array();
        $numeric_values = array();

        foreach ($entries as $entry) {
            if ($chart_type === 'line' && $field_name === '') {
                $label = gmdate('Y-m-d', strtotime($entry['created_at']));
                if (!isset($buckets[$label])) {
                    $buckets[$label] = 0;
                }
                $buckets[$label]++;
                continue;
            }

            $value = $entry['data'][$field_name] ?? null;
            if (is_array($value)) {
                foreach ($value as $item) {
                    $label = (string) $item;
                    if ($label === '') {
                        $label = __('Empty', 'syntekpro-forms');
                    }
                    if (!isset($buckets[$label])) {
                        $buckets[$label] = 0;
                    }
                    $buckets[$label]++;
                }
                continue;
            }

            if ($chart_type === 'histogram' && is_numeric($value)) {
                $numeric_values[] = (float) $value;
                continue;
            }

            $label = (string) $value;
            if ($label === '') {
                $label = __('Empty', 'syntekpro-forms');
            }
            if (!isset($buckets[$label])) {
                $buckets[$label] = 0;
            }
            $buckets[$label]++;
        }

        if ($chart_type === 'histogram') {
            if (empty($numeric_values)) {
                return array('labels' => array(), 'datasets' => array(), 'status' => 'ok');
            }

            $min = min($numeric_values);
            $max = max($numeric_values);
            $range = max(1, ($max - $min));
            $bins = 5;
            $step = $range / $bins;
            $hist = array();

            for ($i = 0; $i < $bins; $i++) {
                $start = $min + ($i * $step);
                $end = $start + $step;
                $key = round($start, 2) . '-' . round($end, 2);
                $hist[$key] = 0;
            }

            foreach ($numeric_values as $num) {
                $idx = (int) floor(($num - $min) / $step);
                if ($idx >= $bins) {
                    $idx = $bins - 1;
                }
                $keys = array_keys($hist);
                $hist[$keys[$idx]]++;
            }

            $buckets = $hist;
        }

        ksort($buckets);
        $labels = array_keys($buckets);
        $values = array_values($buckets);

        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => $field_name !== '' ? $field_name : __('Submissions', 'syntekpro-forms'),
                    'data' => $values,
                    'backgroundColor' => array_slice(self::palette(), 0, max(1, count($labels))),
                    'borderColor' => '#1f2937',
                ),
            ),
            'status' => 'ok',
        );
    }

    /**
     * Create custom analytics dashboard
     * 
     * @param int $form_id Form ID
     * @param string $dashboard_name Dashboard name
     * @param array $widgets Widget configuration array
     * @return int|WP_Error Dashboard ID or error
     */
    public static function create_dashboard($form_id, $dashboard_name, $widgets = array()) {
        global $wpdb;

        if (!self::can_manage_forms()) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        $form_id = absint($form_id);
        $dashboard_name = sanitize_text_field((string) $dashboard_name);

        if ($form_id <= 0 || $dashboard_name === '') {
            return new WP_Error('invalid_data', __('Form ID and dashboard name are required.', 'syntekpro-forms'));
        }

        $exists = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}spf_dashboards WHERE form_id = %d AND dashboard_name = %s",
            $form_id,
            $dashboard_name
        ));
        if ($exists > 0) {
            return new WP_Error('duplicate', __('A dashboard with this name already exists for this form.', 'syntekpro-forms'));
        }

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'spf_dashboards',
            array(
                'form_id' => $form_id,
                'dashboard_name' => $dashboard_name,
                'widget_config' => wp_json_encode(is_array($widgets) ? $widgets : array()),
                'created_by' => get_current_user_id(),
                'updated_by' => get_current_user_id(),
            ),
            array('%d', '%s', '%s', '%d', '%d')
        );

        if ($inserted === false) {
            return new WP_Error('db_error', __('Failed to create dashboard.', 'syntekpro-forms'));
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Get dashboard widgets with data
     * 
     * @param int $dashboard_id Dashboard ID
     * @return array|WP_Error Dashboard with all widgets populated with data
     */
    public static function get_dashboard_with_data($dashboard_id) {
        global $wpdb;

        if (!self::can_view_entries()) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        $dashboard = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spf_dashboards WHERE id = %d",
            absint($dashboard_id)
        ));
        if (!$dashboard) {
            return new WP_Error('not_found', __('Dashboard not found.', 'syntekpro-forms'));
        }

        $widgets = self::decode_json_array($dashboard->widget_config);
        $populated = array();
        foreach ((array) $widgets as $widget) {
            if (!is_array($widget)) {
                continue;
            }
            $type = isset($widget['type']) ? sanitize_key((string) $widget['type']) : 'chart';
            if ($type === 'stat') {
                $value = self::get_stat((int) $dashboard->form_id, (string) ($widget['stat_type'] ?? 'submissions'));
                $widget['value'] = $value;
            } else {
                $chart = self::get_chart_data(
                    (int) $dashboard->form_id,
                    (string) ($widget['field_name'] ?? ''),
                    (string) ($widget['chart_type'] ?? 'bar')
                );
                $widget['chart'] = $chart;
            }
            $populated[] = $widget;
        }

        return array(
            'dashboard_id' => (int) $dashboard_id,
            'form_id' => (int) $dashboard->form_id,
            'dashboard_name' => (string) $dashboard->dashboard_name,
            'widgets' => $populated,
            'status' => 'ok',
        );
    }

    /**
     * Get stats for dashboard stat widgets
     * 
     * @param int $form_id Form ID
     * @param string $stat_type Type of statistic (submissions, avg_time, etc)
     * @param array $filters Filters for calculation
     * @return int|float Statistic value
     */
    public static function get_stat($form_id, $stat_type, $filters = array()) {
        global $wpdb;

        if (!self::can_view_entries()) {
            return 0;
        }

        $form_id = absint($form_id);
        $stat_type = sanitize_key((string) $stat_type);

        if ($stat_type === 'submissions') {
            return (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}spf_entries WHERE form_id = %d",
                $form_id
            ));
        }

        if ($stat_type === 'avg_time_to_complete') {
            $time = SyntekPro_Forms_Funnel_Analytics::get_time_to_complete($form_id, $filters);
            return is_array($time) ? (float) ($time['average_seconds'] ?? 0) : 0;
        }

        if ($stat_type === 'completion_rate') {
            $funnel = SyntekPro_Forms_Funnel_Analytics::get_funnel_analysis($form_id, $filters);
            return is_array($funnel) ? (float) ($funnel['totals']['completion_rate'] ?? 0) : 0;
        }

        if ($stat_type === 'abandonment_rate') {
            $funnel = SyntekPro_Forms_Funnel_Analytics::get_funnel_analysis($form_id, $filters);
            return is_array($funnel) ? (float) ($funnel['totals']['dropoff_rate'] ?? 0) : 0;
        }

        if ($stat_type === 'avg_rating') {
            $entries = self::get_entries($form_id);
            $sum = 0;
            $count = 0;
            foreach ($entries as $entry) {
                foreach ((array) $entry['data'] as $key => $val) {
                    if (strpos(strtolower((string) $key), 'rating') !== false && is_numeric($val)) {
                        $sum += (float) $val;
                        $count++;
                    }
                }
            }
            return $count > 0 ? round($sum / $count, 2) : 0;
        }

        return 0;
    }

    /**
     * Export chart as image
     * 
     * @param int $form_id Form ID
     * @param string $field_name Field to chart
     * @param string $chart_type Chart type
     * @param string $format 'png' or 'svg' or 'pdf'
     * @return string|WP_Error Image file path or error
     */
    public static function export_chart($form_id, $field_name, $chart_type, $format = 'png') {
        if (!self::can_view_entries()) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        $format = strtolower(sanitize_key((string) $format));
        if (!in_array($format, array('svg', 'pdf', 'png'), true)) {
            return new WP_Error('invalid_format', __('Unsupported chart export format.', 'syntekpro-forms'));
        }

        if ($format !== 'svg') {
            return new WP_Error('not_supported', __('PNG/PDF export requires a rendering engine; SVG export is available.', 'syntekpro-forms'));
        }

        $chart = self::get_chart_data($form_id, $field_name, $chart_type);
        if (is_wp_error($chart)) {
            return $chart;
        }

        $upload = wp_upload_dir();
        $dir = trailingslashit($upload['basedir']) . 'syntekpro-forms/charts';
        if (!wp_mkdir_p($dir)) {
            return new WP_Error('write_failed', __('Unable to create charts directory.', 'syntekpro-forms'));
        }

        $filename = 'chart-' . absint($form_id) . '-' . gmdate('Ymd-His') . '.svg';
        $path = trailingslashit($dir) . $filename;

        $labels = implode(', ', (array) ($chart['labels'] ?? array()));
        $values = implode(', ', (array) (($chart['datasets'][0]['data'] ?? array())));
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="300">'
            . '<rect width="100%" height="100%" fill="#f8fafc"/>'
            . '<text x="20" y="40" font-size="22" fill="#0f172a">SyntekPro Chart Export</text>'
            . '<text x="20" y="90" font-size="16" fill="#334155">Field: ' . esc_html($field_name) . '</text>'
            . '<text x="20" y="125" font-size="14" fill="#334155">Labels: ' . esc_html($labels) . '</text>'
            . '<text x="20" y="160" font-size="14" fill="#334155">Values: ' . esc_html($values) . '</text>'
            . '</svg>';

        $written = file_put_contents($path, $svg);
        if ($written === false) {
            return new WP_Error('write_failed', __('Unable to write SVG chart export.', 'syntekpro-forms'));
        }

        return $path;
    }
}
