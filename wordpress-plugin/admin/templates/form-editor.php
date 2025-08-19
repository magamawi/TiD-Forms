<?php
/**
 * Admin Template: Form Editor
 */

if (!defined('ABSPATH')) {
    exit;
}

$form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
$form = $form_id ? InnovativeForms_Form_Manager::get_form($form_id) : null;
$is_edit = !empty($form);
?>

<div class="wrap innovative-forms-admin">
    <h1 class="wp-heading-inline">
        <?php echo $is_edit ? __('Edit Form', 'innovative-forms') : __('Add New Form', 'innovative-forms'); ?>
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=innovative-forms'); ?>" class="page-title-action">
        <?php _e('Back to Forms', 'innovative-forms'); ?>
    </a>
    
    <hr class="wp-header-end">
    
    <?php if (!$is_edit): ?>
        <div class="innovative-forms-templates">
            <h2><?php _e('Choose a Template', 'innovative-forms'); ?></h2>
            <p><?php _e('Start with a pre-designed template or create a custom form from scratch.', 'innovative-forms'); ?></p>
            
            <div class="template-grid">
                <div class="template-card" data-template="newsletter">
                    <div class="template-preview">
                        <div class="template-icon">
                            <span class="dashicons dashicons-email-alt"></span>
                        </div>
                        <h3><?php _e('Newsletter Subscription', 'innovative-forms'); ?></h3>
                        <p><?php _e('Perfect for collecting email subscribers with interest preferences and GDPR compliance.', 'innovative-forms'); ?></p>
                        <div class="template-fields">
                            <span class="field-tag"><?php _e('Name', 'innovative-forms'); ?></span>
                            <span class="field-tag"><?php _e('Email', 'innovative-forms'); ?></span>
                            <span class="field-tag"><?php _e('Interests', 'innovative-forms'); ?></span>
                            <span class="field-tag"><?php _e('GDPR', 'innovative-forms'); ?></span>
                        </div>
                    </div>
                    <button type="button" class="button button-primary use-template" data-template="newsletter">
                        <?php _e('Use This Template', 'innovative-forms'); ?>
                    </button>
                </div>
                
                <div class="template-card" data-template="contributor">
                    <div class="template-preview">
                        <div class="template-icon">
                            <span class="dashicons dashicons-groups"></span>
                        </div>
                        <h3><?php _e('Contributors Registration', 'innovative-forms'); ?></h3>
                        <p><?php _e('Comprehensive form for registering contributors, experts, and community members.', 'innovative-forms'); ?></p>
                        <div class="template-fields">
                            <span class="field-tag"><?php _e('Personal Info', 'innovative-forms'); ?></span>
                            <span class="field-tag"><?php _e('Company', 'innovative-forms'); ?></span>
                            <span class="field-tag"><?php _e('Contribution Type', 'innovative-forms'); ?></span>
                            <span class="field-tag"><?php _e('Additional Info', 'innovative-forms'); ?></span>
                        </div>
                    </div>
                    <button type="button" class="button button-primary use-template" data-template="contributor">
                        <?php _e('Use This Template', 'innovative-forms'); ?>
                    </button>
                </div>
                
                <div class="template-card" data-template="contact">
                    <div class="template-preview">
                        <div class="template-icon">
                            <span class="dashicons dashicons-phone"></span>
                        </div>
                        <h3><?php _e('Contact Form', 'innovative-forms'); ?></h3>
                        <p><?php _e('Simple contact form for general inquiries and customer support.', 'innovative-forms'); ?></p>
                        <div class="template-fields">
                            <span class="field-tag"><?php _e('Name', 'innovative-forms'); ?></span>
                            <span class="field-tag"><?php _e('Email', 'innovative-forms'); ?></span>
                            <span class="field-tag"><?php _e('Subject', 'innovative-forms'); ?></span>
                            <span class="field-tag"><?php _e('Message', 'innovative-forms'); ?></span>
                        </div>
                    </div>
                    <button type="button" class="button button-primary use-template" data-template="contact">
                        <?php _e('Use This Template', 'innovative-forms'); ?>
                    </button>
                </div>
                
                <div class="template-card" data-template="custom">
                    <div class="template-preview">
                        <div class="template-icon">
                            <span class="dashicons dashicons-admin-customizer"></span>
                        </div>
                        <h3><?php _e('Custom Form', 'innovative-forms'); ?></h3>
                        <p><?php _e('Start with a blank form and add your own fields and customizations.', 'innovative-forms'); ?></p>
                        <div class="template-fields">
                            <span class="field-tag"><?php _e('Blank Canvas', 'innovative-forms'); ?></span>
                            <span class="field-tag"><?php _e('Full Control', 'innovative-forms'); ?></span>
                        </div>
                    </div>
                    <button type="button" class="button button-secondary use-template" data-template="custom">
                        <?php _e('Start from Scratch', 'innovative-forms'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Template Selection Modal -->
        <div id="template-modal" class="innovative-forms-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><?php _e('Create Form', 'innovative-forms'); ?></h2>
                    <button type="button" class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="create-form-form">
                        <div class="form-field">
                            <label for="form-name"><?php _e('Form Name', 'innovative-forms'); ?></label>
                            <input type="text" id="form-name" name="form_name" class="regular-text" required>
                            <p class="description"><?php _e('Enter a descriptive name for your form.', 'innovative-forms'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="form-theme"><?php _e('Form Theme', 'innovative-forms'); ?></label>
                            <select id="form-theme" name="form_theme">
                                <?php foreach ($themes as $theme_id => $theme): ?>
                                    <option value="<?php echo esc_attr($theme_id); ?>">
                                        <?php echo esc_html($theme['name']); ?> - <?php echo esc_html($theme['description']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <input type="hidden" id="selected-template" name="template" value="">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button button-secondary modal-cancel"><?php _e('Cancel', 'innovative-forms'); ?></button>
                    <button type="button" class="button button-primary" id="create-form-btn"><?php _e('Create Form', 'innovative-forms'); ?></button>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Edit Form Interface -->
        <div class="innovative-forms-editor">
            <div class="editor-header">
                <h2><?php echo esc_html($form->name); ?></h2>
                <div class="editor-actions">
                    <button type="button" class="button button-secondary preview-form">
                        <?php _e('Preview Form', 'innovative-forms'); ?>
                    </button>
                    <button type="button" class="button button-primary save-form">
                        <?php _e('Save Changes', 'innovative-forms'); ?>
                    </button>
                </div>
            </div>
            
            <div class="editor-content">
                <div class="editor-sidebar">
                    <div class="sidebar-section">
                        <h3><?php _e('Form Settings', 'innovative-forms'); ?></h3>
                        <div class="form-field">
                            <label for="edit-form-name"><?php _e('Form Name', 'innovative-forms'); ?></label>
                            <input type="text" id="edit-form-name" value="<?php echo esc_attr($form->name); ?>" class="regular-text">
                        </div>
                        <div class="form-field">
                            <label for="edit-form-description"><?php _e('Description', 'innovative-forms'); ?></label>
                            <textarea id="edit-form-description" rows="3" class="regular-text"><?php echo esc_textarea($form->description); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="sidebar-section">
                        <h3><?php _e('Shortcode', 'innovative-forms'); ?></h3>
                        <div class="shortcode-container">
                            <code>[innovative_form id="<?php echo $form->id; ?>"]</code>
                            <button type="button" class="button button-small copy-shortcode" data-shortcode="[innovative_form id=&quot;<?php echo $form->id; ?>&quot;]">
                                <?php _e('Copy', 'innovative-forms'); ?>
                            </button>
                        </div>
                        <p class="description"><?php _e('Use this shortcode to display the form on any page or post.', 'innovative-forms'); ?></p>
                    </div>
                    
                    <div class="sidebar-section">
                        <h3><?php _e('Form Statistics', 'innovative-forms'); ?></h3>
                        <?php $stats = InnovativeForms_Form_Manager::get_form_stats($form->id); ?>
                        <div class="stats-list">
                            <div class="stat-item">
                                <span class="stat-label"><?php _e('Total Entries:', 'innovative-forms'); ?></span>
                                <span class="stat-value"><?php echo number_format($stats['total_entries']); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label"><?php _e('This Month:', 'innovative-forms'); ?></span>
                                <span class="stat-value"><?php echo number_format($stats['entries_this_month']); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label"><?php _e('This Week:', 'innovative-forms'); ?></span>
                                <span class="stat-value"><?php echo number_format($stats['entries_this_week']); ?></span>
                            </div>
                        </div>
                        <a href="<?php echo admin_url('admin.php?page=innovative-forms-entries&form_id=' . $form->id); ?>" class="button button-secondary">
                            <?php _e('View All Entries', 'innovative-forms'); ?>
                        </a>
                    </div>
                </div>
                
                <div class="editor-main">
                    <div class="form-preview">
                        <h3><?php _e('Form Preview', 'innovative-forms'); ?></h3>
                        <div class="preview-container">
                            <?php echo do_shortcode('[innovative_form id="' . $form->id . '"]'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Template selection
    $('.use-template').on('click', function() {
        var template = $(this).data('template');
        $('#selected-template').val(template);
        
        // Set default form name based on template
        var defaultNames = {
            'newsletter': '<?php _e('Newsletter Subscription', 'innovative-forms'); ?>',
            'contributor': '<?php _e('Contributors Registration', 'innovative-forms'); ?>',
            'contact': '<?php _e('Contact Form', 'innovative-forms'); ?>',
            'custom': '<?php _e('Custom Form', 'innovative-forms'); ?>'
        };
        
        $('#form-name').val(defaultNames[template] || '');
        $('#template-modal').show();
    });
    
    // Modal close
    $('.modal-close, .modal-cancel').on('click', function() {
        $('#template-modal').hide();
    });
    
    // Create form
    $('#create-form-btn').on('click', function() {
        var formData = {
            action: 'innovative_forms_create_form',
            nonce: '<?php echo wp_create_nonce('innovative_forms_admin_nonce'); ?>',
            template: $('#selected-template').val(),
            form_name: $('#form-name').val(),
            form_theme: $('#form-theme').val()
        };
        
        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                window.location.href = response.data.redirect_url;
            } else {
                alert(response.data || '<?php _e('Error creating form', 'innovative-forms'); ?>');
            }
        });
    });
    
    // Copy shortcode
    $('.copy-shortcode').on('click', function() {
        var shortcode = $(this).data('shortcode');
        var tempInput = $('<input>');
        $('body').append(tempInput);
        tempInput.val(shortcode).select();
        document.execCommand('copy');
        tempInput.remove();
        
        $(this).text('<?php _e('Copied!', 'innovative-forms'); ?>');
        setTimeout(() => {
            $(this).text('<?php _e('Copy', 'innovative-forms'); ?>');
        }, 2000);
    });
});
</script>

