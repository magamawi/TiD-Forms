# MVP Implementation Guide - Simple WordPress Form Plugin

## Plugin Structure

```
custom-forms-mvp/
├── custom-forms-mvp.php          # Main plugin file
├── includes/
│   ├── class-form-manager.php    # Form CRUD operations
│   ├── class-entry-manager.php   # Entry storage and retrieval
│   ├── class-admin-interface.php # Admin pages
│   └── class-shortcode.php       # Shortcode handling
├── admin/
│   ├── css/
│   │   └── admin-style.css       # Admin styling
│   ├── js/
│   │   └── admin-script.js       # Admin JavaScript
│   └── templates/
│       ├── forms-list.php        # Forms listing page
│       ├── form-editor.php       # Form creation/editing
│       └── entries-view.php      # Entries display
├── public/
│   ├── css/
│   │   └── form-style.css        # Frontend form styling
│   └── js/
│       └── form-script.js        # Frontend form handling
└── templates/
    ├── newsletter-form.php       # Newsletter form template
    └── contributor-form.php      # Contributor form template
```

## Core Implementation

### 1. Main Plugin File (custom-forms-mvp.php)

```php
<?php
/**
 * Plugin Name: Custom Forms MVP
 * Description: Simple form plugin to replace WPForms
 * Version: 1.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CUSTOM_FORMS_MVP_VERSION', '1.0.0');
define('CUSTOM_FORMS_MVP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CUSTOM_FORMS_MVP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Main plugin class
class CustomFormsMVP {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load required files
        $this->load_dependencies();
        
        // Initialize components
        new CustomForms_Admin_Interface();
        new CustomForms_Shortcode();
        
        // Load assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    private function load_dependencies() {
        require_once CUSTOM_FORMS_MVP_PLUGIN_DIR . 'includes/class-form-manager.php';
        require_once CUSTOM_FORMS_MVP_PLUGIN_DIR . 'includes/class-entry-manager.php';
        require_once CUSTOM_FORMS_MVP_PLUGIN_DIR . 'includes/class-admin-interface.php';
        require_once CUSTOM_FORMS_MVP_PLUGIN_DIR . 'includes/class-shortcode.php';
    }
    
    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Set default options
        add_option('custom_forms_mvp_version', CUSTOM_FORMS_MVP_VERSION);
    }
    
    public function deactivate() {
        // Cleanup if needed
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Forms table
        $forms_table = $wpdb->prefix . 'custom_forms';
        $forms_sql = "CREATE TABLE $forms_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            fields longtext NOT NULL,
            status varchar(20) DEFAULT 'active',
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Entries table
        $entries_table = $wpdb->prefix . 'custom_form_entries';
        $entries_sql = "CREATE TABLE $entries_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            form_id mediumint(9) NOT NULL,
            entry_data longtext NOT NULL,
            submission_date datetime DEFAULT CURRENT_TIMESTAMP,
            user_ip varchar(45),
            PRIMARY KEY (id),
            KEY form_id (form_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($forms_sql);
        dbDelta($entries_sql);
    }
    
    public function enqueue_public_assets() {
        wp_enqueue_style('custom-forms-style', 
            CUSTOM_FORMS_MVP_PLUGIN_URL . 'public/css/form-style.css', 
            array(), CUSTOM_FORMS_MVP_VERSION);
        
        wp_enqueue_script('custom-forms-script', 
            CUSTOM_FORMS_MVP_PLUGIN_URL . 'public/js/form-script.js', 
            array('jquery'), CUSTOM_FORMS_MVP_VERSION, true);
        
        // Localize script for AJAX
        wp_localize_script('custom-forms-script', 'custom_forms_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('custom_forms_nonce')
        ));
    }
    
    public function enqueue_admin_assets($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'custom-forms') === false) {
            return;
        }
        
        wp_enqueue_style('custom-forms-admin-style', 
            CUSTOM_FORMS_MVP_PLUGIN_URL . 'admin/css/admin-style.css', 
            array(), CUSTOM_FORMS_MVP_VERSION);
        
        wp_enqueue_script('custom-forms-admin-script', 
            CUSTOM_FORMS_MVP_PLUGIN_URL . 'admin/js/admin-script.js', 
            array('jquery'), CUSTOM_FORMS_MVP_VERSION, true);
    }
}

// Initialize the plugin
new CustomFormsMVP();
```

