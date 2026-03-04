<?php
/**
 * Email Templates Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class SPF_Email_Templates {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Send admin notification
     */
    public function send_admin_notification($form, $data, $entry_id) {
        $settings = get_option('spf_settings');
        $form_settings = json_decode((string)($form->settings ?? '{}'), true);
        
        $notifications_enabled = isset($form_settings['notify_enabled'])
            ? (int) $form_settings['notify_enabled']
            : (isset($form_settings['notifications_enabled']) ? (int) $form_settings['notifications_enabled'] : 1);
        if ($notifications_enabled === 0) {
            return;
        }

        $recipient_source = '';
        if (!empty($form_settings['notify_emails'])) {
            $recipient_source = $form_settings['notify_emails'];
        } elseif (!empty($form_settings['notification_emails'])) {
            $recipient_source = $form_settings['notification_emails'];
        } elseif (!empty($form_settings['notification_email'])) {
            // Legacy key
            $recipient_source = $form_settings['notification_email'];
        }

        $recipients = $this->parse_recipient_list($recipient_source);
        if (empty($recipients)) {
            $recipients[] = get_option('admin_email');
        }

        // Email routing by field value
        $routing_recipients = $this->get_routing_recipients($form_settings, $data);
        if (!empty($routing_recipients)) {
            $recipients = array_unique(array_merge($recipients, $routing_recipients));
        }

        $to = implode(',', $recipients);
        
        // Subject – allow per-form customization with merge tags
        $subject_template = !empty($form_settings['notify_subject'])
            ? $form_settings['notify_subject']
            : sprintf(__('New Form Submission: %s', 'syntekpro-forms'), (string)($form->title ?? ''));
        $subject = $this->replace_merge_tags($subject_template, $data, $form, $entry_id);
        
        // Build message – use custom template if set, otherwise default
        if (!empty($form_settings['notify_message_template'])) {
            $message = $this->wrap_html($this->replace_merge_tags($form_settings['notify_message_template'], $data, $form, $entry_id));
        } else {
            $message = $this->build_notification_message($form, $data, $entry_id);
        }
        
        // Headers
        $from_name = !empty($form_settings['notify_from_name']) ? $form_settings['notify_from_name'] : ((string)($settings['from_name'] ?? ''));
        $from_email = !empty($form_settings['notify_from_email']) ? $form_settings['notify_from_email'] : ((string)($settings['from_email'] ?? ''));
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
        );
        if (!empty($from_name) && !empty($from_email)) {
            $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        }

        // Reply-To: use submitter email if found
        $reply_to = $this->find_email_in_data($data);
        if (!empty($reply_to)) {
            $headers[] = 'Reply-To: ' . $reply_to;
        }
        
        // Send email
        $sent = wp_mail($to, $subject, $message, $headers);
        if ( ! $sent ) {
            SyntekPro_Forms_Builder::log_error(
                sprintf( 'Admin notification failed for form #%d, entry #%d, recipients: %s', $form->id, $entry_id, $to ),
                'email',
                true
            );
        }
    }
    
    /**
     * Build notification message
     */
    private function build_notification_message($form, $data, $entry_id) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #0073aa; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                .field { margin-bottom: 15px; padding: 10px; background: white; border-left: 3px solid #0073aa; }
                .field-label { font-weight: bold; color: #0073aa; margin-bottom: 5px; }
                .field-value { color: #333; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2><?php echo esc_html((string)($form->title ?? '')); ?></h2>
                    <p><?php _e('New Form Submission', 'syntekpro-forms'); ?></p>
                </div>
                <div class="content">
                    <p><strong><?php _e('Submission Details:', 'syntekpro-forms'); ?></strong></p>
                    <?php foreach ($data as $field_name => $field_value): ?>
                        <div class="field">
                            <div class="field-label"><?php echo esc_html(ucfirst(str_replace('_', ' ', (string)$field_name))); ?>:</div>
                            <div class="field-value">
                                <?php 
                                if (is_array($field_value)) {
                                    echo esc_html(implode(', ', $field_value));
                                } else {
                                    echo nl2br(esc_html((string)$field_value));
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <p style="margin-top: 20px;">
                        <strong><?php _e('Entry ID:', 'syntekpro-forms'); ?></strong> <?php echo (int)$entry_id; ?><br>
                        <strong><?php _e('Submitted:', 'syntekpro-forms'); ?></strong> <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format')); ?>
                    </p>
                    
                    <p style="text-align: center; margin-top: 20px;">
                        <a href="<?php echo admin_url('admin.php?page=syntekpro-forms-entries'); ?>" 
                           style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block;">
                            <?php _e('View All Entries', 'syntekpro-forms'); ?>
                        </a>
                    </p>
                </div>
                <div class="footer">
                    <p><?php echo get_bloginfo('name'); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Send user confirmation email
     */
    public function send_user_confirmation($form, $data) {
        $form_settings = json_decode((string)($form->settings ?? '{}'), true);
        
        // Check if user confirmation is enabled
        if (!isset($form_settings['send_confirmation']) || !$form_settings['send_confirmation']) {
            return;
        }
        
        // Get user email from form data
        $user_email = '';
        foreach ($data as $field_name => $field_value) {
            if (!empty($field_name) && strpos(strtolower((string)$field_name), 'email') !== false) {
                $user_email = $field_value;
                break;
            }
        }
        
        if (empty($user_email) || !is_email((string)$user_email)) {
            return;
        }
        
        $settings = get_option('spf_settings');
        
        // Subject
        $subject = isset($form_settings['confirmation_subject']) 
            ? (string)$form_settings['confirmation_subject'] 
            : sprintf(__('Thank you for your submission: %s', 'syntekpro-forms'), (string)($form->title ?? ''));
        
        // Message
        $message = isset($form_settings['confirmation_message']) 
            ? (string)$form_settings['confirmation_message'] 
            : $this->build_confirmation_message($form, $data);
        
        // Headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . ((string)($settings['from_name'] ?? '')) . ' <' . ((string)($settings['from_email'] ?? '')) . '>'
        );
        
        // Send email
        $sent = wp_mail($user_email, $subject, $message, $headers);
        if ( ! $sent ) {
            SyntekPro_Forms_Builder::log_error(
                sprintf( 'User confirmation email failed for %s (form #%d)', $user_email, $form->id ),
                'email',
                true
            );
        }
    }
    
    /**
     * Build confirmation message
     */
    private function build_confirmation_message($form, $data) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #0073aa; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2><?php _e('Thank You!', 'syntekpro-forms'); ?></h2>
                </div>
                <div class="content">
                    <p><?php printf(__('Thank you for submitting the %s form.', 'syntekpro-forms'), '<strong>' . esc_html((string)($form->title ?? '')) . '</strong>'); ?></p>
                    <p><?php _e('We have received your submission and will get back to you soon.', 'syntekpro-forms'); ?></p>
                </div>
                <div class="footer">
                    <p><?php echo get_bloginfo('name'); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Parse and sanitize a list of email recipients
     */
    private function parse_recipient_list($raw) {
        if (empty($raw)) {
            return array();
        }

        $candidates = preg_split('/[,\n]+/', (string)$raw);
        $emails = array();

        foreach ((array)$candidates as $candidate) {
            $candidate = trim($candidate);
            if (empty($candidate)) {
                continue;
            }

            if (is_email($candidate)) {
                $emails[] = $candidate;
            }
        }

        return array_values(array_unique($emails));
    }

    /**
     * Get additional recipients based on field-value routing rules.
     *
     * Form setting format (JSON array):
     *   email_routing_rules: [
     *     { "field": "department", "value": "sales", "email": "sales@example.com" },
     *     { "field": "department", "value": "support", "email": "support@example.com" }
     *   ]
     *
     * @param array $form_settings Decoded form settings.
     * @param array $data          Submitted field data.
     * @return array               Additional email addresses.
     */
    private function get_routing_recipients($form_settings, $data) {
        $rules = !empty($form_settings['email_routing_rules']) ? $form_settings['email_routing_rules'] : array();

        if (is_string($rules)) {
            $rules = json_decode($rules, true);
        }

        if (!is_array($rules)) {
            return array();
        }

        $emails = array();
        foreach ($rules as $rule) {
            if (empty($rule['field']) || !isset($rule['value']) || empty($rule['email'])) {
                continue;
            }

            $field_name = sanitize_text_field($rule['field']);
            $expected   = sanitize_text_field($rule['value']);
            $submitted  = isset($data[$field_name]) ? (is_array($data[$field_name]) ? implode(',', $data[$field_name]) : (string)$data[$field_name]) : '';

            if (strcasecmp(trim($submitted), trim($expected)) === 0 && is_email($rule['email'])) {
                $emails[] = sanitize_email($rule['email']);
            }
        }

        return $emails;
    }

    /**
     * Replace merge tags like {field_name}, {form_title}, {entry_id}, {date}.
     *
     * @param string $template Template string with merge tags.
     * @param array  $data     Submitted data.
     * @param object $form     Form object.
     * @param int    $entry_id Entry ID.
     * @return string
     */
    private function replace_merge_tags($template, $data, $form, $entry_id) {
        $template = str_replace('{form_title}', esc_html((string)($form->title ?? '')), $template);
        $template = str_replace('{entry_id}', (int)$entry_id, $template);
        $template = str_replace('{date}', date_i18n(get_option('date_format') . ' ' . get_option('time_format')), $template);
        $template = str_replace('{site_name}', get_bloginfo('name'), $template);
        $template = str_replace('{admin_email}', get_option('admin_email'), $template);
        $template = str_replace('{entries_url}', admin_url('admin.php?page=syntekpro-forms-entries'), $template);

        // {all_fields} – render all submitted fields as an HTML table
        if (strpos($template, '{all_fields}') !== false) {
            $all = '<table cellpadding="6" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;">';
            foreach ($data as $key => $value) {
                $label = esc_html(ucfirst(str_replace('_', ' ', $key)));
                $val   = is_array($value) ? esc_html(implode(', ', $value)) : nl2br(esc_html((string)$value));
                $all  .= '<tr><td style="border-bottom:1px solid #eee;font-weight:bold;vertical-align:top;padding:8px;width:180px;">' . $label . '</td>';
                $all  .= '<td style="border-bottom:1px solid #eee;padding:8px;">' . $val . '</td></tr>';
            }
            $all .= '</table>';
            $template = str_replace('{all_fields}', $all, $template);
        }

        // Replace individual field tags: {field_name}
        foreach ($data as $key => $value) {
            $tag = '{' . $key . '}';
            if (strpos($template, $tag) !== false) {
                $val = is_array($value) ? implode(', ', $value) : (string)$value;
                $template = str_replace($tag, esc_html($val), $template);
            }
        }

        return $template;
    }

    /**
     * Wrap raw HTML content in a basic email HTML structure.
     */
    private function wrap_html($content) {
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;color:#333;line-height:1.6;">' . wp_kses_post($content) . '</body></html>';
    }

    /**
     * Find the first email value in submitted data.
     */
    private function find_email_in_data($data) {
        foreach ((array)$data as $key => $value) {
            if (!empty($key) && strpos(strtolower((string)$key), 'email') !== false && is_email((string)$value)) {
                return sanitize_email($value);
            }
        }
        return '';
    }
}

function SPF_email_templates() {
    return SPF_Email_Templates::get_instance();
}

SPF_email_templates();