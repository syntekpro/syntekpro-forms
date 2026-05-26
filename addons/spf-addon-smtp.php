<?php
/**
 * Addon: SMTP & OAuth2 Delivery
 * Description: Enables SMTP transport, OAuth2 auth for Gmail/Outlook, provider presets, and email delivery logging.
 * Version: 1.0.0
 * Author: SyntekPro
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SyntekPro_Forms_SMTP')) {
    return;
}

SyntekPro_Forms_SMTP::get_instance();
