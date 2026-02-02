<?php
/**
 * Spam and abuse helpers for SyntekPro Forms submissions.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SyntekPro_Forms_Spam_Filter {

    /**
     * Keeps track of the latest rate limit lock key for cleanup.
     *
     * @var string
     */
    private $rate_limit_lock_key = '';

    /**
     * Reset the stored lock reference so future releases know which key to target.
     */
    public function reset_rate_limit_reference() {
        $this->rate_limit_lock_key = '';
    }

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

        $this->rate_limit_lock_key = $lock_key;
        set_transient($lock_key, time(), $seconds);
    }

    /**
     * Release the current rate limit lock and optionally keep the transient.
     */
    public function release_rate_limit_lock($preserve_lock = false) {
        if (!$preserve_lock && !empty($this->rate_limit_lock_key)) {
            delete_transient($this->rate_limit_lock_key);
        }

        $this->rate_limit_lock_key = '';
    }

    /**
     * Verify a reCAPTCHA response token with Google.
     */
    public function verify_recaptcha($response, $secret) {
        if (empty($response) || empty($secret)) {
            return false;
        }

        $remote_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $url = 'https://www.google.com/recaptcha/api/siteverify';

        $request = wp_remote_post($url, array(
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
     * Run the submission through Akismet if credentials are present.
     */
    public function check_akismet_spam($form, $data, $ip, $user_agent) {
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
