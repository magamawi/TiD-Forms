/**
 * Innovative Forms - Admin JavaScript
 * Handles admin interface interactions
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize admin functionality
    InnovativeFormsAdmin.init();
    
    var InnovativeFormsAdmin = {
        
        init: function() {
            this.bindEvents();
            this.initColorPickers();
            this.initTooltips();
        },
        
        bindEvents: function() {
            // Template selection
            $(document).on('click', '.use-template', this.handleTemplateSelection);
            
            // Modal controls
            $(document).on('click', '.modal-close, .modal-cancel', this.closeModal);
            $(document).on('click', '#create-form-btn', this.createForm);
            
            // Form actions
            $(document).on('click', '.copy-shortcode', this.copyShortcode);
            $(document).on('click', '.delete-form', this.confirmDeleteForm);
            $(document).on('click', '.export-entries', this.exportEntries);
            
            // Entry actions
            $(document).on('click', '.view-entry', this.viewEntry);
            $(document).on('click', '.delete-entry', this.confirmDeleteEntry);
            
            // Bulk actions
            $(document).on('change', '#bulk-action-selector-top', this.handleBulkAction);
            $(document).on('click', '#doaction', this.processBulkAction);
            
            // Form editor
            $(document).on('click', '.save-form', this.saveForm);
            $(document).on('click', '.preview-form', this.previewForm);
            
            // Settings
            $(document).on('change', '.form-theme-selector', this.updateThemePreview);
        },
        
        initColorPickers: function() {
            if ($.fn.wpColorPicker) {
                $('.color-picker').wpColorPicker({
                    change: function(event, ui) {
                        $(this).trigger('colorchange', ui.color.toString());
                    }
                });
            }
        },
        
        initTooltips: function() {
            $('[data-tooltip]').each(function() {
                var $this = $(this);
                var tooltip = $this.data('tooltip');
                
                $this.hover(
                    function() {
                        var $tooltip = $('<div class="admin-tooltip">' + tooltip + '</div>');
                        $('body').append($tooltip);
                        
                        var offset = $this.offset();
                        $tooltip.css({
                            top: offset.top - $tooltip.outerHeight() - 5,
                            left: offset.left + ($this.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                        });
                    },
                    function() {
                        $('.admin-tooltip').remove();
                    }
                );
            });
        },
        
        handleTemplateSelection: function(e) {
            e.preventDefault();
            
            var template = $(this).data('template');
            $('#selected-template').val(template);
            
            // Set default form name based on template
            var defaultNames = {
                'newsletter': innovative_forms_admin.strings.newsletter_form || 'Newsletter Subscription',
                'contributor': innovative_forms_admin.strings.contributor_form || 'Contributors Registration',
                'contact': innovative_forms_admin.strings.contact_form || 'Contact Form',
                'custom': innovative_forms_admin.strings.custom_form || 'Custom Form'
            };
            
            $('#form-name').val(defaultNames[template] || '');
            $('#template-modal').show();
        },
        
        closeModal: function(e) {
            e.preventDefault();
            $('.innovative-forms-modal').hide();
        },
        
        createForm: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var originalText = $button.text();
            
            $button.text(innovative_forms_admin.strings.creating || 'Creating...').prop('disabled', true);
            
            var formData = {
                action: 'innovative_forms_create_form',
                nonce: innovative_forms_admin.nonce,
                template: $('#selected-template').val(),
                form_name: $('#form-name').val(),
                form_theme: $('#form-theme').val()
            };
            
            $.post(innovative_forms_admin.ajax_url, formData)
                .done(function(response) {
                    if (response.success) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        alert(response.data || innovative_forms_admin.strings.error);
                    }
                })
                .fail(function() {
                    alert(innovative_forms_admin.strings.error);
                })
                .always(function() {
                    $button.text(originalText).prop('disabled', false);
                });
        },
        
        copyShortcode: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var shortcode = $button.data('shortcode');
            var originalText = $button.text();
            
            // Create temporary input
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(shortcode).select();
            
            try {
                document.execCommand('copy');
                $button.text(innovative_forms_admin.strings.copied || 'Copied!');
                
                setTimeout(function() {
                    $button.text(originalText);
                }, 2000);
            } catch (err) {
                console.error('Copy failed:', err);
            }
            
            $temp.remove();
        },
        
        confirmDeleteForm: function(e) {
            return confirm(innovative_forms_admin.strings.confirm_delete || 'Are you sure you want to delete this form? This action cannot be undone.');
        },
        
        confirmDeleteEntry: function(e) {
            return confirm(innovative_forms_admin.strings.confirm_delete_entry || 'Are you sure you want to delete this entry?');
        },
        
        exportEntries: function(e) {
            e.preventDefault();
            
            var formId = $(this).data('form-id');
            var $button = $(this);
            var originalText = $button.text();
            
            $button.text(innovative_forms_admin.strings.exporting || 'Exporting...').prop('disabled', true);
            
            // Create form for download
            var $form = $('<form method="post" action="' + innovative_forms_admin.ajax_url + '">');
            $form.append('<input type="hidden" name="action" value="innovative_forms_export_entries">');
            $form.append('<input type="hidden" name="nonce" value="' + innovative_forms_admin.nonce + '">');
            $form.append('<input type="hidden" name="form_id" value="' + formId + '">');
            
            $('body').append($form);
            $form.submit();
            $form.remove();
            
            setTimeout(function() {
                $button.text(originalText).prop('disabled', false);
            }, 2000);
        },
        
        viewEntry: function(e) {
            e.preventDefault();
            
            var entryId = $(this).data('entry-id');
            var $row = $(this).closest('tr');
            
            // Build entry details from table row
            var details = '<div class="entry-details-grid">';
            
            // Get submission date
            var submissionDate = $row.find('.column-date').text();
            details += '<div class="detail-item"><strong>' + (innovative_forms_admin.strings.submission_date || 'Submission Date:') + '</strong> ' + submissionDate + '</div>';
            
            // Get field values
            $row.find('.column-field').each(function(index) {
                var fieldValue = $(this).text();
                var fieldLabel = $row.closest('table').find('th.column-field').eq(index).text();
                details += '<div class="detail-item"><strong>' + fieldLabel + ':</strong> ' + fieldValue + '</div>';
            });
            
            // Get status
            var status = $row.find('.column-status .status-badge').text();
            details += '<div class="detail-item"><strong>' + (innovative_forms_admin.strings.status || 'Status:') + '</strong> ' + status + '</div>';
            
            details += '</div>';
            
            $('#entry-details').html(details);
            $('#entry-modal').show();
        },
        
        handleBulkAction: function() {
            var action = $(this).val();
            var $submitButton = $('#doaction');
            
            if (action && action !== '-1') {
                $submitButton.prop('disabled', false);
            } else {
                $submitButton.prop('disabled', true);
            }
        },
        
        processBulkAction: function(e) {
            var action = $('#bulk-action-selector-top').val();
            var selectedItems = $('input[name="bulk-select[]"]:checked');
            
            if (!action || action === '-1') {
                e.preventDefault();
                alert(innovative_forms_admin.strings.select_action || 'Please select an action.');
                return false;
            }
            
            if (selectedItems.length === 0) {
                e.preventDefault();
                alert(innovative_forms_admin.strings.select_items || 'Please select items to perform the action on.');
                return false;
            }
            
            if (action === 'delete') {
                if (!confirm(innovative_forms_admin.strings.confirm_bulk_delete || 'Are you sure you want to delete the selected items?')) {
                    e.preventDefault();
                    return false;
                }
            }
            
            return true;
        },
        
        saveForm: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var originalText = $button.text();
            
            $button.text(innovative_forms_admin.strings.saving || 'Saving...').prop('disabled', true);
            
            // Collect form data
            var formData = {
                action: 'innovative_forms_save_form',
                nonce: innovative_forms_admin.nonce,
                form_id: $('#form-id').val(),
                form_name: $('#edit-form-name').val(),
                form_description: $('#edit-form-description').val()
            };
            
            $.post(innovative_forms_admin.ajax_url, formData)
                .done(function(response) {
                    if (response.success) {
                        InnovativeFormsAdmin.showNotice(innovative_forms_admin.strings.form_saved || 'Form saved successfully!', 'success');
                    } else {
                        InnovativeFormsAdmin.showNotice(response.data || innovative_forms_admin.strings.error, 'error');
                    }
                })
                .fail(function() {
                    InnovativeFormsAdmin.showNotice(innovative_forms_admin.strings.error, 'error');
                })
                .always(function() {
                    $button.text(originalText).prop('disabled', false);
                });
        },
        
        previewForm: function(e) {
            e.preventDefault();
            
            var formId = $('#form-id').val();
            if (formId) {
                var previewUrl = innovative_forms_admin.preview_url + '?form_id=' + formId;
                window.open(previewUrl, '_blank');
            }
        },
        
        updateThemePreview: function() {
            var theme = $(this).val();
            var $preview = $('.theme-preview');
            
            if ($preview.length) {
                $preview.removeClass().addClass('theme-preview theme-' + theme);
            }
        },
        
        showNotice: function(message, type) {
            type = type || 'info';
            
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after($notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Add dismiss functionality
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            });
        },
        
        initFormBuilder: function() {
            // Initialize drag and drop for form fields
            if ($.fn.sortable) {
                $('.form-fields-list').sortable({
                    handle: '.field-handle',
                    placeholder: 'field-placeholder',
                    update: function(event, ui) {
                        InnovativeFormsAdmin.updateFieldOrder();
                    }
                });
            }
            
            // Add field button
            $(document).on('click', '.add-field-btn', function() {
                var fieldType = $(this).data('field-type');
                InnovativeFormsAdmin.addField(fieldType);
            });
            
            // Remove field button
            $(document).on('click', '.remove-field-btn', function() {
                if (confirm(innovative_forms_admin.strings.confirm_remove_field || 'Are you sure you want to remove this field?')) {
                    $(this).closest('.form-field-item').remove();
                    InnovativeFormsAdmin.updateFieldOrder();
                }
            });
        },
        
        addField: function(fieldType) {
            var fieldTemplate = $('#field-template-' + fieldType).html();
            if (fieldTemplate) {
                var $fieldsList = $('.form-fields-list');
                var fieldIndex = $fieldsList.children().length;
                
                fieldTemplate = fieldTemplate.replace(/{{INDEX}}/g, fieldIndex);
                $fieldsList.append(fieldTemplate);
                
                this.updateFieldOrder();
            }
        },
        
        updateFieldOrder: function() {
            $('.form-field-item').each(function(index) {
                $(this).find('.field-order').val(index);
            });
        }
    };
    
    // Initialize form builder if on form editor page
    if ($('.form-builder').length) {
        InnovativeFormsAdmin.initFormBuilder();
    }
    
    // Handle AJAX errors globally
    $(document).ajaxError(function(event, xhr, settings, error) {
        console.error('AJAX Error:', error);
        InnovativeFormsAdmin.showNotice(innovative_forms_admin.strings.ajax_error || 'An error occurred while processing your request.', 'error');
    });
    
    // Auto-save functionality for form editor
    var autoSaveTimer;
    $('.form-editor input, .form-editor textarea, .form-editor select').on('change input', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            if ($('.auto-save-enabled').length) {
                $('.save-form').trigger('click');
            }
        }, 5000);
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl+S to save
        if (e.ctrlKey && e.which === 83) {
            e.preventDefault();
            $('.save-form').trigger('click');
        }
        
        // Escape to close modals
        if (e.which === 27) {
            $('.innovative-forms-modal').hide();
        }
    });
    
    // Initialize everything
    InnovativeFormsAdmin.init();
});

