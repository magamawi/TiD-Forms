<?php
/**
 * Shortcode Class
 * Handles form shortcode rendering and AJAX submissions
 */

if (!defined('ABSPATH')) {
    exit;
}

class InnovativeForms_Shortcode {
    
    public function __construct() {
        add_shortcode('innovative_form', array($this, 'render_form'));
        add_action('wp_ajax_submit_innovative_form', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_submit_innovative_form', array($this, 'handle_form_submission'));
    }
    
    /**
     * Render form shortcode
     */
    public function render_form($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'theme' => '',
            'animation' => ''
        ), $atts);
        
        $form_id = intval($atts['id']);
        
        if (!$form_id) {
            return '<div class="innovative-form-error">' . __('Error: Form ID is required.', 'innovative-forms') . '</div>';
        }
        
        $form = InnovativeForms_Form_Manager::get_form($form_id);
        
        if (!$form || $form->status !== 'active') {
            return '<div class="innovative-form-error">' . __('Error: Form not found or inactive.', 'innovative-forms') . '</div>';
        }
        
        // Override theme if specified in shortcode
        if (!empty($atts['theme'])) {
            $form->settings['theme'] = $atts['theme'];
        }
        
        if (!empty($atts['animation'])) {
            $form->settings['animation'] = $atts['animation'];
        }
        
