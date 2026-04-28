<?php
/**
 * SyntekPro Forms - Funnel Analytics (Phase 2)
 * 
 * Advanced funnel analysis dashboard with field drop-off, time-to-complete,
 * and geographic breakdown. Phase 2 architectural stub.
 * 
 * @package SyntekPro_Forms
 * @subpackage Funnel_Analytics
 */

if (!defined('ABSPATH')) {
    exit;
}

class SyntekPro_Forms_Funnel_Analytics {

    private static function can_view_entries() {
        return current_user_can('spf_view_entries') || current_user_can('manage_options');
    }

    private static function apply_date_filter_sql($filters, &$args, $column = 'created_at') {
        $sql = '';
        if (!empty($filters['date_from'])) {
            $sql .= " AND {$column} >= %s";
            $args[] = sanitize_text_field((string) $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND {$column} <= %s";
            $args[] = sanitize_text_field((string) $filters['date_to']);
        }
        return $sql;
    }

    private static function decode_entry_data($json) {
        $decoded = json_decode((string) $json, true);
        return is_array($decoded) ? $decoded : array();
    }

    /**
     * Get form funnel analysis
     * 
     * @param int $form_id Form ID
     * @param array $filters Optional filters (date_range, variant, etc)
     * @return array|WP_Error Funnel data with drop-off metrics
     */
    public static function get_funnel_analysis($form_id, $filters = array()) {
        global $wpdb;

        if (!self::can_view_entries()) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        $form_id = absint($form_id);
        $entry_args = array($form_id);
        $entry_where = self::apply_date_filter_sql($filters, $entry_args);

        $submissions = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}spf_entries WHERE form_id = %d {$entry_where}",
            $entry_args
        ));

        $view_args = array($form_id);
        $view_where = self::apply_date_filter_sql($filters, $view_args);
        $views = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}spf_analytics
             WHERE form_id = %d AND event_type IN ('view','start','form_view','form_start') {$view_where}",
            $view_args
        ));

        if ($views < $submissions) {
            $views = $submissions;
        }

        $abandoned = max(0, $views - $submissions);
        $completion_rate = $views > 0 ? round(($submissions / $views) * 100, 2) : 0;
        $dropoff_rate = $views > 0 ? round(($abandoned / $views) * 100, 2) : 0;

        return array(
            'stages' => array(
                array(
                    'stage' => 'views',
                    'count' => $views,
                    'dropoff_rate' => 0,
                ),
                array(
                    'stage' => 'submissions',
                    'count' => $submissions,
                    'dropoff_rate' => $dropoff_rate,
                ),
            ),
            'totals' => array(
                'views' => $views,
                'submissions' => $submissions,
                'abandoned' => $abandoned,
                'completion_rate' => $completion_rate,
                'dropoff_rate' => $dropoff_rate,
            ),
            'status' => 'ok',
        );
    }

    /**
     * Get field-level drop-off analysis
     * 
     * @param int $form_id Form ID
     * @param array $filters Optional filters
     * @return array|WP_Error Drop-off metrics per field
     */
    public static function get_field_dropoff($form_id, $filters = array()) {
        global $wpdb;

        if (!self::can_view_entries()) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        $form_id = absint($form_id);
        $form = $wpdb->get_row($wpdb->prepare(
            "SELECT fields FROM {$wpdb->prefix}spf_forms WHERE id = %d",
            $form_id
        ));

        if (!$form) {
            return new WP_Error('not_found', __('Form not found.', 'syntekpro-forms'));
        }

        $fields = self::decode_entry_data($form->fields);
        $field_keys = array();
        foreach ((array) $fields as $f) {
            if (!empty($f['name'])) {
                $field_keys[] = (string) $f['name'];
            }
        }

        $args = array($form_id);
        $where = self::apply_date_filter_sql($filters, $args);
        $entries = $wpdb->get_results($wpdb->prepare(
            "SELECT entry_data FROM {$wpdb->prefix}spf_entries WHERE form_id = %d {$where}",
            $args
        ));

        $total = count((array) $entries);
        $result = array();
        foreach ($field_keys as $key) {
            $filled = 0;
            foreach ((array) $entries as $entry) {
                $data = self::decode_entry_data($entry->entry_data);
                if (!array_key_exists($key, $data)) {
                    continue;
                }
                $value = $data[$key];
                if (is_array($value)) {
                    if (!empty($value)) {
                        $filled++;
                    }
                } elseif ($value !== '' && $value !== null) {
                    $filled++;
                }
            }

            $dropoff = $total > 0 ? round((($total - $filled) / $total) * 100, 2) : 0;
            $result[] = array(
                'field_name' => $key,
                'entries_total' => $total,
                'filled_count' => $filled,
                'dropoff_percent' => $dropoff,
                'high_dropoff' => $dropoff >= 30,
            );
        }

        usort($result, function($a, $b) {
            return $b['dropoff_percent'] <=> $a['dropoff_percent'];
        });

        return array(
            'fields' => $result,
            'status' => 'ok',
        );
    }

    /**
     * Get time-to-complete analysis
     * 
     * @param int $form_id Form ID
     * @param array $filters Optional filters
     * @return array|WP_Error Time metrics (average, median, P95, etc)
     */
    public static function get_time_to_complete($form_id, $filters = array()) {
        global $wpdb;

        if (!self::can_view_entries()) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        $form_id = absint($form_id);
        $args = array($form_id);
        $where = self::apply_date_filter_sql($filters, $args);

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT session_id,
                    MIN(CASE WHEN event_type IN ('start','form_start') THEN created_at END) AS started_at,
                    MAX(CASE WHEN event_type IN ('complete','submit','form_submit') THEN created_at END) AS completed_at
             FROM {$wpdb->prefix}spf_analytics
             WHERE form_id = %d {$where}
             GROUP BY session_id
             HAVING started_at IS NOT NULL AND completed_at IS NOT NULL",
            $args
        ));

        $durations = array();
        foreach ((array) $rows as $row) {
            $start = strtotime((string) $row->started_at);
            $end = strtotime((string) $row->completed_at);
            if ($start && $end && $end >= $start) {
                $durations[] = $end - $start;
            }
        }

        if (empty($durations)) {
            return array(
                'average_seconds' => 0,
                'median_seconds' => 0,
                'percentiles' => array('p50' => 0, 'p75' => 0, 'p95' => 0),
                'status' => 'ok',
            );
        }

        sort($durations);
        $count = count($durations);
        $avg = array_sum($durations) / $count;
        $median = $durations[(int) floor(($count - 1) / 2)];
        $p50 = $durations[(int) floor(($count - 1) * 0.50)];
        $p75 = $durations[(int) floor(($count - 1) * 0.75)];
        $p95 = $durations[(int) floor(($count - 1) * 0.95)];

        return array(
            'average_seconds' => round($avg, 2),
            'median_seconds' => $median,
            'percentiles' => array(
                'p50' => $p50,
                'p75' => $p75,
                'p95' => $p95,
            ),
            'status' => 'ok',
        );
    }

    /**
     * Get geographic breakdown of submissions
     * 
     * @param int $form_id Form ID
     * @param array $filters Optional filters
     * @return array|WP_Error Geographic metrics (countries, regions, cities)
     */
    public static function get_geo_breakdown($form_id, $filters = array()) {
        global $wpdb;

        if (!self::can_view_entries()) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        $form_id = absint($form_id);
        $args = array($form_id);
        $where = self::apply_date_filter_sql($filters, $args);

        $ips = $wpdb->get_results($wpdb->prepare(
            "SELECT ip_address FROM {$wpdb->prefix}spf_entries WHERE form_id = %d {$where}",
            $args
        ));

        $countries = array();
        foreach ((array) $ips as $row) {
            $ip = trim((string) $row->ip_address);
            $label = 'Unknown';
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $first = (int) explode('.', $ip)[0];
                $label = 'IPv4-' . $first;
            } elseif ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $label = 'IPv6';
            }

            if (!isset($countries[$label])) {
                $countries[$label] = 0;
            }
            $countries[$label]++;
        }

        arsort($countries);
        $result = array();
        foreach ($countries as $country => $count) {
            $result[] = array(
                'country' => $country,
                'submissions_count' => $count,
            );
        }

        return array(
            'countries' => $result,
            'status' => 'ok',
            'note' => __('Country resolution uses IP grouping until MaxMind integration is enabled in Phase 2.3.', 'syntekpro-forms'),
        );
    }

    /**
     * Get device/browser breakdown
     * 
     * @param int $form_id Form ID
     * @param array $filters Optional filters
     * @return array|WP_Error Device metrics (device_type, browser, OS)
     */
    public static function get_device_breakdown($form_id, $filters = array()) {
        global $wpdb;

        if (!self::can_view_entries()) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        $form_id = absint($form_id);
        $args = array($form_id);
        $where = self::apply_date_filter_sql($filters, $args);

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT user_agent FROM {$wpdb->prefix}spf_entries WHERE form_id = %d {$where}",
            $args
        ));

        $devices = array();
        foreach ((array) $rows as $row) {
            $ua = strtolower((string) $row->user_agent);
            if ($ua === '') {
                $key = 'unknown';
            } elseif (strpos($ua, 'mobile') !== false) {
                $key = 'mobile';
            } elseif (strpos($ua, 'tablet') !== false || strpos($ua, 'ipad') !== false) {
                $key = 'tablet';
            } else {
                $key = 'desktop';
            }

            if (!isset($devices[$key])) {
                $devices[$key] = 0;
            }
            $devices[$key]++;
        }

        $result = array();
        foreach ($devices as $device => $count) {
            $result[] = array(
                'device_type' => $device,
                'submissions_count' => $count,
            );
        }

        return array(
            'devices' => $result,
            'status' => 'ok',
        );
    }

    /**
     * Generate funnel report for download
     * 
     * @param int $form_id Form ID
     * @param string $format 'pdf' or 'csv'
     * @param array $filters Filters for analysis
     * @return string|WP_Error Report file path or error
     */
    public static function generate_report($form_id, $format = 'pdf', $filters = array()) {
        if (!self::can_view_entries()) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        $format = strtolower(sanitize_key((string) $format));
        if (!in_array($format, array('csv', 'pdf'), true)) {
            return new WP_Error('invalid_format', __('Report format must be csv or pdf.', 'syntekpro-forms'));
        }

        if ($format === 'pdf') {
            return new WP_Error('not_supported', __('PDF export requires a PDF library; use CSV for now.', 'syntekpro-forms'));
        }

        $funnel = self::get_funnel_analysis($form_id, $filters);
        $dropoff = self::get_field_dropoff($form_id, $filters);
        $time = self::get_time_to_complete($form_id, $filters);
        $geo = self::get_geo_breakdown($form_id, $filters);

        $upload = wp_upload_dir();
        $dir = trailingslashit($upload['basedir']) . 'syntekpro-forms/reports';
        if (!wp_mkdir_p($dir)) {
            return new WP_Error('write_failed', __('Unable to create reports directory.', 'syntekpro-forms'));
        }

        $filename = 'funnel-report-form-' . absint($form_id) . '-' . gmdate('Ymd-His') . '.csv';
        $path = trailingslashit($dir) . $filename;
        $handle = fopen($path, 'w');
        if ($handle === false) {
            return new WP_Error('write_failed', __('Unable to create report file.', 'syntekpro-forms'));
        }

        fputcsv($handle, array('Section', 'Metric', 'Value'));
        foreach ((array) ($funnel['totals'] ?? array()) as $metric => $value) {
            fputcsv($handle, array('funnel', $metric, $value));
        }
        foreach ((array) ($time['percentiles'] ?? array()) as $metric => $value) {
            fputcsv($handle, array('time_to_complete', $metric, $value));
        }
        foreach ((array) ($dropoff['fields'] ?? array()) as $row) {
            fputcsv($handle, array('field_dropoff', (string) ($row['field_name'] ?? ''), (string) ($row['dropoff_percent'] ?? 0)));
        }
        foreach ((array) ($geo['countries'] ?? array()) as $row) {
            fputcsv($handle, array('geo', (string) ($row['country'] ?? ''), (string) ($row['submissions_count'] ?? 0)));
        }

        fclose($handle);
        return $path;
    }
}
