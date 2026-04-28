<?php
/**
 * SyntekPro Forms - Native Integrations (Phase 2)
 * 
 * Framework for native integrations with SendGrid, Twilio, Google Sheets,
 * Airtable, and Notion. Phase 2 architectural stub.
 * 
 * @package SyntekPro_Forms
 * @subpackage Integrations
 */

if (!defined('ABSPATH')) {
    exit;
}

class SyntekPro_Forms_Integrations {

    const SUPPORTED_INTEGRATIONS = array(
        'sendgrid' => 'SendGrid Email',
        'twilio' => 'Twilio SMS',
        'google_sheets' => 'Google Sheets',
        'airtable' => 'Airtable',
        'notion' => 'Notion',
    );

    private static function can_manage_forms() {
        return current_user_can('spf_manage_forms') || current_user_can('manage_options');
    }

    private static function encrypt_value($plain) {
        $plain = (string) $plain;
        $salt = defined('AUTH_KEY') ? AUTH_KEY : wp_salt();

        if (function_exists('openssl_encrypt')) {
            $key = hash('sha256', $salt, true);
            $iv = substr(hash('sha256', 'spf_integrations_iv_' . $salt), 0, 16);
            $cipher = openssl_encrypt($plain, 'AES-256-CBC', $key, 0, $iv);
            if (is_string($cipher) && $cipher !== '') {
                return 'enc:' . $cipher;
            }
        }

        return 'b64:' . base64_encode($plain);
    }

    private static function decrypt_value($encoded) {
        $encoded = (string) $encoded;
        if ($encoded === '') {
            return '';
        }

        if (strpos($encoded, 'enc:') === 0 && function_exists('openssl_decrypt')) {
            $salt = defined('AUTH_KEY') ? AUTH_KEY : wp_salt();
            $key = hash('sha256', $salt, true);
            $iv = substr(hash('sha256', 'spf_integrations_iv_' . $salt), 0, 16);
            $cipher = substr($encoded, 4);
            $plain = openssl_decrypt($cipher, 'AES-256-CBC', $key, 0, $iv);
            return is_string($plain) ? $plain : '';
        }

        if (strpos($encoded, 'b64:') === 0) {
            $plain = base64_decode(substr($encoded, 4), true);
            return is_string($plain) ? $plain : '';
        }

        return $encoded;
    }

    private static function normalize_config($config) {
        $config = is_array($config) ? $config : array();
        $normalized = array(
            'enabled' => !empty($config['enabled']),
            'api_key' => isset($config['api_key']) ? (string) $config['api_key'] : '',
            'api_secret' => isset($config['api_secret']) ? (string) $config['api_secret'] : '',
            'access_token' => isset($config['access_token']) ? (string) $config['access_token'] : '',
            'account_sid' => isset($config['account_sid']) ? (string) $config['account_sid'] : '',
            'from' => isset($config['from']) ? sanitize_text_field((string) $config['from']) : '',
            'to' => isset($config['to']) ? sanitize_text_field((string) $config['to']) : '',
            'subject' => isset($config['subject']) ? sanitize_text_field((string) $config['subject']) : '',
            'message_template' => isset($config['message_template']) ? wp_kses_post((string) $config['message_template']) : '',
            'field_mappings' => isset($config['field_mappings']) && is_array($config['field_mappings']) ? $config['field_mappings'] : array(),
            'webhook_url' => isset($config['webhook_url']) ? esc_url_raw((string) $config['webhook_url']) : '',
            'extra' => isset($config['extra']) && is_array($config['extra']) ? $config['extra'] : array(),
        );

        return $normalized;
    }

    private static function parse_stored_config($json, $decrypt = true) {
        $config = json_decode((string) $json, true);
        $config = is_array($config) ? $config : array();

        if ($decrypt) {
            foreach (array('api_key', 'api_secret', 'access_token') as $secret_key) {
                if (!empty($config[$secret_key])) {
                    $config[$secret_key] = self::decrypt_value($config[$secret_key]);
                }
            }
        }

        return $config;
    }

