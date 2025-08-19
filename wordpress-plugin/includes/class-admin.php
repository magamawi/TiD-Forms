<?php
/**
 * Admin Class
 * Handles the WordPress admin interface for the plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class InnovativeForms_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_innovative_forms_create_form', array($this, 'ajax_create_form'));
        add_action('wp_ajax_innovative_forms_delete_form', array($this, 'ajax_delete_form'));
        add_action('wp_ajax_innovative_forms_toggle_status', array($this, 'ajax_toggle_status'));
        add_action('wp_ajax_innovative_forms_export_entries', array($this, 'ajax_export_entries'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        $capability = 'manage_options';
        
        // Main menu
        add_menu_page(
            __('Innovative Forms', 'innovative-forms'),
            __('Innovative Forms', 'innovative-forms'),
            $capability,
            'innovative-forms',
            array($this, 'forms_page'),
            'dashicons-feedback',
            30
        );
        
        // Submenu pages
        add_submenu_page(
            'innovative-forms',
            __('All Forms', 'innovative-forms'),
            __('All Forms', 'innovative-forms'),
            $capability,
            'innovative-forms',
            array($this, 'forms_page')
        );
        
        add_submenu_page(
            'innovative-forms',
            __('Add New Form', 'innovative-forms'),
            __('Add New Form', 'innovative-forms'),
            $capability,
            'innovative-forms-new',
            array($this, 'new_form_page')
        );
        
        add_submenu_page(
            'innovative-forms',
            __('Entries', 'innovative-forms'),
            __('Entries', 'innovative-forms'),
            $capability,
            'innovative-forms-entries',
            array($this, 'entries_page')
        );
        
        add_submenu_page(
            'innovative-forms',
            __('Settings', 'innovative-forms'),
            __('Settings', 'innovative-forms'),
            $capability,
            'innovative-forms-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Admin init
     */
    public function admin_init() {
        // Handle form actions
        if (isset($_GET['action']) && isset($_GET['form_id'])) {
            $this->handle_form_actions();
        }
        
        // Handle entry actions
        if (isset($_GET['action']) && isset($_GET['entry_id'])) {
            $this->handle_entry_actions();
        }
    }
    
    /**
     * Forms listing page
     */
    public function forms_page() {
        $forms = InnovativeForms_Form_Manager::get_all_forms();
        
        include INNOVATIVE_FORMS_PLUGIN_DIR . 'admin/templates/forms-list.php';
    }
    
    /**
     * New form page
     */
    public function new_form_page() {
        $themes = InnovativeForms_Form_Manager::get_form_themes();
        $field_types = InnovativeForms_Form_Manager::get_field_types();
        
        include INNOVATIVE_FORMS_PLUGIN_DIR . 'admin/templates/form-editor.php';
    }
    
    /**
     * Entries page
     */
    public function entries_page() {
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        $page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        if ($form_id) {
            $form = InnovativeForms_Form_Manager::get_form($form_id);
            $entries = InnovativeForms_Entry_Manager::get_form_entries($form_id, $per_page, $offset);
            $total_entries = InnovativeForms_Entry_Manager::get_form_entry_count($form_id);
            $total_pages = ceil($total_entries / $per_page);
        } else {
            $forms = InnovativeForms_Form_Manager::get_all_forms('active');
            $form = null;
            $entries = array();
            $total_entries = 0;
            $total_pages = 0;
        }
        
        include INNOVATIVE_FORMS_PLUGIN_DIR . 'admin/templates/entries-view.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        $settings = get_option('innovative_forms_settings', array());
        
        include INNOVATIVE_FORMS_PLUGIN_DIR . 'admin/templates/settings.php';
    }
    
    /**
     * Handle form actions
     */
    private function handle_form_actions() {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'innovative_forms_action')) {
            wp_die(__('Security check failed', 'innovative-forms'));
        }
        
        $form_id = intval($_GET['form_id']);
        $action = sanitize_text_field($_GET['action']);
        
        switch ($action) {
            case 'delete':
                if (InnovativeForms_Form_Manager::delete_form($form_id)) {
                    wp_redirect(add_query_arg('message', 'deleted', admin_url('admin.php?page=innovative-forms')));
                }
                break;
                
            case 'duplicate':
                if (InnovativeForms_Form_Manager::duplicate_form($form_id)) {
                    wp_redirect(add_query_arg('message', 'duplicated', admin_url('admin.php?page=innovative-forms')));
                }
                break;
                
            case 'toggle_status':
                $form = InnovativeForms_Form_Manager::get_form($form_id);
                $new_status = ($form->status === 'active') ? 'inactive' : 'active';
                
                if (InnovativeForms_Form_Manager::update_form_status($form_id, $new_status)) {
                    wp_redirect(add_query_arg('message', 'status_updated', admin_url('admin.php?page=innovative-forms')));
                }
                break;
        }
        
        exit;
    }
    
    /**
     * Handle entry actions
     */
    private function handle_entry_actions() {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'innovative_forms_action')) {
            wp_die(__('Security check failed', 'innovative-forms'));
        }
        
        $entry_id = intval($_GET['entry_id']);
        $action = sanitize_text_field($_GET['action']);
        
        switch ($action) {
            case 'delete_entry':
                if (InnovativeForms_Entry_Manager::delete_entry($entry_id)) {
                    wp_redirect(add_query_arg('message', 'entry_deleted', $_SERVER['HTTP_REFERER']));
                }
                break;
                
            case 'mark_read':
                if (InnovativeForms_Entry_Manager::update_entry_status($entry_id, 'read')) {
                    wp_redirect(add_query_arg('message', 'entry_updated', $_SERVER['HTTP_REFERER']));
                }
                break;
        }
        
        exit;
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'innovative_forms_settings')) {
            wp_die(__('Security check failed', 'innovative-forms'));
        }
        
        $settings = array(
            'form_theme' => sanitize_text_field($_POST['form_theme']),
            'animation_enabled' => isset($_POST['animation_enabled']),
            'spam_protection' => isset($_POST['spam_protection']),
            'delete_data_on_uninstall' => isset($_POST['delete_data_on_uninstall'])
        );
        
        update_option('innovative_forms_settings', $settings);
        
        add_settings_error(
            'innovative_forms_settings',
            'settings_saved',
            __('Settings saved successfully!', 'innovative-forms'),
            'updated'
        );
    }
    
    /**
     * AJAX: Create form from template
     */
    public function ajax_create_form() {
        if (!wp_verify_nonce($_POST['nonce'], 'innovative_forms_admin_nonce')) {
            wp_die(__('Security check failed', 'innovative-forms'));
        }
        
        $template = sanitize_text_field($_POST['template']);
        $form_name = sanitize_text_field($_POST['form_name']);
        
        // Get template data
        $templates = array(
            'newsletter' => array(
                'name' => 'Newsletter Subscription',
                'description' => 'Subscribe to our newsletter for updates',
                'fields' => $this->get_newsletter_template_fields()
            ),
            'contributor' => array(
                'name' => 'Contributors Registration',
                'description' => 'Join our community of contributors',
                'fields' => $this->get_contributor_template_fields()
            ),
            'contact' => array(
                'name' => 'Contact Form',
                'description' => 'Get in touch with us',
                'fields' => $this->get_contact_template_fields()
            )
        );
        
        if (!isset($templates[$template])) {
            wp_send_json_error(__('Invalid template', 'innovative-forms'));
        }
        
        $template_data = $templates[$template];
        if (!empty($form_name)) {
            $template_data['name'] = $form_name;
        }
        
        $form_id = InnovativeForms_Form_Manager::create_form(
            $template_data['name'],
            $template_data['description'],
            $template_data['fields']
        );
        
        if ($form_id) {
            wp_send_json_success(array(
                'form_id' => $form_id,
                'redirect_url' => admin_url('admin.php?page=innovative-forms')
            ));
        } else {
            wp_send_json_error(__('Failed to create form', 'innovative-forms'));
        }
    }
    
    /**
     * AJAX: Delete form
     */
    public function ajax_delete_form() {
        if (!wp_verify_nonce($_POST['nonce'], 'innovative_forms_admin_nonce')) {
            wp_die(__('Security check failed', 'innovative-forms'));
        }
        
        $form_id = intval($_POST['form_id']);
        
        if (InnovativeForms_Form_Manager::delete_form($form_id)) {
            wp_send_json_success(__('Form deleted successfully', 'innovative-forms'));
        } else {
            wp_send_json_error(__('Failed to delete form', 'innovative-forms'));
        }
    }
    
    /**
     * AJAX: Toggle form status
     */
    public function ajax_toggle_status() {
        if (!wp_verify_nonce($_POST['nonce'], 'innovative_forms_admin_nonce')) {
            wp_die(__('Security check failed', 'innovative-forms'));
        }
        
        $form_id = intval($_POST['form_id']);
        $status = sanitize_text_field($_POST['status']);
        
        if (InnovativeForms_Form_Manager::update_form_status($form_id, $status)) {
            wp_send_json_success(__('Status updated successfully', 'innovative-forms'));
        } else {
            wp_send_json_error(__('Failed to update status', 'innovative-forms'));
        }
    }
    
    /**
     * AJAX: Export entries
     */
    public function ajax_export_entries() {
        if (!wp_verify_nonce($_POST['nonce'], 'innovative_forms_admin_nonce')) {
            wp_die(__('Security check failed', 'innovative-forms'));
        }
        
        $form_id = intval($_POST['form_id']);
        
        InnovativeForms_Entry_Manager::export_entries_csv($form_id);
    }
    
    /**
     * Get newsletter template fields
     */
    private function get_newsletter_template_fields() {
        return array(
            array(
                'type' => 'text',
                'name' => 'first_name',
                'label' => 'First Name',
                'placeholder' => 'Enter your first name',
                'required' => true,
                'icon' => 'user'
            ),
            array(
                'type' => 'text',
                'name' => 'last_name',
                'label' => 'Last Name',
                'placeholder' => 'Enter your last name',
                'required' => true,
                'icon' => 'user'
            ),
            array(
                'type' => 'email',
                'name' => 'email',
                'label' => 'Email Address',
                'placeholder' => 'Enter your email address',
                'required' => true,
                'icon' => 'envelope'
            ),
            array(
                'type' => 'checkbox',
                'name' => 'interests',
                'label' => 'What are you interested in?',
                'required' => true,
                'options' => array(
                    'beta_readers' => 'Beta Readers Community',
                    'pre_order' => 'Pre-Order Notifications',
                    'publishing' => 'Publishing Updates',
                    'activities' => 'Next Activities'
                )
            ),
            array(
                'type' => 'gdpr_consent',
                'name' => 'gdpr_consent',
                'label' => 'I agree to the privacy policy',
                'required' => true,
                'description' => 'We need your consent to store and process your personal data.'
            )
        );
    }
    
    /**
     * Get contributor template fields
     */
    private function get_contributor_template_fields() {
        return array(
            array(
                'type' => 'text',
                'name' => 'first_name',
                'label' => 'First Name',
                'placeholder' => 'Enter your first name',
                'required' => true,
                'icon' => 'user'
            ),
            array(
                'type' => 'text',
                'name' => 'last_name',
                'label' => 'Last Name',
                'placeholder' => 'Enter your last name',
                'required' => true,
                'icon' => 'user'
            ),
            array(
                'type' => 'email',
                'name' => 'email',
                'label' => 'Email Address',
                'placeholder' => 'Enter your email address',
                'required' => true,
                'icon' => 'envelope'
            ),
            array(
                'type' => 'text',
                'name' => 'company',
                'label' => 'Company/Organization',
                'placeholder' => 'Enter your organization',
                'required' => true,
                'icon' => 'building'
            ),
            array(
                'type' => 'checkbox',
                'name' => 'contribution_type',
                'label' => 'How would you like to contribute?',
                'required' => true,
                'options' => array(
                    'beta_reader' => 'Beta Reader',
                    'researcher' => 'Researcher',
                    'thought_leader' => 'Thought Leader',
                    'expert' => 'Expert & Case Studies'
                )
            ),
            array(
                'type' => 'textarea',
                'name' => 'additional_info',
                'label' => 'Additional Information',
                'placeholder' => 'Tell us more about yourself...',
                'required' => false,
                'rows' => 4
            ),
            array(
                'type' => 'gdpr_consent',
                'name' => 'gdpr_consent',
                'label' => 'I agree to the privacy policy',
                'required' => true,
                'description' => 'We need your consent to store and process your personal data.'
            )
        );
    }
    
    /**
     * Get contact template fields
     */
    private function get_contact_template_fields() {
        return array(
            array(
                'type' => 'text',
                'name' => 'name',
                'label' => 'Full Name',
                'placeholder' => 'Enter your full name',
                'required' => true,
                'icon' => 'user'
            ),
            array(
                'type' => 'email',
                'name' => 'email',
                'label' => 'Email Address',
                'placeholder' => 'Enter your email address',
                'required' => true,
                'icon' => 'envelope'
            ),
            array(
                'type' => 'text',
                'name' => 'subject',
                'label' => 'Subject',
                'placeholder' => 'Enter the subject',
                'required' => true,
                'icon' => 'tag'
            ),
            array(
                'type' => 'textarea',
                'name' => 'message',
                'label' => 'Message',
                'placeholder' => 'Enter your message...',
                'required' => true,
                'rows' => 6
            )
        );
    }
}

