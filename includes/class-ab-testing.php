<?php
/**
 * SyntekPro Forms - A/B Testing (Phase 2)
 * 
 * A/B testing infrastructure for form variants, traffic split, and analytics.
 * Phase 2 architectural stub.
 * 
 * @package SyntekPro_Forms
 * @subpackage AB_Testing
 */

if (!defined('ABSPATH')) {
    exit;
}

class SyntekPro_Forms_AB_Testing {

    private static function can_manage_forms() {
        return current_user_can('spf_manage_forms') || current_user_can('manage_options');
    }

    private static function can_view_entries() {
        return current_user_can('spf_view_entries') || current_user_can('manage_options');
    }

    private static function normalize_variant_data($variant_config) {
        if (!is_array($variant_config)) {
            $variant_config = array();
        }

        return array(
            'fields' => isset($variant_config['fields']) && is_array($variant_config['fields']) ? $variant_config['fields'] : array(),
            'settings' => isset($variant_config['settings']) && is_array($variant_config['settings']) ? $variant_config['settings'] : array(),
            'notes' => isset($variant_config['notes']) ? sanitize_text_field((string) $variant_config['notes']) : '',
        );
    }

    private static function get_session_id() {
        if (!empty($_POST['spf_session_id'])) {
            return sanitize_text_field(wp_unslash((string) $_POST['spf_session_id']));
        }

        if (!empty($_COOKIE['spf_session_id'])) {
            return sanitize_text_field(wp_unslash((string) $_COOKIE['spf_session_id']));
        }

        return wp_generate_password(20, false, false);
    }

