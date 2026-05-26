<?php
/**
 * SMTP + OAuth2 mail transport for SyntekPro Forms.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SyntekPro_Forms_SMTP')) {
    class SyntekPro_Forms_SMTP {

        private static $instance = null;

        const SETTINGS_OPTION = 'spf_settings';
        const SECRET_OPTION_PREFIX = 'spf_smtp_secret_';

        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        private function __construct() {
            add_action('phpmailer_init', array($this, 'configure_phpmailer'));
            add_action('wp_mail_succeeded', array($this, 'log_email_success'));
            add_action('wp_mail_failed', array($this, 'log_email_failed'));
        }

        public static function get_provider_presets() {
            return array(
                'custom' => array(
                    'label' => __('Custom SMTP', 'syntekpro-forms'),
                    'host' => '',
                    'port' => 587,
                    'encryption' => 'tls',
                    'auth_type' => 'password',
                    'oauth_provider' => 'google',
                ),
                'gmail_oauth2' => array(
                    'label' => __('Gmail (OAuth2)', 'syntekpro-forms'),
                    'host' => 'smtp.gmail.com',
                    'port' => 587,
                    'encryption' => 'tls',
                    'auth_type' => 'oauth2',
                    'oauth_provider' => 'google',
                ),
                'outlook_oauth2' => array(
                    'label' => __('Outlook / Microsoft 365 (OAuth2)', 'syntekpro-forms'),
                    'host' => 'smtp.office365.com',
                    'port' => 587,
                    'encryption' => 'tls',
                    'auth_type' => 'oauth2',
                    'oauth_provider' => 'microsoft',
                ),
                'sendgrid' => array(
                    'label' => __('SendGrid', 'syntekpro-forms'),
                    'host' => 'smtp.sendgrid.net',
                    'port' => 587,
                    'encryption' => 'tls',
                    'auth_type' => 'password',
                    'oauth_provider' => 'google',
                ),
                'mailgun' => array(
                    'label' => __('Mailgun', 'syntekpro-forms'),
                    'host' => 'smtp.mailgun.org',
                    'port' => 587,
                    'encryption' => 'tls',
                    'auth_type' => 'password',
                    'oauth_provider' => 'google',
                ),
                'ses' => array(
                    'label' => __('Amazon SES', 'syntekpro-forms'),
                    'host' => 'email-smtp.us-east-1.amazonaws.com',
                    'port' => 587,
                    'encryption' => 'tls',
                    'auth_type' => 'password',
                    'oauth_provider' => 'google',
                ),
            );
        }

        public static function save_secret($name, $plain_text) {
            $option_name = self::get_secret_option_name($name);
            $plain_text = (string) $plain_text;

            if ($plain_text === '') {
                delete_option($option_name);
                return;
            }

            $encrypted = self::encrypt_secret($plain_text);
            if ($encrypted === '') {
                $encrypted = base64_encode($plain_text);
            }

            if (get_option($option_name, null) === null) {
                add_option($option_name, $encrypted, '', 'no');
                return;
            }

            update_option($option_name, $encrypted, false);
        }

        public static function get_secret($name) {
            $option_name = self::get_secret_option_name($name);
            $raw = get_option($option_name, '');
            if (!is_string($raw) || $raw === '') {
                return '';
            }

            $decrypted = self::decrypt_secret($raw);
            if ($decrypted !== '') {
                return $decrypted;
            }

            $fallback = base64_decode($raw, true);
            return is_string($fallback) ? $fallback : '';
        }

        public static function has_secret($name) {
            return self::get_secret($name) !== '';
        }

        public function configure_phpmailer($phpmailer) {
            $settings = get_option(self::SETTINGS_OPTION, array());
            if (!is_array($settings) || empty($settings['smtp_enabled'])) {
                return;
            }

            $host = isset($settings['smtp_host']) ? trim((string) $settings['smtp_host']) : '';
            $port = isset($settings['smtp_port']) ? absint($settings['smtp_port']) : 587;
            $secure = isset($settings['smtp_encryption']) ? (string) $settings['smtp_encryption'] : 'tls';
            $username = isset($settings['smtp_username']) ? trim((string) $settings['smtp_username']) : '';
            $auth_type = isset($settings['smtp_auth_type']) ? (string) $settings['smtp_auth_type'] : 'password';

            if ($host === '') {
                return;
            }

            $phpmailer->isSMTP();
            $phpmailer->Host = $host;
            $phpmailer->Port = $port > 0 ? $port : 587;
            $phpmailer->SMTPAuth = true;
            $phpmailer->SMTPAutoTLS = true;

            if (in_array($secure, array('ssl', 'tls'), true)) {
                $phpmailer->SMTPSecure = $secure;
            } else {
                $phpmailer->SMTPSecure = '';
            }

            if (!empty($settings['from_email']) && is_email($settings['from_email'])) {
                $phpmailer->setFrom(
                    sanitize_email((string) $settings['from_email']),
                    sanitize_text_field((string) ($settings['from_name'] ?? '')),
                    false
                );
            }

            if ($auth_type === 'oauth2') {
                $token = $this->get_oauth_access_token($settings);
                if ($token !== '' && interface_exists('PHPMailer\\PHPMailer\\OAuthTokenProvider')) {
                    $phpmailer->AuthType = 'XOAUTH2';
                    $phpmailer->Username = $username;
                    $phpmailer->setOAuth(new SPF_SMTP_OAuth_Token_Provider($username, $token));
                    return;
                }
            }

            $phpmailer->AuthType = 'LOGIN';
            $phpmailer->Username = $username;
            $phpmailer->Password = self::get_secret('password');
        }

        public function log_email_success($mail_data) {
            $to = '';
            if (!empty($mail_data['to'])) {
                $to = is_array($mail_data['to']) ? implode(', ', $mail_data['to']) : (string) $mail_data['to'];
            }

            $subject = isset($mail_data['subject']) ? sanitize_text_field((string) $mail_data['subject']) : '';
            $this->insert_log('sent', $to, $subject, '');
        }

        public function log_email_failed($wp_error) {
            $to = '';
            $subject = '';
            $error_message = '';

            if (is_wp_error($wp_error)) {
                $error_message = sanitize_text_field($wp_error->get_error_message());
                $data = $wp_error->get_error_data();
                if (is_array($data)) {
                    if (!empty($data['to'])) {
                        $to = is_array($data['to']) ? implode(', ', $data['to']) : (string) $data['to'];
                    }
                    if (!empty($data['subject'])) {
                        $subject = sanitize_text_field((string) $data['subject']);
                    }
                }
            }

            $this->insert_log('failed', $to, $subject, $error_message);
        }

        public static function get_recent_logs($limit = 25) {
            global $wpdb;

            $limit = max(1, min(100, absint($limit)));
            $table = $wpdb->prefix . 'spf_email_logs';

            $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
            if ($exists !== $table) {
                return array();
            }

            $query = $wpdb->prepare(
                "SELECT id, status, recipient, subject, error_message, created_at FROM {$table} ORDER BY id DESC LIMIT %d",
                $limit
            );

            $rows = $wpdb->get_results($query, ARRAY_A);
            return is_array($rows) ? $rows : array();
        }

        private function insert_log($status, $recipient, $subject, $error_message) {
            global $wpdb;

            $table = $wpdb->prefix . 'spf_email_logs';
            $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
            if ($exists !== $table) {
                return;
            }

            $wpdb->insert(
                $table,
                array(
                    'status' => sanitize_key((string) $status),
                    'recipient' => sanitize_text_field((string) $recipient),
                    'subject' => sanitize_text_field((string) $subject),
                    'error_message' => sanitize_text_field((string) $error_message),
                    'created_at' => current_time('mysql'),
                ),
                array('%s', '%s', '%s', '%s', '%s')
            );
        }

        private function get_oauth_access_token($settings) {
            $provider = isset($settings['smtp_oauth_provider']) ? (string) $settings['smtp_oauth_provider'] : 'google';
            $client_id = isset($settings['smtp_oauth_client_id']) ? trim((string) $settings['smtp_oauth_client_id']) : '';
            $client_secret = self::get_secret('oauth_client_secret');
            $refresh_token = self::get_secret('oauth_refresh_token');
            $tenant_id = isset($settings['smtp_oauth_tenant_id']) ? trim((string) $settings['smtp_oauth_tenant_id']) : 'common';

            if ($client_id === '' || $client_secret === '' || $refresh_token === '') {
                return '';
            }

            $cache_key = 'spf_smtp_oauth_' . md5($provider . '|' . $client_id . '|' . $refresh_token . '|' . $tenant_id);
            $cached = get_transient($cache_key);
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }

            $token_endpoint = 'https://oauth2.googleapis.com/token';
            $body = array(
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'refresh_token' => $refresh_token,
                'grant_type' => 'refresh_token',
            );

            if ($provider === 'microsoft') {
                if ($tenant_id === '') {
                    $tenant_id = 'common';
                }

                $token_endpoint = 'https://login.microsoftonline.com/' . rawurlencode($tenant_id) . '/oauth2/v2.0/token';
                $body['scope'] = 'https://outlook.office.com/.default offline_access';
            }

            $response = wp_remote_post(
                $token_endpoint,
                array(
                    'timeout' => 20,
                    'body' => $body,
                    'headers' => array(
                        'Accept' => 'application/json',
                    ),
                )
            );

            if (is_wp_error($response)) {
                return '';
            }

            $status = (int) wp_remote_retrieve_response_code($response);
            $body_raw = wp_remote_retrieve_body($response);
            $decoded = json_decode($body_raw, true);

            if ($status !== 200 || !is_array($decoded) || empty($decoded['access_token'])) {
                return '';
            }

            $access_token = sanitize_text_field((string) $decoded['access_token']);
            $expires_in = !empty($decoded['expires_in']) ? max(60, absint($decoded['expires_in']) - 60) : 3000;

            set_transient($cache_key, $access_token, $expires_in);
            return $access_token;
        }

        private static function get_secret_option_name($name) {
            return self::SECRET_OPTION_PREFIX . sanitize_key((string) $name);
        }

        private static function get_crypto_key() {
            $base = defined('AUTH_KEY') && AUTH_KEY && AUTH_KEY !== 'put your unique phrase here'
                ? AUTH_KEY
                : wp_salt('auth');

            return hash('sha256', (string) $base, true);
        }

        private static function encrypt_secret($plain_text) {
            if (!function_exists('openssl_encrypt') || !function_exists('openssl_cipher_iv_length')) {
                return '';
            }

            $cipher = 'aes-256-cbc';
            $iv_len = openssl_cipher_iv_length($cipher);
            if ($iv_len <= 0) {
                return '';
            }

            try {
                $iv = random_bytes($iv_len);
            } catch (Exception $e) {
                return '';
            }

            $encrypted = openssl_encrypt($plain_text, $cipher, self::get_crypto_key(), 0, $iv);
            if (!is_string($encrypted) || $encrypted === '') {
                return '';
            }

            $payload = wp_json_encode(
                array(
                    'cipher' => $cipher,
                    'iv' => base64_encode($iv),
                    'value' => $encrypted,
                )
            );

            return is_string($payload) ? base64_encode($payload) : '';
        }

        private static function decrypt_secret($stored_value) {
            if (!function_exists('openssl_decrypt')) {
                return '';
            }

            $decoded = base64_decode((string) $stored_value, true);
            if (!is_string($decoded) || $decoded === '') {
                return '';
            }

            $payload = json_decode($decoded, true);
            if (!is_array($payload) || empty($payload['cipher']) || empty($payload['iv']) || !isset($payload['value'])) {
                return '';
            }

            $iv = base64_decode((string) $payload['iv'], true);
            if (!is_string($iv) || $iv === '') {
                return '';
            }

            $decrypted = openssl_decrypt(
                (string) $payload['value'],
                (string) $payload['cipher'],
                self::get_crypto_key(),
                0,
                $iv
            );

            return is_string($decrypted) ? $decrypted : '';
        }
    }
}

if (interface_exists('PHPMailer\\PHPMailer\\OAuthTokenProvider') && !class_exists('SPF_SMTP_OAuth_Token_Provider')) {
    class SPF_SMTP_OAuth_Token_Provider implements PHPMailer\PHPMailer\OAuthTokenProvider {

        private $email;
        private $access_token;

        public function __construct($email, $access_token) {
            $this->email = (string) $email;
            $this->access_token = (string) $access_token;
        }

        public function getOauth64() {
            return base64_encode('user=' . $this->email . "\001auth=Bearer " . $this->access_token . "\001\001");
        }
    }
}
