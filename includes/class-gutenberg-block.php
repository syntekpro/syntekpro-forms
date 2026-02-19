<?php
/**
 * Gutenberg Block Support for SyntekPro Forms
 */

if (!defined('ABSPATH')) {
    exit;
}

class SyntekPro_Forms_Gutenberg {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'register_block'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
    }
    
    /**
     * Register the Gutenberg block
     */
    public function register_block() {
        if (!function_exists('register_block_type')) {
            return;
        }
        
        register_block_type('syntekpro-forms/form-selector', array(
            'attributes' => array(
                'formId' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'showTitle' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showDescription' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'theme' => array(
                    'type' => 'string',
                    'default' => 'classic'
                ),
                'inputSize' => array(
                    'type' => 'string',
                    'default' => 'medium'
                ),
                'inputBgColor' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'inputBorderColor' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'inputTextColor' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'inputAccentColor' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'labelFontSize' => array(
                    'type' => 'number',
                    'default' => 16
                ),
                'labelTextColor' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'descriptionFontSize' => array(
                    'type' => 'number',
                    'default' => 14
                ),
                'descriptionTextColor' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'buttonBgColor' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'buttonTextColor' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'preview' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'ajax' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'tabindex' => array(
                    'type' => 'number',
                    'default' => 0
                ),
                'fieldValues' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'primaryColor' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'labelColor' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'bgColor' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'borderRadius' => array(
                    'type' => 'number',
                    'default' => 0
                ),
                'fieldPadding' => array(
                    'type' => 'number',
                    'default' => 0
                ),
                'fontFamily' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'submitAlign' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'titleAlign' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'descriptionAlign' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'labelAlign' => array(
                    'type' => 'string',
                    'default' => ''
                )
            ),
            'render_callback' => array($this, 'render_block')
        ));
    }
    
    /**
     * Render the block on the frontend
     */
    public function render_block($attributes) {
        if (empty($attributes['formId'])) {
            return '<p>' . __('Please select a form.', 'syntekpro-forms') . '</p>';
        }
        
        $form_id = intval($attributes['formId']);
        
        ob_start();
        $atts = array_merge(array('id' => $form_id), $attributes);
        $view_file = SPF_PLUGIN_DIR . 'includes/frontend/form-display.php';
        if (file_exists($view_file)) {
            include $view_file;
        }
        return ob_get_clean();
    }
    
    /**
     * Enqueue block editor assets
     */
    public function enqueue_block_editor_assets() {
        wp_enqueue_script(
            'syntekpro-forms-block',
            SPF_PLUGIN_URL . 'assets/js/gutenberg-block.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render'),
            SPF_VERSION,
            true
        );
        
        global $wpdb;
        $forms = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}spf_forms WHERE status = 'active' ORDER BY title ASC");
        
        $forms_list = array();
        foreach ($forms as $form) {
            $forms_list[] = array(
                'value' => (string)$form->id,
                'label' => $form->title
            );
        }
        
        wp_localize_script('syntekpro-forms-block', 'syntekproFormsData', array(
            'forms' => $forms_list,
            'pluginUrl' => SPF_PLUGIN_URL
        ));
        
        wp_enqueue_style(
            'syntekpro-forms-block-editor',
            SPF_PLUGIN_URL . 'assets/css/gutenberg-block.css',
            array('wp-edit-blocks'),
            SPF_VERSION
        );

        wp_enqueue_style(
            'syntekpro-forms-frontend',
            SPF_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            SPF_VERSION
        );
    }
}

function syntekpro_forms_gutenberg_init() {
    return SyntekPro_Forms_Gutenberg::get_instance();
}

add_action('plugins_loaded', 'syntekpro_forms_gutenberg_init');