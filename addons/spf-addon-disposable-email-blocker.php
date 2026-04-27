<?php
/**
 * Add-on Name: Disposable Email Blocker
 * Description: Blocks submissions that use known disposable email domains.
 * Version: 1.0.0
 * Author: SyntekPro
 * Icon: dashicons-shield
 * Graphic: ../assets/images/Syntekpro Forms Colored Favicons.png
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SPF_Addon_Disposable_Email_Blocker')) {
    class SPF_Addon_Disposable_Email_Blocker {

        public function __construct() {
            add_filter('syntekpro_forms_submission_pre_validate', array($this, 'validate_submission'), 10, 8);
        }

        public function validate_submission($pre_validate, $raw_form_data, $form, $fields) {
            if (is_wp_error($pre_validate) || $pre_validate === false) {
                return $pre_validate;
            }

            $email_fields = $this->get_email_field_names($fields);
            if (empty($email_fields)) {
                return $pre_validate;
            }

            if (!is_array($raw_form_data)) {
                return $pre_validate;
            }

            $domains = $this->get_blocked_domains();
            if (empty($domains)) {
                return $pre_validate;
            }

            foreach ($email_fields as $field_name) {
                if (!isset($raw_form_data[$field_name])) {
                    continue;
                }

                $email = sanitize_email(wp_unslash((string) $raw_form_data[$field_name]));
                if (empty($email) || strpos($email, '@') === false) {
                    continue;
                }

                $parts = explode('@', strtolower($email));
                $domain = trim(end($parts));
                if ($domain !== '' && in_array($domain, $domains, true)) {
                    return new WP_Error('spf_disposable_email_blocked', __('Please use a valid business or personal email address.', 'syntekpro-forms'));
                }
            }

            return $pre_validate;
        }

        private function get_email_field_names($fields) {
            if (!is_array($fields)) {
                return array();
            }

            $email_fields = array();
            foreach ($fields as $field) {
                $type = isset($field['type']) ? (string) $field['type'] : '';
                $name = isset($field['name']) ? (string) $field['name'] : '';

                if ($name === '') {
                    continue;
                }

                if ($type === 'email' || stripos($name, 'email') !== false) {
                    $email_fields[] = $name;
                }
            }

            return array_unique($email_fields);
        }

        private function get_blocked_domains() {
            $defaults = array(
                'mailinator.com',
                'tempmail.com',
                '10minutemail.com',
                'guerrillamail.com',
                'trashmail.com',
                'yopmail.com',
                'getnada.com',
                'dispostable.com',
                'throwawaymail.com',
                'temp-mail.org',
                'fakeinbox.com',
                'sharklasers.com'
            );

            $domains = apply_filters('spf_disposable_email_domains', $defaults);
            if (!is_array($domains)) {
                return $defaults;
            }

            $normalized = array();
            foreach ($domains as $domain) {
                $domain = strtolower(trim((string) $domain));
                if ($domain !== '') {
                    $normalized[] = $domain;
                }
            }

            return array_values(array_unique($normalized));
        }
    }

    new SPF_Addon_Disposable_Email_Blocker();
}
