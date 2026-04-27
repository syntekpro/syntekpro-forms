<?php
/**
 * Preview Links - Generate shareable form preview URLs
 *
 * @package SyntekPro_Forms
 * @since 2.0.0
 */

class SyntekPro_Forms_Preview_Links {

    public static function create_preview_link($form_id, $expiry_hours = 24) {
        global $wpdb;
        
        if (!current_user_can('spf_manage_forms')) {
            return new WP_Error('unauthorized', __('You do not have permission to create preview links.', 'syntekpro-forms'));
        }
        
        $token = wp_generate_password(32, false);
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_hours} hours"));
        
        $wpdb->insert(
            $wpdb->prefix . 'spf_preview_links',
            array(
                'form_id' => $form_id,
                'token' => $token,
                'expires_at' => $expires_at,
                'created_by' => get_current_user_id(),
            ),
            array('%d', '%s', '%s', '%d')
        );
        
        $preview_url = add_query_arg(
            array('preview_token' => $token),
            home_url('/')
        );
        
        return array(
            'link_id' => $wpdb->insert_id,
            'token' => $token,
            'preview_url' => $preview_url,
            'expires_at' => $expires_at,
        );
    }
    
    public static function validate_preview_token($token) {
        global $wpdb;
        
        $link = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spf_preview_links 
             WHERE token = %s 
             AND expires_at > NOW()",
            $token
        ));
        
        return $link ? $link : false;
    }
    
    public static function get_form_preview_links($form_id) {
        global $wpdb;
        
        $links = $wpdb->get_results($wpdb->prepare(
            "SELECT id, token, expires_at, created_by, created_at 
             FROM {$wpdb->prefix}spf_preview_links 
             WHERE form_id = %d 
             ORDER BY created_at DESC",
            $form_id
        ));
        
        return $links ?: array();
    }
    
    public static function revoke_preview_link($link_id) {
        global $wpdb;
        
        return $wpdb->delete(
            $wpdb->prefix . 'spf_preview_links',
            array('id' => $link_id),
            array('%d')
        );
    }
    
    public static function cleanup_expired_links() {
        global $wpdb;
        
        return $wpdb->query(
            "DELETE FROM {$wpdb->prefix}spf_preview_links 
             WHERE expires_at < NOW()"
        );
    }
}
