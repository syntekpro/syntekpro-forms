<?php
/**
 * Plugin Name: SyntekPro Forms
 * Plugin URI: https://github.com/yourusername/syntekpro-forms
 * Description: A custom form builder plugin for WordPress
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://syntekpro.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: syntekpro-forms
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Plugin main code will go here
add_action('admin_menu', 'syntekpro_forms_menu');

function syntekpro_forms_menu() {
    add_menu_page(
        'SyntekPro Forms',
        'SyntekPro Forms',
        'manage_options',
        'syntekpro-forms',
        'syntekpro_forms_page',
        'dashicons-feedback',
        30
    );
}

function syntekpro_forms_page() {
    echo '<div class="wrap">';
    echo '<h1>SyntekPro Forms</h1>';
    echo '<p>Welcome to SyntekPro Forms plugin!</p>';
    echo '</div>';
}