<?php
/**
 * Form Validator Class
 * Handles form validation and data sanitization
 */

if (!defined('ABSPATH')) {
    exit;
}

class InnovativeForms_Form_Validator {
    
    private $form;
    
    public function __construct($form) {
        $this->form = $form;
    }
    
    /**
     * Validate form submission data
     */
    public function validate($data) {
        $errors = array();
        $sanitized_data = array();
        
        foreach ($this->form->fields as $field) {
            $field_name = $field['name'];
            $field_value = isset($data[$field_name]) ? $data[$field_name] : '';
            
            // Validate required fields
            if (!empty($field['required']) && $this->is_empty_value($field_value)) {
                $errors[$field_name] = sprintf(__('%s is required.', 'innovative-forms'), $field['label']);
                continue;
            }
            
            // Skip validation for empty optional fields
            if (empty($field['required']) && $this->is_empty_value($field_value)) {
                $sanitized_data[$field_name] = '';
                continue;
            }
            
            // Validate and sanitize based on field type
            $validation_result = $this->validate_field($field, $field_value);
            
            if ($validation_result['valid']) {
                $sanitized_data[$field_name] = $validation_result['value'];
            } else {
                $errors[$field_name] = $validation_result['error'];
            }
        }
        
        return array(
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $sanitized_data
        );
    }
    
    /**
     * Validate individual field
     */
    private function validate_field($field, $value) {
        switch ($field['type']) {
            case 'email':
                return $this->validate_email($field, $value);
                
            case 'url':
                return $this->validate_url($field, $value);
                
            case 'tel':
                return $this->validate_phone($field, $value);
                
            case 'number':
                return $this->validate_number($field, $value);
                
            case 'date':
                return $this->validate_date($field, $value);
                
            case 'checkbox':
                return $this->validate_checkbox($field, $value);
                
            case 'radio':
                return $this->validate_radio($field, $value);
                
            case 'select':
                return $this->validate_select($field, $value);
                
            case 'gdpr_consent':
                return $this->validate_gdpr_consent($field, $value);
                
            case 'text':
            case 'textarea':
            default:
                return $this->validate_text($field, $value);
        }
    }
    
    /**
     * Validate email field
     */
    private function validate_email($field, $value) {
        $sanitized = sanitize_email($value);
        
        if (!is_email($sanitized)) {
            return array(
                'valid' => false,
                'error' => sprintf(__('%s must be a valid email address.', 'innovative-forms'), $field['label'])
            );
        }
        
        return array('valid' => true, 'value' => $sanitized);
    }
    
    /**
     * Validate URL field
     */
    private function validate_url($field, $value) {
        $sanitized = esc_url_raw($value);
        
        if (!filter_var($sanitized, FILTER_VALIDATE_URL)) {
            return array(
                'valid' => false,
                'error' => sprintf(__('%s must be a valid URL.', 'innovative-forms'), $field['label'])
            );
        }
        
        return array('valid' => true, 'value' => $sanitized);
    }
    
    /**
     * Validate phone field
     */
    private function validate_phone($field, $value) {
        $sanitized = sanitize_text_field($value);
        
        // Basic phone validation - allow numbers, spaces, dashes, parentheses, plus
        if (!preg_match('/^[\d\s\-\(\)\+]+$/', $sanitized)) {
            return array(
                'valid' => false,
                'error' => sprintf(__('%s must be a valid phone number.', 'innovative-forms'), $field['label'])
            );
        }
        
        return array('valid' => true, 'value' => $sanitized);
    }
    
    /**
     * Validate number field
     */
    private function validate_number($field, $value) {
        $sanitized = floatval($value);
        
        if (!is_numeric($value)) {
            return array(
                'valid' => false,
                'error' => sprintf(__('%s must be a valid number.', 'innovative-forms'), $field['label'])
            );
        }
        
        // Check min value
        if (isset($field['min']) && $sanitized < floatval($field['min'])) {
            return array(
                'valid' => false,
                'error' => sprintf(__('%s must be at least %s.', 'innovative-forms'), $field['label'], $field['min'])
            );
        }
        
        // Check max value
        if (isset($field['max']) && $sanitized > floatval($field['max'])) {
            return array(
                'valid' => false,
                'error' => sprintf(__('%s must be no more than %s.', 'innovative-forms'), $field['label'], $field['max'])
            );
        }
        
        return array('valid' => true, 'value' => $sanitized);
    }
    
