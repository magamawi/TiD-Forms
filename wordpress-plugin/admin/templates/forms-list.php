<?php
/**
 * Admin Template: Forms List
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle messages
$message = isset($_GET['message']) ? $_GET['message'] : '';
?>

<div class="wrap innovative-forms-admin">
    <h1 class="wp-heading-inline">
        <?php _e('Innovative Forms', 'innovative-forms'); ?>
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=innovative-forms-new'); ?>" class="page-title-action">
        <?php _e('Add New Form', 'innovative-forms'); ?>
    </a>
    
    <hr class="wp-header-end">
    
    <?php if ($message): ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                switch ($message) {
                    case 'deleted':
                        _e('Form deleted successfully.', 'innovative-forms');
                        break;
                    case 'duplicated':
                        _e('Form duplicated successfully.', 'innovative-forms');
                        break;
                    case 'status_updated':
                        _e('Form status updated successfully.', 'innovative-forms');
                        break;
                }
                ?>
            </p>
        </div>
    <?php endif; ?>
    
    <?php if (empty($forms)): ?>
        <div class="innovative-forms-empty-state">
            <div class="empty-state-content">
                <div class="empty-state-icon">
                    <span class="dashicons dashicons-feedback"></span>
                </div>
                <h2><?php _e('No forms yet', 'innovative-forms'); ?></h2>
                <p><?php _e('Create your first beautiful form to get started.', 'innovative-forms'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=innovative-forms-new'); ?>" class="button button-primary button-large">
                    <?php _e('Create Your First Form', 'innovative-forms'); ?>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="innovative-forms-stats">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($forms); ?></div>
                    <div class="stat-label"><?php _e('Total Forms', 'innovative-forms'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $active_forms = array_filter($forms, function($form) { return $form->status === 'active'; });
                        echo count($active_forms);
                        ?>
                    </div>
                    <div class="stat-label"><?php _e('Active Forms', 'innovative-forms'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $total_entries = array_sum(array_column($forms, 'entry_count'));
                        echo number_format($total_entries);
                        ?>
                    </div>
                    <div class="stat-label"><?php _e('Total Submissions', 'innovative-forms'); ?></div>
                </div>
            </div>
        </div>
        
        <div class="innovative-forms-table-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="column-name"><?php _e('Form Name', 'innovative-forms'); ?></th>
                        <th scope="col" class="column-shortcode"><?php _e('Shortcode', 'innovative-forms'); ?></th>
                        <th scope="col" class="column-entries"><?php _e('Entries', 'innovative-forms'); ?></th>
                        <th scope="col" class="column-status"><?php _e('Status', 'innovative-forms'); ?></th>
                        <th scope="col" class="column-date"><?php _e('Created', 'innovative-forms'); ?></th>
                        <th scope="col" class="column-actions"><?php _e('Actions', 'innovative-forms'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms as $form): ?>
                        <tr>
                            <td class="column-name">
                                <strong>
                                    <a href="<?php echo admin_url('admin.php?page=innovative-forms-entries&form_id=' . $form->id); ?>">
                                        <?php echo esc_html($form->name); ?>
                                    </a>
                                </strong>
                                <?php if ($form->description): ?>
                                    <div class="form-description"><?php echo esc_html($form->description); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="column-shortcode">
                                <code class="shortcode-display" data-shortcode="[innovative_form id=&quot;<?php echo $form->id; ?>&quot;]">
                                    [innovative_form id="<?php echo $form->id; ?>"]
                                </code>
                                <button type="button" class="button button-small copy-shortcode" data-shortcode="[innovative_form id=&quot;<?php echo $form->id; ?>&quot;]">
                                    <?php _e('Copy', 'innovative-forms'); ?>
                                </button>
                            </td>
                            <td class="column-entries">
                                <a href="<?php echo admin_url('admin.php?page=innovative-forms-entries&form_id=' . $form->id); ?>" class="entries-count">
                                    <?php echo number_format($form->entry_count); ?>
                                </a>
                            </td>
                            <td class="column-status">
                                <span class="status-badge status-<?php echo $form->status; ?>">
                                    <?php echo ucfirst($form->status); ?>
                                </span>
                            </td>
                            <td class="column-date">
                                <?php echo date_i18n(get_option('date_format'), strtotime($form->created_date)); ?>
                            </td>
                            <td class="column-actions">
                                <div class="row-actions">
                                    <span class="view">
                                        <a href="<?php echo admin_url('admin.php?page=innovative-forms-entries&form_id=' . $form->id); ?>">
                                            <?php _e('View Entries', 'innovative-forms'); ?>
                                        </a> |
                                    </span>
                                    <span class="edit">
                                        <a href="<?php echo admin_url('admin.php?page=innovative-forms-new&form_id=' . $form->id); ?>">
                                            <?php _e('Edit', 'innovative-forms'); ?>
                                        </a> |
                                    </span>
                                    <span class="duplicate">
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=innovative-forms&action=duplicate&form_id=' . $form->id), 'innovative_forms_action'); ?>">
                                            <?php _e('Duplicate', 'innovative-forms'); ?>
                                        </a> |
                                    </span>
                                    <span class="toggle-status">
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=innovative-forms&action=toggle_status&form_id=' . $form->id), 'innovative_forms_action'); ?>" class="toggle-status-link">
                                            <?php echo ($form->status === 'active') ? __('Deactivate', 'innovative-forms') : __('Activate', 'innovative-forms'); ?>
                                        </a> |
                                    </span>
                                    <span class="delete">
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=innovative-forms&action=delete&form_id=' . $form->id), 'innovative_forms_action'); ?>" 
                                           class="delete-form" 
                                           onclick="return confirm('<?php _e('Are you sure you want to delete this form? This action cannot be undone.', 'innovative-forms'); ?>')">
                                            <?php _e('Delete', 'innovative-forms'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Copy shortcode functionality
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

