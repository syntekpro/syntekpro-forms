<?php
/**
 * SyntekPro Forms - Geolocation & Fraud Scoring (Phase 2)
 * 
 * IP geolocation and fraud detection scoring with MaxMind integration.
 * Phase 2 architectural stub.
 * 
 * @package SyntekPro_Forms
 * @subpackage Geolocation_Fraud
 */

if (!defined('ABSPATH')) {
    exit;
}

class SyntekPro_Forms_Geolocation_Fraud {

    private static function can_manage_forms() {
        return current_user_can('spf_manage_forms') || current_user_can('manage_options');
    }

    private static function can_view_entries() {
        return current_user_can('spf_view_entries') || current_user_can('manage_options');
    }

    private static function get_default_settings() {
        return array(
            'sensitivity' => 'medium',
            'fraud_threshold' => 70,
            'action_on_fraud' => 'flag',
            'blocked_ips' => array(),
            'blocked_email_domains' => array(),
            'allowed_ip_whitelist' => array(),
        );
    }

    private static function normalize_list($value) {
        if (is_string($value)) {
            $value = array_map('trim', explode(',', $value));
        }
        if (!is_array($value)) {
            return array();
        }
        $value = array_map('sanitize_text_field', $value);
        $value = array_filter($value, function($v) { return $v !== ''; });
        return array_values(array_unique($value));
    }

    private static function normalize_settings($settings) {
        $defaults = self::get_default_settings();
        $settings = is_array($settings) ? $settings : array();

        $normalized = wp_parse_args($settings, $defaults);
        $normalized['sensitivity'] = in_array($normalized['sensitivity'], array('low', 'medium', 'high'), true) ? $normalized['sensitivity'] : 'medium';
        $normalized['fraud_threshold'] = max(0, min(100, absint($normalized['fraud_threshold'])));
        $normalized['action_on_fraud'] = in_array($normalized['action_on_fraud'], array('block', 'flag', 'require_verification'), true) ? $normalized['action_on_fraud'] : 'flag';
        $normalized['blocked_ips'] = self::normalize_list($normalized['blocked_ips']);
        $normalized['blocked_email_domains'] = self::normalize_list($normalized['blocked_email_domains']);
        $normalized['allowed_ip_whitelist'] = self::normalize_list($normalized['allowed_ip_whitelist']);

        return $normalized;
    }