        ob_start();
        $this->render_form_html($form);
        return ob_get_clean();
    }
    
    /**
     * Render form HTML
     */
    private function render_form_html($form) {
        $theme = isset($form->settings['theme']) ? $form->settings['theme'] : 'modern';
        $animation = isset($form->settings['animation']) ? $form->settings['animation'] : 'fade-in';
        $primary_color = isset($form->settings['primary_color']) ? $form->settings['primary_color'] : '#667eea';
        $secondary_color = isset($form->settings['secondary_color']) ? $form->settings['secondary_color'] : '#764ba2';
        $border_radius = isset($form->settings['border_radius']) ? $form->settings['border_radius'] : '12';
        $spacing = isset($form->settings['spacing']) ? $form->settings['spacing'] : 'comfortable';
        
        ?>
        <div class="innovative-form-container" 
             id="innovative-form-<?php echo esc_attr($form->id); ?>"
             data-theme="<?php echo esc_attr($theme); ?>"
             data-animation="<?php echo esc_attr($animation); ?>"
             data-form-id="<?php echo esc_attr($form->id); ?>"
             style="--primary-color: <?php echo esc_attr($primary_color); ?>; --secondary-color: <?php echo esc_attr($secondary_color); ?>; --border-radius: <?php echo esc_attr($border_radius); ?>px;">
            
            <div class="innovative-form-wrapper theme-<?php echo esc_attr($theme); ?> spacing-<?php echo esc_attr($spacing); ?>">
                <?php if ($form->name || $form->description): ?>
                    <div class="form-header">
                        <?php if ($form->name): ?>
                            <h2 class="form-title"><?php echo esc_html($form->name); ?></h2>
                        <?php endif; ?>
                        <?php if ($form->description): ?>
                            <p class="form-description"><?php echo esc_html($form->description); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <form class="innovative-form" data-form-id="<?php echo esc_attr($form->id); ?>" novalidate>
                    <?php wp_nonce_field('innovative_forms_nonce', 'innovative_forms_nonce'); ?>
                    
                    <div class="form-fields">
                        <?php foreach ($form->fields as $index => $field): ?>
                            <div class="form-field field-<?php echo esc_attr($field['type']); ?> field-animation" 
                                 data-field-index="<?php echo $index; ?>"
                                 style="animation-delay: <?php echo ($index * 0.1); ?>s;">
                                <?php $this->render_field($field); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Honeypot field for spam protection -->
                    <div class="honeypot-field" style="position: absolute; left: -9999px; opacity: 0; pointer-events: none;">
                        <input type="text" name="honeypot" value="" tabindex="-1" autocomplete="off" />
                    </div>
                    
                    <div class="form-submit">
                        <button type="submit" class="submit-button">
                            <span class="button-text">
                                <?php echo esc_html(isset($form->settings['submit_button_text']) ? $form->settings['submit_button_text'] : __('Submit', 'innovative-forms')); ?>
                            </span>
                            <span class="button-loader">
                                <svg class="spinner" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="32">
                                        <animate attributeName="stroke-dashoffset" dur="1s" values="32;0;32" repeatCount="indefinite"/>
                                    </circle>
                                </svg>
                            </span>
                        </button>
                    </div>
                    
                    <div class="form-messages"></div>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render individual field
     */
    private function render_field($field) {
        $name = esc_attr($field['name']);
        $label = esc_html($field['label']);
        $required = !empty($field['required']);
        $required_mark = $required ? ' <span class="required-mark">*</span>' : '';
        $placeholder = isset($field['placeholder']) ? esc_attr($field['placeholder']) : '';
        $icon = isset($field['icon']) ? esc_attr($field['icon']) : '';
        
        switch ($field['type']) {
            case 'text':
            case 'email':
            case 'tel':
            case 'url':
                ?>
                <div class="field-wrapper">
                    <label for="<?php echo $name; ?>" class="field-label">
                        <?php if ($icon): ?>
                            <i class="field-icon fas fa-<?php echo $icon; ?>"></i>
                        <?php endif; ?>
                        <?php echo $label . $required_mark; ?>
                    </label>
                    <div class="input-wrapper">
                        <input type="<?php echo esc_attr($field['type']); ?>" 
                               id="<?php echo $name; ?>" 
                               name="<?php echo $name; ?>" 
                               placeholder="<?php echo $placeholder; ?>"
                               class="form-input"
                               <?php echo $required ? 'required' : ''; ?> />
                        <div class="input-focus-border"></div>
                    </div>
                    <div class="field-error"></div>
                </div>
                <?php
                break;
                
            case 'number':
                $min = isset($field['min']) ? esc_attr($field['min']) : '';
                $max = isset($field['max']) ? esc_attr($field['max']) : '';
                $step = isset($field['step']) ? esc_attr($field['step']) : '';
                ?>
                <div class="field-wrapper">
                    <label for="<?php echo $name; ?>" class="field-label">
                        <?php if ($icon): ?>
                            <i class="field-icon fas fa-<?php echo $icon; ?>"></i>
                        <?php endif; ?>
                        <?php echo $label . $required_mark; ?>
                    </label>
                    <div class="input-wrapper">
                        <input type="number" 
                               id="<?php echo $name; ?>" 
                               name="<?php echo $name; ?>" 
                               placeholder="<?php echo $placeholder; ?>"
                               class="form-input"
                               <?php echo $min ? 'min="' . $min . '"' : ''; ?>
                               <?php echo $max ? 'max="' . $max . '"' : ''; ?>
                               <?php echo $step ? 'step="' . $step . '"' : ''; ?>
                               <?php echo $required ? 'required' : ''; ?> />
                        <div class="input-focus-border"></div>
                    </div>
                    <div class="field-error"></div>
                </div>
                <?php
                break;
                
            case 'textarea':
                $rows = isset($field['rows']) ? intval($field['rows']) : 4;
                ?>
                <div class="field-wrapper">
                    <label for="<?php echo $name; ?>" class="field-label">
                        <?php echo $label . $required_mark; ?>
                    </label>
                    <div class="textarea-wrapper">
                        <textarea id="<?php echo $name; ?>" 
                                 name="<?php echo $name; ?>" 
                                 placeholder="<?php echo $placeholder; ?>"
                                 class="form-textarea"
                                 rows="<?php echo $rows; ?>"
                                 <?php echo $required ? 'required' : ''; ?>></textarea>
                        <div class="textarea-focus-border"></div>
                    </div>
                    <div class="field-error"></div>
                </div>
                <?php
                break;
                
            case 'select':
                ?>
                <div class="field-wrapper">
                    <label for="<?php echo $name; ?>" class="field-label">
                        <?php echo $label . $required_mark; ?>
                    </label>
                    <div class="select-wrapper">
                        <select id="<?php echo $name; ?>" 
                               name="<?php echo $name; ?>" 
                               class="form-select"
                               <?php echo $required ? 'required' : ''; ?>>
                            <option value=""><?php _e('Please select...', 'innovative-forms'); ?></option>
                            <?php if (isset($field['options'])): ?>
                                <?php foreach ($field['options'] as $value => $option_label): ?>
                                    <option value="<?php echo esc_attr($value); ?>">
                                        <?php echo esc_html($option_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="select-arrow">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6,9 12,15 18,9"></polyline>
                            </svg>
                        </div>
                    </div>
                    <div class="field-error"></div>
                </div>
                <?php
                break;
                
            case 'radio':
                ?>
                <div class="field-wrapper">
                    <fieldset class="radio-fieldset">
                        <legend class="field-label"><?php echo $label . $required_mark; ?></legend>
                        <div class="radio-group">
                            <?php if (isset($field['options'])): ?>
                                <?php foreach ($field['options'] as $value => $option_label): ?>
                                    <label class="radio-label">
                                        <input type="radio" 
                                               name="<?php echo $name; ?>" 
                                               value="<?php echo esc_attr($value); ?>"
                                               class="radio-input"
                                               <?php echo $required ? 'required' : ''; ?> />
                                        <span class="radio-custom"></span>
                                        <span class="radio-text"><?php echo esc_html($option_label); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </fieldset>
                    <div class="field-error"></div>
                </div>
                <?php
                break;
                
            case 'checkbox':
                ?>
                <div class="field-wrapper">
                    <fieldset class="checkbox-fieldset">
                        <legend class="field-label"><?php echo $label . $required_mark; ?></legend>
                        <div class="checkbox-group">
                            <?php if (isset($field['options'])): ?>
                                <?php foreach ($field['options'] as $value => $option_label): ?>
                                    <label class="checkbox-label">
                                        <input type="checkbox" 
                                               name="<?php echo $name; ?>[]" 
                                               value="<?php echo esc_attr($value); ?>"
                                               class="checkbox-input"
                                               <?php echo $required ? 'required' : ''; ?> />
                                        <span class="checkbox-custom">
                                            <svg class="checkbox-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                <polyline points="20,6 9,17 4,12"></polyline>
                                            </svg>
                                        </span>
                                        <span class="checkbox-text"><?php echo esc_html($option_label); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </fieldset>
                    <div class="field-error"></div>
                </div>
                <?php
                break;
                
            case 'date':
                $min = isset($field['min']) ? esc_attr($field['min']) : '';
                $max = isset($field['max']) ? esc_attr($field['max']) : '';
                ?>
                <div class="field-wrapper">
                    <label for="<?php echo $name; ?>" class="field-label">
                        <i class="field-icon fas fa-calendar"></i>
                        <?php echo $label . $required_mark; ?>
                    </label>
                    <div class="input-wrapper">
                        <input type="date" 
                               id="<?php echo $name; ?>" 
                               name="<?php echo $name; ?>" 
                               class="form-input"
                               <?php echo $min ? 'min="' . $min . '"' : ''; ?>
                               <?php echo $max ? 'max="' . $max . '"' : ''; ?>
                               <?php echo $required ? 'required' : ''; ?> />
                        <div class="input-focus-border"></div>
                    </div>
                    <div class="field-error"></div>
                </div>
                <?php
                break;
                
            case 'gdpr_consent':
                $description = isset($field['description']) ? $field['description'] : '';
                ?>
                <div class="field-wrapper gdpr-consent-wrapper">
                    <div class="gdpr-consent-container">
                        <label class="gdpr-consent-label">
                            <input type="checkbox" 
                                   name="<?php echo $name; ?>" 
                                   value="1"
                                   class="gdpr-consent-input"
                                   <?php echo $required ? 'required' : ''; ?> />
                            <span class="gdpr-consent-custom">
                                <svg class="gdpr-consent-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <polyline points="20,6 9,17 4,12"></polyline>
                                </svg>
                            </span>
                            <span class="gdpr-consent-text"><?php echo $label . $required_mark; ?></span>
                        </label>
                        <?php if ($description): ?>
                            <div class="gdpr-description">
                                <?php echo esc_html($description); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="field-error"></div>
                </div>
                <?php
                break;
        }
    }
    
    /**
     * Handle form submission via AJAX
     */
    public function handle_form_submission() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['innovative_forms_nonce'], 'innovative_forms_nonce')) {
            wp_send_json_error(__('Security check failed', 'innovative-forms'));
        }
        
        // Check honeypot
        if (!empty($_POST['honeypot'])) {
            wp_send_json_error(__('Spam detected', 'innovative-forms'));
        }
        
        // Rate limiting check
        $user_ip = $_SERVER['REMOTE_ADDR'];
        if ($this->is_rate_limited($user_ip)) {
            wp_send_json_error(__('Too many submissions. Please try again later.', 'innovative-forms'));
        }
        
        $form_id = intval($_POST['form_id']);
        $form = InnovativeForms_Form_Manager::get_form($form_id);
        
        if (!$form) {
            wp_send_json_error(__('Form not found', 'innovative-forms'));
        }
        
        // Validate and sanitize form data
        require_once INNOVATIVE_FORMS_PLUGIN_DIR . 'includes/class-form-validator.php';
        $validator = new InnovativeForms_Form_Validator($form);
        $validation_result = $validator->validate($_POST);
        
        if (!$validation_result['valid']) {
            wp_send_json_error(array(
                'message' => __('Please check the highlighted fields.', 'innovative-forms'),
                'field_errors' => $validation_result['errors']
            ));
        }
        
        // Save entry
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $entry_id = InnovativeForms_Entry_Manager::create_entry($form_id, $validation_result['data'], $user_ip, $user_agent);
        
        if ($entry_id) {
            $success_message = isset($form->settings['success_message']) ? $form->settings['success_message'] : __('Thank you! Your form has been submitted successfully.', 'innovative-forms');
            wp_send_json_success($success_message);
        } else {
            wp_send_json_error(__('Failed to save form submission. Please try again.', 'innovative-forms'));
        }
    }
    
    /**
     * Check if IP is rate limited
     */
    private function is_rate_limited($ip) {
        $transient_key = 'innovative_forms_rate_limit_' . md5($ip);
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

