<?php
/**
 * SyntekPro Forms - Email Templates (Phase 2)
 * 
 * Advanced email templates with visual builder, conditional blocks,
 * and loop sections for dynamic email generation. Phase 2 architectural stub.
 * 
 * @package SyntekPro_Forms
 * @subpackage Email_Templates
 */

if (!defined('ABSPATH')) {
    exit;
}

class SyntekPro_Forms_Email_Templates {

    private static function can_manage_forms() {
        return current_user_can('spf_manage_forms') || current_user_can('manage_options');
    }

    private static function normalize_array($value) {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : array();
    }

    private static function sanitize_template_data($template_data) {
        $template_data = is_array($template_data) ? $template_data : array();

        return array(
            'id' => isset($template_data['id']) ? absint($template_data['id']) : 0,
            'name' => sanitize_text_field((string) ($template_data['name'] ?? '')),
            'subject' => sanitize_text_field((string) ($template_data['subject'] ?? '')),
            'from_email' => sanitize_email((string) ($template_data['from_email'] ?? '')),
            'reply_to' => sanitize_email((string) ($template_data['reply_to'] ?? '')),
            'recipients' => array_values(array_filter(array_map('sanitize_email', (array) ($template_data['recipients'] ?? array())))),
            'conditions' => self::normalize_array($template_data['conditions'] ?? array()),
            'html_content' => wp_kses_post((string) ($template_data['html_content'] ?? '')),
            'is_active' => !empty($template_data['is_active']) ? 1 : 0,
        );
    }

    private static function replace_tokens($content, $entry_data, $loop_item = null) {
        $rendered = (string) $content;
        $entry_data = is_array($entry_data) ? $entry_data : array();

        foreach ($entry_data as $key => $value) {
            $replacement = is_array($value) ? wp_json_encode($value) : (string) $value;
            $rendered = str_replace('{' . $key . '}', esc_html($replacement), $rendered);
        }

        if (is_array($loop_item)) {
            foreach ($loop_item as $key => $value) {
                $replacement = is_array($value) ? wp_json_encode($value) : (string) $value;
                $rendered = str_replace('{{item.' . $key . '}}', esc_html($replacement), $rendered);
            }
        }

        return $rendered;
    }

    private static function process_conditionals($content, $entry_data) {
        return preg_replace_callback('/\[\[if\s+([a-zA-Z0-9_\-]+)(?:\s*==\s*"([^"]*)")?\]\](.*?)\[\[\/if\]\]/s', function($matches) use ($entry_data) {
            $field = $matches[1];
            $expected = isset($matches[2]) ? (string) $matches[2] : null;
            $block = $matches[3];

            $actual = isset($entry_data[$field]) ? $entry_data[$field] : null;
            if (is_array($actual)) {
                $actual = wp_json_encode($actual);
            }

            if ($expected === null) {
                return empty($actual) ? '' : $block;
            }

            return ((string) $actual === $expected) ? $block : '';
        }, (string) $content);
    }

    private static function process_loops($content, $entry_data) {
        return preg_replace_callback('/\[\[loop\s+([a-zA-Z0-9_\-]+)\]\](.*?)\[\[\/loop\]\]/s', function($matches) use ($entry_data) {
            $field = $matches[1];
            $block = $matches[2];
            $items = isset($entry_data[$field]) && is_array($entry_data[$field]) ? $entry_data[$field] : array();

            if (empty($items)) {
                return '';
            }

            $output = '';
            foreach ($items as $item) {
                if (!is_array($item)) {
                    $item = array('value' => $item);
                }
                $output .= self::replace_tokens($block, $entry_data, $item);
            }

            return $output;
        }, (string) $content);
    }

