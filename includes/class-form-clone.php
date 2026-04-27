<?php
/**
 * Form Cloning - Duplicate forms with all fields and settings
 *
 * @package SyntekPro_Forms
 * @since 2.0.0
 */

class SyntekPro_Forms_Clone {

    public static function clone_form($form_id) {
        global $wpdb;
        
        if (!current_user_can('spf_manage_forms')) {
            return new WP_Error('unauthorized', __('You do not have permission to clone forms.', 'syntekpro-forms'));
        }
        
        $original_form = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spf_forms WHERE id = %d",
            $form_id
        ));
        
        if (!$original_form) {
            return new WP_Error('not_found', __('Form not found.', 'syntekpro-forms'));
        }
        
        // Create cloned form
        $cloned_title = $original_form->title . ' (Copy)';
        $wpdb->insert(
            $wpdb->prefix . 'spf_forms',
            array(
                'title' => $cloned_title,
                'description' => $original_form->description,
                'fields' => $original_form->fields,
                'settings' => $original_form->settings,
                'status' => 'draft',
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
        
        $cloned_form_id = $wpdb->insert_id;
        
        // Log action
        if (function_exists('spf_log_audit')) {
            spf_log_audit($cloned_form_id, 'form_cloned', array(
                'cloned_from' => $form_id,
                'original_title' => $original_form->title,
            ));
        }
        
        return array(
            'success' => true,
            'cloned_form_id' => $cloned_form_id,
            'cloned_title' => $cloned_title,
        );
    }
}
