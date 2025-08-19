<?php
/**
 * Public Class
 * Handles public-facing functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class InnovativeForms_Public {
    
    public function __construct() {
        add_action('wp_head', array($this, 'add_custom_styles'));
        add_action('wp_footer', array($this, 'add_form_scripts'));
    }
    
    /**
     * Add custom styles to head
     */
    public function add_custom_styles() {
        // Only add styles if there's a form on the page
        global $post;
        if (!$post || !has_shortcode($post->post_content, 'innovative_form')) {
            return;
        }
        
        ?>
        <style>
        :root {
            --innovative-primary: #667eea;
            --innovative-secondary: #764ba2;
            --innovative-success: #10b981;
            --innovative-error: #ef4444;
            --innovative-warning: #f59e0b;
            --innovative-text: #1f2937;
            --innovative-text-light: #6b7280;
            --innovative-border: #e5e7eb;
            --innovative-bg: #ffffff;
            --innovative-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --innovative-shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        </style>
        <?php
    }
    
    /**
     * Add form scripts to footer
     */
    public function add_form_scripts() {
        // Only add scripts if there's a form on the page
        global $post;
        if (!$post || !has_shortcode($post->post_content, 'innovative_form')) {
            return;
        }
        
        ?>
        <script>
        // Initialize forms when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            InnovativeForms.init();
        });
        </script>
        <?php
    }
}

