/**
 * TiD Forms Embed System
 * JavaScript library for embedding forms on any website
 */

(function(window, document) {
    'use strict';

    // TiD Forms namespace
    window.TiDForms = window.TiDForms || {};

    // Configuration
    const CONFIG = {
        apiUrl: 'http://localhost:5000/api',
        cssUrl: 'http://localhost:5000/static/embed-styles.css',
        version: '1.0.0'
    };

    // Form themes configuration
    const THEMES = {
        modern: {
            primaryColor: '#6366f1',
            secondaryColor: '#8b5cf6',
            gradient: 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)',
            borderRadius: '12px',
            fontFamily: 'Inter, system-ui, sans-serif'
        },
        professional: {
            primaryColor: '#1e40af',
            secondaryColor: '#1e3a8a',
            gradient: 'linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%)',
            borderRadius: '8px',
            fontFamily: 'Inter, system-ui, sans-serif'
        },
        elegant: {
            primaryColor: '#7c3aed',
            secondaryColor: '#6d28d9',
            gradient: 'linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%)',
            borderRadius: '16px',
            fontFamily: 'Inter, system-ui, sans-serif'
        },
        creative: {
            primaryColor: '#ec4899',
            secondaryColor: '#f97316',
            gradient: 'linear-gradient(135deg, #ec4899 0%, #f97316 100%)',
            borderRadius: '20px',
            fontFamily: 'Inter, system-ui, sans-serif'
        },
        minimal: {
            primaryColor: '#4b5563',
            secondaryColor: '#374151',
            gradient: 'linear-gradient(135deg, #4b5563 0%, #374151 100%)',
            borderRadius: '4px',
            fontFamily: 'Inter, system-ui, sans-serif'
        }
    };

    // Utility functions
    const utils = {
        // Create element with attributes
        createElement: function(tag, attributes, content) {
            const element = document.createElement(tag);
            
            if (attributes) {
                Object.keys(attributes).forEach(key => {
                    if (key === 'className') {
                        element.className = attributes[key];
                    } else if (key === 'style' && typeof attributes[key] === 'object') {
                        Object.assign(element.style, attributes[key]);
                    } else {
                        element.setAttribute(key, attributes[key]);
                    }
                });
            }
            
            if (content) {
                if (typeof content === 'string') {
                    element.innerHTML = content;
                } else {
                    element.appendChild(content);
                }
            }
            
            return element;
        },

        // Load CSS dynamically
        loadCSS: function(url) {
            return new Promise((resolve, reject) => {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = url;
                link.onload = resolve;
                link.onerror = reject;
                document.head.appendChild(link);
            });
        },

        // Make API request
        apiRequest: function(endpoint, options = {}) {
            const url = `${CONFIG.apiUrl}${endpoint}`;
            const defaultOptions = {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            };

            return fetch(url, { ...defaultOptions, ...options })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                });
        },

        // Sanitize HTML to prevent XSS
        sanitizeHTML: function(str) {
            const temp = document.createElement('div');
            temp.textContent = str;
            return temp.innerHTML;
        },

        // Generate unique ID
        generateId: function() {
            return 'tid-form-' + Math.random().toString(36).substr(2, 9);
        }
    };

    // Form renderer class
    class FormRenderer {
        constructor(formData, theme, containerId) {
            this.formData = formData;
            this.theme = THEMES[theme] || THEMES.modern;
            this.containerId = containerId;
            this.formId = utils.generateId();
        }

        render() {
            const container = document.getElementById(this.containerId);
            if (!container) {
                console.error('TiD Forms: Container not found');
                return;
            }

            // Create form container
            const formContainer = utils.createElement('div', {
                className: 'tid-form-container',
                style: {
                    fontFamily: this.theme.fontFamily,
                    maxWidth: '600px',
                    margin: '0 auto',
                    padding: '24px',
                    background: 'white',
                    borderRadius: this.theme.borderRadius,
                    boxShadow: '0 10px 25px rgba(0, 0, 0, 0.1)',
                    border: '1px solid #e5e7eb'
                }
            });

            // Create form element
            const form = utils.createElement('form', {
                id: this.formId,
                className: 'tid-form',
                'data-form-id': this.formData.id
            });

            // Add form header
            if (this.formData.name || this.formData.description) {
                const header = this.createHeader();
                form.appendChild(header);
            }

            // Add form fields
            const fieldsContainer = this.createFields();
            form.appendChild(fieldsContainer);

            // Add submit button
            const submitButton = this.createSubmitButton();
            form.appendChild(submitButton);

            // Add form to container
            formContainer.appendChild(form);
            container.appendChild(formContainer);

            // Attach event listeners
            this.attachEventListeners();
        }

        createHeader() {
            const header = utils.createElement('div', {
                className: 'tid-form-header',
                style: {
                    marginBottom: '24px',
                    textAlign: 'center'
                }
            });

            if (this.formData.name) {
                const title = utils.createElement('h2', {
                    style: {
                        fontSize: '24px',
                        fontWeight: '700',
                        color: '#1f2937',
                        marginBottom: '8px',
                        background: this.theme.gradient,
                        WebkitBackgroundClip: 'text',
                        WebkitTextFillColor: 'transparent',
                        backgroundClip: 'text'
                    }
                }, utils.sanitizeHTML(this.formData.name));
                header.appendChild(title);
            }

            if (this.formData.description) {
                const description = utils.createElement('p', {
                    style: {
                        fontSize: '16px',
                        color: '#6b7280',
                        margin: '0'
                    }
                }, utils.sanitizeHTML(this.formData.description));
                header.appendChild(description);
            }

            return header;
        }

        createFields() {
            const fieldsContainer = utils.createElement('div', {
                className: 'tid-form-fields',
                style: {
                    marginBottom: '24px'
                }
            });

            this.formData.fields.forEach(field => {
                const fieldElement = this.createField(field);
                fieldsContainer.appendChild(fieldElement);
            });

            return fieldsContainer;
        }

        createField(field) {
            const fieldContainer = utils.createElement('div', {
                className: 'tid-form-field',
                style: {
                    marginBottom: '20px'
                }
            });

            // Create label
            if (field.label) {
                const label = utils.createElement('label', {
                    for: field.name,
                    style: {
                        display: 'block',
                        fontSize: '14px',
                        fontWeight: '600',
                        color: '#374151',
                        marginBottom: '6px'
                    }
                }, utils.sanitizeHTML(field.label) + (field.required ? ' <span style="color: #ef4444;">*</span>' : ''));
                fieldContainer.appendChild(label);
            }

            // Create input element
            let inputElement;
            const inputStyles = {
                width: '100%',
                padding: '12px 16px',
                fontSize: '16px',
                border: '2px solid #e5e7eb',
                borderRadius: '8px',
                outline: 'none',
                transition: 'all 0.2s ease',
                fontFamily: this.theme.fontFamily
            };

            switch (field.type) {
                case 'textarea':
                    inputElement = utils.createElement('textarea', {
                        id: field.name,
                        name: field.name,
                        placeholder: field.placeholder || '',
                        required: field.required || false,
                        rows: 4,
                        style: { ...inputStyles, resize: 'vertical' }
                    });
                    break;

                case 'select':
                    inputElement = utils.createElement('select', {
                        id: field.name,
                        name: field.name,
                        required: field.required || false,
                        style: inputStyles
                    });

                    // Add default option
                    const defaultOption = utils.createElement('option', {
                        value: ''
                    }, field.placeholder || 'Select an option');
                    inputElement.appendChild(defaultOption);

                    // Add options
                    if (field.options) {
                        field.options.forEach(option => {
                            const optionElement = utils.createElement('option', {
                                value: option
                            }, utils.sanitizeHTML(option));
                            inputElement.appendChild(optionElement);
                        });
                    }
                    break;

                default:
                    inputElement = utils.createElement('input', {
                        type: field.type || 'text',
                        id: field.name,
                        name: field.name,
                        placeholder: field.placeholder || '',
                        required: field.required || false,
                        style: inputStyles
                    });
            }

            // Add focus/blur event listeners for styling
            inputElement.addEventListener('focus', () => {
                inputElement.style.borderColor = this.theme.primaryColor;
                inputElement.style.boxShadow = `0 0 0 3px ${this.theme.primaryColor}20`;
            });

            inputElement.addEventListener('blur', () => {
                inputElement.style.borderColor = '#e5e7eb';
                inputElement.style.boxShadow = 'none';
            });

            fieldContainer.appendChild(inputElement);
            return fieldContainer;
        }

        createSubmitButton() {
            const button = utils.createElement('button', {
                type: 'submit',
                className: 'tid-form-submit',
                style: {
                    width: '100%',
                    padding: '14px 24px',
                    fontSize: '16px',
                    fontWeight: '600',
                    color: 'white',
                    background: this.theme.gradient,
                    border: 'none',
                    borderRadius: '8px',
                    cursor: 'pointer',
                    transition: 'all 0.2s ease',
                    fontFamily: this.theme.fontFamily
                }
            }, 'Submit');

            // Add hover effects
            button.addEventListener('mouseenter', () => {
                button.style.transform = 'translateY(-2px)';
                button.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.15)';
            });

            button.addEventListener('mouseleave', () => {
                button.style.transform = 'translateY(0)';
                button.style.boxShadow = 'none';
            });

            return button;
        }

        attachEventListeners() {
            const form = document.getElementById(this.formId);
            if (!form) return;

            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleSubmit(form);
            });
        }

        async handleSubmit(form) {
            const submitButton = form.querySelector('.tid-form-submit');
            const originalText = submitButton.textContent;

            try {
                // Show loading state
                submitButton.textContent = 'Submitting...';
                submitButton.disabled = true;
                submitButton.style.opacity = '0.7';

                // Collect form data
                const formData = new FormData(form);
                const data = {};
                for (let [key, value] of formData.entries()) {
                    data[key] = value;
                }

                // Submit to API
                const response = await utils.apiRequest(`/forms/${this.formData.id}/submit`, {
                    method: 'POST',
                    body: JSON.stringify({ data })
                });

                if (response.success) {
                    this.showSuccessMessage(form);
                } else {
                    throw new Error(response.error || 'Submission failed');
                }

            } catch (error) {
                console.error('TiD Forms submission error:', error);
                this.showErrorMessage(form, error.message);
            } finally {
                // Reset button
                submitButton.textContent = originalText;
                submitButton.disabled = false;
                submitButton.style.opacity = '1';
            }
        }

        showSuccessMessage(form) {
            const message = utils.createElement('div', {
                className: 'tid-form-message tid-form-success',
                style: {
                    padding: '16px',
                    marginTop: '16px',
                    backgroundColor: '#dcfce7',
                    border: '1px solid #bbf7d0',
                    borderRadius: '8px',
                    color: '#166534',
                    fontSize: '14px',
                    fontWeight: '500'
                }
            }, '✓ Thank you! Your form has been submitted successfully.');

            form.appendChild(message);

            // Remove message after 5 seconds
            setTimeout(() => {
                if (message.parentNode) {
                    message.parentNode.removeChild(message);
                }
            }, 5000);

            // Reset form
            form.reset();
        }

        showErrorMessage(form, errorText) {
            const message = utils.createElement('div', {
                className: 'tid-form-message tid-form-error',
                style: {
                    padding: '16px',
                    marginTop: '16px',
                    backgroundColor: '#fef2f2',
                    border: '1px solid #fecaca',
                    borderRadius: '8px',
                    color: '#dc2626',
                    fontSize: '14px',
                    fontWeight: '500'
                }
            }, `✗ Error: ${errorText}`);

            form.appendChild(message);

            // Remove message after 5 seconds
            setTimeout(() => {
                if (message.parentNode) {
                    message.parentNode.removeChild(message);
                }
            }, 5000);
        }
    }

    // Main embed function
    TiDForms.embed = async function(options) {
        try {
            // Validate options
            if (!options.formId) {
                throw new Error('formId is required');
            }

            // Set API URL if provided
            if (options.apiUrl) {
                CONFIG.apiUrl = options.apiUrl;
            }

            // Load CSS if not already loaded
            if (!document.querySelector('link[href*="embed-styles.css"]')) {
                await utils.loadCSS(CONFIG.cssUrl);
            }

            // Fetch form data
            const response = await utils.apiRequest(`/forms/${options.formId}`);
            
            if (!response.success) {
                throw new Error(response.error || 'Failed to load form');
            }

            // Find or create container
            let containerId = options.containerId || `tid-form-${options.formId}`;
            let container = document.getElementById(containerId);
            
            if (!container) {
                container = utils.createElement('div', { id: containerId });
                document.body.appendChild(container);
            }

            // Render form
            const renderer = new FormRenderer(
                response.form,
                options.theme || response.form.theme || 'modern',
                containerId
            );

            renderer.render();

        } catch (error) {
            console.error('TiD Forms embed error:', error);
            
            // Show error message in container
            const containerId = options.containerId || `tid-form-${options.formId}`;
            const container = document.getElementById(containerId);
            
            if (container) {
                container.innerHTML = `
                    <div style="
                        padding: 20px;
                        background: #fef2f2;
                        border: 1px solid #fecaca;
                        border-radius: 8px;
                        color: #dc2626;
                        text-align: center;
                        font-family: system-ui, sans-serif;
                    ">
                        <strong>Form Loading Error</strong><br>
                        ${error.message}
                    </div>
                `;
            }
        }
    };

    // Auto-embed forms with data attributes
    TiDForms.autoEmbed = function() {
        const autoEmbedElements = document.querySelectorAll('[data-tid-form-id]');
        
        autoEmbedElements.forEach(element => {
            const formId = element.getAttribute('data-tid-form-id');
            const theme = element.getAttribute('data-tid-theme') || 'modern';
            const apiUrl = element.getAttribute('data-tid-api-url');
            
            TiDForms.embed({
                formId: parseInt(formId),
                theme: theme,
                apiUrl: apiUrl,
                containerId: element.id || `tid-auto-${formId}`
            });
        });
    };

    // Initialize auto-embed when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', TiDForms.autoEmbed);
    } else {
        TiDForms.autoEmbed();
    }

    // Expose utilities for advanced usage
    TiDForms.utils = utils;
    TiDForms.themes = THEMES;
    TiDForms.version = CONFIG.version;

})(window, document);

