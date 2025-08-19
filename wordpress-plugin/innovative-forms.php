<?php
/**
 * Plugin Name: Innovative Forms
 * Plugin URI: https://theinnovativedinosaur.com
 * Description: Beautiful, modern WordPress forms with impressive UI/UX design. A powerful replacement for WPForms with focus on stunning frontend design.
 * Version: 1.0.0
 * Author: The Innovative Dinosaur
 * Author URI: https://theinnovativedinosaur.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: innovative-forms
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('INNOVATIVE_FORMS_VERSION', '1.0.0');
define('INNOVATIVE_FORMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('INNOVATIVE_FORMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('INNOVATIVE_FORMS_PLUGIN_FILE', __FILE__);
define('INNOVATIVE_FORMS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Innovative Forms Plugin Class
 */
class InnovativeForms {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('InnovativeForms', 'uninstall'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('innovative-forms', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Load required files
        $this->load_dependencies();
        
        // Initialize components
        if (is_admin()) {
            new InnovativeForms_Admin();
        }
        
        new InnovativeForms_Public();
        new InnovativeForms_Shortcode();
        
        // Load assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Core classes
        require_once INNOVATIVE_FORMS_PLUGIN_DIR . 'includes/class-form-manager.php';
        require_once INNOVATIVE_FORMS_PLUGIN_DIR . 'includes/class-entry-manager.php';
        require_once INNOVATIVE_FORMS_PLUGIN_DIR . 'includes/class-field-renderer.php';
        require_once INNOVATIVE_FORMS_PLUGIN_DIR . 'includes/class-form-validator.php';
        require_once INNOVATIVE_FORMS_PLUGIN_DIR . 'includes/class-security-manager.php';
        
        // Admin classes
        if (is_admin()) {
            require_once INNOVATIVE_FORMS_PLUGIN_DIR . 'includes/class-admin.php';
        }
        
        // Public classes
        require_once INNOVATIVE_FORMS_PLUGIN_DIR . 'includes/class-public.php';
        require_once INNOVATIVE_FORMS_PLUGIN_DIR . 'includes/class-shortcode.php';
    }
    
    /**
     * Enqueue public assets
     */
    public function enqueue_public_assets() {
        // Main form styles
        wp_enqueue_style(
            'innovative-forms-public',
            INNOVATIVE_FORMS_PLUGIN_URL . 'public/css/innovative-forms.css',
            array(),
            INNOVATIVE_FORMS_VERSION
        );
        
        // Form animations and interactions
        wp_enqueue_script(
            'innovative-forms-public',
            INNOVATIVE_FORMS_PLUGIN_URL . 'public/js/innovative-forms.js',
            array('jquery'),
            INNOVATIVE_FORMS_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('innovative-forms-public', 'innovative_forms_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('innovative_forms_nonce'),
            'messages' => array(
                'submitting' => __('Submitting...', 'innovative-forms'),
                'success' => __('Thank you! Your form has been submitted successfully.', 'innovative-forms'),
                'error' => __('An error occurred. Please try again.', 'innovative-forms'),
                'validation_error' => __('Please check the highlighted fields.', 'innovative-forms')
            )
        ));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'innovative-forms') === false) {
            return;
        }
        
        wp_enqueue_style(
            'innovative-forms-admin',
            INNOVATIVE_FORMS_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            INNOVATIVE_FORMS_VERSION
        );
        
        wp_enqueue_script(
            'innovative-forms-admin',
            INNOVATIVE_FORMS_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery', 'wp-color-picker'),
            INNOVATIVE_FORMS_VERSION,
            true
        );
        
        // Add color picker support
        wp_enqueue_style('wp-color-picker');
        
