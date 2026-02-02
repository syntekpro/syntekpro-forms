<?php
/**
 * File Upload Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class SPF_File_Handler {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'setup_upload_dir'));
    }
    
    /**
     * Setup custom upload directory for form files
     */
    public function setup_upload_dir() {
        $upload_dir = wp_upload_dir();
        $custom_dir = $upload_dir['basedir'] . '/advanced-forms';
        
        if (!file_exists($custom_dir)) {
            wp_mkdir_p($custom_dir);
            
            // Add .htaccess for security
            $htaccess = $custom_dir . '/.htaccess';
            if (!file_exists($htaccess)) {
                file_put_contents($htaccess, 'Options -Indexes');
            }
        }
    }
    
    /**
     * Handle file upload
     */
    public function handle_upload($file, $field_name) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $upload_overrides = array(
            'test_form' => false,
            'upload_path' => wp_upload_dir()['basedir'] . '/advanced-forms'
        );
        
        $uploaded_file = wp_handle_upload($file, $upload_overrides);
        
        if (isset($uploaded_file['error'])) {
            return new WP_Error('upload_error', $uploaded_file['error']);
        }
        
        return $uploaded_file;
    }
    
    /**
     * Validate file type
     */
    public function validate_file_type($file, $allowed_types = array()) {
        if (empty($allowed_types)) {
            $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx');
        }
        
        $file_type = wp_check_filetype($file['name']);
        $extension = $file_type['ext'];
        
        if (!in_array($extension, $allowed_types)) {
            return new WP_Error('invalid_file_type', __('File type not allowed', 'advanced-forms'));
        }
        
        return true;
    }
    
    /**
     * Validate file size
     */
    public function validate_file_size($file, $max_size = 5242880) { // 5MB default
        if ($file['size'] > $max_size) {
            return new WP_Error('file_too_large', __('File size exceeds maximum allowed', 'advanced-forms'));
        }
        
        return true;
    }
}

function SPF_file_handler() {
    return SPF_File_Handler::get_instance();
}

SPF_file_handler();