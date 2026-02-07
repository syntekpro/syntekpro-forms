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
                this.initFieldSections();
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
                handle: '.spf-field-sort-handle',
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
        
        initFieldSections: function() {
            var self = this;
            
            // Handle collapsible field sections
            $('.spf-field-section-header').on('click', function() {
                var $header = $(this);
                var section = $header.data('section');
                var $content = $('[data-section-content="' + section + '"]');
                
                $header.toggleClass('collapsed');
                $content.toggleClass('collapsed');
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
            
            // Field types that need options
            if (['select', 'radio', 'checkbox', 'multiple-choice', 'multi-select', 'post-category'].indexOf(type) !== -1) {
                field.options = ['Option 1', 'Option 2', 'Option 3'];
            }
            
            // Image choice needs special structure
            if (type === 'image-choice') {
                field.options = [
                    {label: 'Choice 1', image: ''},
                    {label: 'Choice 2', image: ''},
                    {label: 'Choice 3', image: ''}
                ];
            }
            
            // Name field needs sub-fields
            if (type === 'name') {
                field.format = 'first-last'; // or 'first-middle-last'
                field.subfields = {
                    first_name: true,
                    middle_name: false,
                    last_name: true
                };
            }
            
            // Address field needs sub-fields
            if (type === 'address') {
                field.subfields = {
                    street: true,
                    city: true,
                    state: true,
                    zip: true,
                    country: true
                };
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
                'phone': 'Enter phone number...',
                'website': 'https://example.com',
                'date': 'Select date...',
                'time': 'Select time...',
                'hidden': '',
                'html': '',
                'section': '',
                'page': '',
                'name': 'Enter name...',
                'address': 'Enter address...',
                'captcha': '',
                'list': 'Add items...',
                'consent': '',
                'post-title': 'Enter post title...',
                'post-body': 'Enter post content...',
                'post-excerpt': 'Enter excerpt...',
                'post-tags': 'Enter tags separated by commas...',
                'post-custom-field': 'Enter value...',
                'select': '',
                'radio': '',
                'checkbox': '',
                'file': '',
                'step': ''
            };
            return placeholders[type] || '';
        },
        
        getFieldLabel: function(type) {
            var labels = {
                'text': 'Single Line Text',
                'email': 'Email',
                'textarea': 'Paragraph Text',
                'number': 'Number',
                'select': 'Drop Down',
                'radio': 'Radio Buttons',
                'checkbox': 'Checkboxes',
                'hidden': 'Hidden Field',
                'html': 'HTML Block',
                'section': 'Section Break',
                'page': 'Page Break',
                'multiple-choice': 'Multiple Choice',
                'image-choice': 'Image Choice',
                'name': 'Name',
                'date': 'Date',
                'time': 'Time',
                'phone': 'Phone',
                'address': 'Address',
                'website': 'Website URL',
                'file': 'File Upload',
                'captcha': 'CAPTCHA',
                'list': 'List',
                'multi-select': 'Multi Select',
                'consent': 'Consent',
                'post-title': 'Post Title',
                'post-body': 'Post Body',
                'post-excerpt': 'Post Excerpt',
                'post-tags': 'Post Tags',
                'post-category': 'Post Category',
                'post-image': 'Post Featured Image',
                'post-custom-field': 'Custom Field',
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
            html += '<div class="spf-field-sort-handle"><span class="dashicons dashicons-arrow-up-alt2"></span><span class="dashicons dashicons-arrow-down-alt2"></span></div>';
            html += '<span class="spf-field-title">' + this.escapeHtml(field.label) + ' <small>(' + field.type + ')</small></span>';
            html += '<div class="spf-field-actions">';
            html += '<button type="button" class="spf-action-btn spf-edit-field spf-tooltip" title="Edit"><span class="dashicons dashicons-edit"></span></button>';
            html += '<button type="button" class="spf-action-btn spf-delete-field spf-tooltip" title="Delete"><span class="dashicons dashicons-trash"></span></button>';
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
            } else if (field.type === 'section' || field.type === 'page') {
                html += '<div class="spf-section-preview">' + this.escapeHtml(field.label || field.type) + '</div>';
            } else if (field.type === 'html') {
                html += '<div class="spf-html-preview">[HTML Content]</div>';
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
                case 'phone':
                case 'website':
                case 'post-title':
                case 'post-tags':
                case 'post-custom-field':
                    html += '<input type="text" placeholder="' + this.escapeHtml(field.placeholder || '') + '" disabled>';
                    break;
                
                case 'textarea':
                case 'post-body':
                case 'post-excerpt':
                    html += '<textarea placeholder="' + this.escapeHtml(field.placeholder || '') + '" rows="3" disabled></textarea>';
                    break;
                
                case 'date':
                    html += '<input type="date" disabled>';
                    break;
                
                case 'time':
                    html += '<input type="time" disabled>';
                    break;
                
                case 'hidden':
                    html += '<div class="spf-hidden-field-preview">[Hidden field - not visible to users]</div>';
                    break;
                
                case 'name':
                    html += '<div class="spf-name-field-preview">';
                    html += '<input type="text" placeholder="First Name" disabled style="width: 48%; margin-right: 4%;">';
                    html += '<input type="text" placeholder="Last Name" disabled style="width: 48%;">';
                    html += '</div>';
                    break;
                
                case 'address':
                    html += '<div class="spf-address-field-preview">';
                    html += '<input type="text" placeholder="Street Address" disabled style="margin-bottom: 8px;">';
                    html += '<div style="display: flex; gap: 8px;">';
                    html += '<input type="text" placeholder="City" disabled style="flex: 1;">';
                    html += '<input type="text" placeholder="State" disabled style="flex: 1;">';
                    html += '<input type="text" placeholder="ZIP" disabled style="flex: 1;">';
                    html += '</div>';
                    html += '</div>';
                    break;
                
                case 'select':
                case 'post-category':
                    html += '<select disabled><option>-- Select --</option>';
                    if (field.options) {
                        field.options.forEach(function(opt) {
                            html += '<option>' + opt + '</option>';
                        });
                    }
                    html += '</select>';
                    break;
                
                case 'multi-select':
                    html += '<select multiple disabled style="height: 80px;"><option>-- Select Multiple --</option>';
                    if (field.options) {
                        field.options.forEach(function(opt) {
                            html += '<option>' + opt + '</option>';
                        });
                    }
                    html += '</select>';
                    break;
                
                case 'radio':
                case 'checkbox':
                case 'multiple-choice':
                    if (field.options) {
                        field.options.forEach(function(opt) {
                            var inputType = (field.type === 'multiple-choice' || field.type === 'radio') ? 'radio' : 'checkbox';
                            html += '<label style="display: block; margin: 5px 0;"><input type="' + inputType + '" disabled> ' + opt + '</label>';
                        });
                    }
                    break;
                
                case 'image-choice':
                    html += '<div class="spf-image-choice-preview">';
                    if (field.options) {
                        field.options.forEach(function(opt) {
                            html += '<div class="spf-image-option" style="display: inline-block; margin: 5px; text-align: center;">';
                            html += '<div style="width: 80px; height: 80px; border: 2px dashed #ddd; background: #f9f9f9; display: flex; align-items: center; justify-content: center; margin-bottom: 5px;">';
                            html += '<span class="dashicons dashicons-format-image" style="font-size: 24px; color: #ccc;"></span>';
                            html += '</div>';
                            html += '<label><input type="radio" disabled> ' + (opt.label || 'Choice') + '</label>';
                            html += '</div>';
                        });
                    }
                    html += '</div>';
                    break;
                
                case 'file':
                case 'post-image':
                    html += '<input type="file" disabled>';
                    break;
                
                case 'captcha':
                    html += '<div class="spf-captcha-preview" style="background: #f0f0f0; padding: 15px; text-align: center; border: 1px solid #ddd;">';
                    html += '<span class="dashicons dashicons-shield" style="font-size: 32px; color: #666;"></span>';
                    html += '<p style="margin: 10px 0 0;">CAPTCHA Verification</p>';
                    html += '</div>';
                    break;
                
                case 'list':
                    html += '<div class="spf-list-preview">';
                    html += '<input type="text" placeholder="Item 1" disabled style="margin-bottom: 5px;">';
                    html += '<input type="text" placeholder="Item 2" disabled>';
                    html += '<button type="button" disabled style="margin-top: 5px;">+ Add Item</button>';
                    html += '</div>';
                    break;
                
                case 'consent':
                    html += '<label style="display: block;"><input type="checkbox" disabled> I agree to the terms and conditions</label>';
                    break;
                
                case 'step':
                    html += '<div class="spf-step-divider">' + this.escapeHtml(field.description || 'Step break') + '</div>';
                    break;
            }
            
            if (field.description && field.type !== 'step') {
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

            $('.spf-field-item').removeClass('active');
            $('.spf-field-item[data-field-id="' + fieldId + '"]').addClass('active');
            
            var settingsHtml = '<div class="spf-field-settings-content">';
            
            // Section 1: Basic Information (default open)
            settingsHtml += '<div class="spf-settings-section active">';
            settingsHtml += '<div class="spf-section-header"><h4>Basic Information <span class="dashicons dashicons-arrow-down-alt2"></span></h4></div>';
            settingsHtml += '<div class="spf-section-body">';
            
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
            settingsHtml += '</div></div>'; // End Basic Information

            // Section 2: Options (conditional)
            if (['select', 'radio', 'checkbox'].indexOf(fieldData.type) !== -1) {
                settingsHtml += '<div class="spf-settings-section">';
                settingsHtml += '<div class="spf-section-header"><h4>Field Options <span class="dashicons dashicons-arrow-down-alt2"></span></h4></div>';
                settingsHtml += '<div class="spf-section-body">';
                settingsHtml += '<div class="spf-setting-row">';
                settingsHtml += '<label>Choices (one per line)</label>';
                settingsHtml += '<textarea class="spf-setting-options" rows="5" style="font-family: monospace; font-size: 13px;">' + this.escapeHtml(fieldData.options ? fieldData.options.join('\n') : '') + '</textarea>';
                settingsHtml += '</div>';
                settingsHtml += '</div></div>';
            }

            // Section 3: Advanced Settings
            settingsHtml += '<div class="spf-settings-section">';
            settingsHtml += '<div class="spf-section-header"><h4>Advanced & Validation <span class="dashicons dashicons-arrow-down-alt2"></span></h4></div>';
            settingsHtml += '<div class="spf-section-body">';
            
            settingsHtml += '<div class="spf-setting-row">';
            settingsHtml += '<label>Description/Help Text</label>';
            settingsHtml += '<textarea class="spf-setting-description" rows="3">' + this.escapeHtml(fieldData.description || '') + '</textarea>';
            settingsHtml += '</div>';
            
            if (fieldData.type !== 'step') {
                settingsHtml += '<div class="spf-setting-row">';
                var isChecked = (fieldData.required === true) ? ' checked' : '';
                settingsHtml += '<label style="display: flex; align-items: center; gap: 8px; text-transform: none; font-weight: 500;"><input type="checkbox" class="spf-setting-required"' + isChecked + '> Required Field</label>';
                settingsHtml += '</div>';
            }
            settingsHtml += '</div></div>'; // End Advanced

            settingsHtml += '<div style="margin: 20px; display: flex; justify-content: flex-end;">';
            settingsHtml += '<button type="button" class="spf-submit-button spf-save-field-settings" data-field-id="' + fieldId + '">Save Settings</button>';
            settingsHtml += '</div>';
            settingsHtml += '</div>'; // End content
            
            var $settingsPanel = $('#spf-field-settings');
            if ($settingsPanel.length) {
                $settingsPanel.html(settingsHtml); 
                
                // Show field settings window and slide up other panes
                var $sidebar = $('.spf-sidebar');
                var $fieldWindow = $('#spf-field-settings-window');
                
                if (!$fieldWindow.is(':visible')) {
                    $sidebar.find('.spf-sidebar-tabs').slideUp(200);
                    $sidebar.find('.spf-sidebar-content').slideUp(200);
                    $fieldWindow.slideDown(200);
                }
                
                $settingsPanel.find('.spf-save-field-settings').on('click', function(e) {
                    e.preventDefault();
                    self.saveFieldSettings(fieldId);
                });

                // Initialize accordion state: open first section only
                var $sections = $settingsPanel.find('.spf-settings-section');
                $sections.removeClass('active').find('.spf-section-body').hide();
                $sections.first().addClass('active').find('.spf-section-body').show();

                // Add toggle functionality for field settings sections (accordion style)
                $settingsPanel.find('.spf-section-header').on('click', function() {
                    var $section = $(this).parent('.spf-settings-section');
                    var $container = $section.parent();
                    $container.find('.spf-settings-section').not($section).removeClass('active').find('.spf-section-body').slideUp(200);
                    $section.toggleClass('active');
                    $section.find('.spf-section-body').slideToggle(200);
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
            
            // Check if we're on the form builder page
            if ($('.spf-form-builder-wrap').length > 0) {
                // Insert after the top navigation bar in the form builder
                $('.spf-builder-top-nav').after($notification);
            } else if ($('.wrap h1').length > 0) {
                // Standard WordPress admin page
                $('.wrap h1').after($notification);
            } else {
                // Fallback: prepend to .wrap or body
                if ($('.wrap').length > 0) {
                    $('.wrap').prepend($notification);
                } else {
                    $('body').prepend($notification);
                }
            }
            
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

            // NEW: Tab switching in right sidebar
            $(document).off('click', '.spf-tab-btn').on('click', '.spf-tab-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var tabName = $(this).data('tab');
                var $sidebar = $(this).closest('.spf-sidebar');
                var $fieldWindow = $('#spf-field-settings-window');
                
                if ($fieldWindow.is(':visible')) {
                    $fieldWindow.slideUp(200);
                    $sidebar.find('.spf-sidebar-tabs').slideDown(200);
                    $sidebar.find('.spf-sidebar-content').slideDown(200);
                }
                
                $sidebar.find('.spf-tab-btn').removeClass('active');
                $(this).addClass('active');
                
                $sidebar.find('.spf-tab-content').removeClass('active');
                $sidebar.find('#spf-tab-' + tabName).addClass('active');
            });

            // NEW: Collapsible sidebar panels (H3) with accordion behavior
            var initPanelAccordion = function() {
                $('.spf-settings-panel').each(function() {
                    var $panel = $(this);
                    var $headers = $panel.find('h3');

                    $headers.each(function(index) {
                        var $header = $(this);
                        var $content = $header.next('.spf-panel-collapsible-content');
                        if (!$content.length) return;
                        if (index === 0) {
                            $header.removeClass('collapsed');
                            $content.show();
                        } else {
                            $header.addClass('collapsed');
                            $content.hide();
                        }
                    });
                });
            };

            initPanelAccordion();

            $(document).on('click', '.spf-settings-panel h3', function() {
                var $panel = $(this).closest('.spf-settings-panel');
                var $content = $(this).next('.spf-panel-collapsible-content');
                if (!$content.length) return;

                $panel.find('h3').not(this).addClass('collapsed').next('.spf-panel-collapsible-content').slideUp(200);
                $(this).toggleClass('collapsed');
                $content.slideToggle(200);
            });

            // Close Field Settings Window
            $(document).off('click', '.spf-field-settings-close').on('click', '.spf-field-settings-close', function() {
                var $sidebar = $('.spf-sidebar');
                $('#spf-field-settings-window').slideUp(200);
                $sidebar.find('.spf-sidebar-tabs').slideDown(200);
                $sidebar.find('.spf-sidebar-content').slideDown(200);
            });

            // NEW: Recent Forms Dropdown Toggle
            $(document).off('click', '#spf-recent-forms-toggle').on('click', '#spf-recent-forms-toggle', function(e) {
                e.preventDefault();
                var $dropdown = $('#spf-recent-forms-dropdown');
                $dropdown.toggle();
                
                if ($dropdown.is(':visible')) {
                    self.loadRecentForms();
                }
            });

            // Close dropdown when clicking outside
            $(document).on('click.spf-dropdown-close', function(e) {
                if (!$(e.target).closest('.spf-nav-recent-forms').length) {
                    $('#spf-recent-forms-dropdown').hide();
                }
            });

            // NEW: Search recent forms
            $(document).off('keyup', '#spf-forms-search').on('keyup', '#spf-forms-search', function() {
                var searchTerm = $(this).val().toLowerCase();
                $('#spf-forms-list .spf-dropdown-item').each(function() {
                    var text = $(this).text().toLowerCase();
                    $(this).toggle(text.indexOf(searchTerm) > -1);
                });
            });

            // NEW: Embed Button
            $(document).off('click', '#spf-embed-form-btn').on('click', '#spf-embed-form-btn', function() {
                $('#spf-embed-modal').show();
            });

            // NEW: Editor Preferences Button
            $(document).off('click', '#spf-editor-preferences-btn').on('click', '#spf-editor-preferences-btn', function() {
                $('#spf-editor-prefs-modal').show();
                // Load saved preferences
                var compactView = localStorage.getItem('spf-compact-view') === 'true';
                var showFieldIds = localStorage.getItem('spf-show-field-ids') === 'true';
                $('#spf-compact-view').prop('checked', compactView);
                $('#spf-show-field-ids').prop('checked', showFieldIds).prop('disabled', !compactView);
            });

            // NEW: Modal close buttons
            $(document).off('click', '.spf-modal-close, #spf-prefs-close').on('click', '.spf-modal-close, #spf-prefs-close', function() {
                $(this).closest('.spf-modal').hide();
            });

            // Close modal when clicking outside
            $(document).off('click', '.spf-modal').on('click', '.spf-modal', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });

            // NEW: Embed Modal Tabs
            $(document).off('click', '.spf-embed-tab-btn').on('click', '.spf-embed-tab-btn', function() {
                var tabName = $(this).data('tab');
                
                $('.spf-embed-tab-btn').removeClass('active');
                $(this).addClass('active');
                
                $('.spf-embed-tab-content').removeClass('active');
                $('#spf-embed-' + tabName).addClass('active');
                
                // Load posts/pages when switching to post-page tab
                if (tabName === 'post-page') {
                    self.loadPostsForEmbed($('input[name="embed-post-type"]:checked').val());
                }
            });
            
            // NEW: Load posts when post type changes
            $(document).off('change', 'input[name="embed-post-type"]').on('change', 'input[name="embed-post-type"]', function() {
                var postType = $(this).val();
                self.loadPostsForEmbed(postType);
            });

            // NEW: Copy Shortcode
            $(document).off('click', '#spf-copy-shortcode').on('click', '#spf-copy-shortcode', function() {
                var text = $('#spf-shortcode-copy').text();
                self.copyToClipboard(text, $(this));
            });

            // NEW: Copy PHP Code
            $(document).off('click', '#spf-copy-php-code').on('click', '#spf-copy-php-code', function() {
                var text = 'echo ' + $('#spf-shortcode-copy').text() + ';';
                self.copyToClipboard(text, $(this));
            });

            // NEW: Insert to Existing Post/Page
            $(document).off('click', '#spf-insert-to-post').on('click', '#spf-insert-to-post', function() {
                var postType = $('input[name="embed-post-type"]:checked').val();
                var postId = $('#spf-select-post').val();
                var formId = $('#spf-form-id').val();
                
                if (!postId) {
                    alert('Please select a post/page');
                    return;
                }
                
                $.ajax({
                    url: spfAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'spf_insert_form_to_post',
                        nonce: spfAdmin.nonce,
                        post_id: postId,
                        form_id: formId
                    },
                    success: function(response) {
                        if (response.success) {
                            self.showNotification('Form inserted successfully!', 'success');
                            setTimeout(function() {
                                window.location.href = response.data.edit_url;
                            }, 1500);
                        } else {
                            self.showNotification(response.data || 'Error inserting form', 'error');
                        }
                    },
                    error: function() {
                        self.showNotification('Error inserting form', 'error');
                    }
                });
            });

            // NEW: Create New Post/Page and Insert
            $(document).off('click', '#spf-create-and-insert').on('click', '#spf-create-and-insert', function() {
                var postType = $('input[name="create-type"]:checked').val();
                var title = $('#spf-create-title').val();
                var formId = $('#spf-form-id').val();
                
                if (!title.trim()) {
                    alert('Please enter a title');
                    return;
                }
                
                $.ajax({
                    url: spfAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'spf_create_post_with_form',
                        nonce: spfAdmin.nonce,
                        post_type: postType,
                        title: title,
                        form_id: formId
                    },
                    success: function(response) {
                        if (response.success) {
                            self.showNotification('Post created successfully!', 'success');
                            setTimeout(function() {
                                window.location.href = response.data.edit_url;
                            }, 1500);
                        } else {
                            self.showNotification(response.data || 'Error creating post', 'error');
                        }
                    },
                    error: function() {
                        self.showNotification('Error creating post', 'error');
                    }
                });
            });

            // NEW: Save Editor Preferences
            $(document).off('change', '#spf-compact-view').on('change', '#spf-compact-view', function() {
                localStorage.setItem('spf-compact-view', $(this).is(':checked'));
                $('#spf-show-field-ids').prop('disabled', !$(this).is(':checked'));
            });

            $(document).off('change', '#spf-show-field-ids').on('change', '#spf-show-field-ids', function() {
                localStorage.setItem('spf-show-field-ids', $(this).is(':checked'));
            });
        },

        // NEW: Load Recent Forms
        loadRecentForms: function() {
            var $list = $('#spf-forms-list');
            $list.html('<div class="spf-loading">Loading forms...</div>');
            
            $.ajax({
                url: spfAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'spf_get_recent_forms',
                    nonce: spfAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        var html = '';
                        $.each(response.data, function(i, form) {
                            html += '<a href="?page=syntekpro-forms&action=edit&form_id=' + form.id + '" class="spf-dropdown-item">';
                            html += form.title + ' <small style="color:#999;">#' + form.id + '</small>';
                            html += '</a>';
                        });
                        $list.html(html);
                    } else {
                        $list.html('<div class="spf-loading">No forms found</div>');
                    }
                }
            });
        },

        // NEW: Copy to Clipboard
        copyToClipboard: function(text, $btn) {
            var originalText = $btn.html();
            navigator.clipboard.writeText(text).then(function() {
                $btn.html('<span class="dashicons dashicons-yes"></span> Copied!');
                setTimeout(function() {
                    $btn.html(originalText);
                }, 2000);
            }).catch(function(err) {
                // Fallback for older browsers
                var $textarea = $('<textarea>').val(text).appendTo('body');
                $textarea[0].select();
                document.execCommand('copy');
                $textarea.remove();
                $btn.html('<span class="dashicons dashicons-yes"></span> Copied!');
                setTimeout(function() {
                    $btn.html(originalText);
                }, 2000);
            });
        },
        
        loadPostsForEmbed: function(postType) {
            var $select = $('#spf-select-post');
            $select.html('<option value="">Loading...</option>');
            
            $.ajax({
                url: spfAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'spf_get_posts_for_embed',
                    nonce: spfAdmin.nonce,
                    post_type: postType
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        var html = '<option value="">-- Choose One --</option>';
                        $.each(response.data, function(i, post) {
                            html += '<option value="' + post.ID + '">' + post.post_title + ' (ID: ' + post.ID + ')</option>';
                        });
                        $select.html(html);
                    } else {
                        $select.html('<option value="">No ' + postType + 's found</option>');
                    }
                },
                error: function() {
                    $select.html('<option value="">Error loading ' + postType + 's</option>');
                }
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