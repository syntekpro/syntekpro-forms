<?php
/**
 * SyntekPro Forms - JavaScript SDK (Phase 2)
 * 
 * Framework for publishing and managing @syntekpro/forms-js NPM SDK
 * for headless form rendering and integration. Phase 2 architectural stub.
 * 
 * @package SyntekPro_Forms
 * @subpackage JavaScript_SDK
 */

if (!defined('ABSPATH')) {
    exit;
}

class SyntekPro_Forms_JavaScript_SDK {

    /**
     * Get SDK configuration for form
     * 
     * @param int $form_id Form ID
     * @return array|WP_Error SDK configuration (API key, form data, settings)
     */
    public static function get_sdk_config($form_id) {
        global $wpdb;

        // TODO: Phase 2 Implementation
        // Get form data (fields, settings, validation rules)
        // Generate/retrieve SDK API key for this form
        // Return configuration object with:
        //   - form_id, form_title, form_description
        //   - fields array with validation rules, labels, placeholders
        //   - settings (submit_button_text, success_message, redirect_url, etc)
        //   - api_endpoint for form submission
        //   - theme/styling configuration
        
        return array(
            'status' => 'stub',
            'message' => 'JavaScript SDK available in Phase 2.4 as @syntekpro/forms-js on NPM',
        );
    }

    /**
     * Generate SDK API key for form
     * 
     * @param int $form_id Form ID
     * @param string $domain_whitelist Comma-separated domains allowed to use key
     * @return string|WP_Error Generated API key
     */
    public static function generate_sdk_api_key($form_id, $domain_whitelist = '*') {
        if (!current_user_can('spf_manage_forms')) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        // TODO: Phase 2 Implementation
        // Generate secure API key (random 32+ character string)
        // Hash key for storage
        // Store in database:
        //   - form_id, api_key_hash, domain_whitelist, created_at, created_by
        //   - expires_at (optional, for time-limited keys)
        // Return generated key (only shown once for security)
        
        return new WP_Error('stub', __('SDK key generation available in Phase 2.4', 'syntekpro-forms'));
    }

    /**
     * Validate SDK API key
     * 
     * @param string $api_key API key to validate
     * @param string $domain Domain making the request
     * @return int|WP_Error Form ID if valid, error otherwise
     */
    public static function validate_sdk_api_key($api_key, $domain) {
        global $wpdb;

        // TODO: Phase 2 Implementation
        // Hash provided API key
        // Query database for matching key hash
        // Validate key not expired
        // Validate domain is in domain_whitelist (or wildcard)
        // Update last_used timestamp
        // Return form_id if valid, WP_Error if invalid/expired/domain mismatch
        
        return new WP_Error('invalid_key', __('Invalid or expired API key', 'syntekpro-forms'));
    }

    /**
     * Revoke SDK API key
     * 
     * @param int $form_id Form ID
     * @param string $api_key API key to revoke
     * @return bool|WP_Error Success or error
     */
    public static function revoke_sdk_api_key($form_id, $api_key) {
        if (!current_user_can('spf_manage_forms')) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        // TODO: Phase 2 Implementation
        // Hash provided key
        // Delete from database
        // Log revocation to audit log
        
        return new WP_Error('stub', __('Key revocation available in Phase 2.4', 'syntekpro-forms'));
    }

    /**
     * Handle SDK form submission via API
     * 
     * This endpoint is called by SDK when user submits form via JavaScript.
     * 
     * @param string $api_key SDK API key
     * @param array $entry_data Form entry data
     * @param string $domain Domain submitting from
     * @return array|WP_Error Result (success, entry_id, redirect_url, etc)
     */
    public static function handle_sdk_submission($api_key, $entry_data, $domain) {
        // Validate API key
        $form_id = self::validate_sdk_api_key($api_key, $domain);
        if (is_wp_error($form_id)) {
            return $form_id;
        }

        // TODO: Phase 2 Implementation
        // Load form definition
        // Validate entry_data against form fields
        // Run spam checks, conditionals, etc
        // Create entry record in database
        // Send confirmation emails
        // Trigger integrations
        // Return success with: entry_id, success_message, redirect_url
        
        return new WP_Error('stub', __('SDK submission endpoint available in Phase 2.4', 'syntekpro-forms'));
    }

    /**
     * Get SDK documentation
     * 
     * @return string HTML documentation for SDK usage
     */
    public static function get_sdk_docs() {
        // TODO: Phase 2 Implementation
        // Return comprehensive SDK documentation:
        //   - Installation: npm install @syntekpro/forms-js
        //   - Basic usage example (React, Vue, Vanilla JS)
        //   - API configuration and options
        //   - Event listeners (onSubmit, onError, onValidation, etc)
        //   - Styling and theming
        //   - TypeScript types
        //   - Browser compatibility
        
        return <<<'DOCS'
<h2>SyntekPro Forms JavaScript SDK</h2>
<p>Embed forms headlessly in any JavaScript application.</p>
<h3>Installation</h3>
<pre>npm install @syntekpro/forms-js</pre>
<h3>Usage</h3>
<pre>
import { SyntekProForm } from '@syntekpro/forms-js';

const form = new SyntekProForm({
  apiKey: 'your-sdk-api-key',
  containerId: 'form-container'
});

form.on('submit', (data) => {
  console.log('Form submitted:', data);
});
</pre>
<p>Full documentation: <a href="https://syntekpro.com/docs/sdk">https://syntekpro.com/docs/sdk</a></p>
DOCS;
    }

    /**
     * List all API keys for form (admin view)
     * 
     * @param int $form_id Form ID
     * @return array|WP_Error List of API keys with metadata
     */
    public static function list_sdk_api_keys($form_id) {
        global $wpdb;

        if (!current_user_can('spf_manage_forms')) {
            return new WP_Error('unauthorized', __('Permission denied', 'syntekpro-forms'));
        }

        // TODO: Phase 2 Implementation
        // Query database for all API keys for form_id
        // Return list with: key_preview (first 8 + last 4 chars),
        //   domain_whitelist, created_at, last_used, status (active/revoked)
        // Note: Never return full key after creation
        
        return array('keys' => array(), 'status' => 'stub');
    }
}
