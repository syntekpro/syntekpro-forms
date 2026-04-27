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
        $custom_dir = $upload_dir['basedir'] . '/syntekpro-forms';
        
        if (!file_exists($custom_dir)) {
            wp_mkdir_p($custom_dir);
            
            // Add .htaccess for security (Apache)
            $htaccess = $custom_dir . '/.htaccess';
            if (!file_exists($htaccess)) {
                file_put_contents($htaccess, 'Options -Indexes');
            }

            // Add index.php for directory listing protection (Nginx and others)
            $index = $custom_dir . '/index.php';
            if (!file_exists($index)) {
                file_put_contents($index, '<?php // Silence is golden.');
            }
        }
    }

    /**
     * Get the custom upload directory path.
     */
    public function get_upload_dir() {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . '/syntekpro-forms';
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
            'upload_path' => $this->get_upload_dir()
        );
        
        $uploaded_file = wp_handle_upload($file, $upload_overrides);
        
        if (isset($uploaded_file['error'])) {
            return new WP_Error('upload_error', $uploaded_file['error']);
        }
        
        return $uploaded_file;
    }

    /**
     * Handle multiple file uploads for a single field.
     *
     * @param array  $files      PHP $_FILES-style array (multi-file).
     * @param string $field_name Field name.
     * @return array|WP_Error Array of upload results or first error.
     */
    public function handle_multi_upload($files, $field_name) {
        $results = array();

        if (empty($files['name']) || !is_array($files['name'])) {
            return array($this->handle_upload($files, $field_name));
        }

        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if (empty($files['tmp_name'][$i])) {
                continue;
            }

            $single = array(
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            );

            $type_check = $this->validate_file_type($single);
            if (is_wp_error($type_check)) {
                return $type_check;
            }

            $size_check = $this->validate_file_size($single);
            if (is_wp_error($size_check)) {
                return $size_check;
            }

            $uploaded = $this->handle_upload($single, $field_name);
            if (is_wp_error($uploaded)) {
                return $uploaded;
            }

            $results[] = $uploaded;
        }

        return $results;
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
            return new WP_Error('invalid_file_type', __('File type not allowed', 'syntekpro-forms'));
        }
        
        return true;
    }
    
    /**
     * Validate file size
     */
    public function validate_file_size($file, $max_size = 5242880) { // 5MB default
        if ($file['size'] > $max_size) {
            return new WP_Error('file_too_large', __('File size exceeds maximum allowed', 'syntekpro-forms'));
        }
        
        return true;
    }
}

function SPF_file_handler() {
    return SPF_File_Handler::get_instance();
}

SPF_file_handler();