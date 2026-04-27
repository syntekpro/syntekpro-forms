<?php
/**
 * SyntekPro Forms - PII Field Masking (Phase 2)
 * 
 * Fine-grained PII field masking UI and logic for protecting sensitive data
 * in entry displays. Phase 2 architectural stub.
 * 
 * @package SyntekPro_Forms
 * @subpackage PII_Masking
 */

if (!defined('ABSPATH')) {
    exit;
}

class SyntekPro_Forms_PII_Masking {

    const MASKING_STRATEGIES = array(
        'none' => 'No masking',
        'full' => 'Full mask (***)',
        'partial' => 'Partial mask (show first/last 4)',
        'last4' => 'Show last 4 characters only',
        'initials' => 'Show initials only (names)',
        'email_mask' => 'Mask email domain',
        'hash' => 'Replace with hash value',
    );

    /**
     * Get PII field configuration for form
     * 
     * @param int $form_id Form ID
     * @return array|WP_Error PII settings and masked fields
     */
    public static function get_pii_config($form_id) {
        global $wpdb;

        if (!current_user_can('spf_view_entries')) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        // TODO: Phase 2 Implementation
        // Get form fields
        // Query database for PII designations
        // Return:
        //   - fields with pii_status: not_pii, designated_pii, auto_detected_pii
        //   - masking_strategy per PII field
        //   - who can view unmasked (roles/capabilities)
        
        return array(
            'status' => 'stub',
            'message' => 'PII masking UI available in Phase 2.4',
        );
    }

    /**
     * Designate field as PII
     * 
     * @param int $form_id Form ID
     * @param int $field_id Field ID
     * @param string $masking_strategy Masking strategy (see MASKING_STRATEGIES)
     * @return bool|WP_Error Success or error
     */
    public static function designate_pii_field($form_id, $field_id, $masking_strategy = 'partial') {
        if (!current_user_can('spf_manage_forms')) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        if (!isset(self::MASKING_STRATEGIES[$masking_strategy])) {
            return new WP_Error('invalid_strategy', __('Invalid masking strategy', 'syntekpro-forms'));
        }

        // TODO: Phase 2 Implementation
        // Store field_id as PII with masking_strategy
        // Log change to audit log
        // Clear entry display caches
        
        return new WP_Error('stub', __('PII designation available in Phase 2.4', 'syntekpro-forms'));
    }

    /**
     * Remove PII designation from field
     * 
     * @param int $form_id Form ID
     * @param int $field_id Field ID
     * @return bool|WP_Error Success or error
     */
    public static function remove_pii_designation($form_id, $field_id) {
        if (!current_user_can('spf_manage_forms')) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        // TODO: Phase 2 Implementation
        // Remove PII designation
        // Log change to audit log
        // Clear caches
        
        return new WP_Error('stub', __('PII management available in Phase 2.4', 'syntekpro-forms'));
    }

    /**
     * Apply masking to entry field value
     * 
     * @param string $value Field value to mask
     * @param string $masking_strategy Strategy to apply
     * @return string Masked value
     */
    public static function apply_masking($value, $masking_strategy) {
        if (!in_array($masking_strategy, array_keys(self::MASKING_STRATEGIES))) {
            return $value; // Return unmasked if invalid strategy
        }

        switch ($masking_strategy) {
            case 'full':
                return '***';
            
            case 'partial':
                // Show first 4 and last 4, mask middle
                if (strlen($value) > 8) {
                    return substr($value, 0, 4) . '*' . substr($value, -4);
                }
                return '****';
            
            case 'last4':
                // Show only last 4 characters
                if (strlen($value) > 4) {
                    return str_repeat('*', strlen($value) - 4) . substr($value, -4);
                }
                return $value;
            
            case 'initials':
                // For names, show initials only (First Last -> F. L.)
                $parts = explode(' ', trim($value));
                $initials = array_map(function($part) {
                    return strtoupper(substr($part, 0, 1)) . '.';
                }, $parts);
                return implode(' ', $initials);
            
            case 'email_mask':
                // mask@example.com -> m***@example.com
                if (strpos($value, '@') !== false) {
                    list($local, $domain) = explode('@', $value);
                    return substr($local, 0, 1) . str_repeat('*', strlen($local) - 1) . '@' . $domain;
                }
                return $value;
            
            case 'hash':
                // Replace with SHA256 hash for consistency
                return 'hash_' . substr(hash('sha256', $value), 0, 8);
            
            case 'none':
            default:
                return $value;
        }
    }

    /**
     * Get masked entry data for display
     * 
     * @param int $entry_id Entry ID
     * @param int $form_id Form ID
     * @param int|null $user_id User requesting data (null = current user)
     * @return array|WP_Error Entry data with PII fields masked
     */
    public static function get_masked_entry($entry_id, $form_id, $user_id = null) {
        global $wpdb;

        if (!current_user_can('spf_view_entries')) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        $user_id = $user_id ?? get_current_user_id();

        // TODO: Phase 2 Implementation
        // Get entry from database
        // Get PII field configuration for form
        // For each field:
        //   - If PII designated: Check user can view unmasked
        //   - If yes: Return unmasked value
        //   - If no: Apply masking strategy
        // Return modified entry data
        
        return array('status' => 'stub');
    }

    /**
     * Check if user can view unmasked PII
     * 
     * @param int $form_id Form ID
     * @param int $user_id User ID to check
     * @return bool True if user can view unmasked PII
     */
    public static function can_view_unmasked_pii($form_id, $user_id = null) {
        $user_id = $user_id ?? get_current_user_id();

        // TODO: Phase 2 Implementation
        // Check user capabilities:
        //   - spf_manage_forms: Always can view
        //   - spf_view_entries: Check form settings (can_view_pii_setting)
        //   - Specific role/capability assigned to form
        
        return current_user_can('spf_manage_forms');
    }

    /**
     * Auto-detect common PII field names
     * 
     * @param int $form_id Form ID
     * @return array Array of field IDs likely to contain PII
     */
    public static function auto_detect_pii_fields($form_id) {
        global $wpdb;

        // TODO: Phase 2 Implementation
        // Get form fields
        // Match field labels/names against common PII patterns:
        //   - "phone", "telephone", "mobile" -> phone number
        //   - "email", "e-mail" -> email
        //   - "ssn", "social security", "tax id" -> SSN/TIN
        //   - "credit card", "card number" -> payment card
        //   - "password", "secret" -> password
        //   - "home address", "street address" -> address
        //   - "date of birth", "dob" -> DOB
        //   - "first name", "last name", "full name" -> name
        // Return array of field IDs suspected to be PII
        
        return array();
    }

    /**
     * Set PII viewing rules for role
     * 
     * @param int $form_id Form ID
     * @param string $role WordPress role
     * @param bool $can_view Whether role can view unmasked PII
     * @return bool|WP_Error Success or error
     */
    public static function set_pii_role_permission($form_id, $role, $can_view) {
        if (!current_user_can('spf_manage_forms')) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        // TODO: Phase 2 Implementation
        // Store permission: form_id, role, can_view_unmasked_pii
        // Log change to audit log
        
        return new WP_Error('stub', __('PII role permissions available in Phase 2.4', 'syntekpro-forms'));
    }

    /**
     * Get masking strategies available
     * 
     * @return array Available masking strategies with descriptions
     */
    public static function get_masking_strategies() {
        return self::MASKING_STRATEGIES;
    }
}
