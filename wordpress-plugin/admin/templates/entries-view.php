<?php
/**
 * Admin Template: Entries View
 */

if (!defined('ABSPATH')) {
    exit;
}

$message = isset($_GET['message']) ? $_GET['message'] : '';
?>

<div class="wrap innovative-forms-admin">
    <h1 class="wp-heading-inline">
        <?php _e('Form Entries', 'innovative-forms'); ?>
        <?php if ($form): ?>
            - <?php echo esc_html($form->name); ?>
        <?php endif; ?>
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=innovative-forms'); ?>" class="page-title-action">
        <?php _e('Back to Forms', 'innovative-forms'); ?>
    </a>
    
    <hr class="wp-header-end">
    
    <?php if ($message): ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                switch ($message) {
                    case 'entry_deleted':
                        _e('Entry deleted successfully.', 'innovative-forms');
                        break;
                    case 'entry_updated':
                        _e('Entry updated successfully.', 'innovative-forms');
                        break;
                }
                ?>
            </p>
        </div>
    <?php endif; ?>
    
    <?php if (!$form): ?>
        <div class="innovative-forms-form-selector">
            <h2><?php _e('Select a Form', 'innovative-forms'); ?></h2>
            <p><?php _e('Choose a form to view its entries.', 'innovative-forms'); ?></p>
            
            <?php if (empty($forms)): ?>
                <div class="empty-state">
                    <p><?php _e('No forms found.', 'innovative-forms'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=innovative-forms-new'); ?>" class="button button-primary">
                        <?php _e('Create Your First Form', 'innovative-forms'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="forms-grid">
                    <?php foreach ($forms as $form_item): ?>
                        <div class="form-card">
                            <h3><?php echo esc_html($form_item->name); ?></h3>
                            <?php if ($form_item->description): ?>
                                <p><?php echo esc_html($form_item->description); ?></p>
                            <?php endif; ?>
                            <div class="form-stats">
                                <span class="entry-count"><?php echo number_format($form_item->entry_count); ?> <?php _e('entries', 'innovative-forms'); ?></span>
                            </div>
                            <a href="<?php echo admin_url('admin.php?page=innovative-forms-entries&form_id=' . $form_item->id); ?>" class="button button-primary">
                                <?php _e('View Entries', 'innovative-forms'); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
    <?php else: ?>
        <div class="innovative-forms-entries">
            <div class="entries-header">
                <div class="entries-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($total_entries); ?></span>
                        <span class="stat-label"><?php _e('Total Entries', 'innovative-forms'); ?></span>
                    </div>
                </div>
                
                <div class="entries-actions">
                    <?php if ($total_entries > 0): ?>
                        <button type="button" class="button export-entries" data-form-id="<?php echo $form->id; ?>">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Export CSV', 'innovative-forms'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (empty($entries)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <span class="dashicons dashicons-admin-post"></span>
                    </div>
                    <h3><?php _e('No entries yet', 'innovative-forms'); ?></h3>
                    <p><?php _e('When people submit this form, their entries will appear here.', 'innovative-forms'); ?></p>
                    <div class="shortcode-reminder">
                        <p><?php _e('Make sure you\'ve added the form to your website:', 'innovative-forms'); ?></p>
                        <code>[innovative_form id="<?php echo $form->id; ?>"]</code>
                        <button type="button" class="button button-small copy-shortcode" data-shortcode="[innovative_form id=&quot;<?php echo $form->id; ?>&quot;]">
                            <?php _e('Copy Shortcode', 'innovative-forms'); ?>
                        </button>
                    </div>
                </div>
                
            <?php else: ?>
                <div class="entries-table-container">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col" class="column-date"><?php _e('Date', 'innovative-forms'); ?></th>
                                <?php foreach ($form->fields as $field): ?>
                                    <th scope="col" class="column-field"><?php echo esc_html($field['label']); ?></th>
                                <?php endforeach; ?>
                                <th scope="col" class="column-status"><?php _e('Status', 'innovative-forms'); ?></th>
                                <th scope="col" class="column-actions"><?php _e('Actions', 'innovative-forms'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries as $entry): ?>
                                <tr class="entry-row <?php echo $entry->status === 'unread' ? 'unread' : ''; ?>">
                                    <td class="column-date">
                                        <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($entry->submission_date)); ?>
                                    </td>
                                    <?php foreach ($form->fields as $field): ?>
                                        <td class="column-field">
                                            <?php
                                            $field_name = $field['name'];
                                            $value = isset($entry->entry_data[$field_name]) ? $entry->entry_data[$field_name] : '';
                                            
                                            if (is_array($value)) {
                                                echo esc_html(implode(', ', $value));
                                            } elseif ($field['type'] === 'gdpr_consent') {
                                                echo $value ? __('Yes', 'innovative-forms') : __('No', 'innovative-forms');
                                            } elseif (strlen($value) > 50) {
                                                echo esc_html(substr($value, 0, 50)) . '...';
                                            } else {
                                                echo esc_html($value);
                                            }
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="column-status">
                                        <span class="status-badge status-<?php echo $entry->status; ?>">
                                            <?php echo ucfirst($entry->status); ?>
                                        </span>
                                    </td>
                                    <td class="column-actions">
                                        <div class="row-actions">
                                            <span class="view">
                                                <a href="#" class="view-entry" data-entry-id="<?php echo $entry->id; ?>">
                                                    <?php _e('View', 'innovative-forms'); ?>
                                                </a> |
                                            </span>
                                            <?php if ($entry->status === 'unread'): ?>
                                                <span class="mark-read">
                                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=innovative-forms-entries&form_id=' . $form->id . '&action=mark_read&entry_id=' . $entry->id), 'innovative_forms_action'); ?>">
                                                        <?php _e('Mark Read', 'innovative-forms'); ?>
                                                    </a> |
                                                </span>
                                            <?php endif; ?>
                                            <span class="delete">
                                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=innovative-forms-entries&form_id=' . $form->id . '&action=delete_entry&entry_id=' . $entry->id), 'innovative_forms_action'); ?>" 
                                                   class="delete-entry" 
                                                   onclick="return confirm('<?php _e('Are you sure you want to delete this entry?', 'innovative-forms'); ?>')">
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
                
                <?php if ($total_pages > 1): ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <?php
                            $pagination_args = array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => __('&laquo;'),
                                'next_text' => __('&raquo;'),
                                'total' => $total_pages,
                                'current' => $page
                            );
                            echo paginate_links($pagination_args);
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Entry Detail Modal -->
<div id="entry-modal" class="innovative-forms-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php _e('Entry Details', 'innovative-forms'); ?></h2>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="entry-details"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="button button-secondary modal-close"><?php _e('Close', 'innovative-forms'); ?></button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Export entries
    $('.export-entries').on('click', function() {
        var formId = $(this).data('form-id');
        var form = $('<form method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">');
        form.append('<input type="hidden" name="action" value="innovative_forms_export_entries">');
        form.append('<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('innovative_forms_admin_nonce'); ?>">');
        form.append('<input type="hidden" name="form_id" value="' + formId + '">');
        $('body').append(form);
        form.submit();
        form.remove();
    });
    
    // View entry details
    $('.view-entry').on('click', function(e) {
        e.preventDefault();
        var entryId = $(this).data('entry-id');
        
        // Get entry data from the row
        var row = $(this).closest('tr');
        var details = '<div class="entry-details-grid">';
        
        <?php if ($form): ?>
            details += '<div class="detail-item"><strong><?php _e('Submission Date:', 'innovative-forms'); ?></strong> ' + row.find('.column-date').text() + '</div>';
            
            <?php foreach ($form->fields as $index => $field): ?>
                var fieldValue = row.find('.column-field').eq(<?php echo $index; ?>).text();
                details += '<div class="detail-item"><strong><?php echo esc_js($field['label']); ?>:</strong> ' + fieldValue + '</div>';
            <?php endforeach; ?>
        <?php endif; ?>
        
        details += '</div>';
        
        $('#entry-details').html(details);
        $('#entry-modal').show();
    });
    
    // Modal close
    $('.modal-close').on('click', function() {
        $('#entry-modal').hide();
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
            $(this).text('<?php _e('Copy Shortcode', 'innovative-forms'); ?>');
        }, 2000);
    });
});
</script>

