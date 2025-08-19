<?php
/**
 * Form Manager Class
 * Handles all form CRUD operations and form data management
 */

if (!defined('ABSPATH')) {
    exit;
}

class InnovativeForms_Form_Manager {
    
    /**
     * Create a new form
     */
    public static function create_form($name, $description = '', $fields = array(), $settings = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'innovative_forms';
        
        $default_settings = array(
            'theme' => 'modern',
            'animation' => 'fade-in',
            'submit_button_text' => 'Submit',
            'success_message' => 'Thank you! Your form has been submitted successfully.',
            'error_message' => 'Please check the highlighted fields and try again.',
            'primary_color' => '#667eea',
            'secondary_color' => '#764ba2',
            'background_style' => 'gradient',
            'border_radius' => '12',
            'spacing' => 'comfortable'
        );
        
        $settings = wp_parse_args($settings, $default_settings);
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'name' => sanitize_text_field($name),
                'description' => sanitize_textarea_field($description),
                'fields' => wp_json_encode($fields),
                'settings' => wp_json_encode($settings),
                'status' => 'active',
                'theme' => sanitize_text_field($settings['theme'])
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Get a form by ID
     */
    public static function get_form($form_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'innovative_forms';
        
        $form = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $form_id)
        );
        
        if ($form) {
            $form->fields = json_decode($form->fields, true);
            $form->settings = json_decode($form->settings, true);
            
            // Ensure settings have defaults
            $default_settings = array(
                'theme' => 'modern',
                'animation' => 'fade-in',
                'submit_button_text' => 'Submit',
                'success_message' => 'Thank you! Your form has been submitted successfully.',
                'error_message' => 'Please check the highlighted fields and try again.',
                'primary_color' => '#667eea',
                'secondary_color' => '#764ba2',
                'background_style' => 'gradient',
                'border_radius' => '12',
                'spacing' => 'comfortable'
            );
            
            $form->settings = wp_parse_args($form->settings, $default_settings);
        }
        