### 2. Form Manager Class (includes/class-form-manager.php)

```php
<?php

class CustomForms_Form_Manager {
    
    public static function create_form($name, $fields) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'custom_forms';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'name' => sanitize_text_field($name),
                'fields' => wp_json_encode($fields),
                'status' => 'active'
            ),
            array('%s', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    public static function get_form($form_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'custom_forms';
        
        $form = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $form_id)
        );
        
        if ($form) {
            $form->fields = json_decode($form->fields, true);
        }
        
        return $form;
    }
    
    public static function get_all_forms() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'custom_forms';
        
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_date DESC");
    }
    
    public static function update_form($form_id, $name, $fields) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'custom_forms';
        
        return $wpdb->update(
            $table_name,
            array(
                'name' => sanitize_text_field($name),
                'fields' => wp_json_encode($fields)
            ),
            array('id' => $form_id),
            array('%s', '%s'),
            array('%d')
        );
    }
    
    public static function delete_form($form_id) {
        global $wpdb;
        
        $forms_table = $wpdb->prefix . 'custom_forms';
        $entries_table = $wpdb->prefix . 'custom_form_entries';
        
        // Delete entries first
        $wpdb->delete($entries_table, array('form_id' => $form_id), array('%d'));
        
        // Delete form
        return $wpdb->delete($forms_table, array('id' => $form_id), array('%d'));
    }
    
    public static function get_form_templates() {
        return array(
            'newsletter' => array(
                'name' => 'Newsletter Subscription',
                'fields' => array(
                    array(
                        'type' => 'text',
                        'name' => 'first_name',
                        'label' => 'First Name',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'last_name',
                        'label' => 'Last Name',
                        'required' => true
                    ),
                    array(
                        'type' => 'email',
                        'name' => 'email',
                        'label' => 'Email',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'country',
                        'label' => 'Country',
                        'required' => false
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'title',
                        'label' => 'Title/Profession/Role',
                        'required' => false
                    ),
                    array(
                        'type' => 'checkbox',
                        'name' => 'interests',
                        'label' => 'Are you interested:',
                        'required' => true,
                        'options' => array(
                            'beta_readers' => 'To be Part of Beta Readers Community',
                            'pre_order' => 'To be notified with Pre-Order Date',
                            'publishing' => 'To be notified with Official Publishing Date',
                            'activities' => 'To Receive Information about our Next Activities',
                            'none' => 'None of the Above'
                        )
                    ),
                    array(
                        'type' => 'radio',
                        'name' => 'preference',
                        'label' => 'Help Us understand your preferences, what you prefer most',
                        'required' => false,
                        'options' => array(
                            'ebook' => 'eBook version',
                            'printed' => 'Printed version',
                            'audio' => 'Audio Book version',
                            'pdf' => 'PDF version'
                        )
                    ),
                    array(
                        'type' => 'gdpr_consent',
                        'name' => 'gdpr_consent',
                        'label' => 'I agree to allow The Innovative Dinosaur to store and process my personal data',
                        'required' => true,
                        'description' => 'In order to provide you the request above, we need to store and process your data provided above. You will be automatically add to the subscription list. If you consent to us storing your personal data for this purpose, please tick the checkbox below. You can unsubscribe from these communications at any time.'
                    )
                )
            ),
            'contributor' => array(
                'name' => 'Contributors Registration',
                'fields' => array(
                    array(
                        'type' => 'text',
                        'name' => 'first_name',
                        'label' => 'First Name',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'last_name',
                        'label' => 'Last Name',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'title',
                        'label' => 'Title/Profession/Role',
                        'required' => false
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'company',
                        'label' => 'Association/Company/Community Name',
                        'required' => true
                    ),
                    array(
                        'type' => 'email',
                        'name' => 'email',
                        'label' => 'Email',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'linkedin_url',
                        'label' => 'LinkedIn Profile or Personal Website URL',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'country',
                        'label' => 'Country',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'industry',
                        'label' => 'Industry',
                        'required' => true
                    ),
                    array(
                        'type' => 'checkbox',
                        'name' => 'contribution_type',
                        'label' => 'Contribution Type',
                        'required' => true,
                        'options' => array(
                            'beta_reader' => 'Beta Reader',
                            'researchers' => 'Researchers',
                            'thought_leader' => 'Thought Leader',
                            'experts_case_studies' => 'Experts & Case Studies',
                            'academic' => 'Academic Institutes & Educators',
                            'business' => 'Business Enterprise',
                            'community' => 'Community Partners & Affiliates',
                            'professional' => 'Professional Bodies'
                        )
                    ),
                    array(
                        'type' => 'textarea',
                        'name' => 'additional_info',
                        'label' => 'Additional Information that you might want to add',
                        'required' => false
                    ),
                    array(
                        'type' => 'gdpr_consent',
                        'name' => 'gdpr_consent',
                        'label' => 'I agree to allow The Innovative Dinosaur to store and process my personal data',
                        'required' => true,
                        'description' => 'In order to provide you the request above, we need to store and process your data provided above. You will be automatically add to the subscription list. If you consent to us storing your personal data for this purpose, please tick the checkbox below. You can unsubscribe from these communications at any time.'
                    )
                )
            )
        );
    }
}
```