    private static function response_ok($response, $expected_codes = array(200, 201, 202, 204)) {
        if (is_wp_error($response)) {
            return false;
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        return in_array($code, $expected_codes, true);
    }

    private static function log_execution($form_id, $entry_id, $integration_name, $status, $response_code = null, $response_body = '', $error_message = '') {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'spf_integration_logs',
            array(
                'form_id' => absint($form_id),
                'entry_id' => absint($entry_id),
                'integration_name' => sanitize_key((string) $integration_name),
                'status' => sanitize_key((string) $status),
                'response_code' => is_null($response_code) ? null : absint($response_code),
                'response_body' => is_scalar($response_body) ? (string) $response_body : wp_json_encode($response_body),
                'error_message' => sanitize_text_field((string) $error_message),
            ),
            array('%d', '%d', '%s', '%s', '%d', '%s', '%s')
        );
    }

    /**
     * Get integration config for form
     * 
     * @param int $form_id Form ID
     * @param string $integration_name Integration key (sendgrid, twilio, etc)
     * @return array|WP_Error Integration configuration
     */
    public static function get_integration_config($form_id, $integration_name, $require_capability = true) {
        global $wpdb;

        if ($require_capability && !self::can_manage_forms()) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        $integration_name = sanitize_key((string) $integration_name);
        if (!isset(self::SUPPORTED_INTEGRATIONS[$integration_name])) {
            return new WP_Error('invalid_integration', __('Unsupported integration.', 'syntekpro-forms'));
        }

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spf_integrations WHERE form_id = %d AND integration_name = %s",
            absint($form_id),
            $integration_name
        ));

        if (!$row) {
            return array(
                'enabled' => false,
                'integration_name' => $integration_name,
                'field_mappings' => array(),
                'extra' => array(),
            );
        }

        $config = self::parse_stored_config($row->config_data, true);
        $config['enabled'] = !empty($row->enabled);
        $config['integration_name'] = $integration_name;
        $config['last_tested'] = $row->last_tested;
        $config['last_error'] = $row->last_error;

        return $config;
    }

    /**
     * Save integration config
     * 
     * @param int $form_id Form ID
     * @param string $integration_name Integration key
     * @param array $config Integration config (api_key, mappings, actions)
     * @return bool|WP_Error Success or error
     */
    public static function save_integration_config($form_id, $integration_name, $config) {
        global $wpdb;

        if (!self::can_manage_forms()) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        $form_id = absint($form_id);
        $integration_name = sanitize_key((string) $integration_name);

        if (!isset(self::SUPPORTED_INTEGRATIONS[$integration_name])) {
            return new WP_Error('invalid_integration', __('Unsupported integration.', 'syntekpro-forms'));
        }

        $normalized = self::normalize_config($config);
        foreach (array('api_key', 'api_secret', 'access_token') as $secret_key) {
            if (!empty($normalized[$secret_key])) {
                $normalized[$secret_key] = self::encrypt_value($normalized[$secret_key]);
            }
        }

        $saved = $wpdb->replace(
            $wpdb->prefix . 'spf_integrations',
            array(
                'form_id' => $form_id,
                'integration_name' => $integration_name,
                'config_data' => wp_json_encode($normalized),
                'enabled' => $normalized['enabled'] ? 1 : 0,
                'updated_by' => get_current_user_id(),
                'last_tested' => null,
                'last_error' => '',
            ),
            array('%d', '%s', '%s', '%d', '%d', '%s', '%s')
        );

        if ($saved === false) {
            return new WP_Error('db_error', __('Failed to save integration config.', 'syntekpro-forms'));
        }

        if (function_exists('spf_log_audit')) {
            spf_log_audit($form_id, 'integration_config_saved', array('integration' => $integration_name, 'enabled' => (bool) $normalized['enabled']));
        }

        return true;
    }

    /**
     * Test integration connection
     * 
     * @param string $integration_name Integration key
     * @param array $credentials API credentials
     * @return bool|WP_Error True if connection successful, error otherwise
     */
    public static function test_integration_connection($integration_name, $credentials) {
        $integration_name = sanitize_key((string) $integration_name);
        $credentials = is_array($credentials) ? $credentials : array();

        switch ($integration_name) {
            case 'sendgrid':
                $api_key = (string) ($credentials['api_key'] ?? '');
                if ($api_key === '') {
                    return new WP_Error('missing_credentials', __('SendGrid API key is required.', 'syntekpro-forms'));
                }
                $res = wp_remote_get('https://api.sendgrid.com/v3/user/account', array(
                    'timeout' => 10,
                    'headers' => array('Authorization' => 'Bearer ' . $api_key),
                ));
                return self::response_ok($res) ? true : new WP_Error('connection_failed', __('SendGrid connection failed.', 'syntekpro-forms'));

            case 'twilio':
                $sid = (string) ($credentials['account_sid'] ?? '');
                $token = (string) ($credentials['api_secret'] ?? $credentials['auth_token'] ?? '');
                if ($sid === '' || $token === '') {
                    return new WP_Error('missing_credentials', __('Twilio SID and Auth Token are required.', 'syntekpro-forms'));
                }
                $res = wp_remote_get('https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode($sid) . '.json', array(
                    'timeout' => 10,
                    'headers' => array('Authorization' => 'Basic ' . base64_encode($sid . ':' . $token)),
                ));
                return self::response_ok($res) ? true : new WP_Error('connection_failed', __('Twilio connection failed.', 'syntekpro-forms'));

            case 'google_sheets':
                $token = (string) ($credentials['access_token'] ?? '');
                if ($token === '') {
                    return new WP_Error('missing_credentials', __('Google access token is required.', 'syntekpro-forms'));
                }
                $res = wp_remote_get('https://www.googleapis.com/oauth2/v3/userinfo', array(
                    'timeout' => 10,
                    'headers' => array('Authorization' => 'Bearer ' . $token),
                ));
                return self::response_ok($res) ? true : new WP_Error('connection_failed', __('Google connection failed.', 'syntekpro-forms'));

            case 'airtable':
                $api_key = (string) ($credentials['api_key'] ?? '');
                if ($api_key === '') {
                    return new WP_Error('missing_credentials', __('Airtable API key is required.', 'syntekpro-forms'));
                }
                $res = wp_remote_get('https://api.airtable.com/v0/meta/bases', array(
                    'timeout' => 10,
                    'headers' => array('Authorization' => 'Bearer ' . $api_key),
                ));
                return self::response_ok($res) ? true : new WP_Error('connection_failed', __('Airtable connection failed.', 'syntekpro-forms'));

            case 'notion':
                $token = (string) ($credentials['access_token'] ?? $credentials['api_key'] ?? '');
                if ($token === '') {
                    return new WP_Error('missing_credentials', __('Notion token is required.', 'syntekpro-forms'));
                }
                $res = wp_remote_get('https://api.notion.com/v1/users/me', array(
                    'timeout' => 10,
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $token,
                        'Notion-Version' => '2022-06-28',
                    ),
                ));
                return self::response_ok($res) ? true : new WP_Error('connection_failed', __('Notion connection failed.', 'syntekpro-forms'));

            default:
                return new WP_Error('unknown_integration', __('Unknown integration.', 'syntekpro-forms'));
        }
    }

    /**
     * Execute integration action on form submission
     * 
     * @param int $entry_id Entry ID
     * @param int $form_id Form ID
     * @param array $entry_data Entry submission data
     * @param string $integration_name Integration to trigger
     * @return array|WP_Error Result of integration action
     */
    public static function trigger_integration($entry_id, $form_id, $entry_data, $integration_name) {
        $config = self::get_integration_config($form_id, $integration_name, false);
        
        if (is_wp_error($config) || empty($config['enabled'])) {
            return array('status' => 'skipped', 'reason' => 'Integration not configured or disabled');
        }

        // TODO: Phase 2 Implementation
        // Based on integration_name, call appropriate integration method:
        switch ($integration_name) {
            case 'sendgrid':
                return self::trigger_sendgrid($entry_id, $form_id, $entry_data, $config);
            case 'twilio':
                return self::trigger_twilio($entry_id, $form_id, $entry_data, $config);
            case 'google_sheets':
                return self::trigger_google_sheets($entry_id, $entry_data, $config);
            case 'airtable':
                return self::trigger_airtable($entry_id, $entry_data, $config);
            case 'notion':
                return self::trigger_notion($entry_id, $entry_data, $config);
            default:
                return new WP_Error('unknown_integration', __('Unknown integration', 'syntekpro-forms'));
        }
    }

    /**
     * SendGrid email integration
     * 
     * @param array $entry_data Entry data
     * @param array $config Integration config
     * @return array Result
     */
    private static function trigger_sendgrid($entry_id, $form_id, $entry_data, $config) {
        $api_key = (string) ($config['api_key'] ?? '');
        $to = (string) ($config['to'] ?? '');
        $from = (string) ($config['from'] ?? get_option('admin_email'));
        $subject = (string) ($config['subject'] ?? __('New form submission', 'syntekpro-forms'));

        if ($api_key === '' || $to === '') {
            return array('status' => 'skipped', 'reason' => 'Missing SendGrid credentials or recipient');
        }

        $content = !empty($config['message_template']) ? (string) $config['message_template'] : wp_json_encode($entry_data, JSON_PRETTY_PRINT);
        foreach ((array) $entry_data as $key => $value) {
            $content = str_replace('{' . $key . '}', is_scalar($value) ? (string) $value : wp_json_encode($value), $content);
            $subject = str_replace('{' . $key . '}', is_scalar($value) ? (string) $value : wp_json_encode($value), $subject);
        }

        $payload = array(
            'personalizations' => array(array('to' => array(array('email' => $to)))),
            'from' => array('email' => $from),
            'subject' => $subject,
            'content' => array(array('type' => 'text/plain', 'value' => $content)),
        );

        $res = wp_remote_post('https://api.sendgrid.com/v3/mail/send', array(
            'timeout' => 10,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($payload),
        ));

        if (is_wp_error($res)) {
            self::log_execution($form_id, $entry_id, 'sendgrid', 'failed', null, '', $res->get_error_message());
            return new WP_Error('send_failed', $res->get_error_message());
        }

        $code = (int) wp_remote_retrieve_response_code($res);
        $body = wp_remote_retrieve_body($res);
        $ok = in_array($code, array(200, 201, 202), true);
        self::log_execution($form_id, $entry_id, 'sendgrid', $ok ? 'success' : 'failed', $code, $body, $ok ? '' : __('SendGrid API error', 'syntekpro-forms'));

        return array('status' => $ok ? 'success' : 'failed', 'response_code' => $code);
    }

    /**
     * Twilio SMS integration
     * 
     * @param array $entry_data Entry data
     * @param array $config Integration config
     * @return array Result
     */
    private static function trigger_twilio($entry_id, $form_id, $entry_data, $config) {
        $sid = (string) ($config['account_sid'] ?? '');
        $token = (string) ($config['api_secret'] ?? '');
        $to = (string) ($config['to'] ?? '');
        $from = (string) ($config['from'] ?? '');
        $message = !empty($config['message_template']) ? (string) $config['message_template'] : __('New form submission received.', 'syntekpro-forms');

        if ($sid === '' || $token === '' || $to === '' || $from === '') {
            return array('status' => 'skipped', 'reason' => 'Missing Twilio credentials or sender/recipient');
        }

        foreach ((array) $entry_data as $key => $value) {
            $message = str_replace('{' . $key . '}', is_scalar($value) ? (string) $value : wp_json_encode($value), $message);
        }

        $res = wp_remote_post('https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode($sid) . '/Messages.json', array(
            'timeout' => 10,
            'headers' => array('Authorization' => 'Basic ' . base64_encode($sid . ':' . $token)),
            'body' => array(
                'To' => $to,
                'From' => $from,
                'Body' => $message,
            ),
        ));

        if (is_wp_error($res)) {
            self::log_execution($form_id, $entry_id, 'twilio', 'failed', null, '', $res->get_error_message());
            return new WP_Error('send_failed', $res->get_error_message());
        }

        $code = (int) wp_remote_retrieve_response_code($res);
        $body = wp_remote_retrieve_body($res);
        $ok = in_array($code, array(200, 201), true);
        self::log_execution($form_id, $entry_id, 'twilio', $ok ? 'success' : 'failed', $code, $body, $ok ? '' : __('Twilio API error', 'syntekpro-forms'));

        return array('status' => $ok ? 'success' : 'failed', 'response_code' => $code);
    }

    /**
     * Google Sheets integration
     * 
     * @param int $entry_id Entry ID
     * @param array $entry_data Entry data
     * @param array $config Integration config
     * @return array Result
     */
    private static function trigger_google_sheets($entry_id, $entry_data, $config) {
        if (!empty($config['webhook_url'])) {
            $res = wp_remote_post($config['webhook_url'], array(
                'timeout' => 10,
                'headers' => array('Content-Type' => 'application/json'),
                'body' => wp_json_encode(array('entry_id' => $entry_id, 'entry_data' => $entry_data, 'integration' => 'google_sheets')),
            ));
            if (is_wp_error($res)) {
                return new WP_Error('send_failed', $res->get_error_message());
            }
            return array('status' => 'success', 'response_code' => (int) wp_remote_retrieve_response_code($res));
        }

        return array('status' => 'skipped', 'reason' => 'Configure webhook_url or custom Sheets handler to enable dispatch.');
    }

    /**
     * Airtable integration
     * 
     * @param int $entry_id Entry ID
     * @param array $entry_data Entry data
     * @param array $config Integration config
     * @return array Result
     */
    private static function trigger_airtable($entry_id, $entry_data, $config) {
        if (!empty($config['webhook_url'])) {
            $res = wp_remote_post($config['webhook_url'], array(
                'timeout' => 10,
                'headers' => array('Content-Type' => 'application/json'),
                'body' => wp_json_encode(array('entry_id' => $entry_id, 'entry_data' => $entry_data, 'integration' => 'airtable')),
            ));
            if (is_wp_error($res)) {
                return new WP_Error('send_failed', $res->get_error_message());
            }
            return array('status' => 'success', 'response_code' => (int) wp_remote_retrieve_response_code($res));
        }

        return array('status' => 'skipped', 'reason' => 'Configure webhook_url or custom Airtable handler to enable dispatch.');
    }

    /**
     * Notion integration
     * 
     * @param int $entry_id Entry ID
     * @param array $entry_data Entry data
     * @param array $config Integration config
     * @return array Result
     */
    private static function trigger_notion($entry_id, $entry_data, $config) {
        if (!empty($config['webhook_url'])) {
            $res = wp_remote_post($config['webhook_url'], array(
                'timeout' => 10,
                'headers' => array('Content-Type' => 'application/json'),
                'body' => wp_json_encode(array('entry_id' => $entry_id, 'entry_data' => $entry_data, 'integration' => 'notion')),
            ));
            if (is_wp_error($res)) {
                return new WP_Error('send_failed', $res->get_error_message());
            }
            return array('status' => 'success', 'response_code' => (int) wp_remote_retrieve_response_code($res));
        }

        return array('status' => 'skipped', 'reason' => 'Configure webhook_url or custom Notion handler to enable dispatch.');
    }

    /**
     * Get supported integrations list
     * 
     * @return array List of supported integration keys and names
     */
    public static function get_supported_integrations() {
        return self::SUPPORTED_INTEGRATIONS;
    }

    /**
     * Get integration logs for form
     * 
     * @param int $form_id Form ID
     * @param string $integration_name Filter by integration (optional)
     * @param array $filters Date range, status filters
     * @return array Integration execution logs
     */
    public static function get_integration_logs($form_id, $integration_name = null, $filters = array()) {
        global $wpdb;

        if (!self::can_manage_forms()) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        $form_id = absint($form_id);
        $args = array($form_id);
        $where = 'WHERE form_id = %d';

        if (!empty($integration_name)) {
            $where .= ' AND integration_name = %s';
            $args[] = sanitize_key((string) $integration_name);
        }
        if (!empty($filters['status'])) {
            $where .= ' AND status = %s';
            $args[] = sanitize_key((string) $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $where .= ' AND created_at >= %s';
            $args[] = sanitize_text_field((string) $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $where .= ' AND created_at <= %s';
            $args[] = sanitize_text_field((string) $filters['date_to']);
        }

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spf_integration_logs {$where} ORDER BY created_at DESC LIMIT 200",
            $args
        ));

        return array('logs' => $rows ? $rows : array(), 'status' => 'ok');
    }

    public static function trigger_all_integrations($entry_id, $form_id, $entry_data) {
        $results = array();
        foreach (array_keys(self::SUPPORTED_INTEGRATIONS) as $integration) {
            $result = self::trigger_integration($entry_id, $form_id, $entry_data, $integration);

            if (is_wp_error($result)) {
                self::log_execution($form_id, $entry_id, $integration, 'failed', null, '', $result->get_error_message());
                $results[$integration] = array('status' => 'failed', 'error' => $result->get_error_message());
                continue;
            }

            $status = isset($result['status']) ? (string) $result['status'] : 'success';
            self::log_execution(
                $form_id,
                $entry_id,
                $integration,
                $status,
                isset($result['response_code']) ? absint($result['response_code']) : null,
                isset($result['response']) ? $result['response'] : '',
                isset($result['reason']) ? $result['reason'] : ''
            );
            $results[$integration] = $result;
        }

        return $results;
    }
}
