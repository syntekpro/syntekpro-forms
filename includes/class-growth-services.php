<?php
/**
 * Growth features services (payments, connectors, drafts, analytics)
 */

if (!defined('ABSPATH')) {
    exit;
}

class SyntekPro_Forms_Growth_Services {

    public function build_payment_summary($sanitized_data, $fields, $form_settings, $plugin_settings) {
        $payment_enabled = !empty($form_settings['payment_enabled']) || !empty($plugin_settings['stripe_secret_key']);
        $summary = array(
            'enabled' => $payment_enabled,
            'currency' => !empty($plugin_settings['payment_currency']) ? sanitize_text_field($plugin_settings['payment_currency']) : 'USD',
            'subtotal' => 0,
            'discount' => 0,
            'total' => 0,
            'coupon_code' => '',
            'status' => 'not_required',
            'stripe_checkout_url' => '',
            'line_items' => array(),
        );

        if (empty($summary['enabled'])) {
            return $summary;
        }

        $amount_fields = array();
        foreach ((array) $fields as $field) {
            if (empty($field['name'])) {
                continue;
            }
            $type = isset($field['type']) ? (string) $field['type'] : '';
            if ($type === 'number' || $type === 'payment-amount') {
                $amount_fields[] = (string) $field['name'];
            }
        }

        $subtotal = 0.0;
        foreach ($amount_fields as $field_name) {
            if (!isset($sanitized_data[$field_name])) {
                continue;
            }
            $value = is_array($sanitized_data[$field_name]) ? 0 : (float) $sanitized_data[$field_name];
            if ($value > 0) {
                $subtotal += $value;
                $summary['line_items'][] = array(
                    'name' => $field_name,
                    'amount' => round($value, 2),
                );
            }
        }

        if ($subtotal <= 0 && !empty($form_settings['payment_fixed_amount'])) {
            $subtotal = (float) $form_settings['payment_fixed_amount'];
            $summary['line_items'][] = array(
                'name' => 'fixed_amount',
                'amount' => round($subtotal, 2),
            );
        }

        $summary['subtotal'] = round(max(0, $subtotal), 2);

        $coupon_field = !empty($form_settings['payment_coupon_field']) ? sanitize_key($form_settings['payment_coupon_field']) : 'coupon';
        $submitted_coupon = isset($sanitized_data[$coupon_field]) ? sanitize_text_field((string) $sanitized_data[$coupon_field]) : '';
        $coupon_code = !empty($form_settings['payment_coupon_code']) ? sanitize_text_field((string) $form_settings['payment_coupon_code']) : '';
        $coupon_percent = !empty($form_settings['payment_coupon_percent']) ? (float) $form_settings['payment_coupon_percent'] : 0;

        $discount = 0.0;
        if ($coupon_code !== '' && $submitted_coupon !== '' && strcasecmp($coupon_code, $submitted_coupon) === 0 && $coupon_percent > 0) {
            $discount = ($summary['subtotal'] * min(100, $coupon_percent)) / 100;
            $summary['coupon_code'] = $submitted_coupon;
        }

        $summary['discount'] = round(max(0, $discount), 2);
        $summary['total'] = round(max(0, $summary['subtotal'] - $summary['discount']), 2);
        $summary['status'] = $summary['total'] > 0 ? 'pending_payment' : 'paid';

        $stripe_checkout_url = $this->create_stripe_checkout_url($summary, $form_settings, $plugin_settings, $sanitized_data);
        if (!empty($stripe_checkout_url)) {
            $summary['stripe_checkout_url'] = esc_url_raw($stripe_checkout_url);
        }

        return $summary;
    }

