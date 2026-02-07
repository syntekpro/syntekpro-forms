/**
 * SyntekPro Forms - Frontend JavaScript
 */

(function($) {
    'use strict';
    
    var SPF_Frontend = {
        
        activeForm: null,

        // Initialize
        init: function() {
            var self = this;
            this.initFormSubmission();
            this.initValidation();
            this.initSteps();
            this.initDynamicFields();

            // Global callback for reCAPTCHA
            window.spfRecaptchaCallback = function(token) {
                if (self.activeForm) {
                    self.submitFormAjax(self.activeForm);
                }
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