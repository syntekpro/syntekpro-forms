/**
 * SyntekPro Forms - Admin JavaScript - FIXED
 */

(function($) {
    'use strict';
    
    window.spfFieldsData = window.spfFieldsData || {};
    
    var SPF_Admin = {
        
        init: function() {
            // Only initialize if we are on the form builder page
            if ($('#spf-form-fields').length > 0) {
                this.initTabs();
                this.initSync();
                this.initFormBuilder();
                this.initFieldTypes();
                this.initFormSave();
                this.initFormActions();
                this.initLivePreview();
            }

            // Entries page initialization
            if ($('#spf-entries-table-wrap').length > 0) {
                this.initEntries();
            }
        },

        initEntries: function() {
            var self = this;
            var $entriesWrap = $('#spf-entries-table-wrap');

            // Select All Checkbox
            $entriesWrap.off('change', '#cb-select-all-1').on('change', '#cb-select-all-1', function() {
                $entriesWrap.find('.spf-entry-checkbox').prop('checked', $(this).prop('checked'));
            });

            // Individual View Entry
            $entriesWrap.off('click', '.spf-view-entry').on('click', '.spf-view-entry', function() {
                var entryId = $(this).data('entry-id');
                var $row = $(this).closest('tr');
                
                $.ajax({
                    url: spfAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'spf_get_entry',
                        nonce: spfAdmin.nonce,
                        entry_id: entryId
                    },
                    beforeSend: function() {
                        $('#spf-entry-details-content').html('<div style="text-align:center;padding:40px;"><span class="dashicons dashicons-update spin"></span> Loading...</div>');
                        $('#spf-entry-modal').css('display', 'flex').fadeIn();
                    },
                    success: function(response) {
                        if (response.success) {
                            var html = '';
                            
                            // Form & Metadata info
                            html += '<div class="spf-entry-metadata" style="background:#f9f9f9;padding:15px;border-radius:4px;margin-bottom:20px;display:grid;grid-template-columns:1fr 1fr;gap:15px;">';
                            html += '<div><strong>' + 'Form:' + '</strong><br>' + response.data.form_title + '</div>';
                            html += '<div><strong>' + 'Submitted:' + '</strong><br>' + response.data.created_at + '</div>';
                            html += '<div><strong>' + 'IP Address:' + '</strong><br>' + response.data.ip_address + '</div>';
                            html += '<div><strong>' + 'User Agent:' + '</strong><br><small>' + response.data.user_agent + '</small></div>';
                            html += '</div>';

                            if (response.data.entry_data) {
                                $.each(response.data.entry_data, function(key, value) {
                                    html += '<div class="spf-entry-detail-row">';
                                    html += '<div class="spf-detail-label">' + key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) + '</div>';
                                    html += '<div class="spf-detail-value">' + (Array.isArray(value) ? value.join(', ') : value) + '</div>';
                                    html += '</div>';
                                });
                            }
                            
                            $('#spf-entry-details-content').html(html);
                            
                            // Mark as read in UI
                            $row.removeClass('spf-unread-row');
                            $row.find('.spf-status-badge').removeClass('spf-status-unread').addClass('spf-status-read').text('Read');
                            
                            // Send silent mark as read request
                            $.post(spfAdmin.ajaxurl, {
                                action: 'spf_mark_entry_read',
                                nonce: spfAdmin.nonce,
                                entry_id: entryId
                            });
                        } else {
                            $('#spf-entry-details-content').html('<div class="error">' + response.data + '</div>');
                        }
                    }
                });
            });

            // Delete Individual Entry
            $entriesWrap.off('click', '.spf-delete-entry').on('click', '.spf-delete-entry', function() {
                if (!confirm(spfAdmin.strings.confirmDelete)) return;
                
                var entryId = $(this).data('entry-id');
                var $row = $(this).closest('tr');
                
                $.ajax({
                    url: spfAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'spf_delete_entry',
                        nonce: spfAdmin.nonce,
                        entry_id: entryId
                    },
                    success: function(response) {
                        if (response.success) {
                            $row.fadeOut(function() { $(this).remove(); });
                        } else {
                            alert(response.data);
                        }
                    }
                });
            });

            // Bulk Actions
            $('#spf-apply-bulk-action').on('click', function() {
                var action = $('#spf-bulk-action-selector').val();
                if (!action) return;

                var selectedIds = [];
                $entriesWrap.find('.spf-entry-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                if (selectedIds.length === 0) {
                    alert('Please select at least one entry.');
                    return;
                }

                if (action === 'delete') {
                    if (!confirm('Are you sure you want to delete these ' + selectedIds.length + ' entries?')) return;

                    $.ajax({
                        url: spfAdmin.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'spf_bulk_delete_entries',
                            nonce: spfAdmin.nonce,
                            entry_ids: selectedIds
                        },
                        success: function(response) {
                            if (response.success) {
                                $entriesWrap.find('.spf-entry-checkbox:checked').closest('tr').fadeOut(function() { $(this).remove(); });
                                $entriesWrap.find('#cb-select-all-1').prop('checked', false);
                            } else {
                                alert(response.data);
                            }
                        }
                    });
                } else if (action === 'mark_read') {
                    // Mark multiple as read
                    selectedIds.forEach(function(id) {
                        var $row = $entriesWrap.find('tr[data-entry-id="' + id + '"]');
                        $.post(spfAdmin.ajaxurl, {
                            action: 'spf_mark_entry_read',
                            nonce: spfAdmin.nonce,
                            entry_id: id
                        }, function() {
                            $row.removeClass('spf-unread-row');
                            $row.find('.spf-status-badge').removeClass('spf-status-unread').addClass('spf-status-read').text('Read');
                        });
                    });
                    $entriesWrap.find('.spf-entry-checkbox').prop('checked', false);
                    $entriesWrap.find('#cb-select-all-1').prop('checked', false);
                }
            });

            // Close Modal
            $('.spf-modal-close, .spf-modal').on('click', function(e) {
                if (e.target === this || $(e.target).hasClass('spf-modal-close')) {
                    $('#spf-entry-modal').fadeOut();
                }
            });

            this.initEntriesSearch();
        },

        initEntriesSearch: function() {
            var self = this;
            var $filtersForm = $('.spf-filters-form');

            if (!$filtersForm.length) {
                return;
            }

            var debounceTimer;
            $filtersForm.on('submit', function(e) {
                e.preventDefault();
                self.fetchEntries(1);
            });

            $('#spf-entries-search').on('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    self.fetchEntries(1);
                }, 350);
            });

            $('#spf-entries-form, #spf-entries-status, #spf-entries-date-from, #spf-entries-date-to').on('change', function() {
                self.fetchEntries(1);
            });

            $('#spf-entries-pagination').on('click', '.spf-page-link', function(e) {
                e.preventDefault();
                var page = parseInt($(this).data('page'), 10) || 1;
                self.fetchEntries(page);
            });

            self.fetchEntries(1);
        },

        fetchEntries: function(page) {
            var self = this;
            var perPage = parseInt($('#spf-entries-per-page').val(), 10) || 20;

            $.ajax({
                url: spfAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'spf_search_entries',
                    nonce: spfAdmin.nonce,
                    form_id: $('#spf-entries-form').val() || '',
                    status: $('#spf-entries-status').val() || '',
                    s: $('#spf-entries-search').val() || '',
                    date_from: $('#spf-entries-date-from').val() || '',
                    date_to: $('#spf-entries-date-to').val() || '',
                    page: page || 1,
                    per_page: perPage
                },
                beforeSend: function() {
                    $('#spf-entries-table-wrap').addClass('spf-loading');
                },
                success: function(response) {
                    if (!response.success) {
                        alert(response.data || 'Failed to load entries');
                        return;
                    }

                    self.renderEntriesTable(response.data.entries || []);
                    self.renderEntriesPagination(response.data);
                },
                complete: function() {
                    $('#spf-entries-table-wrap').removeClass('spf-loading');
                }
            });
        },

        renderEntriesTable: function(entries) {
            var self = this;
            var $wrap = $('#spf-entries-table-wrap');

            if (!entries.length) {
                $wrap.html('<div class="spf-empty-state"><div class="spf-empty-icon"><span class="dashicons dashicons-database"></span></div><p>No entries found matching your criteria.</p></div>');
                return;
            }

            var html = '';
            html += '<table class="wp-list-table widefat fixed striped spf-entries-table">';
            html += '<thead><tr>';
            html += '<td id="cb" class="manage-column column-cb check-column">';
            html += '<label class="screen-reader-text" for="cb-select-all-1">Select All</label>';
            html += '<input id="cb-select-all-1" type="checkbox">';
            html += '</td>';
            html += '<th class="column-id">ID</th>';
            html += '<th>Form Name</th>';
            html += '<th>Entry Data Preview</th>';
            html += '<th>Date Submitted</th>';
            html += '<th class="column-status">Status</th>';
            html += '<th class="column-actions">Actions</th>';
            html += '</tr></thead>';
            html += '<tbody>';

            entries.forEach(function(entry) {
                var previewHtml = self.buildEntryPreview(entry.entry_data || {});
                var status = (entry.status || 'unread');
                var statusLabel = status.charAt(0).toUpperCase() + status.slice(1);

                html += '<tr class="' + (status === 'unread' ? 'spf-unread-row' : '') + '" data-entry-id="' + entry.id + '">';
                html += '<th scope="row" class="check-column"><input type="checkbox" name="entry[]" value="' + entry.id + '" class="spf-entry-checkbox"></th>';
                html += '<td>#' + entry.id + '</td>';
                html += '<td><strong>' + self.escapeHtml(entry.form_title || '') + '</strong><br><small>' + self.escapeHtml(entry.ip_address || '') + '</small></td>';
                html += '<td><div class="spf-entry-preview">' + previewHtml + '</div></td>';
                html += '<td>' + self.escapeHtml(entry.created_at || '') + '</td>';
                html += '<td><span class="spf-status-badge spf-status-' + self.escapeHtml(status) + '">' + self.escapeHtml(statusLabel) + '</span></td>';
                html += '<td class="spf-row-actions">';
                html += '<button class="button button-small spf-view-entry spf-tooltip" title="View full details" data-entry-id="' + entry.id + '"><span class="dashicons dashicons-visibility"></span></button>';
                html += '<button class="button button-small spf-delete-entry spf-tooltip" title="Delete entry" data-entry-id="' + entry.id + '"><span class="dashicons dashicons-trash"></span></button>';
                html += '</td>';
                html += '</tr>';
            });

            html += '</tbody></table>';
            $wrap.html(html);
        },

        renderEntriesPagination: function(data) {
            var $pagination = $('#spf-entries-pagination');
            if (!$pagination.length) {
                return;
            }

            var page = parseInt(data.page, 10) || 1;
            var totalPages = parseInt(data.total_pages, 10) || 1;

            if (totalPages <= 1) {
                $pagination.html('');
                return;
            }

            var html = '<div class="spf-pagination">';
            if (page > 1) {
                html += '<a href="#" class="spf-page-link" data-page="' + (page - 1) + '">&laquo; Previous</a>';
            }

            html += '<span class="spf-page-info">Page ' + page + ' of ' + totalPages + '</span>';

            if (page < totalPages) {
                html += '<a href="#" class="spf-page-link" data-page="' + (page + 1) + '">Next &raquo;</a>';
            }

            html += '</div>';
            $pagination.html(html);
        },

        buildEntryPreview: function(entryData) {
            var self = this;
            var html = '';
            var count = 0;

            if (entryData && typeof entryData === 'object') {
                Object.keys(entryData).forEach(function(key) {
                    if (count >= 2) {
                        return;
                    }
                    var value = entryData[key];
                    var displayValue = Array.isArray(value) ? value.join(', ') : String(value || '');
                    html += '<span class="spf-preview-item"><strong>' + self.escapeHtml(key.replace(/_/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); })) + ':</strong> ' + self.escapeHtml(displayValue.slice(0, 50)) + '</span>';
                    count++;
                });
            }

            return html;
        },

        initLivePreview: function() {
            var self = this;
            var stylingInputs = [
                '#spf-form-theme',
                '#spf-font-family',
                '#spf-font-size',
                '#spf-field-padding',
                '#spf-border-radius',
                '#spf-primary-color',
                '#spf-label-color',
                '#spf-bg-color',
                '#spf-submit-align'
            ];

            $(stylingInputs.join(',')).on('input change', function() {
                self.updateLivePreview();
            });

            // Initial preview update
            this.updateLivePreview();
        },

        updateLivePreview: function() {
            var theme = $('#spf-form-theme').val();
            var fontFamily = $('#spf-font-family').val();
            var fontSize = $('#spf-font-size').val();
            var fieldPadding = $('#spf-field-padding').val();
            var borderRadius = $('#spf-border-radius').val();
            var primaryColor = $('#spf-primary-color').val();
            var labelColor = $('#spf-label-color').val();
            var bgColor = $('#spf-bg-color').val();
            var submitAlign = $('#spf-submit-align').val();

            // Handle potential undefined or empty values from templates
            primaryColor = primaryColor || '#0073aa';
            labelColor = labelColor || '#1d2327';
            bgColor = bgColor || '#ffffff';
            borderRadius = borderRadius || '4';
            fieldPadding = fieldPadding || '12';
            fontSize = fontSize || '16';
            fontFamily = fontFamily || 'inherit';

            var css = `
                .spf-form-fields-canvas,
                .spf-main-content .spf-form-fields-canvas {
                    --spf-primary-color: ${primaryColor};
                    --spf-label-color: ${labelColor};
                    --spf-bg-color: ${bgColor};
                    --spf-border-radius: ${borderRadius}px;
                    --spf-field-padding: ${fieldPadding}px;
                    --spf-font-size: ${fontSize}px;
                    --spf-font-family: ${fontFamily === 'inherit' ? 'inherit' : fontFamily};
                }
                .spf-form-fields-canvas {
                    font-family: var(--spf-font-family);
                    font-size: var(--spf-font-size);
                    background-color: var(--spf-bg-color);
                }
                .spf-form-fields-canvas label {
                    color: var(--spf-label-color);
                }
                .spf-form-fields-canvas .spf-form-footer {
                    display: flex;
                    justify-content: ${submitAlign === 'center' ? 'center' : (submitAlign === 'right' ? 'flex-end' : 'flex-start')};
                }
                .spf-form-fields-canvas .spf-submit-button {
                    background-color: var(--spf-primary-color);
                }
                .spf-form-fields-canvas .spf-field-body input,
                .spf-form-fields-canvas .spf-field-body textarea,
                .spf-form-fields-canvas .spf-field-body select {
                    padding: var(--spf-field-padding);
                    border-radius: var(--spf-border-radius);
                }
                .spf-form-fields-canvas .spf-field-item:hover,
                .spf-form-fields-canvas .spf-field-item.active {
                    border-color: var(--spf-primary-color);
                }
            `;

            $('#spf-live-preview-styles').text(css);

            // Update canvas theme class
            $('#spf-form-fields').removeClass('spf-theme-classic spf-theme-modern spf-theme-minimal spf-theme-elegant spf-theme-contrast spf-theme-pastel spf-theme-outline spf-theme-glass')
                                .addClass('spf-theme-' + (theme || 'classic'));
        },

        initTabs: function() {
            $('.spf-tab-btn').on('click', function() {
                var tab = $(this).data('tab');
                $('.spf-tab-btn').removeClass('active');
                $(this).addClass('active');
                $('.spf-tab-content').removeClass('active');
                $('#spf-tab-' + tab).addClass('active');
            });
        },

        initSync: function() {
            // Sync title and description between sidebar and canvas
            $('.spf-sync-title').on('input', function() {
                $('.spf-sync-title').val($(this).val());
            });
            $('.spf-sync-desc').on('input', function() {
                $('.spf-sync-desc').val($(this).val());
            });
            $('#spf-submit-button-text').on('input', function() {
                $('#spf-submit-button-preview').text($(this).val());
            });
        },
        
        initFormBuilder: function() {
            var self = this;
            
            // Load existing form data if editing
            if (typeof spfFormData !== 'undefined' && spfFormData.fields && spfFormData.fields.length > 0) {
                console.log('Populating builder with fields:', spfFormData.fields.length);
                // Remove empty state if we have fields
                $('#spf-form-fields .spf-empty-builder').remove();
                
                spfFormData.fields.forEach(function(field) {
                    // CRITICAL FIX: Ensure required is boolean, not string
                    field.required = (field.required === true || field.required === 'true' || field.required === 1 || field.required === '1');
                    
                    window.spfFieldsData[field.id] = field;
                    self.addFieldToBuilder(field);
                });
            }
            
            $('#spf-form-fields').sortable({
                handle: '.spf-field-header',
                placeholder: 'spf-field-placeholder',
                opacity: 0.6,
                update: function() {
                    self.updateFieldOrder();
                }
            });
        },
        
        initFieldTypes: function() {
            var self = this;
            
            $('.spf-field-type').on('click', function() {
                var fieldType = $(this).data('type');
                var field = self.createField(fieldType);
                window.spfFieldsData[field.id] = field;
                self.addFieldToBuilder(field);
                
                $('.spf-empty-builder').remove();

                // Automatically open settings for the newly added field
                self.editField(field.id);
                
                // Switch to styling tab if needed? No, usually people want to edit field settings first.
            });
            
            $('.spf-field-type').attr('draggable', true);
            
            $('.spf-field-type').on('dragstart', function(e) {
                e.originalEvent.dataTransfer.setData('fieldType', $(this).data('type'));
                $(this).addClass('dragging');
            });
            
            $('.spf-field-type').on('dragend', function() {
                $(this).removeClass('dragging');
            });
            
            $('#spf-form-fields').on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('drag-over');
            });
            
            $('#spf-form-fields').on('dragleave', function() {
                $(this).removeClass('drag-over');
            });
            
            $('#spf-form-fields').on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over');
                
                var fieldType = e.originalEvent.dataTransfer.getData('fieldType');
                if (fieldType) {
                    var field = self.createField(fieldType);
                    window.spfFieldsData[field.id] = field;
                    self.addFieldToBuilder(field);
                    $('.spf-empty-builder').remove();
                    
                    // Automatically open settings for the newly added field
                    self.editField(field.id);
                }
            });
        },
        
        createField: function(type) {
            var timestamp = Date.now();
            var randomStr = Math.random().toString(36).substr(2, 5);
            var fieldId = 'field_' + timestamp + '_' + randomStr;
            
            // Generate a more readable default name
            var count = $('.spf-field-item').length + 1;
            var readableName = type + '_' + count;
            
            var field = {
                id: fieldId,
                type: type,
                name: readableName,
                label: this.getFieldLabel(type),
                placeholder: this.getFieldDefaultPlaceholder(type),
                description: '',
                required: false, // BOOLEAN, not string!
                options: []
            };
            
            if (['select', 'radio', 'checkbox'].indexOf(type) !== -1) {
                field.options = ['Option 1', 'Option 2', 'Option 3'];
            }

            if (type === 'step') {
                field.required = false;
                field.placeholder = '';
                field.options = [];
            }
            
            return field;
        },
        
        getFieldDefaultPlaceholder: function(type) {
            var placeholders = {
                'text': 'Enter text...',
                'email': 'Enter email address...',
                'textarea': 'Enter your message...',
                'number': 'Enter number...',
                'select': '',
                'radio': '',
                'checkbox': '',
                'date': '',
                'file': '',
                'step': ''
            };
            return placeholders[type] || '';
        },
        
        getFieldLabel: function(type) {
            var labels = {
                'text': 'Text Field',
                'email': 'Email Address',
                'textarea': 'Message',
                'number': 'Number',
                'select': 'Select Option',
                'radio': 'Radio Choice',
                'checkbox': 'Checkbox Options',
                'date': 'Date',
                'file': 'File Upload',
                'step': 'Step'
            };
            return labels[type] || 'Field';
        },
        
        addFieldToBuilder: function(field) {
            var self = this;
            var fieldHtml = this.renderField(field);
            $('#spf-form-fields').append(fieldHtml);
            
            this.attachFieldEvents(field.id);
        },
        
        renderField: function(field) {
            var html = '<div class="spf-field-item" data-field-id="' + field.id + '">';
            html += '<div class="spf-field-header">';
            html += '<span class="spf-field-title"><span class="dashicons dashicons-sort"></span> ' + this.escapeHtml(field.label) + ' (' + field.type + ')</span>';
            html += '<div class="spf-field-actions">';
            html += '<button type="button" class="button button-small spf-edit-field spf-tooltip" title="Edit field settings">Edit</button>';
            html += '<button type="button" class="button button-small spf-delete-field spf-tooltip" title="Remove this field">Delete</button>';
            html += '</div>';
            html += '</div>';
            html += '<div class="spf-field-body">';
            html += this.renderFieldPreview(field);
            html += '</div>';
            html += '</div>';
            
            return html;
        },
        
        renderFieldPreview: function(field) {
            var html = '';
            if (field.type === 'step') {
                html += '<div class="spf-step-preview">' + this.escapeHtml(field.label || 'Step') + '</div>';
            } else {
                html += '<label>' + this.escapeHtml(field.label);
                // CRITICAL FIX: Check boolean properly
                if (field.required === true) {
                    html += ' <span class="spf-required">*</span>';
                }
                html += '</label>';
            }
            
            switch (field.type) {
                case 'text':
                case 'email':
                case 'number':
                case 'date':
                    html += '<input type="' + field.type + '" placeholder="' + this.escapeHtml(field.placeholder || '') + '" disabled>';
                    break;
                case 'textarea':
                    html += '<textarea placeholder="' + this.escapeHtml(field.placeholder || '') + '" rows="3" disabled></textarea>';
                    break;
                case 'select':
                    html += '<select disabled><option>-- Select --</option>';
                    if (field.options) {
                        field.options.forEach(function(opt) {
                            html += '<option>' + opt + '</option>';
                        });
                    }
                    html += '</select>';
                    break;
                case 'radio':
                case 'checkbox':
                    if (field.options) {
                        field.options.forEach(function(opt) {
                            html += '<label style="display: block; margin: 5px 0;"><input type="' + field.type + '" disabled> ' + opt + '</label>';
                        });
                    }
                    break;
                case 'file':
                    html += '<input type="file" disabled>';
                    break;
                case 'step':
                    html += '<div class="spf-step-divider">' + this.escapeHtml(field.description || 'Step break') + '</div>';
                    break;
            }
            
            if (field.description) {
                html += '<p class="description">' + this.escapeHtml(field.description) + '</p>';
            }
            
            return html;
        },
        
        attachFieldEvents: function(fieldId) {
            var self = this;
            var $field = $('.spf-field-item[data-field-id="' + fieldId + '"]');
            
            // Allow clicking the entire field item to edit
            $field.on('click', function(e) {
                // Don't trigger if clicking on actions (delete button)
                if ($(e.target).closest('.spf-field-actions').length || $(e.target).hasClass('spf-delete-field')) {
                    return;
                }
                self.editField(fieldId);
                
                // Add smooth scroll to settings on mobile/small screens if they are stacked
                if ($(window).width() <= 1200) {
                    $('html, body').animate({
                        scrollTop: $('#spf-field-settings').offset().top - 50
                    }, 500);
                }
            });
            
            $field.find('.spf-edit-field').on('click', function(e) {
                e.stopPropagation(); // Prevent double trigger since parent also has click event
                self.editField(fieldId);
            });
            
            $field.find('.spf-delete-field').on('click', function(e) {
                e.stopPropagation();
                if (confirm('Are you sure you want to delete this field?')) {
                    delete window.spfFieldsData[fieldId];
                    $field.fadeOut(function() {
                        $(this).remove();
                        
                        if ($('#spf-form-fields .spf-field-item').length === 0) {
                            $('#spf-form-fields').html('<div class="spf-empty-builder"><p>Drag and drop fields from the left sidebar to build your form</p></div>');
                        }
                    });
                }
            });
        },
        
        editField: function(fieldId) {
            var self = this;
            var fieldData = window.spfFieldsData[fieldId];
            
            if (!fieldData) {
                console.error('AFB: Field data not found for ID: ' + fieldId);
                return;
            }

            // Ensure we are on the "Fields" tab to show the edit menu if it was hidden or if on another tab
            // Actually, the field settings are in the right sidebar, which is always visible.
            // But we might want to make sure the right sidebar is visible if it was hidden (though it's not currently)
            
            $('.spf-field-item').removeClass('active');
            $('.spf-field-item[data-field-id="' + fieldId + '"]').addClass('active');
            
            var settingsHtml = '<div class="spf-field-settings-header"><h3><span class="dashicons dashicons-admin-tools"></span> Field Settings</h3></div>';
            settingsHtml += '<div class="spf-field-settings-content">';
            settingsHtml += '<div class="spf-setting-row">';
            settingsHtml += '<label>Field Label</label>';
            settingsHtml += '<input type="text" class="spf-setting-label" value="' + this.escapeHtml(fieldData.label || '') + '">';
            settingsHtml += '</div>';
            
            if (fieldData.type !== 'step') {
                settingsHtml += '<div class="spf-setting-row">';
                settingsHtml += '<label>Field Name</label>';
                settingsHtml += '<input type="text" class="spf-setting-name" value="' + this.escapeHtml(fieldData.name || '') + '">';
                settingsHtml += '</div>';
                
                settingsHtml += '<div class="spf-setting-row">';
                settingsHtml += '<label>Placeholder</label>';
                settingsHtml += '<input type="text" class="spf-setting-placeholder" value="' + this.escapeHtml(fieldData.placeholder || '') + '">';
                settingsHtml += '</div>';
            }
            
            settingsHtml += '<div class="spf-setting-row">';
            settingsHtml += '<label>Description</label>';
            settingsHtml += '<textarea class="spf-setting-description">' + this.escapeHtml(fieldData.description || '') + '</textarea>';
            settingsHtml += '</div>';
            
            if (fieldData.type !== 'step') {
                settingsHtml += '<div class="spf-setting-row">';
                // CRITICAL FIX: Check boolean value properly
                var isChecked = (fieldData.required === true) ? ' checked' : '';
                settingsHtml += '<label><input type="checkbox" class="spf-setting-required"' + isChecked + '> Required Field</label>';
                settingsHtml += '</div>';
            }
            
            if (['select', 'radio', 'checkbox'].indexOf(fieldData.type) !== -1) {
                settingsHtml += '<div class="spf-setting-row">';
                settingsHtml += '<label>Options (one per line)</label>';
                settingsHtml += '<textarea class="spf-setting-options" rows="5">' + this.escapeHtml(fieldData.options ? fieldData.options.join('\n') : '') + '</textarea>';
                settingsHtml += '</div>';
            }
            
            settingsHtml += '<button type="button" class="button button-primary button-large spf-save-field-settings" data-field-id="' + fieldId + '"><span class="dashicons dashicons-saved"></span> Save Settings</button>';
            settingsHtml += '</div>'; // End content
            
            var $settingsPanel = $('#spf-field-settings');
            if ($settingsPanel.length) {
                $settingsPanel.html(settingsHtml); // Replace entire panel content to include header
                
                $settingsPanel.find('.spf-save-field-settings').on('click', function(e) {
                    e.preventDefault();
                    self.saveFieldSettings(fieldId);
                });
            }
        },
        
        saveFieldSettings: function(fieldId) {
            var fieldData = window.spfFieldsData[fieldId];
            
            if (!fieldData) return;
            
            fieldData.label = $('.spf-setting-label').val();
            fieldData.description = $('.spf-setting-description').val();

            if (fieldData.type !== 'step') {
                fieldData.name = $('.spf-setting-name').val();
                fieldData.placeholder = $('.spf-setting-placeholder').val();
                // CRITICAL FIX: Store as boolean, not string!
                fieldData.required = $('.spf-setting-required').is(':checked');
            } else {
                fieldData.required = false;
            }
            
            if ($('.spf-setting-options').length > 0) {
                var optionsText = $('.spf-setting-options').val();
                fieldData.options = optionsText.split('\n').filter(function(opt) {
                    return opt.trim() !== '';
                });
            }
            
            window.spfFieldsData[fieldId] = fieldData;
            
            var $field = $('.spf-field-item[data-field-id="' + fieldId + '"]');
            $field.find('.spf-field-title').text(fieldData.label + ' (' + fieldData.type + ')');
            $field.find('.spf-field-body').html(this.renderFieldPreview(fieldData));
            
            this.showNotification('Field settings saved!', 'success');
        },
        
        updateFieldOrder: function() {
            console.log('Field order updated');
        },
        
        initFormSave: function() {
            var self = this;
            
            $('#spf-save-form').on('click', function() {
                self.saveForm();
            });
        },
        
        saveForm: function() {
            var self = this;
            var formId = $('#spf-form-id').val() || '';
            var title = $('#spf-form-title').val();
            var description = $('#spf-form-description').val() || '';
            
            if (!title) {
                self.showNotification('Please enter a form title', 'error');
                return;
            }
            
            var fields = [];
            $('#spf-form-fields .spf-field-item').each(function() {
                var fieldId = $(this).data('field-id');
                if (window.spfFieldsData[fieldId]) {
                    fields.push(window.spfFieldsData[fieldId]);
                }
            });
            
            if (fields.length === 0) {
                self.showNotification('Please add at least one field to your form', 'error');
                return;
            }
            
            $('#spf-save-form').prop('disabled', true).text('Saving...');
            
            if (typeof spfAdmin === 'undefined' || !spfAdmin.ajaxurl) {
                self.showNotification('Error: Plugin configuration missing', 'error');
                $('#spf-save-form').prop('disabled', false).text('Save Form');
                return;
            }
            
            $.ajax({
                url: spfAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'spf_save_form',
                    nonce: spfAdmin.nonce,
                    form_id: formId,
                    title: title,
                    description: description,
                    fields: JSON.stringify(fields),
                    settings: JSON.stringify({
                        submit_button_text: $('#spf-submit-button-text').val(),
                        success_message: $('#spf-success-message').val(),
                        success_behavior: $('#spf-success-behavior').val(),
                        success_redirect_url: $('#spf-success-redirect-url').val(),
                        notify_enabled: $('#spf-notifications-enabled').is(':checked') ? 1 : 0,
                        notify_emails: $('#spf-notification-emails').val(),
                        notifications_enabled: $('#spf-notifications-enabled').is(':checked') ? 1 : 0,
                        notification_emails: $('#spf-notification-emails').val(),
                        submission_limit: $('#spf-submission-limit').val(),
                        submission_limit_message: $('#spf-submission-limit-message').val(),
                        schedule_start: $('#spf-schedule-start').val(),
                        schedule_end: $('#spf-schedule-end').val(),
                        schedule_not_started_message: $('#spf-schedule-not-started-message').val(),
                        schedule_expired_message: $('#spf-schedule-expired-message').val(),
                        theme: $('#spf-form-theme').val(),
                        font_family: $('#spf-font-family').val(),
                        font_size: $('#spf-font-size').val(),
                        field_padding: $('#spf-field-padding').val(),
                        border_radius: $('#spf-border-radius').val(),
                        primary_color: $('#spf-primary-color').val(),
                        label_color: $('#spf-label-color').val(),
                        bg_color: $('#spf-bg-color').val(),
                        submit_align: $('#spf-submit-align').val(),
                        webhook_enabled: $('#spf-webhook-enabled').is(':checked') ? 1 : 0,
                        webhook_urls: $('#spf-webhook-urls').val()
                    })
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification('Form saved successfully!', 'success');
                        
                        if (!formId && response.data && response.data.form_id) {
                            $('#spf-form-id').val(response.data.form_id);
                            setTimeout(function() {
                                window.location.href = '?page=syntekpro-forms-new&form_id=' + response.data.form_id;
                            }, 1500);
                        }
                    } else {
                        self.showNotification('Error: ' + (response.data || 'Failed to save form'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error, xhr.responseText);
                    self.showNotification('Error: ' + error, 'error');
                },
                complete: function() {
                    $('#spf-save-form').prop('disabled', false).text('Save Form');
                }
            });
        },
        
        showNotification: function(message, type) {
            $('.spf-notification').remove();
            
            var $notification = $('<div class="notice notice-' + type + ' spf-notification is-dismissible"><p>' + message + '</p></div>');
            
            $('.wrap h1').after($notification);
            
            if (type === 'success') {
                setTimeout(function() {
                    $notification.fadeOut(function() {
                        $(this).remove();
                    });
                }, 3000);
            }
            
            $('html, body').animate({
                scrollTop: 0
            }, 300);
        },
        
        initFormActions: function() {
            var self = this;
            
            $('#spf-preview-form').on('click', function() {
                var formId = $('#spf-form-id').val();
                if (!formId || formId === '0') {
                    self.showNotification('Please save the form first to preview it.', 'error');
                    return;
                }
                
                // For now, we'll open a new window with a dummy page that has the shortcode
                // In a real scenario, this might open a specific preview URL
                var previewUrl = spfAdmin.ajaxurl + '?action=spf_preview_form&form_id=' + formId + '&nonce=' + spfAdmin.nonce;
                window.open(previewUrl, '_blank');
            });
        },
        
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text || '').replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    };
    
    $(document).ready(function() {
        SPF_Admin.init();
    });
    
})(jQuery);