    private function create_stripe_checkout_url($summary, $form_settings, $plugin_settings, $sanitized_data) {
        if (empty($summary['enabled']) || $summary['total'] <= 0) {
            return '';
        }

        if (empty($form_settings['payment_stripe_enabled'])) {
            return '';
        }

        $secret_key = !empty($plugin_settings['stripe_secret_key']) ? trim((string) $plugin_settings['stripe_secret_key']) : '';
        if ($secret_key === '') {
            return '';
        }

        $currency = strtolower((string) $summary['currency']);
        $amount_cents = (int) round($summary['total'] * 100);
        if ($amount_cents < 50) {
            return '';
        }

        $success_url = !empty($form_settings['payment_success_url']) ? esc_url_raw((string) $form_settings['payment_success_url']) : home_url('/');
        $cancel_url = !empty($form_settings['payment_cancel_url']) ? esc_url_raw((string) $form_settings['payment_cancel_url']) : home_url('/');
        $product_name = !empty($form_settings['payment_product_name']) ? sanitize_text_field((string) $form_settings['payment_product_name']) : __('Form Payment', 'syntekpro-forms');

        $body = array(
            'mode' => 'payment',
            'success_url' => $success_url,
            'cancel_url' => $cancel_url,
            'line_items[0][price_data][currency]' => $currency,
            'line_items[0][price_data][product_data][name]' => $product_name,
            'line_items[0][price_data][unit_amount]' => $amount_cents,
            'line_items[0][quantity]' => 1,
        );

        foreach ((array) $sanitized_data as $key => $value) {
            if (is_array($value)) {
                continue;
            }
            $body['metadata[' . sanitize_key((string) $key) . ']'] = sanitize_text_field((string) $value);
        }

        $response = wp_remote_post('https://api.stripe.com/v1/checkout/sessions', array(
            'timeout' => 15,
            'headers' => array(
                'Authorization' => 'Bearer ' . $secret_key,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body' => $body,
        ));

        if (is_wp_error($response)) {
            return '';
        }

        if ((int) wp_remote_retrieve_response_code($response) !== 200) {
            return '';
        }

        $payload = json_decode((string) wp_remote_retrieve_body($response), true);
        if (!is_array($payload) || empty($payload['url'])) {
            return '';
        }

        return (string) $payload['url'];
    }

    public function dispatch_connectors($form, $sanitized_data, $entry_id, $form_settings, $plugin_settings) {
        $payload = array(
            'entry_id' => (int) $entry_id,
            'form_id' => (int) $form->id,
            'form_title' => (string) $form->title,
            'submitted_at' => current_time('mysql'),
            'data' => $sanitized_data,
        );

        $urls = array();
        if (!empty($plugin_settings['automation_zapier_url'])) {
            $urls[] = esc_url_raw((string) $plugin_settings['automation_zapier_url']);
        }
        if (!empty($plugin_settings['automation_make_url'])) {
            $urls[] = esc_url_raw((string) $plugin_settings['automation_make_url']);
        }
        if (!empty($form_settings['automation_webhook_urls'])) {
            $urls = array_merge($urls, preg_split('/[\r\n,]+/', (string) $form_settings['automation_webhook_urls']));
        }

        foreach ((array) $urls as $url) {
            $url = trim((string) $url);
            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }
            wp_remote_post($url, array(
                'timeout' => 12,
                'headers' => array('Content-Type' => 'application/json'),
                'body' => wp_json_encode($payload),
            ));
        }

        $this->send_mailchimp($payload, $plugin_settings);
        $this->send_hubspot($payload, $plugin_settings);
    }

    private function send_mailchimp($payload, $settings) {
        $api_key = !empty($settings['mailchimp_api_key']) ? trim((string) $settings['mailchimp_api_key']) : '';
        $audience_id = !empty($settings['mailchimp_audience_id']) ? trim((string) $settings['mailchimp_audience_id']) : '';
        if ($api_key === '' || $audience_id === '') {
            return;
        }

        $parts = explode('-', $api_key);
        $dc = end($parts);
        if (empty($dc)) {
            return;
        }

        $email = '';
        $merge_fields = array();
        foreach ((array) $payload['data'] as $key => $value) {
            if (is_array($value)) {
                continue;
            }
            $val = sanitize_text_field((string) $value);
            if ($email === '' && strpos(strtolower((string) $key), 'email') !== false && is_email($val)) {
                $email = $val;
            }
            $merge_fields[strtoupper(substr(sanitize_key((string) $key), 0, 10))] = $val;
        }

        if ($email === '') {
            return;
        }

        $endpoint = sprintf('https://%s.api.mailchimp.com/3.0/lists/%s/members', $dc, rawurlencode($audience_id));

        wp_remote_post($endpoint, array(
            'timeout' => 12,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode('any:' . $api_key),
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode(array(
                'email_address' => $email,
                'status_if_new' => 'subscribed',
                'status' => 'subscribed',
                'merge_fields' => $merge_fields,
            )),
        ));
    }

    private function send_hubspot($payload, $settings) {
        $token = !empty($settings['hubspot_private_token']) ? trim((string) $settings['hubspot_private_token']) : '';
        if ($token === '') {
            return;
        }

        $email = '';
        $properties = array();
        foreach ((array) $payload['data'] as $key => $value) {
            if (is_array($value)) {
                continue;
            }
            $val = sanitize_text_field((string) $value);
            if ($email === '' && strpos(strtolower((string) $key), 'email') !== false && is_email($val)) {
                $email = $val;
            }
            $properties[] = array(
                'property' => sanitize_key((string) $key),
                'value' => $val,
            );
        }

        if ($email === '') {
            return;
        }

        $properties[] = array('property' => 'email', 'value' => $email);

        wp_remote_post('https://api.hubapi.com/contacts/v1/contact/createOrUpdate/email/' . rawurlencode($email), array(
            'timeout' => 12,
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode(array('properties' => $properties)),
        ));
    }