    /**
     * Create A/B test variant
     * 
     * @param int $form_id Base form ID
     * @param string $variant_name Name of variant
     * @param array $variant_config Variant configuration (field changes, theme, etc)
     * @param int $traffic_percentage Traffic allocation percentage (0-100)
     * @return int|WP_Error Variant ID or error
     */
    public static function create_variant($form_id, $variant_name, $variant_config, $traffic_percentage = 50) {
        global $wpdb;

        if (!self::can_manage_forms()) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        $form_id = absint($form_id);
        $traffic_percentage = max(0, min(100, absint($traffic_percentage)));
        $variant_name = sanitize_text_field((string) $variant_name);

        if ($form_id <= 0 || $variant_name === '') {
            return new WP_Error('invalid_data', __('Form ID and variant name are required.', 'syntekpro-forms'));
        }

        $current_total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(traffic_percentage), 0)
             FROM {$wpdb->prefix}spf_ab_variants
             WHERE form_id = %d AND status = 'active'",
            $form_id
        ));

        if (($current_total + $traffic_percentage) > 100) {
            return new WP_Error('traffic_overflow', __('Total active variant traffic cannot exceed 100%.', 'syntekpro-forms'));
        }

        $variant_data = self::normalize_variant_data($variant_config);
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'spf_ab_variants',
            array(
                'form_id' => $form_id,
                'variant_name' => $variant_name,
                'variant_data' => wp_json_encode($variant_data),
                'traffic_percentage' => $traffic_percentage,
                'status' => 'active',
                'created_by' => get_current_user_id(),
            ),
            array('%d', '%s', '%s', '%d', '%s', '%d')
        );

        if ($inserted === false) {
            return new WP_Error('db_error', __('Failed to create variant.', 'syntekpro-forms'));
        }

        if (function_exists('spf_log_audit')) {
            spf_log_audit($form_id, 'ab_variant_created', array(
                'variant_id' => (int) $wpdb->insert_id,
                'variant_name' => $variant_name,
                'traffic_percentage' => $traffic_percentage,
            ));
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Get A/B test results
     * 
     * @param int $form_id Form ID
     * @return array|WP_Error Test results with conversion metrics
     */
    public static function get_test_results($form_id) {
        global $wpdb;

        if (!self::can_view_entries()) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        $form_id = absint($form_id);
        $variants = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spf_ab_variants WHERE form_id = %d ORDER BY id ASC",
            $form_id
        ));

        if (!$variants) {
            return array(
                'variants' => array(),
                'winner' => null,
                'status' => 'ok',
            );
        }

        $result_variants = array();
        $winner = null;

        foreach ($variants as $variant) {
            $views = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}spf_ab_events
                 WHERE form_id = %d AND variant_id = %d AND event_type = 'view'",
                $form_id,
                (int) $variant->id
            ));

            $submits = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}spf_ab_events
                 WHERE form_id = %d AND variant_id = %d AND event_type = 'submit'",
                $form_id,
                (int) $variant->id
            ));

            $conversion_rate = $views > 0 ? round(($submits / $views) * 100, 2) : 0.0;
            $abandonment_count = max(0, $views - $submits);
            $abandonment_rate = $views > 0 ? round(($abandonment_count / $views) * 100, 2) : 0.0;

            $row = array(
                'variant_id' => (int) $variant->id,
                'variant_name' => (string) $variant->variant_name,
                'status' => (string) $variant->status,
                'traffic_percentage' => (int) $variant->traffic_percentage,
                'views_count' => $views,
                'submissions_count' => $submits,
                'conversion_rate' => $conversion_rate,
                'abandonment_count' => $abandonment_count,
                'abandonment_rate' => $abandonment_rate,
            );

            $result_variants[] = $row;

            if ($winner === null || $conversion_rate > $winner['conversion_rate']) {
                $winner = $row;
            }
        }

        return array(
            'variants' => $result_variants,
            'winner' => $winner,
            'status' => 'ok',
        );
    }

    /**
     * Route form view to variant based on traffic split
     * 
     * @param int $form_id Base form ID
     * @return int Variant ID to display (base form or variant)
     */
    public static function get_user_variant($form_id) {
        global $wpdb;

        $form_id = absint($form_id);
        if ($form_id <= 0) {
            return 0;
        }

        $variants = $wpdb->get_results($wpdb->prepare(
            "SELECT id, traffic_percentage FROM {$wpdb->prefix}spf_ab_variants
             WHERE form_id = %d AND status = 'active'
             ORDER BY id ASC",
            $form_id
        ));

        if (!$variants) {
            return $form_id;
        }

        $user_key = (string) get_current_user_id();
        if ($user_key === '0') {
            $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '0.0.0.0';
            $user_key = $ip;
        }

        $bucket = hexdec(substr(md5($form_id . '|' . $user_key), 0, 8)) % 100;
        $running = 0;

        foreach ($variants as $variant) {
            $running += (int) $variant->traffic_percentage;
            if ($bucket < $running) {
                self::record_interaction($form_id, (int) $variant->id, 'view');
                return (int) $variant->id;
            }
        }

        return $form_id;
    }

    /**
     * Pause/stop A/B test
     * 
     * @param int $form_id Form ID
     * @param string $action 'pause' or 'stop' or 'declare_winner'
     * @param int $winner_variant_id Winning variant ID (for declare_winner)
     * @return bool|WP_Error Success or error
     */
    public static function end_test($form_id, $action, $winner_variant_id = null) {
        global $wpdb;

        if (!self::can_manage_forms()) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        $form_id = absint($form_id);
        $action = sanitize_key((string) $action);
        $allowed = array('pause', 'stop', 'declare_winner');

        if (!in_array($action, $allowed, true)) {
            return new WP_Error('invalid_action', __('Invalid test action.', 'syntekpro-forms'));
        }

        if ($action === 'pause') {
            $wpdb->update(
                $wpdb->prefix . 'spf_ab_variants',
                array('status' => 'paused'),
                array('form_id' => $form_id, 'status' => 'active'),
                array('%s'),
                array('%d', '%s')
            );
        }

        if ($action === 'stop') {
            $wpdb->update(
                $wpdb->prefix . 'spf_ab_variants',
                array('status' => 'completed'),
                array('form_id' => $form_id),
                array('%s'),
                array('%d')
            );
        }

        if ($action === 'declare_winner') {
            $winner_variant_id = absint($winner_variant_id);
            if ($winner_variant_id <= 0) {
                return new WP_Error('missing_winner', __('Winner variant is required.', 'syntekpro-forms'));
            }

            $winner = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}spf_ab_variants WHERE id = %d AND form_id = %d",
                $winner_variant_id,
                $form_id
            ));
            if (!$winner) {
                return new WP_Error('not_found', __('Winner variant not found for this form.', 'syntekpro-forms'));
            }

            $wpdb->update(
                $wpdb->prefix . 'spf_ab_variants',
                array('status' => 'completed'),
                array('form_id' => $form_id),
                array('%s'),
                array('%d')
            );
            $wpdb->update(
                $wpdb->prefix . 'spf_ab_variants',
                array('status' => 'winner'),
                array('id' => $winner_variant_id),
                array('%s'),
                array('%d')
            );

            $winner_data = json_decode((string) $winner->variant_data, true);
            if (is_array($winner_data) && (!empty($winner_data['fields']) || !empty($winner_data['settings']))) {
                $form = $wpdb->get_row($wpdb->prepare(
                    "SELECT fields, settings FROM {$wpdb->prefix}spf_forms WHERE id = %d",
                    $form_id
                ));
                if ($form) {
                    $current_fields = json_decode((string) $form->fields, true);
                    $current_settings = json_decode((string) $form->settings, true);
                    $new_fields = !empty($winner_data['fields']) ? $winner_data['fields'] : (is_array($current_fields) ? $current_fields : array());
                    $new_settings = !empty($winner_data['settings']) ? $winner_data['settings'] : (is_array($current_settings) ? $current_settings : array());

                    $wpdb->update(
                        $wpdb->prefix . 'spf_forms',
                        array(
                            'fields' => wp_json_encode($new_fields),
                            'settings' => wp_json_encode($new_settings),
                        ),
                        array('id' => $form_id),
                        array('%s', '%s'),
                        array('%d')
                    );
                }
            }
        }

        if (function_exists('spf_log_audit')) {
            spf_log_audit($form_id, 'ab_test_action', array(
                'action' => $action,
                'winner_variant_id' => absint($winner_variant_id),
            ));
        }

        return true;
    }

    /**
     * Get A/B test list for admin
     * 
     * @param int $form_id Form ID
     * @return array List of active tests with status
     */
    public static function get_tests($form_id) {
        global $wpdb;

        $form_id = absint($form_id);
        $variants = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spf_ab_variants WHERE form_id = %d ORDER BY created_at DESC",
            $form_id
        ));

        $tests = array();
        foreach ((array) $variants as $variant) {
            $views = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}spf_ab_events WHERE form_id = %d AND variant_id = %d AND event_type = 'view'",
                $form_id,
                (int) $variant->id
            ));
            $submits = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}spf_ab_events WHERE form_id = %d AND variant_id = %d AND event_type = 'submit'",
                $form_id,
                (int) $variant->id
            ));

            $tests[] = array(
                'variant_id' => (int) $variant->id,
                'variant_name' => (string) $variant->variant_name,
                'status' => (string) $variant->status,
                'traffic_percentage' => (int) $variant->traffic_percentage,
                'views_count' => $views,
                'submissions_count' => $submits,
                'conversion_rate' => $views > 0 ? round(($submits / $views) * 100, 2) : 0,
                'created_at' => (string) $variant->created_at,
            );
        }

        return array(
            'tests' => $tests,
            'status' => 'ok',
        );
    }

    /**
     * Record variant interaction
     * 
     * Internal method called on form view and submission.
     * 
     * @param int $form_id Form ID
     * @param int $variant_id Variant ID user is interacting with
     * @param string $action 'view' or 'submit'
     * @return void
     */
    public static function record_interaction($form_id, $variant_id, $action) {
        global $wpdb;

        $form_id = absint($form_id);
        $variant_id = absint($variant_id);
        $action = sanitize_key((string) $action);

        if ($form_id <= 0 || $variant_id <= 0 || !in_array($action, array('view', 'submit'), true)) {
            return;
        }

        $session_id = self::get_session_id();
        $wpdb->insert(
            $wpdb->prefix . 'spf_ab_events',
            array(
                'form_id' => $form_id,
                'variant_id' => $variant_id,
                'session_id' => $session_id,
                'event_type' => $action,
                'user_id' => get_current_user_id(),
            ),
            array('%d', '%d', '%s', '%s', '%d')
        );
    }
}