    /**
     * Get email templates for form
     * 
     * @param int $form_id Form ID
     * @return array|WP_Error Array of email templates
     */
    public static function get_templates($form_id) {
        global $wpdb;

        if (!self::can_manage_forms()) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        $form_id = absint($form_id);
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spf_email_templates
             WHERE form_id = %d
             ORDER BY updated_at DESC",
            $form_id
        ));

        if (!$rows) {
            return array();
        }

        return array_map(function($row) {
            $row->recipients = self::normalize_array($row->recipients);
            $row->conditions = self::normalize_array($row->conditions);
            $row->is_active = (int) $row->is_active;
            return $row;
        }, $rows);
    }

    /**
     * Create/update email template
     * 
     * @param int $form_id Form ID
     * @param array $template_data Template data (name, subject, html, conditions)
     * @return int|WP_Error Template ID or error
     */
    public static function save_template($form_id, $template_data) {
        global $wpdb;

        if (!self::can_manage_forms()) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        $form_id = absint($form_id);
        $data = self::sanitize_template_data($template_data);

        if ($form_id <= 0 || $data['name'] === '' || $data['subject'] === '' || $data['html_content'] === '') {
            return new WP_Error('validation_failed', __('Template name, subject, and content are required.', 'syntekpro-forms'));
        }

        $payload = array(
            'form_id' => $form_id,
            'name' => $data['name'],
            'subject' => $data['subject'],
            'from_email' => $data['from_email'],
            'reply_to' => $data['reply_to'],
            'recipients' => wp_json_encode($data['recipients']),
            'conditions' => wp_json_encode($data['conditions']),
            'html_content' => $data['html_content'],
            'is_active' => $data['is_active'],
            'updated_by' => (int) get_current_user_id(),
        );

        if ($data['id'] > 0) {
            $updated = $wpdb->update(
                $wpdb->prefix . 'spf_email_templates',
                $payload,
                array('id' => $data['id'], 'form_id' => $form_id),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d'),
                array('%d', '%d')
            );

            if ($updated === false) {
                return new WP_Error('db_error', __('Failed to update email template.', 'syntekpro-forms'));
            }

            if (function_exists('spf_log_audit')) {
                spf_log_audit($form_id, 'email_template_updated', array('template_id' => $data['id'], 'name' => $data['name']));
            }

            return $data['id'];
        }

        $payload['created_by'] = (int) get_current_user_id();
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'spf_email_templates',
            $payload,
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d')
        );

        if ($inserted === false) {
            return new WP_Error('db_error', __('Failed to create email template.', 'syntekpro-forms'));
        }

        $template_id = (int) $wpdb->insert_id;
        if (function_exists('spf_log_audit')) {
            spf_log_audit($form_id, 'email_template_created', array('template_id' => $template_id, 'name' => $data['name']));
        }

        return $template_id;
    }

    /**
     * Render email from template with entry data
     * 
     * @param int $template_id Template ID
     * @param array $entry_data Form entry data to substitute
     * @return string|WP_Error Rendered HTML email
     */
    public static function render_template($template_id, $entry_data) {
        global $wpdb;

        $template_id = absint($template_id);
        if ($template_id <= 0) {
            return new WP_Error('invalid_template', __('Invalid template ID.', 'syntekpro-forms'));
        }

        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spf_email_templates WHERE id = %d AND is_active = 1",
            $template_id
        ));

        if (!$template) {
            return new WP_Error('not_found', __('Template not found.', 'syntekpro-forms'));
        }

        $html = (string) $template->html_content;
        $entry_data = is_array($entry_data) ? $entry_data : array();

        $html = self::process_conditionals($html, $entry_data);
        $html = self::process_loops($html, $entry_data);
        $html = self::replace_tokens($html, $entry_data);

        return $html;
    }

    /**
     * Preview template with sample data
     * 
     * @param array $template_html Visual builder HTML
     * @param array $sample_data Sample entry data
     * @return string Rendered preview
     */
    public static function preview_template($template_html, $sample_data) {
        $template_html = (string) $template_html;
        $sample_data = is_array($sample_data) ? $sample_data : array();

        $rendered = self::process_conditionals($template_html, $sample_data);
        $rendered = self::process_loops($rendered, $sample_data);
        $rendered = self::replace_tokens($rendered, $sample_data);

        return $rendered;
    }

    /**
     * Get email template conditions builder schema
     * 
     * @param int $form_id Form ID to build conditions for
     * @return array Condition schema for UI builder
     */
    public static function get_conditions_schema($form_id) {
        global $wpdb;

        $form_id = absint($form_id);
        $form = $wpdb->get_row($wpdb->prepare(
            "SELECT fields FROM {$wpdb->prefix}spf_forms WHERE id = %d",
            $form_id
        ));

        $fields = array();
        if ($form && !empty($form->fields)) {
            $fields = self::normalize_array($form->fields);
        }

        $operators_by_type = array(
            'text' => array('contains', 'equals', 'not_equals', 'regex', 'is_empty', 'is_not_empty'),
            'textarea' => array('contains', 'equals', 'not_equals', 'regex', 'is_empty', 'is_not_empty'),
            'email' => array('equals', 'not_equals', 'contains', 'is_empty', 'is_not_empty'),
            'number' => array('equals', 'not_equals', 'greater_than', 'less_than', 'between', 'is_empty'),
            'select' => array('equals', 'not_equals', 'in_list', 'not_in_list', 'is_empty'),
            'radio' => array('equals', 'not_equals', 'in_list', 'not_in_list', 'is_empty'),
            'checkbox' => array('contains', 'not_contains', 'is_empty', 'is_not_empty'),
            'date' => array('equals', 'before', 'after', 'between', 'is_empty'),
        );

        $schema_fields = array();
        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }
            $field_type = isset($field['type']) ? sanitize_key((string) $field['type']) : 'text';
            $schema_fields[] = array(
                'id' => isset($field['id']) ? (string) $field['id'] : '',
                'name' => isset($field['name']) ? (string) $field['name'] : '',
                'label' => isset($field['label']) ? (string) $field['label'] : '',
                'type' => $field_type,
                'operators' => isset($operators_by_type[$field_type]) ? $operators_by_type[$field_type] : $operators_by_type['text'],
            );
        }

        return array(
            'fields' => $schema_fields,
            'operators_by_type' => $operators_by_type,
        );
    }
}
