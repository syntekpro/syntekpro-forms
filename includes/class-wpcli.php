<?php
/**
 * WP-CLI Commands for SyntekPro Forms
 *
 * Provides convenient CLI access to common plugin operations:
 *   wp spf form list
 *   wp spf form get <id>
 *   wp spf form delete <id>
 *   wp spf entry list [--form_id=<id>] [--limit=<n>]
 *   wp spf entry export [--form_id=<id>] [--format=csv|json]
 *   wp spf entry delete <id>
 *   wp spf entry purge --older-than=<days>
 *   wp spf cache flush
 *
 * @package SyntekPro_Forms
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) ) {
    return;
}

class SyntekPro_Forms_CLI {

    /**
     * List all forms.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Output format. Accepts table, json, csv. Default table.
     *
     * ## EXAMPLES
     *
     *     wp spf form list
     *     wp spf form list --format=json
     *
     * @subcommand form list
     */
    public function form_list( $args, $assoc_args ) {
        global $wpdb;

        $rows = $wpdb->get_results(
            "SELECT id, title, status, views, created_at, updated_at FROM {$wpdb->prefix}spf_forms ORDER BY id DESC",
            ARRAY_A
        );

        if ( empty( $rows ) ) {
            WP_CLI::success( 'No forms found.' );
            return;
        }

        $format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';
        WP_CLI\Utils\format_items( $format, $rows, array( 'id', 'title', 'status', 'views', 'created_at' ) );
    }

    /**
     * Get details for a specific form.
     *
     * ## OPTIONS
     *
     * <id>
     * : The form ID.
     *
     * [--format=<format>]
     * : Output format. Accepts table, json, yaml. Default table.
     *
     * ## EXAMPLES
     *
     *     wp spf form get 1
     *
     * @subcommand form get
     */
    public function form_get( $args, $assoc_args ) {
        global $wpdb;

        $form = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spf_forms WHERE id = %d",
            (int) $args[0]
        ), ARRAY_A );

        if ( ! $form ) {
            WP_CLI::error( 'Form not found.' );
        }

        $format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';
        WP_CLI\Utils\format_items( $format, array( $form ), array_keys( $form ) );
    }

    /**
     * Delete a form and all its entries.
     *
     * ## OPTIONS
     *
     * <id>
     * : The form ID to delete.
     *
     * [--yes]
     * : Skip confirmation prompt.
     *
     * ## EXAMPLES
     *
     *     wp spf form delete 3 --yes
     *
     * @subcommand form delete
     */
    public function form_delete( $args, $assoc_args ) {
        global $wpdb;

        $form_id = (int) $args[0];
        $form = $wpdb->get_row( $wpdb->prepare( "SELECT id, title FROM {$wpdb->prefix}spf_forms WHERE id = %d", $form_id ) );

        if ( ! $form ) {
            WP_CLI::error( 'Form not found.' );
        }

        WP_CLI::confirm( sprintf( 'Delete form "%s" (ID %d) and all its entries?', $form->title, $form_id ), $assoc_args );

        $wpdb->delete( $wpdb->prefix . 'spf_entries', array( 'form_id' => $form_id ), array( '%d' ) );
        $wpdb->delete( $wpdb->prefix . 'spf_forms', array( 'id' => $form_id ), array( '%d' ) );

        WP_CLI::success( sprintf( 'Form "%s" deleted.', $form->title ) );
    }

    /**
     * List entries.
     *
     * ## OPTIONS
     *
     * [--form_id=<id>]
     * : Filter by form ID.
     *
     * [--limit=<n>]
     * : Number of entries to show. Default 20.
     *
     * [--format=<format>]
     * : Output format. Default table.
     *
     * ## EXAMPLES
     *
     *     wp spf entry list --form_id=1 --limit=50
     *
     * @subcommand entry list
     */
    public function entry_list( $args, $assoc_args ) {
        global $wpdb;

        $form_id = isset( $assoc_args['form_id'] ) ? (int) $assoc_args['form_id'] : 0;
        $limit   = isset( $assoc_args['limit'] ) ? min( 1000, max( 1, (int) $assoc_args['limit'] ) ) : 20;
        $format  = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';

        $where  = '';
        $params = array();
        if ( $form_id > 0 ) {
            $where    = 'WHERE e.form_id = %d';
            $params[] = $form_id;
        }

        $sql = "SELECT e.id, e.form_id, f.title AS form_title, e.status, e.starred, e.ip_address, e.created_at
                FROM {$wpdb->prefix}spf_entries e
                LEFT JOIN {$wpdb->prefix}spf_forms f ON e.form_id = f.id
                {$where}
                ORDER BY e.created_at DESC
                LIMIT %d";

        $params[] = $limit;
        $rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

        if ( empty( $rows ) ) {
            WP_CLI::success( 'No entries found.' );
            return;
        }

        WP_CLI\Utils\format_items( $format, $rows, array( 'id', 'form_id', 'form_title', 'status', 'starred', 'ip_address', 'created_at' ) );
    }

    /**
     * Export entries to stdout (CSV or JSON).
     *
     * ## OPTIONS
     *
     * [--form_id=<id>]
     * : Filter by form ID.
     *
     * [--format=<format>]
     * : csv or json. Default csv.
     *
     * ## EXAMPLES
     *
     *     wp spf entry export --form_id=1 --format=json > entries.json
     *
     * @subcommand entry export
     */
    public function entry_export( $args, $assoc_args ) {
        global $wpdb;

        $form_id = isset( $assoc_args['form_id'] ) ? (int) $assoc_args['form_id'] : 0;
        $format  = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'csv';

        $where  = '';
        $params = array();
        if ( $form_id > 0 ) {
            $where    = 'WHERE e.form_id = %d';
            $params[] = $form_id;
        }

        $sql = "SELECT e.*, f.title AS form_title
                FROM {$wpdb->prefix}spf_entries e
                LEFT JOIN {$wpdb->prefix}spf_forms f ON e.form_id = f.id
                {$where}
                ORDER BY e.created_at DESC";

        $entries = empty( $params )
            ? $wpdb->get_results( $sql )
            : $wpdb->get_results( $wpdb->prepare( $sql, $params ) );

        if ( empty( $entries ) ) {
            WP_CLI::error( 'No entries found.' );
        }

        if ( $format === 'json' ) {
            $output = array();
            foreach ( $entries as $entry ) {
                $row                = (array) $entry;
                $row['entry_data'] = json_decode( (string) $entry->entry_data, true );
                $output[]           = $row;
            }
            WP_CLI::line( wp_json_encode( $output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
            return;
        }

        // CSV
        $all_keys = array();
        foreach ( $entries as $entry ) {
            $data = json_decode( (string) $entry->entry_data, true );
            if ( is_array( $data ) ) {
                $all_keys = array_unique( array_merge( $all_keys, array_keys( $data ) ) );
            }
        }

        $fp = fopen( 'php://output', 'w' );
        fputcsv( $fp, array_merge( array( 'ID', 'Form ID', 'Form Title', 'Created At', 'IP', 'Status' ), $all_keys ) );

        foreach ( $entries as $entry ) {
            $data = json_decode( (string) $entry->entry_data, true );
            if ( ! is_array( $data ) ) {
                $data = array();
            }
            $row = array( $entry->id, $entry->form_id, $entry->form_title, $entry->created_at, $entry->ip_address, $entry->status );
            foreach ( $all_keys as $key ) {
                $val   = isset( $data[ $key ] ) ? $data[ $key ] : '';
                $row[] = is_array( $val ) ? implode( ', ', $val ) : (string) $val;
            }
            fputcsv( $fp, $row );
        }
        fclose( $fp );
    }

    /**
     * Delete a specific entry.
     *
     * ## OPTIONS
     *
     * <id>
     * : Entry ID.
     *
     * [--yes]
     * : Skip confirmation.
     *
     * @subcommand entry delete
     */
    public function entry_delete( $args, $assoc_args ) {
        global $wpdb;

        $entry_id = (int) $args[0];
        WP_CLI::confirm( sprintf( 'Delete entry #%d?', $entry_id ), $assoc_args );

        $deleted = $wpdb->delete( $wpdb->prefix . 'spf_entries', array( 'id' => $entry_id ), array( '%d' ) );
        if ( $deleted ) {
            WP_CLI::success( 'Entry deleted.' );
        } else {
            WP_CLI::error( 'Entry not found or could not be deleted.' );
        }
    }

    /**
     * Purge entries older than N days.
     *
     * ## OPTIONS
     *
     * --older-than=<days>
     * : Delete entries older than this many days.
     *
     * [--yes]
     * : Skip confirmation.
     *
     * ## EXAMPLES
     *
     *     wp spf entry purge --older-than=90 --yes
     *
     * @subcommand entry purge
     */
    public function entry_purge( $args, $assoc_args ) {
        global $wpdb;

        if ( empty( $assoc_args['older-than'] ) ) {
            WP_CLI::error( '--older-than=<days> is required.' );
        }

        $days = max( 1, (int) $assoc_args['older-than'] );
        WP_CLI::confirm( sprintf( 'Delete all entries older than %d days?', $days ), $assoc_args );

        $deleted = $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}spf_entries WHERE created_at < (NOW() - INTERVAL %d DAY)",
            $days
        ) );

        WP_CLI::success( sprintf( '%d entries purged.', (int) $deleted ) );
    }

    /**
     * Flush plugin transient caches.
     *
     * ## EXAMPLES
     *
     *     wp spf cache flush
     *
     * @subcommand cache flush
     */
    public function cache_flush( $args, $assoc_args ) {
        global $wpdb;

        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_spf_%' OR option_name LIKE '_transient_timeout_spf_%'" );

        WP_CLI::success( 'All SyntekPro Forms transients cleared.' );
    }
}

WP_CLI::add_command( 'spf', 'SyntekPro_Forms_CLI' );
