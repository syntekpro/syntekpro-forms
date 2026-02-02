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
        
        $notifications_enabled = isset($form_settings['notifications_enabled']) ? (int)$form_settings['notifications_enabled'] : 1;
        if ($notifications_enabled === 0) {
            return;
        }

        $recipient_source = '';
        if (!empty($form_settings['notification_emails'])) {
            $recipient_source = $form_settings['notification_emails'];
        } elseif (!empty($form_settings['notification_email'])) {
            // Legacy key
            $recipient_source = $form_settings['notification_email'];
        }

        $recipients = $this->parse_recipient_list($recipient_source);
        if (empty($recipients)) {
            $recipients[] = get_option('admin_email');
        }

        $to = implode(',', $recipients);
        
        // Subject
        $subject = sprintf(__('New Form Submission: %s', 'syntekpro-forms'), (string)($form->title ?? ''));
        
        // Build message
        $message = $this->build_notification_message($form, $data, $entry_id);
        
        // Headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . ((string)($settings['from_name'] ?? '')) . ' <' . ((string)($settings['from_email'] ?? '')) . '>'
        );
        
        // Send email
        wp_mail($to, $subject, $message, $headers);
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
                    <p><?php echo get_bloginfo('name'); ?> | <?php _e('Powered by SyntekPro Forms', 'syntekpro-forms'); ?></p>
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
        wp_mail($user_email, $subject, $message, $headers);
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
                    <p><?php echo get_bloginfo('name'); ?> | <?php _e('Powered by SyntekPro Forms', 'syntekpro-forms'); ?></p>
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
}

function SPF_email_templates() {
    return SPF_Email_Templates::get_instance();
}

SPF_email_templates();