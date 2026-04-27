<?php
/**
 * Spam and abuse helpers for SyntekPro Forms submissions.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SyntekPro_Forms_Spam_Filter {

    /**
     * Build the transient key for a visitor IP.
     */
    public function get_rate_limit_lock_key($client_ip) {
        if (empty($client_ip)) {
            return '';
        }

        return 'spf_rl_' . md5($client_ip);
    }

    /**
     * Check if a rate limit lock currently exists.
     */
    public function has_rate_limit_lock($lock_key) {
        if (empty($lock_key)) {
            return false;
        }

        return (bool) get_transient($lock_key);
    }

    /**
     * Store a transient-based lock for the configured duration.
     */
    public function acquire_rate_limit_lock($lock_key, $seconds) {
        if (empty($lock_key) || $seconds <= 0) {
            return;
        }

        set_transient($lock_key, time(), $seconds);
    }

    /**
     * Verify a Cloudflare Turnstile response token.
     */
    public function verify_turnstile($response, $secret) {
        if (empty($response) || empty($secret)) {
            return false;
        }

        $remote_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

        $request = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
            'body' => array(
                'secret'   => $secret,
                'response' => $response,
                'remoteip' => $remote_ip,
            ),
        ));

        if (is_wp_error($request)) {
            return false;
        }

        $body = wp_remote_retrieve_body($request);
        $result = json_decode($body, true);

        return !empty($result['success']);
    }

    /**
     * Verify an hCaptcha response token.
     */
    public function verify_hcaptcha($response, $secret) {
        if (empty($response) || empty($secret)) {
            return false;
        }

        $remote_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

        $request = wp_remote_post('https://hcaptcha.com/siteverify', array(
            'body' => array(
                'secret'   => $secret,
                'response' => $response,
                'remoteip' => $remote_ip,
            ),
        ));

        if (is_wp_error($request)) {
            return false;
        }

        $body = wp_remote_retrieve_body($request);
        $result = json_decode($body, true);

        return !empty($result['success']);
    }

    /**
     * Verify a reCAPTCHA response token with Google.
     */
    public function verify_recaptcha($response, $secret) {
        if (empty($response) || empty($secret)) {
            return false;
        }

        $remote_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        $url = 'https://www.google.com/recaptcha/api/siteverify';

        $request = wp_remote_post($url, array(
            'body' => array(
                'secret'   => $secret,
                'response' => $response,
                'remoteip' => $remote_ip,
            ),
        ));

        if (is_wp_error($request)) {
            // Fail-open: network errors allow submission through. If you want fail-closed instead, change the return to true on WP_Error.
            return false;
        }

        $body = wp_remote_retrieve_body($request);
        $result = json_decode($body, true);

        return !empty($result['success']);
    }

    /**
     * Run the submission through Akismet if credentials are present.
     */
    public function check_akismet_spam($form, $data, $ip, $user_agent) {
        if (!is_array($data)) {
            return false;
        }

        if (empty($ip)) {
            return false;
        }

        $api_key = get_option('wordpress_api_key');
        if (empty($api_key)) {
            return false;
        }

        $comment_content = '';
        if (is_array($data)) {
            $flattened = array();
            foreach ($data as $k => $v) {
                $flattened[] = $k . ': ' . (is_array($v) ? implode(', ', $v) : $v);
            }
            $comment_content = implode("\n", $flattened);
        }

        $author_email = '';
        foreach ($data as $k => $v) {
            if (strpos(strtolower((string) $k), 'email') !== false && is_email((string) $v)) {
                $author_email = $v;
                break;
            }
        }

        $body = array(
            'blog'                 => home_url('/'),
            'user_ip'              => $ip,
            'user_agent'           => $user_agent,
            'referrer'             => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
            'comment_type'         => 'syntekpro-form',
            'comment_author'       => '',
            'comment_author_email' => $author_email,
            'comment_content'      => $comment_content,
            'blog_lang'            => get_locale(),
            'blog_charset'         => get_option('blog_charset'),
        );

        $response = wp_remote_post('https://' . $api_key . '.rest.akismet.com/1.1/comment-check', array(
            'body'    => $body,
            'timeout' => 5,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $response_body = wp_remote_retrieve_body($response);
        return trim((string) $response_body) === 'true';
    }
}
