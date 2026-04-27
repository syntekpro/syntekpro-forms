<?php
/**
 * Form Backup - Automatic and manual form backups
 *
 * @package SyntekPro_Forms
 * @since 2.0.0
 */

class SyntekPro_Forms_Backup {

    public static function backup_form($form_id, $created_by = null) {
        global $wpdb;
        
        if (!$created_by) {
            $created_by = get_current_user_id();
        }
        
        $form = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spf_forms WHERE id = %d",
            $form_id
        ));
        
        if (!$form) {
            return new WP_Error('not_found', __('Form not found.', 'syntekpro-forms'));
        }
        
        $backup_data = array(
            'form_id' => $form->id,
            'title' => $form->title,
            'description' => $form->description,
            'fields' => json_decode($form->fields, true),
            'settings' => json_decode($form->settings, true),
            'backup_timestamp' => current_time('mysql'),
        );
        
        $wpdb->insert(
            $wpdb->prefix . 'spf_form_backups',
            array(
                'form_id' => $form_id,
                'backup_data' => wp_json_encode($backup_data),
                'created_by' => $created_by,
            ),
            array('%d', '%s', '%d')
        );
        
        return $wpdb->insert_id;
    }
    
    public static function get_form_backups($form_id, $limit = 10) {
        global $wpdb;
        
        $backups = $wpdb->get_results($wpdb->prepare(
            "SELECT id, form_id, backup_date, created_by, restored_at 
             FROM {$wpdb->prefix}spf_form_backups 
             WHERE form_id = %d 
             ORDER BY backup_date DESC 
             LIMIT %d",
            $form_id,
            $limit
        ));
        
        return $backups ?: array();
    }
    
    public static function restore_backup($backup_id) {
        global $wpdb;
        
        if (!current_user_can('spf_manage_forms')) {
            return new WP_Error('unauthorized', __('You do not have permission to restore backups.', 'syntekpro-forms'));
        }
        
        $backup = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spf_form_backups WHERE id = %d",
            $backup_id
        ));
        
        if (!$backup) {
            return new WP_Error('not_found', __('Backup not found.', 'syntekpro-forms'));
        }
        
        $backup_data = json_decode($backup->backup_data, true);
        
        $wpdb->update(
            $wpdb->prefix . 'spf_forms',
            array(
                'title' => $backup_data['title'],
                'description' => $backup_data['description'],
                'fields' => wp_json_encode($backup_data['fields']),
                'settings' => wp_json_encode($backup_data['settings']),
            ),
            array('id' => $backup->form_id),
            array('%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        $wpdb->update(
            $wpdb->prefix . 'spf_form_backups',
            array('restored_at' => current_time('mysql')),
            array('id' => $backup_id),
            array('%s'),
            array('%d')
        );
        
        return true;
    }
    
    public static function cleanup_old_backups($retention_days = 30) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}spf_form_backups 
             WHERE backup_date < %s AND restored_at IS NULL",
            $cutoff_date
        ));
    }
}