        wp_localize_script('innovative-forms-admin', 'innovative_forms_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('innovative_forms_admin_nonce')
        ));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Set default options
        add_option('innovative_forms_version', INNOVATIVE_FORMS_VERSION);
        add_option('innovative_forms_settings', array(
            'form_theme' => 'modern',
            'animation_enabled' => true,
            'spam_protection' => true
        ));
        
        // Create default forms
        $this->create_default_forms();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        global $wpdb;
        
        // Remove database tables
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}innovative_forms");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}innovative_form_entries");
        
        // Remove options
        delete_option('innovative_forms_version');
        delete_option('innovative_forms_settings');
        
        // Remove any cached data
        wp_cache_flush();
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Forms table
        $forms_table = $wpdb->prefix . 'innovative_forms';
        $forms_sql = "CREATE TABLE $forms_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            fields longtext NOT NULL,
            settings longtext,
            status varchar(20) DEFAULT 'active',
            theme varchar(50) DEFAULT 'modern',
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY created_date (created_date)
        ) $charset_collate;";
        
        // Entries table
        $entries_table = $wpdb->prefix . 'innovative_form_entries';
        $entries_sql = "CREATE TABLE $entries_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            form_id mediumint(9) NOT NULL,
            entry_data longtext NOT NULL,
            submission_date datetime DEFAULT CURRENT_TIMESTAMP,
            user_ip varchar(45),
            user_agent text,
            status varchar(20) DEFAULT 'unread',
            spam_score int DEFAULT 0,
            PRIMARY KEY (id),
            KEY form_id (form_id),
            KEY submission_date (submission_date),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($forms_sql);
        dbDelta($entries_sql);
    }
    
    /**
     * Create default forms
     */
    private function create_default_forms() {
        require_once INNOVATIVE_FORMS_PLUGIN_DIR . 'includes/class-form-manager.php';
        
        // Newsletter form
        $newsletter_fields = array(
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
                'name' => 'country',
                'label' => 'Country',
                'placeholder' => 'Enter your country',
                'required' => false,
                'icon' => 'globe'
            ),
            array(
                'type' => 'text',
                'name' => 'title',
                'label' => 'Title/Profession/Role',
                'placeholder' => 'Enter your professional title',
                'required' => false,
                'icon' => 'briefcase'
            ),
            array(
                'type' => 'checkbox',
                'name' => 'interests',
                'label' => 'What are you interested in?',
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
                'label' => 'What format do you prefer most?',
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
        );
        
        InnovativeForms_Form_Manager::create_form(
            'Newsletter Subscription',
            'Subscribe to our newsletter for updates and exclusive content',
            $newsletter_fields,
            array('theme' => 'modern', 'animation' => 'fade-in')
        );
        
        // Contributors form
        $contributor_fields = array(
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
                'type' => 'text',
                'name' => 'title',
                'label' => 'Title/Profession/Role',
                'placeholder' => 'Enter your professional title',
                'required' => false,
                'icon' => 'briefcase'
            ),
            array(
                'type' => 'text',
                'name' => 'company',
                'label' => 'Association/Company/Community Name',
                'placeholder' => 'Enter your organization name',
                'required' => true,
                'icon' => 'building'
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
                'name' => 'linkedin_url',
                'label' => 'LinkedIn Profile or Personal Website URL',
                'placeholder' => 'https://linkedin.com/in/yourprofile',
                'required' => true,
                'icon' => 'link'
            ),
            array(
                'type' => 'text',
                'name' => 'country',
                'label' => 'Country',
                'placeholder' => 'Enter your country',
                'required' => true,
                'icon' => 'globe'
            ),
            array(
                'type' => 'text',
                'name' => 'industry',
                'label' => 'Industry',
                'placeholder' => 'Enter your industry',
                'required' => true,
                'icon' => 'industry'
            ),
            array(
                'type' => 'checkbox',
                'name' => 'contribution_type',
                'label' => 'How would you like to contribute?',
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
                'label' => 'Additional Information',
                'placeholder' => 'Tell us more about yourself and how you\'d like to contribute...',
                'required' => false,
                'rows' => 5
            ),
            array(
                'type' => 'gdpr_consent',
                'name' => 'gdpr_consent',
                'label' => 'I agree to allow The Innovative Dinosaur to store and process my personal data',
                'required' => true,
                'description' => 'In order to provide you the request above, we need to store and process your data provided above. You will be automatically add to the subscription list. If you consent to us storing your personal data for this purpose, please tick the checkbox below. You can unsubscribe from these communications at any time.'
            )
        );
        
        InnovativeForms_Form_Manager::create_form(
            'Contributors Registration',
            'Join our community of contributors and thought leaders',
            $contributor_fields,
            array('theme' => 'professional', 'animation' => 'slide-up')
        );
    }
}

// Initialize the plugin
InnovativeForms::get_instance();

