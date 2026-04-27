<?php
/**
 * Form Audit Log - Track all form modifications
 *
 * @package SyntekPro_Forms
 * @since 2.0.0
 */

class SyntekPro_Forms_Audit_Log {

    public static function log_action($form_id, $action, $changes = array()) {
        global $wpdb;
        
        if (!apply_filters('spf_enable_audit_logging', true)) {
            return false;
        }
        
        $user_id = get_current_user_id();
        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        
        $wpdb->insert(
            $wpdb->prefix . 'spf_audit_log',
            array(
                'form_id' => intval($form_id),
                'user_id' => $user_id,
                'action' => sanitize_text_field($action),
                'changes' => wp_json_encode($changes),
                'ip_address' => $ip_address,
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    public static function get_form_audit_log($form_id, $limit = 50, $offset = 0) {
        global $wpdb;
        
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spf_audit_log 
             WHERE form_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            $form_id,
            $limit,
            $offset
        ));
        
        return $logs ? array_map(function($log) {
            $log->changes = json_decode($log->changes, true);
            return $log;
        }, $logs) : array();
    }
    
    public static function get_audit_count($form_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}spf_audit_log WHERE form_id = %d",
            $form_id
        ));
    }
}

// Helper function
function spf_log_audit($form_id, $action, $changes = array()) {
    return SyntekPro_Forms_Audit_Log::log_action($form_id, $action, $changes);
}