### 3. Shortcode Implementation (includes/class-shortcode.php)

```php
<?php

class CustomForms_Shortcode {
    
    public function __construct() {
        add_shortcode('custom_form', array($this, 'render_form'));
        add_action('wp_ajax_submit_custom_form', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_submit_custom_form', array($this, 'handle_form_submission'));
    }
    
    public function render_form($atts) {
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts);
        
        $form_id = intval($atts['id']);
        
        if (!$form_id) {
            return '<p>Error: Form ID is required.</p>';
        }
        
        $form = CustomForms_Form_Manager::get_form($form_id);
        
        if (!$form || $form->status !== 'active') {
            return '<p>Error: Form not found or inactive.</p>';
        }
        
        ob_start();
        $this->render_form_html($form);
        return ob_get_clean();
    }
    
    private function render_form_html($form) {
        ?>
        <div class="custom-form-container" id="custom-form-<?php echo esc_attr($form->id); ?>">
            <form class="custom-form" data-form-id="<?php echo esc_attr($form->id); ?>">
                <?php wp_nonce_field('custom_forms_nonce', 'custom_forms_nonce'); ?>
                
                <?php foreach ($form->fields as $field): ?>
                    <div class="form-field field-<?php echo esc_attr($field['type']); ?>">
                        <?php $this->render_field($field); ?>
                    </div>
                <?php endforeach; ?>
                
                <!-- Honeypot field for spam protection -->
                <div style="display: none;">
                    <input type="text" name="honeypot" value="" />
                </div>
                
                <div class="form-submit">
                    <button type="submit" class="submit-button">Submit</button>
                </div>
                
                <div class="form-messages"></div>
            </form>
        </div>
        <?php
    }
    
    private function render_field($field) {
        $name = esc_attr($field['name']);
        $label = esc_html($field['label']);
        $required = !empty($field['required']) ? 'required' : '';
        $required_mark = !empty($field['required']) ? ' *' : '';
        
        switch ($field['type']) {
            case 'text':
            case 'email':
                ?>
                <label for="<?php echo $name; ?>"><?php echo $label . $required_mark; ?></label>
                <input type="<?php echo esc_attr($field['type']); ?>" 
                       id="<?php echo $name; ?>" 
                       name="<?php echo $name; ?>" 
                       <?php echo $required; ?> />
                <?php
                break;
                
            case 'textarea':
                ?>
                <label for="<?php echo $name; ?>"><?php echo $label . $required_mark; ?></label>
                <textarea id="<?php echo $name; ?>" 
                         name="<?php echo $name; ?>" 
                         rows="5" 
                         <?php echo $required; ?>></textarea>
                <?php
                break;
                
            case 'checkbox':
                ?>
                <fieldset>
                    <legend><?php echo $label . $required_mark; ?></legend>
                    <?php foreach ($field['options'] as $value => $option_label): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" 
                                   name="<?php echo $name; ?>[]" 
                                   value="<?php echo esc_attr($value); ?>" />
                            <?php echo esc_html($option_label); ?>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
                <?php
                break;
                
            case 'radio':
                ?>
                <fieldset>
                    <legend><?php echo $label . $required_mark; ?></legend>
                    <?php foreach ($field['options'] as $value => $option_label): ?>
                        <label class="radio-label">
                            <input type="radio" 
                                   name="<?php echo $name; ?>" 
                                   value="<?php echo esc_attr($value); ?>" />
                            <?php echo esc_html($option_label); ?>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
                <?php
                break;
                
            case 'gdpr_consent':
                ?>
                <div class="gdpr-consent">
                    <label class="checkbox-label">
                        <input type="checkbox" 
                               name="<?php echo $name; ?>" 
                               value="1" 
                               <?php echo $required; ?> />
                        <?php echo $label . $required_mark; ?>
                    </label>
                    <?php if (!empty($field['description'])): ?>
                        <p class="gdpr-description"><?php echo esc_html($field['description']); ?></p>
                    <?php endif; ?>
                </div>
                <?php
                break;
        }
    }
    
    public function handle_form_submission() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['custom_forms_nonce'], 'custom_forms_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check honeypot
        if (!empty($_POST['honeypot'])) {
            wp_die('Spam detected');
        }
        
        // Rate limiting check
        $user_ip = $_SERVER['REMOTE_ADDR'];
        if ($this->is_rate_limited($user_ip)) {
            wp_send_json_error('Too many submissions. Please try again later.');
            return;
        }
        
        $form_id = intval($_POST['form_id']);
        $form = CustomForms_Form_Manager::get_form($form_id);
        
        if (!$form) {
            wp_send_json_error('Form not found');
            return;
        }
        
        // Validate and sanitize form data
        $entry_data = array();
        $errors = array();
        
        foreach ($form->fields as $field) {
            $field_name = $field['name'];
            $field_value = isset($_POST[$field_name]) ? $_POST[$field_name] : '';
            
            // Validate required fields
            if (!empty($field['required']) && empty($field_value)) {
                $errors[] = $field['label'] . ' is required.';
                continue;
            }
            
            // Sanitize based on field type
            switch ($field['type']) {
                case 'email':
                    $field_value = sanitize_email($field_value);
                    if (!is_email($field_value) && !empty($field_value)) {
                        $errors[] = $field['label'] . ' must be a valid email address.';
                    }
                    break;
                case 'text':
                    $field_value = sanitize_text_field($field_value);
                    break;
                case 'textarea':
                    $field_value = sanitize_textarea_field($field_value);
                    break;
                case 'checkbox':
                    if (is_array($field_value)) {
                        $field_value = array_map('sanitize_text_field', $field_value);
                    }
                    break;
                case 'radio':
                    $field_value = sanitize_text_field($field_value);
                    break;
                case 'gdpr_consent':
                    $field_value = !empty($field_value) ? '1' : '0';
                    break;
            }
            
            $entry_data[$field_name] = $field_value;
        }
        
        if (!empty($errors)) {
            wp_send_json_error(implode(' ', $errors));
            return;
        }
        
        // Save entry
        $entry_id = CustomForms_Entry_Manager::create_entry($form_id, $entry_data, $user_ip);
        
        if ($entry_id) {
            wp_send_json_success('Form submitted successfully!');
        } else {
            wp_send_json_error('Failed to save form submission.');
        }
    }
    
    private function is_rate_limited($ip) {
        $transient_key = 'custom_forms_rate_limit_' . md5($ip);
        $submissions = get_transient($transient_key);
        
        if ($submissions === false) {
            $submissions = 0;
        }
        
        if ($submissions >= 5) { // Max 5 submissions per hour
            return true;
        }
        
        // Increment counter
        set_transient($transient_key, $submissions + 1, HOUR_IN_SECONDS);
        
        return false;
    }
}
```

