/**
 * TiD Forms - Standalone Form JavaScript
 * For iframe embedded forms
 */

(function() {
    'use strict';

    // Configuration
    const API_BASE = window.location.origin + '/api';

    // Initialize form when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initializeForm();
    });

    function initializeForm() {
        const form = document.querySelector('.tid-form');
        if (!form) return;

        const formId = form.getAttribute('data-form-id');
        if (!formId) return;

        // Add form submission handler
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            handleFormSubmission(form, formId);
        });

        // Add input focus effects
        addInputEffects(form);

        // Auto-resize iframe if embedded
        autoResizeIframe();
    }

    function addInputEffects(form) {
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            // Add floating label effect
            if (input.placeholder) {
                addFloatingLabel(input);
            }

            // Add validation feedback
            input.addEventListener('blur', function() {
                validateField(input);
            });

            // Clear validation on focus
            input.addEventListener('focus', function() {
                clearFieldValidation(input);
            });
        });
    }

    function addFloatingLabel(input) {
        const field = input.closest('.form-field');
        if (!field) return;

        const label = field.querySelector('.field-label');
        if (!label) return;

        // Add floating label class
        field.classList.add('floating-label-field');

        // Check if input has value on load
        if (input.value) {
            field.classList.add('has-value');
        }

        // Add event listeners
        input.addEventListener('focus', function() {
            field.classList.add('is-focused');
        });

        input.addEventListener('blur', function() {
            field.classList.remove('is-focused');
            if (input.value) {
                field.classList.add('has-value');
            } else {
                field.classList.remove('has-value');
            }
        });

        input.addEventListener('input', function() {
            if (input.value) {
                field.classList.add('has-value');
            } else {
                field.classList.remove('has-value');
            }
        });
    }

    function validateField(input) {
        const field = input.closest('.form-field');
        if (!field) return;

        // Remove existing validation classes
        field.classList.remove('field-valid', 'field-invalid');

        // Check if field is required and empty
        if (input.hasAttribute('required') && !input.value.trim()) {
            field.classList.add('field-invalid');
            showFieldError(field, 'This field is required');
            return false;
        }

        // Email validation
        if (input.type === 'email' && input.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(input.value)) {
                field.classList.add('field-invalid');
                showFieldError(field, 'Please enter a valid email address');
                return false;
            }
        }

        // Phone validation (basic)
        if (input.type === 'tel' && input.value) {
            const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
            if (!phoneRegex.test(input.value.replace(/[\s\-\(\)]/g, ''))) {
                field.classList.add('field-invalid');
                showFieldError(field, 'Please enter a valid phone number');
                return false;
            }
        }

        // URL validation
        if (input.type === 'url' && input.value) {
            try {
                new URL(input.value);
            } catch {
                field.classList.add('field-invalid');
                showFieldError(field, 'Please enter a valid URL');
                return false;
            }
        }

        // If we get here, field is valid
        field.classList.add('field-valid');
        clearFieldError(field);
        return true;
    }

    function showFieldError(field, message) {
        // Remove existing error message
        const existingError = field.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }

        // Create error message element
        const errorElement = document.createElement('div');
        errorElement.className = 'field-error';
        errorElement.textContent = message;
        errorElement.style.cssText = `
            color: #dc2626;
            font-size: 12px;
            margin-top: 4px;
            animation: slideDown 0.2s ease-out;
        `;

        field.appendChild(errorElement);
    }

    function clearFieldError(field) {
        const errorElement = field.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
    }

    function clearFieldValidation(input) {
        const field = input.closest('.form-field');
        if (!field) return;

        field.classList.remove('field-valid', 'field-invalid');
        clearFieldError(field);
    }

    async function handleFormSubmission(form, formId) {
        const submitButton = form.querySelector('.submit-btn');
        const originalText = submitButton.textContent;

        try {
            // Show loading state
            submitButton.textContent = 'Submitting...';
            submitButton.disabled = true;
            submitButton.classList.add('loading');

            // Validate all fields
            const inputs = form.querySelectorAll('input, textarea, select');
            let isValid = true;

            inputs.forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                }
            });

            if (!isValid) {
                throw new Error('Please correct the errors above');
            }

            // Collect form data
            const formData = new FormData(form);
            const data = {};
            
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }

            // Submit to API
            const response = await fetch(`${API_BASE}/forms/${formId}/submit`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ data })
            });

            const result = await response.json();

            if (result.success) {
                showSuccessMessage(form);
                form.reset();
                
                // Clear all validation states
                inputs.forEach(input => {
                    clearFieldValidation(input);
                    const field = input.closest('.form-field');
                    if (field) {
                        field.classList.remove('has-value');
                    }
                });

                // Auto-resize iframe after success message
                setTimeout(autoResizeIframe, 100);
            } else {
                throw new Error(result.error || 'Submission failed');
            }

        } catch (error) {
            console.error('Form submission error:', error);
            showErrorMessage(form, error.message);
        } finally {
            // Reset button
            submitButton.textContent = originalText;
            submitButton.disabled = false;
            submitButton.classList.remove('loading');
        }
    }

    function showSuccessMessage(form) {
        // Remove existing messages
        const existingMessages = form.querySelectorAll('.form-message');
        existingMessages.forEach(msg => msg.remove());

        const message = document.createElement('div');
        message.className = 'form-message success-message';
        message.innerHTML = `
            <div style="
                padding: 16px;
                background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
                border: 1px solid #86efac;
                border-radius: 8px;
                color: #166534;
                font-weight: 500;
                text-align: center;
                animation: slideDown 0.3s ease-out;
            ">
                <div style="font-size: 18px; margin-bottom: 4px;">✓ Success!</div>
                <div style="font-size: 14px;">Thank you for your submission. We'll get back to you soon.</div>
            </div>
        `;

        form.appendChild(message);

        // Remove message after 5 seconds
        setTimeout(() => {
            if (message.parentNode) {
                message.style.animation = 'slideUp 0.3s ease-out';
                setTimeout(() => {
                    if (message.parentNode) {
                        message.remove();
                        autoResizeIframe();
                    }
                }, 300);
            }
        }, 5000);
    }

    function showErrorMessage(form, errorText) {
        // Remove existing messages
        const existingMessages = form.querySelectorAll('.form-message');
        existingMessages.forEach(msg => msg.remove());

        const message = document.createElement('div');
        message.className = 'form-message error-message';
        message.innerHTML = `
            <div style="
                padding: 16px;
                background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%);
                border: 1px solid #f87171;
                border-radius: 8px;
                color: #dc2626;
                font-weight: 500;
                text-align: center;
                animation: slideDown 0.3s ease-out;
            ">
                <div style="font-size: 18px; margin-bottom: 4px;">✗ Error</div>
                <div style="font-size: 14px;">${errorText}</div>
            </div>
        `;

        form.appendChild(message);

        // Auto-resize iframe
        setTimeout(autoResizeIframe, 100);

        // Remove message after 5 seconds
        setTimeout(() => {
            if (message.parentNode) {
                message.style.animation = 'slideUp 0.3s ease-out';
                setTimeout(() => {
                    if (message.parentNode) {
                        message.remove();
                        autoResizeIframe();
                    }
                }, 300);
            }
        }, 5000);
    }

    function autoResizeIframe() {
        // Send height to parent window for iframe resizing
        if (window.parent !== window) {
            const height = document.body.scrollHeight;
            window.parent.postMessage({
                type: 'tid-form-resize',
                height: height
            }, '*');
        }
    }

    // Add CSS animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-10px);
            }
        }

        .floating-label-field {
            position: relative;
        }

        .floating-label-field .field-label {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: white;
            padding: 0 4px;
            transition: all 0.2s ease;
            pointer-events: none;
            color: #6b7280;
            font-size: 16px;
        }

        .floating-label-field.is-focused .field-label,
        .floating-label-field.has-value .field-label {
            top: 0;
            font-size: 12px;
            color: #374151;
            font-weight: 600;
        }

        .floating-label-field .field-input,
        .floating-label-field .field-textarea,
        .floating-label-field .field-select {
            padding-top: 20px;
            padding-bottom: 8px;
        }

        .field-valid .field-input,
        .field-valid .field-textarea,
        .field-valid .field-select {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .field-invalid .field-input,
        .field-invalid .field-textarea,
        .field-invalid .field-select {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        .submit-btn.loading {
            position: relative;
            color: transparent;
        }

        .submit-btn.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .floating-label-field .field-label {
                font-size: 14px;
            }
            
            .floating-label-field.is-focused .field-label,
            .floating-label-field.has-value .field-label {
                font-size: 11px;
            }
        }
    `;
    document.head.appendChild(style);

    // Listen for iframe resize messages from parent
    window.addEventListener('message', function(event) {
        if (event.data.type === 'tid-form-theme-change') {
            // Handle theme changes from parent
            const container = document.querySelector('.tid-form-container');
            if (container && event.data.theme) {
                container.className = container.className.replace(/theme-\w+/, `theme-${event.data.theme}`);
            }
        }
    });

    // Initial iframe resize
    setTimeout(autoResizeIframe, 100);

})();