    public function save_draft($form_id, $draft_data, $resume_token = '', $email = '') {
        global $wpdb;

        $table = $wpdb->prefix . 'spf_drafts';
        $form_id = absint($form_id);
        if ($form_id <= 0 || !is_array($draft_data)) {
            return new WP_Error('spf_invalid_draft', __('Invalid draft payload.', 'syntekpro-forms'));
        }

        if (empty($resume_token)) {
            $resume_token = wp_generate_password(32, false, false);
        }

        $data_json = wp_json_encode($draft_data);
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE resume_token = %s", $resume_token));

        if ($existing) {
            $wpdb->update(
                $table,
                array(
                    'draft_data' => $data_json,
                    'email' => sanitize_email($email),
                    'user_id' => get_current_user_id(),
                ),
                array('id' => absint($existing)),
                array('%s', '%s', '%d'),
                array('%d')
            );
        } else {
            $wpdb->insert(
                $table,
                array(
                    'form_id' => $form_id,
                    'resume_token' => $resume_token,
                    'draft_data' => $data_json,
                    'email' => sanitize_email($email),
                    'user_id' => get_current_user_id(),
                ),
                array('%d', '%s', '%s', '%s', '%d')
            );
        }

        if (!empty($wpdb->last_error)) {
            return new WP_Error('spf_draft_db_error', $wpdb->last_error);
        }

        $base_url = wp_get_referer();
        if (empty($base_url)) {
            $base_url = home_url('/');
        }

        return array(
            'resume_token' => $resume_token,
            'resume_url' => add_query_arg(array('spf_resume' => $resume_token), $base_url),
        );
    }

    public function get_draft($resume_token, $form_id = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'spf_drafts';

        if (empty($resume_token)) {
            return null;
        }

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE resume_token = %s", sanitize_text_field((string) $resume_token)));
        if (!$row) {
            return null;
        }

        if ($form_id > 0 && (int) $row->form_id !== (int) $form_id) {
            return null;
        }

        return array(
            'form_id' => (int) $row->form_id,
            'resume_token' => (string) $row->resume_token,
            'draft_data' => json_decode((string) $row->draft_data, true),
            'updated_at' => (string) $row->updated_at,
        );
    }

    public function track_event($form_id, $event_type, $field_name = '', $session_id = '') {
        global $wpdb;

        $form_id = absint($form_id);
        if ($form_id <= 0 || empty($event_type)) {
            return;
        }

        $allowed = array('view', 'start', 'complete', 'abandon', 'field_dropoff');
        if (!in_array($event_type, $allowed, true)) {
            return;
        }

        $wpdb->insert(
            $wpdb->prefix . 'spf_analytics',
            array(
                'form_id' => $form_id,
                'event_type' => sanitize_key($event_type),
                'field_name' => $field_name !== '' ? sanitize_key($field_name) : null,
                'session_id' => $session_id !== '' ? sanitize_text_field($session_id) : null,
            ),
            array('%d', '%s', '%s', '%s')
        );
    }

    public function get_analytics_summary($days = 30) {
        global $wpdb;

        $days = max(1, absint($days));
        $table = $wpdb->prefix . 'spf_analytics';
        $forms_table = $wpdb->prefix . 'spf_forms';

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT a.form_id, f.title, a.event_type, COUNT(*) AS total
             FROM {$table} a
             LEFT JOIN {$forms_table} f ON a.form_id = f.id
             WHERE a.created_at >= (NOW() - INTERVAL %d DAY)
             GROUP BY a.form_id, a.event_type
             ORDER BY total DESC",
            $days
        ));

        $summary = array();
        foreach ((array) $rows as $row) {
            $fid = (int) $row->form_id;
            if (!isset($summary[$fid])) {
                $summary[$fid] = array(
                    'form_id' => $fid,
                    'form_title' => (string) $row->title,
                    'view' => 0,
                    'start' => 0,
                    'complete' => 0,
                    'abandon' => 0,
                    'field_dropoff' => 0,
                );
            }
            $summary[$fid][(string) $row->event_type] = (int) $row->total;
        }

        return array_values($summary);
    }

    public function get_field_dropoff($days = 30) {
        global $wpdb;
        $days = max(1, absint($days));
        $table = $wpdb->prefix . 'spf_analytics';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT form_id, field_name, COUNT(*) as total
             FROM {$table}
             WHERE event_type = %s
               AND created_at >= (NOW() - INTERVAL %d DAY)
             GROUP BY form_id, field_name
             ORDER BY total DESC
             LIMIT 100",
            'field_dropoff',
            $days
        ));
    }
}
