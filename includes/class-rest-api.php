<?php
/**
 * REST API endpoints for SyntekPro Forms
 */

if (!defined('ABSPATH')) {
    exit;
}

class SyntekPro_Forms_REST_API {

    /** @var SyntekPro_Forms_Builder */
    private $builder;

    public function __construct( $builder ) {
        $this->builder = $builder;
        $this->register_routes();
    }

    /**
     * Register all REST routes.
     */
    public function register_routes() {
        register_rest_route(
            'syntekpro-forms/v1',
            '/forms',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_forms' ),
                    'permission_callback' => array( $this, 'permissions_manage' ),
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'create_form' ),
                    'permission_callback' => array( $this, 'permissions_manage' ),
                ),
            )
        );

        register_rest_route(
            'syntekpro-forms/v1',
            '/forms/(?P<id>\d+)',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_form' ),
                    'permission_callback' => array( $this, 'permissions_manage' ),
                ),
                array(
                    'methods'             => 'PUT',
                    'callback'            => array( $this, 'update_form' ),
                    'permission_callback' => array( $this, 'permissions_manage' ),
                ),
                array(
                    'methods'             => 'DELETE',
                    'callback'            => array( $this, 'delete_form' ),
                    'permission_callback' => array( $this, 'permissions_manage' ),
                ),
            )
        );

        register_rest_route(
            'syntekpro-forms/v1',
            '/entries',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_entries' ),
                    'permission_callback' => array( $this, 'permissions_manage' ),
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'create_entry' ),
                    'permission_callback' => '__return_true', // allow public submissions via REST
                ),
            )
        );

        register_rest_route(
            'syntekpro-forms/v1',
            '/entries/(?P<id>\d+)',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_entry' ),
                    'permission_callback' => array( $this, 'permissions_manage' ),
                ),
                array(
                    'methods'             => 'PUT',
                    'callback'            => array( $this, 'update_entry' ),
                    'permission_callback' => array( $this, 'permissions_manage' ),
                ),
                array(
                    'methods'             => 'DELETE',
                    'callback'            => array( $this, 'delete_entry' ),
                    'permission_callback' => array( $this, 'permissions_manage' ),
                ),
            )
        );
    }

    /**
     * Capability check used for most routes
     */
    public function permissions_manage( $request ) {
        return current_user_can( 'manage_options' );
    }

    /* =======================================================================
     * Forms handlers
     * ======================================================================= */

    public function get_forms( $request ) {
        global $wpdb;

        $page     = max( 1, intval( $request->get_param( 'page' ) ) );
        $per_page = min( 100, max( 1, intval( $request->get_param( 'per_page' ) ) ) );
        $offset   = ( $page - 1 ) * $per_page;

        $where   = '';
        $params  = array();
        $search  = $request->get_param( 'search' );
        if ( $search ) {
            $where  = 'WHERE title LIKE %s';
            $params = array( '%' . $wpdb->esc_like( $search ) . '%' );
        }

        $sql = "SELECT * FROM {$wpdb->prefix}spf_forms {$where} ORDER BY id DESC LIMIT %d OFFSET %d";
        if ( ! empty( $params ) ) {
            $params[] = $per_page;
            $params[] = $offset;
            $results  = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
        } else {
            $results = $wpdb->get_results( $wpdb->prepare( $sql, $per_page, $offset ) );
        }

        foreach ( $results as $form ) {
            $form->fields   = json_decode( (string) $form->fields, true );
            $form->settings = json_decode( (string) $form->settings, true );
        }

        /**
         * Filter the list of forms returned via the REST API.
         */
        $results = apply_filters( 'syntekpro_forms_rest_prepare_forms', $results, $request );

        return rest_ensure_response( $results );
    }

    public function get_form( $request ) {
        global $wpdb;
        $id = intval( $request->get_param( 'id' ) );

        $form = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}spf_forms WHERE id = %d", $id ) );
        if ( ! $form ) {
            return new WP_Error( 'spf_rest_not_found', __( 'Form not found', 'syntekpro-forms' ), array( 'status' => 404 ) );
        }

        $form->fields   = json_decode( (string) $form->fields, true );
        $form->settings = json_decode( (string) $form->settings, true );

        $form = apply_filters( 'syntekpro_forms_rest_prepare_form', $form, $request );

        return rest_ensure_response( $form );
    }

    public function create_form( $request ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'spf_rest_forbidden', __( 'Unauthorized', 'syntekpro-forms' ), array( 'status' => 403 ) );
        }

        global $wpdb;
        $params = $request->get_json_params();

        $title       = isset( $params['title'] ) ? sanitize_text_field( $params['title'] ) : '';
        $description = isset( $params['description'] ) ? sanitize_textarea_field( $params['description'] ) : '';
        $fields      = isset( $params['fields'] ) ? wp_json_encode( $params['fields'] ) : '';
        $settings    = isset( $params['settings'] ) ? wp_json_encode( $params['settings'] ) : '';

        if ( empty( $title ) ) {
            return new WP_Error( 'spf_rest_missing_title', __( 'Form title is required', 'syntekpro-forms' ), array( 'status' => 400 ) );
        }

        $wpdb->insert(
            $wpdb->prefix . 'spf_forms',
            array(
                'title'       => $title,
                'description' => $description,
                'fields'      => $fields,
                'settings'    => $settings,
            ),
            array( '%s', '%s', '%s', '%s' )
        );

        if ( $wpdb->last_error ) {
            return new WP_Error( 'spf_rest_db_error', $wpdb->last_error, array( 'status' => 500 ) );
        }

        $form_id = (int) $wpdb->insert_id;
        $form    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}spf_forms WHERE id = %d", $form_id ) );
        $form->fields   = json_decode( (string) $form->fields, true );
        $form->settings = json_decode( (string) $form->settings, true );

        do_action( 'syntekpro_forms_rest_insert_form', $form, $request );

        return rest_ensure_response( $form );
    }

    public function update_form( $request ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'spf_rest_forbidden', __( 'Unauthorized', 'syntekpro-forms' ), array( 'status' => 403 ) );
        }

        global $wpdb;
        $id     = intval( $request->get_param( 'id' ) );
        $params = $request->get_json_params();

        $data = array();
        $format = array();

        if ( isset( $params['title'] ) ) {
            $data['title'] = sanitize_text_field( $params['title'] );
            $format[] = '%s';
        }
        if ( isset( $params['description'] ) ) {
            $data['description'] = sanitize_textarea_field( $params['description'] );
            $format[] = '%s';
        }
        if ( isset( $params['fields'] ) ) {
            $data['fields'] = wp_json_encode( $params['fields'] );
            $format[] = '%s';
        }
        if ( isset( $params['settings'] ) ) {
            $data['settings'] = wp_json_encode( $params['settings'] );
            $format[] = '%s';
        }

        if ( empty( $data ) ) {
            return new WP_Error( 'spf_rest_no_changes', __( 'No data to update', 'syntekpro-forms' ), array( 'status' => 400 ) );
        }

        $result = $wpdb->update( $wpdb->prefix . 'spf_forms', $data, array( 'id' => $id ), $format, array( '%d' ) );
        if ( $result === false ) {
            return new WP_Error( 'spf_rest_db_error', $wpdb->last_error, array( 'status' => 500 ) );
        }

        $form = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}spf_forms WHERE id = %d", $id ) );
        if ( $form ) {
            $form->fields   = json_decode( (string) $form->fields, true );
            $form->settings = json_decode( (string) $form->settings, true );
            do_action( 'syntekpro_forms_rest_update_form', $form, $request );
        }

        return rest_ensure_response( $form );
    }

    public function delete_form( $request ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'spf_rest_forbidden', __( 'Unauthorized', 'syntekpro-forms' ), array( 'status' => 403 ) );
        }

        global $wpdb;
        $id = intval( $request->get_param( 'id' ) );

        // delete entries first
        $wpdb->delete( $wpdb->prefix . 'spf_entries', array( 'form_id' => $id ), array( '%d' ) );
        $removed = $wpdb->delete( $wpdb->prefix . 'spf_forms', array( 'id' => $id ), array( '%d' ) );

        if ( $removed === false ) {
            return new WP_Error( 'spf_rest_db_error', $wpdb->last_error, array( 'status' => 500 ) );
        }

        do_action( 'syntekpro_forms_rest_delete_form', $id, $request );

        return rest_ensure_response( array( 'deleted' => (bool) $removed ) );
    }

    /* =======================================================================
     * Entries handlers
     * ======================================================================= */

    public function get_entries( $request ) {
        global $wpdb;

        $page     = max( 1, intval( $request->get_param( 'page' ) ) );
        $per_page = min( 100, max( 1, intval( $request->get_param( 'per_page' ) ) ) );
        $offset   = ( $page - 1 ) * $per_page;

        $where   = 'WHERE 1=1';
        $params  = array();

        $form_id = intval( $request->get_param( 'form_id' ) );
        if ( $form_id > 0 ) {
            $where   .= ' AND e.form_id = %d';
            $params[] = $form_id;
        }

        $status = $request->get_param( 'status' );
        if ( in_array( $status, array( 'read', 'unread' ), true ) ) {
            $where   .= ' AND e.status = %s';
            $params[] = $status;
        }

        $search = $request->get_param( 'search' );
        if ( $search ) {
            $like     = '%' . $wpdb->esc_like( $search ) . '%';
            $where   .= ' AND (e.entry_data LIKE %s OR e.ip_address LIKE %s)';
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT e.* FROM {$wpdb->prefix}spf_entries e {$where} ORDER BY e.created_at DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $entries = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );

        foreach ( $entries as $entry ) {
            $entry->entry_data = json_decode( (string) $entry->entry_data, true );
        }

        $entries = apply_filters( 'syntekpro_forms_rest_prepare_entries', $entries, $request );

        return rest_ensure_response( $entries );
    }

    public function get_entry( $request ) {
        global $wpdb;
        $id = intval( $request->get_param( 'id' ) );

        $entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}spf_entries WHERE id = %d", $id ) );
        if ( ! $entry ) {
            return new WP_Error( 'spf_rest_not_found', __( 'Entry not found', 'syntekpro-forms' ), array( 'status' => 404 ) );
        }

        $entry->entry_data = json_decode( (string) $entry->entry_data, true );
        $entry           = apply_filters( 'syntekpro_forms_rest_prepare_entry', $entry, $request );

        return rest_ensure_response( $entry );
    }

    public function create_entry( $request ) {
        global $wpdb;
        $params = $request->get_json_params();

        $form_id = isset( $params['form_id'] ) ? intval( $params['form_id'] ) : 0;
        $data    = isset( $params['entry_data'] ) ? $params['entry_data'] : array();

        if ( $form_id <= 0 ) {
            return new WP_Error( 'spf_rest_invalid_form', __( 'Valid form_id is required', 'syntekpro-forms' ), array( 'status' => 400 ) );
        }

        // Verify form exists and is active
        $form = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spf_forms WHERE id = %d AND status = 'active'",
            $form_id
        ) );

        if ( ! $form ) {
            return new WP_Error( 'spf_rest_form_not_found', __( 'Form not found or inactive', 'syntekpro-forms' ), array( 'status' => 404 ) );
        }

        // Check form availability (schedule, submission limits)
        $form_settings = json_decode( (string) $form->settings, true );
        if ( ! is_array( $form_settings ) ) {
            $form_settings = array();
        }

        $availability = $this->builder->get_form_availability_state( $form_settings, $form_id );
        if ( $availability['status'] !== 'open' ) {
            return new WP_Error( 'spf_rest_form_closed', $availability['message'], array( 'status' => 403 ) );
        }

        // Rate limiting
        $settings = get_option( 'spf_settings', array() );
        $client_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        $rate_limit_seconds = isset( $settings['rate_limit_seconds'] ) ? absint( $settings['rate_limit_seconds'] ) : 0;
        if ( ! empty( $settings['rate_limit_enabled'] ) && $rate_limit_seconds > 0 && ! empty( $client_ip ) ) {
            $lock_key = 'spf_rl_' . md5( $client_ip );
            if ( get_transient( $lock_key ) ) {
                return new WP_Error( 'spf_rest_rate_limited', sprintf( __( 'Please wait %d seconds before submitting again.', 'syntekpro-forms' ), $rate_limit_seconds ), array( 'status' => 429 ) );
            }
            set_transient( $lock_key, time(), $rate_limit_seconds );
        }

        // Honeypot check
        if ( ! empty( $settings['enable_honeypot'] ) && ! empty( $params['spf_hp_field'] ) ) {
            return new WP_Error( 'spf_rest_spam', __( 'Spam detected', 'syntekpro-forms' ), array( 'status' => 403 ) );
        }

        // Sanitize entry data
        $sanitized = array();
        if ( is_array( $data ) ) {
            foreach ( $data as $key => $value ) {
                $key = sanitize_text_field( $key );
                if ( is_array( $value ) ) {
                    $sanitized[ $key ] = array_map( 'sanitize_text_field', $value );
                } else {
                    $sanitized[ $key ] = sanitize_text_field( (string) $value );
                }
            }
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'spf_entries',
            array(
                'form_id'    => $form_id,
                'entry_data' => wp_json_encode( $sanitized ),
                'user_id'    => get_current_user_id(),
                'ip_address' => $client_ip,
                'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%d', '%s', '%s', '%s' )
        );

        if ( $result === false ) {
            return new WP_Error( 'spf_rest_db_error', $wpdb->last_error, array( 'status' => 500 ) );
        }

        $entry_id = (int) $wpdb->insert_id;
        delete_transient( 'spf_count_' . $form_id );

        $entry    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}spf_entries WHERE id = %d", $entry_id ) );
        $entry->entry_data = json_decode( (string) $entry->entry_data, true );

        do_action( 'syntekpro_forms_rest_insert_entry', $entry, $request );

        return rest_ensure_response( $entry );
    }

    public function update_entry( $request ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'spf_rest_forbidden', __( 'Unauthorized', 'syntekpro-forms' ), array( 'status' => 403 ) );
        }

        global $wpdb;
        $id     = intval( $request->get_param( 'id' ) );
        $params = $request->get_json_params();

        $data   = array();
        $format = array();

        if ( isset( $params['entry_data'] ) ) {
            $data['entry_data'] = wp_json_encode( $params['entry_data'] );
            $format[] = '%s';
        }
        if ( isset( $params['status'] ) ) {
            $data['status'] = sanitize_text_field( $params['status'] );
            $format[] = '%s';
        }

        if ( empty( $data ) ) {
            return new WP_Error( 'spf_rest_no_changes', __( 'No data to update', 'syntekpro-forms' ), array( 'status' => 400 ) );
        }

        $result = $wpdb->update( $wpdb->prefix . 'spf_entries', $data, array( 'id' => $id ), $format, array( '%d' ) );
        if ( $result === false ) {
            return new WP_Error( 'spf_rest_db_error', $wpdb->last_error, array( 'status' => 500 ) );
        }

        $entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}spf_entries WHERE id = %d", $id ) );
        if ( $entry ) {
            $entry->entry_data = json_decode( (string) $entry->entry_data, true );
            do_action( 'syntekpro_forms_rest_update_entry', $entry, $request );
        }

        return rest_ensure_response( $entry );
    }

    public function delete_entry( $request ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'spf_rest_forbidden', __( 'Unauthorized', 'syntekpro-forms' ), array( 'status' => 403 ) );
        }

        global $wpdb;
        $id      = intval( $request->get_param( 'id' ) );
        $deleted = $wpdb->delete( $wpdb->prefix . 'spf_entries', array( 'id' => $id ), array( '%d' ) );
        if ( $deleted === false ) {
            return new WP_Error( 'spf_rest_db_error', $wpdb->last_error, array( 'status' => 500 ) );
        }

        do_action( 'syntekpro_forms_rest_delete_entry', $id, $request );

        return rest_ensure_response( array( 'deleted' => (bool) $deleted ) );
    }
}
