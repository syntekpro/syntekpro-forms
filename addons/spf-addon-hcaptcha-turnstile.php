<?php
/**
 * Addon: Cloudflare Turnstile & hCaptcha Support
 *
 * Adds support for Cloudflare Turnstile and hCaptcha as alternatives to
 * Google reCAPTCHA.  Administrators choose the provider in the global
 * settings; the addon injects the correct widget at render time and
 * validates the response server-side during submission.
 *
 * Settings (spf_settings option):
 *   captcha_provider         – 'recaptcha' | 'turnstile' | 'hcaptcha' | '' (none)
 *   turnstile_site_key       – Cloudflare Turnstile site key
 *   turnstile_secret_key     – Cloudflare Turnstile secret key
 *   hcaptcha_site_key        – hCaptcha site key
 *   hcaptcha_secret_key      – hCaptcha secret key
 *
 * @package SyntekPro_Forms
 * @subpackage Addons
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SPF_Addon_HCaptcha_Turnstile {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Replace or augment the default reCAPTCHA widget in the form HTML.
        add_action( 'syntekpro_forms_after_fields', array( $this, 'render_widget' ), 10, 3 );

        // Server-side verification during AJAX submission.
        add_filter( 'syntekpro_forms_spam_check', array( $this, 'verify_response' ), 10, 3 );
    }

    /* ------------------------------------------------------------------ */
    /*  Frontend widget rendering                                         */
    /* ------------------------------------------------------------------ */

    /**
     * Output the captcha widget HTML.
     *
     * This is called from form-display.php via do_action().
     *
     * @param int   $form_id  Current form ID.
     * @param array $settings Decoded form settings array.
     * @param array $plugin_settings Global plugin settings.
     */
    public function render_widget( $form_id, $settings, $plugin_settings ) {
        $provider = $this->get_provider( $plugin_settings );

        if ( $provider === 'turnstile' ) {
            $site_key = ! empty( $plugin_settings['turnstile_site_key'] ) ? $plugin_settings['turnstile_site_key'] : '';
            if ( empty( $site_key ) ) {
                return;
            }
            ?>
            <div class="spf-captcha spf-turnstile-wrap" style="margin:12px 0;">
                <div class="cf-turnstile" data-sitekey="<?php echo esc_attr( $site_key ); ?>" data-callback="spfCaptchaCallback"></div>
            </div>
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            <?php

        } elseif ( $provider === 'hcaptcha' ) {
            $site_key = ! empty( $plugin_settings['hcaptcha_site_key'] ) ? $plugin_settings['hcaptcha_site_key'] : '';
            if ( empty( $site_key ) ) {
                return;
            }
            ?>
            <div class="spf-captcha spf-hcaptcha-wrap" style="margin:12px 0;">
                <div class="h-captcha" data-sitekey="<?php echo esc_attr( $site_key ); ?>" data-callback="spfCaptchaCallback"></div>
            </div>
            <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
            <?php
        }
        // If provider is 'recaptcha' or empty, the core plugin already handles it.
    }

    /* ------------------------------------------------------------------ */
    /*  Server-side verification                                          */
    /* ------------------------------------------------------------------ */

    /**
     * Verify the captcha response token.
     *
     * Hooked into `syntekpro_forms_spam_check`. If verification fails the
     * filter returns an error string which the AJAX handler uses to reject
     * the submission.
     *
     * @param string|null $error          Existing error message (null = no error yet).
     * @param array       $form_data      Submitted form data.
     * @param array       $plugin_settings Global plugin settings.
     * @return string|null  Error message on failure; null on success.
     */
    public function verify_response( $error, $form_data, $plugin_settings ) {
        // If already rejected by an earlier filter, pass through.
        if ( $error ) {
            return $error;
        }

        $provider = $this->get_provider( $plugin_settings );

        if ( $provider === 'turnstile' ) {
            return $this->verify_turnstile( $form_data, $plugin_settings );
        }

        if ( $provider === 'hcaptcha' ) {
            return $this->verify_hcaptcha( $form_data, $plugin_settings );
        }

        return null; // Not our provider – nothing to do.
    }

    /**
     * Verify a Cloudflare Turnstile token.
     */
    private function verify_turnstile( $form_data, $settings ) {
        $secret = ! empty( $settings['turnstile_secret_key'] ) ? $settings['turnstile_secret_key'] : '';
        $token  = ! empty( $form_data['cf-turnstile-response'] ) ? $form_data['cf-turnstile-response'] : '';

        if ( empty( $secret ) || empty( $token ) ) {
            return __( 'CAPTCHA verification failed. Please try again.', 'syntekpro-forms' );
        }

        $response = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
            'timeout' => 10,
            'body'    => array(
                'secret'   => $secret,
                'response' => $token,
                'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return null; // Fail-open on network error.
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body['success'] ) ) {
            return __( 'CAPTCHA verification failed. Please try again.', 'syntekpro-forms' );
        }

        return null;
    }

    /**
     * Verify an hCaptcha token.
     */
    private function verify_hcaptcha( $form_data, $settings ) {
        $secret = ! empty( $settings['hcaptcha_secret_key'] ) ? $settings['hcaptcha_secret_key'] : '';
        $token  = ! empty( $form_data['h-captcha-response'] ) ? $form_data['h-captcha-response'] : '';

        if ( empty( $secret ) || empty( $token ) ) {
            return __( 'CAPTCHA verification failed. Please try again.', 'syntekpro-forms' );
        }

        $response = wp_remote_post( 'https://hcaptcha.com/siteverify', array(
            'timeout' => 10,
            'body'    => array(
                'secret'   => $secret,
                'response' => $token,
                'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return null; // Fail-open on network error.
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body['success'] ) ) {
            return __( 'CAPTCHA verification failed. Please try again.', 'syntekpro-forms' );
        }

        return null;
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                           */
    /* ------------------------------------------------------------------ */

    /**
     * Determine the active captcha provider.
     *
     * @param array $settings Plugin settings.
     * @return string 'recaptcha', 'turnstile', 'hcaptcha', or '' (none).
     */
    private function get_provider( $settings ) {
        if ( ! is_array( $settings ) ) {
            return '';
        }

        if ( ! empty( $settings['captcha_provider'] ) ) {
            return sanitize_key( $settings['captcha_provider'] );
        }

        // Backward-compatible: if reCAPTCHA keys are present, default to recaptcha.
        if ( ! empty( $settings['recaptcha_site_key'] ) && ! empty( $settings['recaptcha_secret_key'] ) ) {
            return 'recaptcha';
        }

        return '';
    }
}

// Bootstrap
SPF_Addon_HCaptcha_Turnstile::get_instance();
