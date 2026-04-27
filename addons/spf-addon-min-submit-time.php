<?php
/**
 * Add-on Name: Minimum Submit Time
 * Description: Adds a minimum time threshold before a form can be submitted to reduce bot submissions.
 * Version: 1.0.0
 * Author: SyntekPro
 * Icon: dashicons-clock
 * Graphic: ../assets/images/Syntekpro Forms Grey Favicons.png
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SPF_Addon_Min_Submit_Time')) {
    class SPF_Addon_Min_Submit_Time {

        public function __construct() {
            add_action('wp_footer', array($this, 'inject_timer_script'), 50);
            add_filter('syntekpro_forms_submission_pre_validate', array($this, 'validate_submit_time'), 20, 8);
        }

        public function inject_timer_script() {
            ?>
            <script>
            (function() {
                function attachStartTime(form) {
                    if (!form || form.querySelector('input[name="spf_mt_started"]')) {
                        return;
                    }

                    var startedInput = document.createElement('input');
                    startedInput.type = 'hidden';
                    startedInput.name = 'spf_mt_started';
                    startedInput.value = String(Date.now());
                    form.appendChild(startedInput);
                }

                function init() {
                    var forms = document.querySelectorAll('.spf-form');
                    if (!forms.length) {
                        return;
                    }

                    forms.forEach(function(form) {
                        attachStartTime(form);
                    });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', init);
                } else {
                    init();
                }
            })();
            </script>
            <?php
        }

        public function validate_submit_time($pre_validate, $raw_form_data) {
            if (is_wp_error($pre_validate) || $pre_validate === false) {
                return $pre_validate;
            }

            if (!is_array($raw_form_data) || !isset($raw_form_data['spf_mt_started'])) {
                return $pre_validate;
            }

            $started_ms = absint($raw_form_data['spf_mt_started']);
            if ($started_ms <= 0) {
                return $pre_validate;
            }

            $now_ms = (int) round(microtime(true) * 1000);
            $elapsed_ms = max(0, $now_ms - $started_ms);

            $minimum_ms = (int) apply_filters('spf_min_submit_time_ms', 2500);
            if ($minimum_ms < 0) {
                $minimum_ms = 0;
            }

            if ($elapsed_ms < $minimum_ms) {
                return new WP_Error('spf_submit_too_fast', __('Please wait a moment before submitting the form.', 'syntekpro-forms'));
            }

            return $pre_validate;
        }
    }

    new SPF_Addon_Min_Submit_Time();
}
