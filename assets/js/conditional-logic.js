/**
 * Advanced Forms Builder - Conditional Logic
 */

(function($) {
    'use strict';
    
    var SPF_ConditionalLogic = {
        
        // Initialize
        init: function() {
            this.initConditionalRules();
            this.monitorFieldChanges();
        },
        
        // Initialize conditional rules
        initConditionalRules: function() {
            // This will be expanded to handle conditional field visibility
            console.log('Conditional logic initialized');
        },
        
        // Monitor field changes
        monitorFieldChanges: function() {
            var self = this;
            
            $(document).on('change', '.spf-form input, .spf-form select, .spf-form textarea', function() {
                self.evaluateConditions();
            });
        },
        
        // Evaluate all conditional rules
        evaluateConditions: function() {
            // Example: Show/hide fields based on conditions
            // This is a placeholder for future conditional logic functionality
            console.log('Evaluating conditions');
        },
        
        // Show field
        showField: function(fieldId) {
            $('.spf-field-wrapper[data-field-id="' + fieldId + '"]').slideDown();
        },
        
        // Hide field
        hideField: function(fieldId) {
            $('.spf-field-wrapper[data-field-id="' + fieldId + '"]').slideUp();
        },
        
        // Check if condition is met
        checkCondition: function(field, operator, value) {
            var fieldValue = this.getFieldValue(field);
            
            switch(operator) {
                case 'is':
                    return fieldValue === value;
                case 'is_not':
                    return fieldValue !== value;
                case 'contains':
                    return fieldValue.indexOf(value) !== -1;
                case 'greater_than':
                    return parseFloat(fieldValue) > parseFloat(value);
                case 'less_than':
                    return parseFloat(fieldValue) < parseFloat(value);
                default:
                    return false;
            }
        },
        
        // Get field value
        getFieldValue: function(fieldName) {
            var $field = $('.spf-form [name="' + fieldName + '"]');
            
            if ($field.attr('type') === 'checkbox') {
                var values = [];
                $field.filter(':checked').each(function() {
                    values.push($(this).val());
                });
                return values.join(',');
            } else if ($field.attr('type') === 'radio') {
                return $field.filter(':checked').val() || '';
            } else {
                return $field.val() || '';
            }
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        SPF_ConditionalLogic.init();
    });
    
})(jQuery);