    private static function assess_submission($form_id, $entry_data, $ip_address) {
        global $wpdb;

        $form_id = absint($form_id);
        $entry_data = is_array($entry_data) ? $entry_data : array();
        $ip_address = sanitize_text_field((string) $ip_address);

        $settings = self::get_fraud_settings($form_id, false);
        if (is_wp_error($settings)) {
            $settings = self::get_default_settings();
        }

        $score = 0;
        $reasons = array();

        if ($ip_address !== '' && in_array($ip_address, (array) $settings['allowed_ip_whitelist'], true)) {
            return array(
                'score' => 0,
                'reasons' => array(__('IP whitelisted', 'syntekpro-forms')),
                'geo' => self::get_geolocation($ip_address, true),
                'settings' => $settings,
            );
        }

        if ($ip_address !== '' && in_array($ip_address, (array) $settings['blocked_ips'], true)) {
            $score += 75;
            $reasons[] = __('IP is in blocked list', 'syntekpro-forms');
        }

        if ($ip_address !== '') {
            $velocity = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}spf_entries
                 WHERE form_id = %d AND ip_address = %s AND created_at >= %s",
                $form_id,
                $ip_address,
                gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS)
            ));
            if ($velocity >= 5) {
                $extra = min(30, ($velocity - 4) * 5);
                $score += $extra;
                $reasons[] = sprintf(__('High submission velocity from IP (%d in 24h)', 'syntekpro-forms'), $velocity);
            }
        }

        $suspicious_keywords = array('viagra', 'casino', 'crypto', 'loan', 'porn', 'btc', 'telegram', 'http://', 'https://');
        foreach ($entry_data as $key => $value) {
            $value_str = is_array($value) ? strtolower(wp_json_encode($value)) : strtolower((string) $value);

            if (strpos(strtolower((string) $key), 'email') !== false) {
                $parts = explode('@', (string) $value);
                if (count($parts) === 2) {
                    $domain = strtolower(trim($parts[1]));
                    if (in_array($domain, array_map('strtolower', (array) $settings['blocked_email_domains']), true)) {
                        $score += 25;
                        $reasons[] = sprintf(__('Blocked/disposable email domain detected: %s', 'syntekpro-forms'), $domain);
                    }
                }
            }

            foreach ($suspicious_keywords as $keyword) {
                if ($value_str !== '' && strpos($value_str, $keyword) !== false) {
                    $score += 5;
                    $reasons[] = sprintf(__('Suspicious keyword in field %s', 'syntekpro-forms'), sanitize_text_field((string) $key));
                    break;
                }
            }
        }

        $geo = self::get_geolocation($ip_address, true);
        if (is_array($geo) && !empty($geo['is_vpn'])) {
            $score += 20;
            $reasons[] = __('Submission appears to come from VPN/Proxy network', 'syntekpro-forms');
        }

        $sensitivity = $settings['sensitivity'];
        if ($sensitivity === 'low') {
            $score = (int) round($score * 0.8);
        } elseif ($sensitivity === 'high') {
            $score = (int) round($score * 1.2);
        }

        $score = max(0, min(100, $score));

        return array(
            'score' => $score,
            'reasons' => array_values(array_unique($reasons)),
            'geo' => $geo,
            'settings' => $settings,
        );
    }

    /**
     * Get geolocation for IP address
     * 
     * @param string $ip_address IP address to geolocate
     * @param bool $cache Whether to use cached result
     * @return array|WP_Error Geolocation data (country, state, city, lat, lng, timezone)
     */
    public static function get_geolocation($ip_address, $cache = true) {
        $ip_address = sanitize_text_field((string) $ip_address);
        if ($ip_address === '' || !filter_var($ip_address, FILTER_VALIDATE_IP)) {
            return array(
                'country_code' => 'UN',
                'country_name' => 'Unknown',
                'state_code' => '',
                'state_name' => '',
                'city' => '',
                'latitude' => null,
                'longitude' => null,
                'timezone' => '',
                'postal_code' => '',
                'is_vpn' => false,
                'source' => 'local',
            );
        }

        $cache_key = 'spf_geo_' . md5($ip_address);
        if ($cache) {
            $cached = get_transient($cache_key);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $result = apply_filters('spf_geolocation_lookup', null, $ip_address);
        if (!is_array($result)) {
            $response = wp_remote_get('https://ipapi.co/' . rawurlencode($ip_address) . '/json/', array('timeout' => 3));
            if (!is_wp_error($response) && (int) wp_remote_retrieve_response_code($response) === 200) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (is_array($body) && empty($body['error'])) {
                    $result = array(
                        'country_code' => sanitize_text_field((string) ($body['country_code'] ?? 'UN')),
                        'country_name' => sanitize_text_field((string) ($body['country_name'] ?? 'Unknown')),
                        'state_code' => sanitize_text_field((string) ($body['region_code'] ?? '')),
                        'state_name' => sanitize_text_field((string) ($body['region'] ?? '')),
                        'city' => sanitize_text_field((string) ($body['city'] ?? '')),
                        'latitude' => isset($body['latitude']) ? (float) $body['latitude'] : null,
                        'longitude' => isset($body['longitude']) ? (float) $body['longitude'] : null,
                        'timezone' => sanitize_text_field((string) ($body['timezone'] ?? '')),
                        'postal_code' => sanitize_text_field((string) ($body['postal'] ?? '')),
                        'is_vpn' => false,
                        'source' => 'ipapi',
                    );
                }
            }
        }

        if (!is_array($result)) {
            $result = array(
                'country_code' => 'UN',
                'country_name' => 'Unknown',
                'state_code' => '',
                'state_name' => '',
                'city' => '',
                'latitude' => null,
                'longitude' => null,
                'timezone' => '',
                'postal_code' => '',
                'is_vpn' => false,
                'source' => 'fallback',
            );
        }

        if ($cache) {
            set_transient($cache_key, $result, DAY_IN_SECONDS * 7);
        }

        return $result;
    }

    /**
     * Calculate fraud score for form submission
     * 
     * @param int $form_id Form ID
     * @param array $entry_data Entry data to score
     * @param string $ip_address Submission IP address
     * @return int Fraud score (0-100, higher = more suspicious)
     */
    public static function calculate_fraud_score($form_id, $entry_data, $ip_address) {
        $assessment = self::assess_submission($form_id, $entry_data, $ip_address);
        return (int) ($assessment['score'] ?? 0);
    }

    /**
     * Get fraud detection settings
     * 
     * @param int $form_id Form ID
     * @return array Fraud detection config (sensitivity, thresholds, blocked_list)
     */
    public static function get_fraud_settings($form_id, $require_capability = true) {
        global $wpdb;

        if ($require_capability && !self::can_manage_forms()) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        $form_id = absint($form_id);
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT settings_data FROM {$wpdb->prefix}spf_fraud_settings WHERE form_id = %d",
            $form_id
        ));

        if (!$row) {
            return self::get_default_settings();
        }

        $decoded = json_decode((string) $row->settings_data, true);
        return self::normalize_settings($decoded);
    }

    /**
     * Update fraud detection settings
     * 
     * @param int $form_id Form ID
     * @param array $settings New settings
     * @return bool|WP_Error Success or error
     */
    public static function update_fraud_settings($form_id, $settings) {
        global $wpdb;

        if (!self::can_manage_forms()) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        $form_id = absint($form_id);
        $normalized = self::normalize_settings($settings);

        $saved = $wpdb->replace(
            $wpdb->prefix . 'spf_fraud_settings',
            array(
                'form_id' => $form_id,
                'settings_data' => wp_json_encode($normalized),
                'updated_by' => get_current_user_id(),
            ),
            array('%d', '%s', '%d')
        );

        if ($saved === false) {
            return new WP_Error('db_error', __('Could not save fraud settings.', 'syntekpro-forms'));
        }

        if (function_exists('spf_log_audit')) {
            spf_log_audit($form_id, 'fraud_settings_updated', array('settings' => $normalized));
        }

        return true;
    }

    /**
     * Block submission based on fraud score
     * 
     * @param int $fraud_score Calculated fraud score
     * @param int $form_id Form ID
     * @return bool True if should block submission
     */
    public static function should_block_submission($fraud_score, $form_id) {
        $settings = self::get_fraud_settings($form_id);
        
        if (is_wp_error($settings)) {
            return false; // Don't block if settings can't load
        }

        $threshold = $settings['fraud_threshold'] ?? 70;
        return $fraud_score >= $threshold;
    }

    /**
     * Get fraud report for admin
     * 
     * @param int $form_id Form ID
     * @param array $filters Filters (date_range, fraud_status, etc)
     * @return array|WP_Error Fraud report with flagged submissions
     */
    public static function get_fraud_report($form_id, $filters = array()) {
        global $wpdb;

        if (!self::can_view_entries()) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        $form_id = absint($form_id);
        $settings = self::get_fraud_settings($form_id, false);
        if (is_wp_error($settings)) {
            $settings = self::get_default_settings();
        }

        $args = array($form_id);
        $where = " WHERE form_id = %d";
        if (!empty($filters['date_from'])) {
            $where .= ' AND created_at >= %s';
            $args[] = sanitize_text_field((string) $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $where .= ' AND created_at <= %s';
            $args[] = sanitize_text_field((string) $filters['date_to']);
        }
        if (!empty($filters['status'])) {
            $where .= ' AND action_status = %s';
            $args[] = sanitize_text_field((string) $filters['status']);
        }

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spf_fraud_events {$where} ORDER BY fraud_score DESC, created_at DESC",
            $args
        ));

        $flagged = array();
        $total_score = 0;
        foreach ((array) $rows as $row) {
            $reasons = json_decode((string) $row->fraud_reasons, true);
            $geo = json_decode((string) $row->geo_data, true);

            if ((int) $row->fraud_score < (int) $settings['fraud_threshold']) {
                continue;
            }

            $flagged[] = array(
                'id' => (int) $row->id,
                'entry_id' => (int) $row->entry_id,
                'fraud_score' => (int) $row->fraud_score,
                'fraud_reasons' => is_array($reasons) ? $reasons : array(),
                'ip_address' => (string) $row->ip_address,
                'geolocation' => is_array($geo) ? $geo : array(),
                'action_taken' => (string) $row->action_status,
                'submitted_at' => (string) $row->created_at,
            );
            $total_score += (int) $row->fraud_score;
        }

        $count = count($flagged);
        return array(
            'flagged_entries' => $flagged,
            'total_flagged' => $count,
            'average_fraud_score' => $count > 0 ? round($total_score / $count, 2) : 0,
            'status' => 'ok',
        );
    }

    /**
     * Manually review/whitelist flagged entry
     * 
     * @param int $entry_id Entry ID
     * @param string $action 'approve', 'flag', or 'block'
     * @return bool|WP_Error Success or error
     */
    public static function review_flagged_entry($entry_id, $action) {
        global $wpdb;

        if (!self::can_manage_forms()) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        $entry_id = absint($entry_id);
        $action = sanitize_key((string) $action);
        if (!in_array($action, array('approve', 'flag', 'block'), true)) {
            return new WP_Error('invalid_action', __('Invalid review action.', 'syntekpro-forms'));
        }

        $updated = $wpdb->update(
            $wpdb->prefix . 'spf_fraud_events',
            array(
                'action_status' => $action,
                'reviewed_by' => get_current_user_id(),
                'reviewed_at' => current_time('mysql'),
            ),
            array('entry_id' => $entry_id),
            array('%s', '%d', '%s'),
            array('%d')
        );

        if ($updated === false) {
            return new WP_Error('db_error', __('Failed to update fraud review status.', 'syntekpro-forms'));
        }

        return true;
    }

    public static function assess_and_log_submission($form_id, $entry_id, $entry_data, $ip_address) {
        global $wpdb;

        $assessment = self::assess_submission($form_id, $entry_data, $ip_address);
        $score = (int) ($assessment['score'] ?? 0);
        $settings = isset($assessment['settings']) && is_array($assessment['settings']) ? $assessment['settings'] : self::get_default_settings();

        $action_status = 'pass';
        if ($score >= (int) $settings['fraud_threshold']) {
            $action_status = $settings['action_on_fraud'] === 'block' ? 'blocked' : 'flag';
        }

        $wpdb->insert(
            $wpdb->prefix . 'spf_fraud_events',
            array(
                'form_id' => absint($form_id),
                'entry_id' => absint($entry_id),
                'ip_address' => sanitize_text_field((string) $ip_address),
                'fraud_score' => $score,
                'fraud_reasons' => wp_json_encode((array) ($assessment['reasons'] ?? array())),
                'geo_data' => wp_json_encode((array) ($assessment['geo'] ?? array())),
                'action_status' => $action_status,
            ),
            array('%d', '%d', '%s', '%d', '%s', '%s', '%s')
        );

        return array(
            'score' => $score,
            'blocked' => self::should_block_submission($score, $form_id),
            'reasons' => (array) ($assessment['reasons'] ?? array()),
            'geo' => (array) ($assessment['geo'] ?? array()),
        );
    }
}
