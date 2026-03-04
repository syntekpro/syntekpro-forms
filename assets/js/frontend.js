/**
 * SyntekPro Forms - Frontend JavaScript
 */

(function($) {
    'use strict';
    
    var SPF_Frontend = {
        
        activeForm: null,
        sessionId: 'spf_' + Math.random().toString(36).slice(2, 12),
        startedForms: {},
        completedForms: {},

        // Initialize
        init: function() {
            var self = this;
            this.initFormSubmission();
            this.initValidation();
            this.initSteps();
            this.initDynamicFields();
            this.initAnalytics();
            this.initDrafts();
            this.initCalculationFields();
            this.initRepeaterFields();
            this.initSignatureFields();
            this.initMultiFilePreview();

            // Global callback for reCAPTCHA
            window.spfRecaptchaCallback = function(token) {
                if (self.activeForm) {
                    self.submitFormAjax(self.activeForm);
                }
            };

            // Global callback for Turnstile / hCaptcha
            window.spfCaptchaCallback = function(token) {
                // Token is automatically included via the hidden input the widget generates
            };
        },
        
        // Initialize form submission
        initFormSubmission: function() {
            var self = this;
            
            $('.spf-form').on('submit', function(e) {
                var $form = $(this);
                
                // If AJAX is disabled, let it submit normally
                if ($form.data('ajax') === false || $form.attr('data-ajax') === 'false') {
                    return;
                }

                e.preventDefault();
                self.activeForm = $form;
                var totalSteps = parseInt($form.data('step-total'), 10) || $form.find('.spf-step').length || 1;
                var currentStep = self.getCurrentStep($form);

                // Stepper flow: validate current and advance if not on last step
                if ($form.hasClass('spf-has-steps') && currentStep < totalSteps - 1) {
                    if (!self.validateForm($form, currentStep)) {
                        return false;
                    }
                    self.goToStep($form, currentStep + 1);
                    return false;
                }
                
                // Validate form
                if (!self.validateForm($form)) {
                    return false;
                }

                // Check for reCAPTCHA
                var recaptchaType = $form.data('recaptcha-type');
                if (recaptchaType === 'invisible') {
                    var widgetId = $form.find('.g-recaptcha').data('widget-id');
                    if (typeof grecaptcha !== 'undefined') {
                        // Show loading state early for invisible
                        $form.addClass('loading');
                        $form.find('.spf-submit-button').prop('disabled', true);
                        
                        grecaptcha.execute();
                        return;
                    }
                }
                
                self.submitFormAjax($form);
            });
        },

        // Submit form via AJAX
        submitFormAjax: function($form) {
            var self = this;
            var formId = $form.data('form-id');

            // Collect form data (supports file uploads)
            var formData = new FormData();
            formData.append('action', 'spf_submit_form');
            formData.append('nonce', spfFrontend.nonce);
            formData.append('form_id', formId);
            formData.append('spf_session_id', self.sessionId);
            
            $form.find('input, textarea, select').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                if (!name) return;

                var type = ($field.attr('type') || '').toLowerCase();

                if (type === 'file') {
                    var files = $field[0].files;
                    if (files && files.length) {
                        for (var i = 0; i < files.length; i++) {
                            formData.append(name, files[i]);
                        }
                    }
                } else if (type === 'checkbox') {
                    if ($field.is(':checked')) {
                        formData.append(name, $field.val());
                    }
                } else if (type === 'radio') {
                    if ($field.is(':checked')) {
                        formData.set(name, $field.val());
                    }
                } else if ($field.is('select[multiple]')) {
                    ($field.val() || []).forEach(function(val) {
                        formData.append(name, val);
                    });
                } else {
                    formData.set(name, $field.val());
                }
            });

            // Include reCAPTCHA response if present
            if ($form.find('[name="g-recaptcha-response"]').length) {
                formData.set('g-recaptcha-response', $form.find('[name="g-recaptcha-response"]').val());
            }
            
            // Show loading state
            $form.addClass('loading');
            $form.find('.spf-submit-button').prop('disabled', true);
            self.hideMessages($form);
            
            // Submit form via AJAX
            $.ajax({
                url: spfFrontend.ajaxurl,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response.success) {
                        var payload = (response.data && typeof response.data === 'object') ? response.data : {};
                        var fallbackMsg = $form.find('.spf-success-message').data('success-msg') || 'Thank you! Your form has been submitted successfully.';
                        var successMsg = payload.message || fallbackMsg;
                        var behavior = payload.behavior || $form.data('success-behavior') || 'message';
                        var redirectUrl = payload.redirect || $form.data('success-redirect') || '';
                        var payment = payload.payment || {};

                        if (payment && payment.stripe_checkout_url) {
                            window.location.href = payment.stripe_checkout_url;
                            return;
                        }

                        if (behavior === 'redirect' && redirectUrl) {
                            window.location.href = redirectUrl;
                            return;
                        }

                        self.showSuccess($form, successMsg);
                        $form[0].reset(); // Reset form
                        if ($form.hasClass('spf-has-steps')) {
                            self.goToStep($form, 0);
                        }
                        if (typeof grecaptcha !== 'undefined') {
                            grecaptcha.reset();
                        }

                        self.completedForms[formId] = true;
                        self.trackAnalytics($form, 'complete');
                        self.clearDraftData($form);
                    } else {
                        self.showError($form, response.data || 'An error occurred. Please try again.');
                        if (typeof grecaptcha !== 'undefined') {
                            grecaptcha.reset();
                        }
                    }
                },
                error: function() {
                    self.showError($form, 'An error occurred. Please try again.');
                    if (typeof grecaptcha !== 'undefined') {
                        grecaptcha.reset();
                    }
                },
                complete: function() {
                    $form.removeClass('loading');
                    $form.find('.spf-submit-button').prop('disabled', false);
                }
            });
        },
        
        // Initialize validation
        initValidation: function() {
            var self = this;
            
            // Real-time validation
            $('.spf-form input, .spf-form textarea, .spf-form select').on('blur change', function() {
                var $field = $(this);
                self.validateField($field);
            });
        },
        
        // Validate entire form
        validateForm: function($form, stepIndex) {
            var isValid = true;
            var self = this;
            var $scope = (typeof stepIndex === 'number') ? $form.find('.spf-step[data-step-index="' + stepIndex + '"]') : $form;
            
            // Clear previous errors
            $scope.find('.spf-field-wrapper').removeClass('error');
            $scope.find('.spf-field-error').remove();
            
            // Validate each required field
            $scope.find('[required]').each(function() {
                var $field = $(this);
                if (!self.validateField($field)) {
                    isValid = false;
                }
            });
            
            // Validate email fields
            $scope.find('input[type="email"]').each(function() {
                var $field = $(this);
                if ($field.val() && !self.isValidEmail($field.val())) {
                    self.showFieldError($field, 'Please enter a valid email address');
                    isValid = false;
                }
            });
            
            return isValid;
        },
        
        // Validate individual field
        validateField: function($field) {
            var $wrapper = $field.closest('.spf-field-wrapper');
            var value = $field.val();
            var isValid = true;
            
            // Remove previous error
            $wrapper.removeClass('error');
            $wrapper.find('.spf-field-error').remove();
            
            // Check if required
            if ($field.prop('required')) {
                // Handle checkboxes and radios
                if ($field.attr('type') === 'checkbox' || $field.attr('type') === 'radio') {
                    var name = $field.attr('name');
                    var isChecked = $('input[name="' + name + '"]:checked').length > 0;
                    
                    if (!isChecked) {
                        this.showFieldError($field, 'This field is required');
                        isValid = false;
                    }
                }
                // Handle other fields
                else if (!value || value.trim() === '') {
                    this.showFieldError($field, 'This field is required');
                    isValid = false;
                }
            }
            
            // Validate email format
            if ($field.attr('type') === 'email' && value && !this.isValidEmail(value)) {
                this.showFieldError($field, 'Please enter a valid email address');
                isValid = false;
            }
            
            // Validate number format
            if ($field.attr('type') === 'number' && value && isNaN(value)) {
                this.showFieldError($field, 'Please enter a valid number');
                isValid = false;
            }
            
            return isValid;
        },
        
        // Show field error
        showFieldError: function($field, message) {
            var $wrapper = $field.closest('.spf-field-wrapper');
            $wrapper.addClass('error');
            $wrapper.append('<span class="spf-field-error">' + message + '</span>');
        },
        
        // Validate email format
        isValidEmail: function(email) {
            var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        },
        
        // Show success message
        showSuccess: function($form, message) {
            var $successMsg = $form.find('.spf-success-message');
            $successMsg.text(message).fadeIn();
            
            // Scroll to message
            $('html, body').animate({
                scrollTop: $successMsg.offset().top - 100
            }, 500);
            
            // Hide after 5 seconds
            setTimeout(function() {
                $successMsg.fadeOut();
            }, 5000);
        },
        
        // Show error message
        showError: function($form, message) {
            var $errorMsg = $form.find('.spf-error-message');
            $errorMsg.text(message).fadeIn();
            
            // Scroll to message
            $('html, body').animate({
                scrollTop: $errorMsg.offset().top - 100
            }, 500);
        },
        
        // Hide messages
        hideMessages: function($form) {
            $form.find('.spf-success-message, .spf-error-message').hide();
        },

        // Steps
        initSteps: function() {
            var self = this;

            $('.spf-form.spf-has-steps').each(function() {
                var $form = $(this);
                $form.data('current-step', 0);
                self.goToStep($form, 0);

                $form.on('click', '.spf-next-step', function() {
                    var current = self.getCurrentStep($form);
                    if (!self.validateForm($form, current)) {
                        return;
                    }
                    self.goToStep($form, current + 1);
                });

                $form.on('click', '.spf-prev-step', function() {
                    var current = self.getCurrentStep($form);
                    self.goToStep($form, current - 1);
                });
            });
        },

        getCurrentStep: function($form) {
            var current = parseInt($form.data('current-step'), 10);
            return isNaN(current) ? 0 : current;
        },

        goToStep: function($form, targetStep) {
            var total = parseInt($form.data('step-total'), 10) || $form.find('.spf-step').length || 1;
            var nextStep = Math.max(0, Math.min(targetStep, total - 1));
            $form.data('current-step', nextStep);

            $form.find('.spf-step').each(function(index) {
                var $step = $(this);
                var isActive = index === nextStep;
                $step.toggle(isActive);

                // Normalize nav controls
                $step.find('.spf-prev-step').css('visibility', index === 0 ? 'hidden' : '');
                $step.find('.spf-next-step').toggle(index !== total - 1);
            });

            // Toggle submit visibility
            $form.find('.spf-submit-button').toggle(nextStep === total - 1);

            // Update progress
            this.updateProgress($form, nextStep, total);
        },

        updateProgress: function($form, currentStep, totalSteps) {
            var $wrapper = $form.closest('.spf-form-wrapper');
            var $progress = $wrapper.find('.spf-progress');
            if (!$progress.length) {
                return;
            }
            var total = totalSteps || parseInt($progress.data('total'), 10) || 1;
            var current = typeof currentStep === 'number' ? currentStep : this.getCurrentStep($form);
            var percent = ((current + 1) / total) * 100;
            $progress.find('.spf-progress-bar-fill').css('width', percent + '%');

            var $currentStep = $form.find('.spf-step[data-step-index="' + current + '"]');
            var title = $currentStep.data('step-title');
            var label = 'Step ' + (current + 1) + ' of ' + total;
            if (title) {
                label += ' – ' + title;
            }
            $progress.find('.spf-progress-label').text(label);
        },

        initAnalytics: function() {
            var self = this;

            $(document).on('focus', '.spf-form input, .spf-form textarea, .spf-form select', function() {
                var $form = $(this).closest('.spf-form');
                var formId = $form.data('form-id');
                if (!self.startedForms[formId]) {
                    self.startedForms[formId] = true;
                    self.trackAnalytics($form, 'start');
                }
            });

            $(document).on('blur', '.spf-form input, .spf-form textarea, .spf-form select', function() {
                var $field = $(this);
                var name = $field.attr('name');
                if (!name) {
                    return;
                }
                var value = $field.val();
                if (value === '' || value === null) {
                    var $form = $field.closest('.spf-form');
                    self.trackAnalytics($form, 'field_dropoff', name.replace(/\[\]$/, ''));
                }
            });

            $(window).on('beforeunload', function() {
                $('.spf-form').each(function() {
                    var $form = $(this);
                    var formId = $form.data('form-id');
                    if (self.startedForms[formId] && !self.completedForms[formId]) {
                        self.trackAnalytics($form, 'abandon');
                    }
                });
            });
        },

        trackAnalytics: function($form, eventType, fieldName) {
            if (!$form || !$form.length) {
                return;
            }

            $.post(spfFrontend.ajaxurl, {
                action: 'spf_track_analytics',
                nonce: spfFrontend.nonce,
                form_id: $form.data('form-id'),
                event_type: eventType,
                field_name: fieldName || '',
                session_id: this.sessionId
            });
        },

        initDrafts: function() {
            var self = this;

            $('.spf-form').each(function() {
                var $form = $(this);
                if (!$form.hasClass('spf-has-steps')) {
                    return;
                }

                setInterval(function() {
                    self.saveDraft($form, false);
                }, 15000);
            });

            $(document).on('click', '.spf-save-draft', function() {
                var $form = $(this).closest('.spf-form');
                self.saveDraft($form, true);
            });
        },

        saveDraft: function($form, announce) {
            if (!$form || !$form.length) {
                return;
            }

            var draftData = {};
            var email = '';
            $form.find('input, textarea, select').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                if (!name) {
                    return;
                }

                var clean = name.replace(/\[\]$/, '');
                var type = ($field.attr('type') || '').toLowerCase();

                if (type === 'checkbox') {
                    if (!draftData[clean]) {
                        draftData[clean] = [];
                    }
                    if ($field.is(':checked')) {
                        draftData[clean].push($field.val());
                    }
                    return;
                }

                if (type === 'radio') {
                    if ($field.is(':checked')) {
                        draftData[clean] = $field.val();
                    }
                    return;
                }

                if ($field.is('select[multiple]')) {
                    draftData[clean] = $field.val() || [];
                    return;
                }

                if (type === 'file') {
                    return;
                }

                draftData[clean] = $field.val();
                if (!email && clean.toLowerCase().indexOf('email') !== -1) {
                    email = $field.val();
                }
            });

            var currentToken = $form.attr('data-resume-token') || '';
            $.post(spfFrontend.ajaxurl, {
                action: 'spf_save_draft',
                nonce: spfFrontend.nonce,
                form_id: $form.data('form-id'),
                resume_token: currentToken,
                email: email,
                draft_data: JSON.stringify(draftData)
            }, function(response) {
                if (!response || !response.success || !response.data) {
                    return;
                }

                if (response.data.resume_token) {
                    $form.attr('data-resume-token', response.data.resume_token);
                }

                if (announce) {
                    var $msg = $form.find('.spf-draft-message');
                    if ($msg.length) {
                        $msg.html('Draft saved. Resume URL: <a href="' + response.data.resume_url + '">' + response.data.resume_url + '</a>').show();
                    }
                }
            });
        },

        clearDraftData: function($form) {
            if (!$form || !$form.length) {
                return;
            }
            $form.attr('data-resume-token', '');
        },

        /* ---- Calculation Fields ---- */
        initCalculationFields: function() {
            var self = this;
            $(document).on('input change', '.spf-form input, .spf-form select', function() {
                var $form = $(this).closest('.spf-form');
                self.recalculate($form);
            });
            // Initial calc on page load
            $('.spf-form').each(function() {
                self.recalculate($(this));
            });
        },

        recalculate: function($form) {
            $form.find('.spf-calc-result').each(function() {
                var $field = $(this);
                var formula = $field.data('formula');
                if (!formula) return;

                // Replace {field_name} tokens with actual values
                var result = formula.replace(/\{([^}]+)\}/g, function(match, name) {
                    var $input = $form.find('[name="' + name + '"]');
                    if (!$input.length) $input = $form.find('[name="' + name + '[]"]');
                    var val = parseFloat($input.val());
                    return isNaN(val) ? 0 : val;
                });

                // Safely evaluate basic math (only numbers and operators)
                try {
                    if (/^[\d\s+\-*/().]+$/.test(result)) {
                        var value = Function('"use strict";return (' + result + ')')();
                        $field.val(isNaN(value) ? 0 : Math.round(value * 100) / 100);
                    }
                } catch(e) {
                    $field.val(0);
                }
            });
        },

        /* ---- Repeater Fields ---- */
        initRepeaterFields: function() {
            $(document).on('click', '.spf-repeater-add', function() {
                var $wrap = $(this).closest('.spf-repeater-field');
                var $rows = $wrap.find('.spf-repeater-rows');
                var max = parseInt($wrap.data('max'), 10) || 10;
                var currentCount = $rows.find('.spf-repeater-row').length;

                if (currentCount >= max) return;

                var $template = $rows.find('.spf-repeater-row').first().clone();
                var newIndex = currentCount;

                $template.attr('data-row-index', newIndex);
                $template.find('input, textarea, select').each(function() {
                    var name = $(this).attr('name') || '';
                    // Replace the row index in the name
                    name = name.replace(/\[\d+\]/, '[' + newIndex + ']');
                    $(this).attr('name', name).val('');
                });
                $template.find('.spf-repeater-remove').show();
                $rows.append($template);

                if ($rows.find('.spf-repeater-row').length >= max) {
                    $(this).hide();
                }
            });

            $(document).on('click', '.spf-repeater-remove', function() {
                var $wrap = $(this).closest('.spf-repeater-field');
                var $rows = $wrap.find('.spf-repeater-rows');
                var min = parseInt($wrap.data('min'), 10) || 1;

                if ($rows.find('.spf-repeater-row').length <= min) return;

                $(this).closest('.spf-repeater-row').remove();
                $wrap.find('.spf-repeater-add').show();

                // Re-index rows
                $rows.find('.spf-repeater-row').each(function(idx) {
                    $(this).attr('data-row-index', idx);
                    $(this).find('input, textarea, select').each(function() {
                        var name = $(this).attr('name') || '';
                        name = name.replace(/\[\d+\]/, '[' + idx + ']');
                        $(this).attr('name', name);
                    });
                    $(this).find('.spf-repeater-remove').toggle(idx > 0 || $rows.find('.spf-repeater-row').length > min);
                });
            });
        },

        /* ---- Signature Fields ---- */
        initSignatureFields: function() {
            $('.spf-signature-field').each(function() {
                var $wrap = $(this);
                var canvas = $wrap.find('.spf-signature-canvas')[0];
                if (!canvas) return;

                var ctx = canvas.getContext('2d');
                var drawing = false;

                function getPos(e) {
                    var rect = canvas.getBoundingClientRect();
                    var touch = e.touches ? e.touches[0] : e;
                    return { x: touch.clientX - rect.left, y: touch.clientY - rect.top };
                }

                function startDraw(e) {
                    e.preventDefault();
                    drawing = true;
                    var pos = getPos(e);
                    ctx.beginPath();
                    ctx.moveTo(pos.x, pos.y);
                }

                function draw(e) {
                    if (!drawing) return;
                    e.preventDefault();
                    var pos = getPos(e);
                    ctx.lineTo(pos.x, pos.y);
                    ctx.strokeStyle = '#000';
                    ctx.lineWidth = 2;
                    ctx.lineCap = 'round';
                    ctx.stroke();
                }

                function endDraw(e) {
                    if (!drawing) return;
                    drawing = false;
                    // Save signature data as base64
                    $wrap.find('.spf-signature-data').val(canvas.toDataURL('image/png'));
                }

                canvas.addEventListener('mousedown', startDraw);
                canvas.addEventListener('mousemove', draw);
                canvas.addEventListener('mouseup', endDraw);
                canvas.addEventListener('mouseleave', endDraw);
                canvas.addEventListener('touchstart', startDraw, {passive: false});
                canvas.addEventListener('touchmove', draw, {passive: false});
                canvas.addEventListener('touchend', endDraw);

                $wrap.find('.spf-signature-clear').on('click', function() {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    $wrap.find('.spf-signature-data').val('');
                });
            });
        },

        /* ---- Multi-File Upload Preview ---- */
        initMultiFilePreview: function() {
            $(document).on('change', '.spf-field-file[multiple]', function() {
                var $input = $(this);
                var files = $input[0].files;
                var $list = $input.siblings('.spf-file-list');
                if (!$list.length) return;

                $list.empty();
                for (var i = 0; i < files.length; i++) {
                    var size = (files[i].size / 1024).toFixed(1);
                    $list.append('<div class="spf-file-item" style="padding:4px 0;font-size:13px;color:#555;"><span class="dashicons dashicons-media-default" style="font-size:14px;width:14px;height:14px;vertical-align:middle;"></span> ' + $('<span>').text(files[i].name).html() + ' <small>(' + size + ' KB)</small></div>');
                }
            });
        }
    };
    
    // Initialize dynamic fields (list field)
    SPF_Frontend.initDynamicFields = function() {
        // List field - Add Item button
        $(document).on('click', '.spf-add-list-item', function() {
            var $listField = $(this).closest('.spf-list-field');
            var $itemsContainer = $listField.find('.spf-list-items');
            var $firstInput = $itemsContainer.find('input').first();
            var fieldName = $firstInput.attr('name');
            var itemCount = $itemsContainer.find('input').length + 1;
            
            var $newInput = $('<input type="text" name="' + fieldName + '" class="spf-field-input" placeholder="Item ' + itemCount + '" style="margin-bottom: 5px;">');
            $itemsContainer.append($newInput);
            $newInput.focus();
        });
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        SPF_Frontend.init();
    });
    
})(jQuery);