<?php
/**
 * GDPR Compliance – Personal Data Export & Erasure
 *
 * Implements WordPress privacy tool hooks so that site owners can
 * export and erase form-submission data tied to an email address.
 *
 * @package SyntekPro_Forms
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SyntekPro_Forms_GDPR {

    /**
     * Register the personal-data exporter with WordPress.
     *
     * @param array $exporters Registered exporters.
     * @return array
     */
    public static function register_exporter( $exporters ) {
        $exporters['syntekpro-forms'] = array(
            'exporter_friendly_name' => __( 'SyntekPro Forms Entries', 'syntekpro-forms' ),
            'callback'               => array( __CLASS__, 'export_personal_data' ),
        );
        return $exporters;
    }

    /**
     * Register the personal-data eraser with WordPress.
     *
     * @param array $erasers Registered erasers.
     * @return array
     */
    public static function register_eraser( $erasers ) {
        $erasers['syntekpro-forms'] = array(
            'eraser_friendly_name' => __( 'SyntekPro Forms Entries', 'syntekpro-forms' ),
            'callback'             => array( __CLASS__, 'erase_personal_data' ),
        );
        return $erasers;
    }

    /**
     * Export personal data for a given email address.
     *
     * Searches entry_data JSON for the email and returns matching entries
     * in the WordPress personal-data export format.
     *
     * @param string $email_address The email to export data for.
     * @param int    $page          Pagination page (1-based).
     * @return array {
     *     @type array $data Items to export.
     *     @type bool  $done Whether all pages have been processed.
     * }
     */
    public static function export_personal_data( $email_address, $page = 1 ) {
        global $wpdb;

        $per_page = 50;
        $offset   = ( max( 1, (int) $page ) - 1 ) * $per_page;
        $like     = '%' . $wpdb->esc_like( $email_address ) . '%';

        $entries = $wpdb->get_results( $wpdb->prepare(
            "SELECT e.*, f.title AS form_title
             FROM {$wpdb->prefix}spf_entries e
             LEFT JOIN {$wpdb->prefix}spf_forms f ON e.form_id = f.id
             WHERE e.entry_data LIKE %s
             ORDER BY e.id ASC
             LIMIT %d OFFSET %d",
            $like,
            $per_page,
            $offset
        ) );

        $export_items = array();

        foreach ( (array) $entries as $entry ) {
            $data   = json_decode( (string) $entry->entry_data, true );
            $fields = array();

            if ( is_array( $data ) ) {
                foreach ( $data as $key => $value ) {
                    $fields[] = array(
                        'name'  => sanitize_text_field( $key ),
                        'value' => is_array( $value ) ? implode( ', ', array_map( 'sanitize_text_field', $value ) ) : sanitize_text_field( (string) $value ),
                    );
                }
            }

            // Metadata fields
            $fields[] = array( 'name' => __( 'Entry ID', 'syntekpro-forms' ),     'value' => (string) $entry->id );
            $fields[] = array( 'name' => __( 'Form', 'syntekpro-forms' ),          'value' => (string) $entry->form_title );
            $fields[] = array( 'name' => __( 'Submitted', 'syntekpro-forms' ),     'value' => (string) $entry->created_at );
            $fields[] = array( 'name' => __( 'IP Address', 'syntekpro-forms' ),    'value' => (string) $entry->ip_address );

            $export_items[] = array(
                'group_id'          => 'syntekpro-forms-entries',
                'group_label'       => __( 'Form Submissions', 'syntekpro-forms' ),
                'group_description' => __( 'Data submitted through SyntekPro Forms.', 'syntekpro-forms' ),
                'item_id'           => 'spf-entry-' . $entry->id,
                'data'              => $fields,
            );
        }

        return array(
            'data' => $export_items,
            'done' => count( $entries ) < $per_page,
        );
    }

    /**
     * Erase personal data for a given email address.
     *
     * Deletes entries whose JSON data contains the email address.
     *
     * @param string $email_address The email to erase data for.
     * @param int    $page          Pagination page (1-based).
     * @return array {
     *     @type int   $items_removed  Number of items erased.
     *     @type int   $items_retained Number of items retained.
     *     @type array $messages       Informational messages.
     *     @type bool  $done           Whether all pages have been processed.
     * }
     */
    public static function erase_personal_data( $email_address, $page = 1 ) {
        global $wpdb;

        $per_page = 50;
        $like     = '%' . $wpdb->esc_like( $email_address ) . '%';

        $entry_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}spf_entries
             WHERE entry_data LIKE %s
             ORDER BY id ASC
             LIMIT %d",
            $like,
            $per_page
        ) );

        $removed  = 0;
        $retained = 0;

        foreach ( (array) $entry_ids as $id ) {
            $deleted = $wpdb->delete(
                $wpdb->prefix . 'spf_entries',
                array( 'id' => (int) $id ),
                array( '%d' )
            );

            if ( $deleted ) {
                $removed++;
            } else {
                $retained++;
            }
        }

        return array(
            'items_removed'  => $removed,
            'items_retained' => $retained,
            'messages'       => array(),
            'done'           => count( $entry_ids ) < $per_page,
        );
    }
}