        return $form;
    }
    
    /**
     * Get all forms
     */
    public static function get_all_forms($status = 'all') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'innovative_forms';
        
        $where_clause = '';
        if ($status !== 'all') {
            $where_clause = $wpdb->prepare(" WHERE status = %s", $status);
        }
        
        $forms = $wpdb->get_results("SELECT * FROM $table_name $where_clause ORDER BY created_date DESC");
        
        // Get entry counts for each form
        $entries_table = $wpdb->prefix . 'innovative_form_entries';
        foreach ($forms as $form) {
            $form->entry_count = $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $entries_table WHERE form_id = %d", $form->id)
            );
        }
        
        return $forms;
    }
    
    /**
     * Update a form
     */
    public static function update_form($form_id, $name, $description = '', $fields = array(), $settings = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'innovative_forms';
        
        $update_data = array(
            'name' => sanitize_text_field($name),
            'description' => sanitize_textarea_field($description),
            'fields' => wp_json_encode($fields)
        );
        
        if (!empty($settings)) {
            $update_data['settings'] = wp_json_encode($settings);
            if (isset($settings['theme'])) {
                $update_data['theme'] = sanitize_text_field($settings['theme']);
            }
        }
        
        return $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $form_id),
            array('%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Delete a form and all its entries
     */
    public static function delete_form($form_id) {
        global $wpdb;
        
        $forms_table = $wpdb->prefix . 'innovative_forms';
        $entries_table = $wpdb->prefix . 'innovative_form_entries';
        
        // Delete entries first
        $wpdb->delete($entries_table, array('form_id' => $form_id), array('%d'));
        
        // Delete form
        return $wpdb->delete($forms_table, array('id' => $form_id), array('%d'));
    }
    
    /**
     * Update form status
     */
    public static function update_form_status($form_id, $status) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'innovative_forms';
        
        return $wpdb->update(
            $table_name,
            array('status' => sanitize_text_field($status)),
            array('id' => $form_id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Duplicate a form
     */
    public static function duplicate_form($form_id) {
        $original_form = self::get_form($form_id);
        
        if (!$original_form) {
            return false;
        }
        
        $new_name = $original_form->name . ' (Copy)';
        
        return self::create_form(
            $new_name,
            $original_form->description,
            $original_form->fields,
            $original_form->settings
        );
    }
    
    /**
     * Get form themes
     */
    public static function get_form_themes() {
        return array(
            'modern' => array(
                'name' => 'Modern',
                'description' => 'Clean, modern design with gradients and smooth animations',
                'preview' => 'modern-preview.jpg',
                'primary_color' => '#667eea',
                'secondary_color' => '#764ba2'
            ),
            'professional' => array(
                'name' => 'Professional',
                'description' => 'Corporate-friendly design with subtle styling',
                'preview' => 'professional-preview.jpg',
                'primary_color' => '#2c3e50',
                'secondary_color' => '#3498db'
            ),
            'creative' => array(
                'name' => 'Creative',
                'description' => 'Bold, colorful design for creative industries',
                'preview' => 'creative-preview.jpg',
                'primary_color' => '#e74c3c',
                'secondary_color' => '#f39c12'
            ),
            'minimal' => array(
                'name' => 'Minimal',
                'description' => 'Ultra-clean, minimalist design',
                'preview' => 'minimal-preview.jpg',
                'primary_color' => '#34495e',
                'secondary_color' => '#95a5a6'
            ),
            'elegant' => array(
                'name' => 'Elegant',
                'description' => 'Sophisticated design with elegant typography',
                'preview' => 'elegant-preview.jpg',
                'primary_color' => '#8e44ad',
                'secondary_color' => '#9b59b6'
            )
        );
    }
    
    /**
     * Get form statistics
     */
    public static function get_form_stats($form_id) {
        global $wpdb;
        
        $entries_table = $wpdb->prefix . 'innovative_form_entries';
        
        $stats = array();
        
        // Total entries
        $stats['total_entries'] = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $entries_table WHERE form_id = %d", $form_id)
        );
        
        // Entries this month
        $stats['entries_this_month'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $entries_table 
                WHERE form_id = %d AND MONTH(submission_date) = MONTH(CURRENT_DATE()) 
                AND YEAR(submission_date) = YEAR(CURRENT_DATE())",
                $form_id
            )
        );
        
        // Entries this week
        $stats['entries_this_week'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $entries_table 
                WHERE form_id = %d AND WEEK(submission_date) = WEEK(CURRENT_DATE()) 
                AND YEAR(submission_date) = YEAR(CURRENT_DATE())",
                $form_id
            )
        );
        
        // Latest entry date
        $stats['latest_entry'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT submission_date FROM $entries_table 
                WHERE form_id = %d ORDER BY submission_date DESC LIMIT 1",
                $form_id
            )
        );
        
        return $stats;
    }
    
    /**
     * Get field types with their configurations
     */
    public static function get_field_types() {
        return array(
            'text' => array(
                'label' => 'Text Input',
                'icon' => 'text-width',
                'description' => 'Single line text input',
                'supports' => array('placeholder', 'required', 'icon', 'validation')
            ),
            'email' => array(
                'label' => 'Email',
                'icon' => 'envelope',
                'description' => 'Email address input with validation',
                'supports' => array('placeholder', 'required', 'icon')
            ),
            'textarea' => array(
                'label' => 'Textarea',
                'icon' => 'align-left',
                'description' => 'Multi-line text input',
                'supports' => array('placeholder', 'required', 'rows')
            ),
            'select' => array(
                'label' => 'Dropdown',
                'icon' => 'caret-down',
                'description' => 'Dropdown selection',
                'supports' => array('options', 'required', 'multiple')
            ),
            'radio' => array(
                'label' => 'Radio Buttons',
                'icon' => 'dot-circle',
                'description' => 'Single selection from options',
                'supports' => array('options', 'required', 'inline')
            ),
            'checkbox' => array(
                'label' => 'Checkboxes',
                'icon' => 'check-square',
                'description' => 'Multiple selection from options',
                'supports' => array('options', 'required', 'inline')
            ),
            'number' => array(
                'label' => 'Number',
                'icon' => 'hashtag',
                'description' => 'Numeric input',
                'supports' => array('placeholder', 'required', 'min', 'max', 'step')
            ),
            'tel' => array(
                'label' => 'Phone',
                'icon' => 'phone',
                'description' => 'Phone number input',
                'supports' => array('placeholder', 'required', 'pattern')
            ),
            'url' => array(
                'label' => 'URL',
                'icon' => 'link',
                'description' => 'Website URL input',
                'supports' => array('placeholder', 'required')
            ),
            'date' => array(
                'label' => 'Date',
                'icon' => 'calendar',
                'description' => 'Date picker',
                'supports' => array('required', 'min', 'max')
            ),
            'gdpr_consent' => array(
                'label' => 'GDPR Consent',
                'icon' => 'shield-alt',
                'description' => 'GDPR compliance checkbox',
                'supports' => array('required', 'description')
            )
        );
    }
}