## Frontend JavaScript (public/js/form-script.js)

```javascript
jQuery(document).ready(function($) {
    $('.custom-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var formData = new FormData(this);
        formData.append('action', 'submit_custom_form');
        formData.append('form_id', form.data('form-id'));
        
        // Disable submit button
        form.find('.submit-button').prop('disabled', true).text('Submitting...');
        
        $.ajax({
            url: custom_forms_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    form.find('.form-messages').html('<div class="success-message">' + response.data + '</div>');
                    form[0].reset(); // Reset form
                } else {
                    form.find('.form-messages').html('<div class="error-message">' + response.data + '</div>');
                }
            },
            error: function() {
                form.find('.form-messages').html('<div class="error-message">An error occurred. Please try again.</div>');
            },
            complete: function() {
                // Re-enable submit button
                form.find('.submit-button').prop('disabled', false).text('Submit');
            }
        });
    });
});
```

## Basic CSS (public/css/form-style.css)

```css
.custom-form-container {
    max-width: 600px;
    margin: 0 auto;
}

.custom-form .form-field {
    margin-bottom: 20px;
}

.custom-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.custom-form input[type="text"],
.custom-form input[type="email"],
.custom-form textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.custom-form fieldset {
    border: none;
    padding: 0;
    margin: 0;
}

.custom-form legend {
    font-weight: bold;
    margin-bottom: 10px;
}

.custom-form .checkbox-label,
.custom-form .radio-label {
    display: block;
    margin-bottom: 8px;
    font-weight: normal;
}

.custom-form .checkbox-label input,
.custom-form .radio-label input {
    margin-right: 8px;
}

.custom-form .submit-button {
    background-color: #0073aa;
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
}

.custom-form .submit-button:hover {
    background-color: #005a87;
}

.custom-form .submit-button:disabled {
    background-color: #ccc;
    cursor: not-allowed;
}

.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 12px;
    border: 1px solid #c3e6cb;
    border-radius: 4px;
    margin-top: 15px;
}

.error-message {
    background-color: #f8d7da;
    color: #721c24;
    padding: 12px;
    border: 1px solid #f5c6cb;
    border-radius: 4px;
    margin-top: 15px;
}

.gdpr-consent {
    background-color: #f9f9f9;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.gdpr-description {
    font-size: 14px;
    color: #666;
    margin-top: 8px;
    line-height: 1.4;
}

/* Responsive design */
@media (max-width: 768px) {
    .custom-form-container {
        padding: 0 15px;
    }
    
    .custom-form input[type="text"],
    .custom-form input[type="email"],
    .custom-form textarea {
        font-size: 16px; /* Prevents zoom on iOS */
    }
}
```

This implementation provides:

1. **Simple form creation** using templates
2. **WordPress integration** via shortcodes
3. **Basic security** (honeypot, rate limiting, nonce verification)
4. **Data storage** and CSV export
5. **Responsive design** that works on all devices
6. **GDPR compliance** with consent checkboxes

The MVP can be built in 6-10 weeks and will perfectly replicate your current forms while being much simpler than a full drag-and-drop solution.

