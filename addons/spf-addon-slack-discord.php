<?php
/**
 * Addon: Slack & Discord Notifications
 *
 * Sends a rich notification to a Slack or Discord webhook URL whenever
 * a form is submitted.
 *
 * Settings (per-form `settings` JSON):
 *   slack_webhook_url    – Slack Incoming Webhook URL
 *   discord_webhook_url  – Discord Webhook URL
 *
 * Global settings (spf_settings option):
 *   slack_webhook_url    – Fallback Slack URL for all forms
 *   discord_webhook_url  – Fallback Discord URL for all forms
 *
 * @package SyntekPro_Forms
 * @subpackage Addons
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SPF_Addon_Slack_Discord {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Fires after an entry is successfully saved via AJAX.
        add_action( 'syntekpro_forms_after_submission', array( $this, 'send_notifications' ), 10, 4 );
        // Fires after an entry is created via REST API.
        add_action( 'syntekpro_forms_rest_insert_entry', array( $this, 'send_notifications_rest' ), 10, 2 );
    }

    /**
     * Hook: AJAX submission path.
     *
     * @param object $form           Form row object.
     * @param array  $sanitized_data Submitted field values.
     * @param int    $entry_id       New entry ID.
     * @param array  $form_settings  Decoded form settings.
     */
    public function send_notifications( $form, $sanitized_data, $entry_id, $form_settings ) {
        $plugin_settings = get_option( 'spf_settings', array() );

        $slack_url   = ! empty( $form_settings['slack_webhook_url'] )   ? trim( $form_settings['slack_webhook_url'] )   : ( ! empty( $plugin_settings['slack_webhook_url'] ) ? trim( $plugin_settings['slack_webhook_url'] ) : '' );
        $discord_url = ! empty( $form_settings['discord_webhook_url'] ) ? trim( $form_settings['discord_webhook_url'] ) : ( ! empty( $plugin_settings['discord_webhook_url'] ) ? trim( $plugin_settings['discord_webhook_url'] ) : '' );

        if ( ! empty( $slack_url ) ) {
            $this->send_slack( $slack_url, $form, $sanitized_data, $entry_id );
        }

        if ( ! empty( $discord_url ) ) {
            $this->send_discord( $discord_url, $form, $sanitized_data, $entry_id );
        }
    }

    /**
     * Hook: REST API insertion path.
     */
    public function send_notifications_rest( $entry, $request ) {
        if ( ! is_object( $entry ) || empty( $entry->form_id ) ) {
            return;
        }

        global $wpdb;
        $form = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spf_forms WHERE id = %d",
            (int) $entry->form_id
        ) );

        if ( ! $form ) {
            return;
        }

        $form_settings  = json_decode( (string) $form->settings, true );
        $sanitized_data = is_array( $entry->entry_data ) ? $entry->entry_data : json_decode( (string) $entry->entry_data, true );
        $entry_id       = (int) $entry->id;

        $this->send_notifications( $form, (array) $sanitized_data, $entry_id, (array) $form_settings );
    }

    /**
     * Build a flat text summary of submitted data.
     *
     * @param array $data Submitted field values.
     * @return string
     */
    private function build_field_summary( $data ) {
        $lines = array();
        foreach ( (array) $data as $key => $value ) {
            $label = ucfirst( str_replace( '_', ' ', $key ) );
            $val   = is_array( $value ) ? implode( ', ', array_map( 'sanitize_text_field', $value ) ) : sanitize_text_field( (string) $value );
            $lines[] = "*{$label}:* {$val}";
        }
        return implode( "\n", $lines );
    }

    /**
     * Send a Slack notification.
     */
    private function send_slack( $url, $form, $data, $entry_id ) {
        $title   = sprintf( __( 'New Submission: %s', 'syntekpro-forms' ), (string) $form->title );
        $summary = $this->build_field_summary( $data );
        $admin_link = admin_url( 'admin.php?page=syntekpro-forms-entries' );

        $payload = array(
            'text'   => $title,
            'blocks' => array(
                array(
                    'type' => 'header',
                    'text' => array( 'type' => 'plain_text', 'text' => $title ),
                ),
                array(
                    'type' => 'section',
                    'text' => array( 'type' => 'mrkdwn', 'text' => $summary ),
                ),
                array(
                    'type'     => 'context',
                    'elements' => array(
                        array( 'type' => 'mrkdwn', 'text' => sprintf( 'Entry #%d | <%s|View Entries>', $entry_id, $admin_link ) ),
                    ),
                ),
            ),
        );

        wp_remote_post( esc_url_raw( $url ), array(
            'timeout' => 10,
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => wp_json_encode( $payload ),
        ) );
    }

    /**
     * Send a Discord notification.
     */
    private function send_discord( $url, $form, $data, $entry_id ) {
        $title      = sprintf( __( 'New Submission: %s', 'syntekpro-forms' ), (string) $form->title );
        $admin_link = admin_url( 'admin.php?page=syntekpro-forms-entries' );

        $fields = array();
        foreach ( (array) $data as $key => $value ) {
            $fields[] = array(
                'name'   => ucfirst( str_replace( '_', ' ', sanitize_text_field( $key ) ) ),
                'value'  => is_array( $value ) ? implode( ', ', array_map( 'sanitize_text_field', $value ) ) : sanitize_text_field( (string) $value ),
                'inline' => true,
            );
        }

        $payload = array(
            'embeds' => array(
                array(
                    'title'       => $title,
                    'url'         => $admin_link,
                    'color'       => 29372, // #0073aa in decimal
                    'fields'      => array_slice( $fields, 0, 25 ), // Discord max 25 fields
                    'footer'      => array( 'text' => sprintf( 'Entry #%d', $entry_id ) ),
                    'timestamp'   => gmdate( 'c' ),
                ),
            ),
        );

        wp_remote_post( esc_url_raw( $url ), array(
            'timeout' => 10,
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => wp_json_encode( $payload ),
        ) );
    }
}

// Bootstrap
SPF_Addon_Slack_Discord::get_instance();