    /**
     * Validate date field
     */
    private function validate_date($field, $value) {
        $sanitized = sanitize_text_field($value);
        
        if (!strtotime($sanitized)) {
            return array(
                'valid' => false,
                'error' => sprintf(__('%s must be a valid date.', 'innovative-forms'), $field['label'])
            );
        }
        
        // Check min date
        if (isset($field['min']) && strtotime($sanitized) < strtotime($field['min'])) {
            return array(
                'valid' => false,
                'error' => sprintf(__('%s must be after %s.', 'innovative-forms'), $field['label'], $field['min'])
            );
        }
        
        // Check max date
        if (isset($field['max']) && strtotime($sanitized) > strtotime($field['max'])) {
            return array(
                'valid' => false,
                'error' => sprintf(__('%s must be before %s.', 'innovative-forms'), $field['label'], $field['max'])
            );
        }
        
        return array('valid' => true, 'value' => $sanitized);
    }
    
    /**
     * Validate checkbox field
     */
    private function validate_checkbox($field, $value) {
        if (!is_array($value)) {
            $value = array();
        }
        
        $sanitized = array_map('sanitize_text_field', $value);
        
        // Validate against allowed options
        if (isset($field['options'])) {
            $allowed_values = array_keys($field['options']);
            foreach ($sanitized as $selected_value) {
                if (!in_array($selected_value, $allowed_values)) {
                    return array(
                        'valid' => false,
                        'error' => sprintf(__('Invalid selection for %s.', 'innovative-forms'), $field['label'])
                    );
                }
            }
        }
        
        return array('valid' => true, 'value' => $sanitized);
    }
    
    /**
     * Validate radio field
     */
    private function validate_radio($field, $value) {
        $sanitized = sanitize_text_field($value);
        
        // Validate against allowed options
        if (isset($field['options'])) {
            $allowed_values = array_keys($field['options']);
            if (!in_array($sanitized, $allowed_values)) {
                return array(
                    'valid' => false,
                    'error' => sprintf(__('Invalid selection for %s.', 'innovative-forms'), $field['label'])
                );
            }
        }
        
        return array('valid' => true, 'value' => $sanitized);
    }
    
    /**
     * Validate select field
     */
    private function validate_select($field, $value) {
        $sanitized = sanitize_text_field($value);
        
        // Validate against allowed options
        if (isset($field['options'])) {
            $allowed_values = array_keys($field['options']);
            if (!in_array($sanitized, $allowed_values)) {
                return array(
                    'valid' => false,
                    'error' => sprintf(__('Invalid selection for %s.', 'innovative-forms'), $field['label'])
                );
            }
        }
        
        return array('valid' => true, 'value' => $sanitized);
    }
    
    /**
     * Validate GDPR consent field
     */
    private function validate_gdpr_consent($field, $value) {
        $sanitized = !empty($value) ? '1' : '0';
        
        if (!empty($field['required']) && $sanitized !== '1') {
            return array(
                'valid' => false,
                'error' => sprintf(__('You must agree to %s.', 'innovative-forms'), $field['label'])
            );
        }
        
        return array('valid' => true, 'value' => $sanitized);
    }
    
    /**
     * Validate text field
     */
    private function validate_text($field, $value) {
        if ($field['type'] === 'textarea') {
            $sanitized = sanitize_textarea_field($value);
        } else {
            $sanitized = sanitize_text_field($value);
        }
        
        // Check minimum length
        if (isset($field['min_length']) && strlen($sanitized) < intval($field['min_length'])) {
            return array(
                'valid' => false,
                'error' => sprintf(__('%s must be at least %d characters long.', 'innovative-forms'), $field['label'], $field['min_length'])
            );
        }
        
        // Check maximum length
        if (isset($field['max_length']) && strlen($sanitized) > intval($field['max_length'])) {
            return array(
                'valid' => false,
                'error' => sprintf(__('%s must be no more than %d characters long.', 'innovative-forms'), $field['label'], $field['max_length'])
            );
        }
        
        return array('valid' => true, 'value' => $sanitized);
    }
    
    /**
     * Check if value is empty
     */
    private function is_empty_value($value) {
        if (is_array($value)) {
            return empty($value);
        }
        
        return trim($value) === '';
    }
}

