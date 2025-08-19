# WPForms Replacement MVP - Simplified Requirements

## MVP Objective

Create a minimal viable WordPress form plugin that can replicate the exact forms currently used on theinnovativedinosaur.com with basic security, simple administration, and WordPress integration.

## Core MVP Features

### 1. Form Creation (Simplified)

**Simple Form Builder Interface**
- **Template-based approach** instead of drag-and-drop
- Pre-built form templates matching your current forms:
  - Newsletter Subscription Template
  - Contributors Registration Template
  - Custom Form Template (basic fields)
- **Field Configuration Panel** - Simple form to add/edit/remove fields
- **Field Types Supported:**
  - Single Line Text
  - Multi-line Text (Textarea)
  - Email (with basic validation)
  - Dropdown/Select
  - Checkboxes (multiple selection)
  - Radio Buttons (single selection)
  - Basic Rich Text Editor (WordPress native editor)
  - GDPR Consent Checkbox

**Form Management**
- Create new forms from templates
- Edit existing forms
- Enable/Disable forms
- Delete forms
- Generate shortcodes for WordPress pages

### 2. WordPress Integration

**Shortcode System**
- Auto-generate shortcode for each form: `[custom_form id="1"]`
- Simple shortcode insertion in posts/pages
- Basic WordPress editor button for shortcode insertion

**Form Display**
- Responsive forms that inherit theme styling
- Basic form validation (required fields, email format)
- Simple success message after submission
- Form submission without page reload (AJAX)

### 3. Data Management (Simple Dashboard)

**Admin Dashboard**
- **Forms List View:**
  - Form name
  - Number of submissions
  - Created date
  - Status (Active/Inactive)
  - Actions (Edit, View Entries, Delete)

**Entry Management:**
- **Grid View of Submissions:**
  - Submission date
  - Form fields as columns
  - Basic search by date range
  - Pagination for large datasets
- **CSV Export:**
  - Export all entries for a form
  - Excel-compatible format
  - Include all form fields and submission dates

### 4. Essential Security (MVP Level)

**Basic Anti-Spam:**
- Simple honeypot field (invisible to users)
- Basic rate limiting (max 5 submissions per IP per hour)
- Input sanitization for all fields
- WordPress nonce verification

**GDPR Minimum Compliance:**
- Consent checkbox field type
- Basic privacy policy link integration
- Data retention notice in admin
- Simple data deletion capability (manual)

**Data Security:**
- WordPress database security (prepared statements)
- Basic input validation and sanitization
- Secure file handling for any uploads

### 5. Database Structure (Simplified)

**Two Main Tables:**
```sql
wp_custom_forms
- id (primary key)
- name (form name)
- fields (JSON structure of form fields)
- status (active/inactive)
- created_date

wp_custom_form_entries
- id (primary key)
- form_id (foreign key)
- entry_data (JSON of submitted data)
- submission_date
- user_ip
```

## Specific Forms to Replicate

### Newsletter Subscription Form
**Fields Required:**
- First Name (text, required)
- Last Name (text, required)
- Email (email, required)
- Country (text)
- Title/Profession/Role (text)
- Interest Selection (checkboxes, required):
  - Beta Readers Community
  - Pre-Order Date Notification
  - Official Publishing Date
  - Next Activities Information
  - None of the Above
- Preference Selection (radio buttons):
  - eBook version
  - Printed version
  - Audio Book version
  - PDF version
- GDPR Consent (checkbox, required)

### Contributors Registration Form
**Fields Required:**
- First Name (text, required)
- Last Name (text, required)
- Title/Profession/Role (text)
- Association/Company/Community Name (text, required)
- Email (email, required)
- LinkedIn Profile/Website URL (text, required)
- Country (text, required)
- Industry (text, required)
- Contribution Type (checkboxes, required):
  - Beta Reader
  - Researchers
  - Thought Leader
  - Experts & Case Studies
  - Academic Institutes & Educators
  - Business Enterprise
  - Community Partners & Affiliates
  - Professional Bodies
- Additional Information (rich text editor)
- GDPR Consent (checkbox, required)

## Technical Implementation (MVP)

### Frontend (Simple)
- **HTML Forms** with basic CSS styling
- **Vanilla JavaScript** for form validation and AJAX submission
- **WordPress theme integration** - inherit existing styles
- **Mobile responsive** using CSS media queries

### Backend (WordPress Plugin)
- **PHP Classes:**
  - Main Plugin Class
  - Form Manager Class
  - Entry Manager Class
  - Admin Interface Class
- **WordPress Hooks:**
  - Admin menu integration
  - Shortcode registration
  - AJAX handlers
- **Database Operations:**
  - Simple CRUD operations
  - WordPress $wpdb for database interactions

### Admin Interface (Simple)
- **WordPress Admin Pages:**
  - Forms list page
  - Form editor page
  - Entries view page
  - Settings page
- **Basic WordPress Admin UI:**
  - Use WordPress admin styles
  - Simple tables for data display
  - Basic forms for configuration

## MVP Development Phases

### Phase 1: Core Foundation (2-3 weeks)
- Plugin structure and activation
- Basic form creation interface
- Simple field types (text, email, textarea)
- Database table creation
- Basic shortcode functionality

### Phase 2: Form Replication (2-3 weeks)
- Add all required field types
- Create templates for existing forms
- Implement form validation
- Basic admin interface for form management

### Phase 3: Data Management (1-2 weeks)
- Entry storage and display
- Admin dashboard for viewing submissions
- CSV export functionality
- Basic search and pagination

### Phase 4: Security & Polish (1-2 weeks)
- Implement security measures
- GDPR compliance features
- Testing and bug fixes
- WordPress compatibility testing

## Success Criteria

**Functional Requirements:**
- ✅ Can create forms identical to current website forms
- ✅ Forms work on WordPress pages via shortcode
- ✅ All form submissions are captured and stored
- ✅ Admin can view and export form data to CSV
- ✅ Basic security prevents spam and protects data
- ✅ GDPR consent functionality works

**Technical Requirements:**
- ✅ Plugin installs and activates without errors
- ✅ Compatible with current WordPress theme
- ✅ Forms are mobile responsive
- ✅ No conflicts with existing plugins
- ✅ Database operations are secure and efficient

## What's NOT in MVP

- Drag-and-drop form builder
- Email notifications
- Payment processing
- Third-party integrations
- Advanced conditional logic
- API endpoints
- Advanced analytics
- Multi-page forms
- File uploads
- Advanced security features
- User registration integration

## Estimated Timeline: 6-10 weeks

This MVP focuses on delivering exactly what you need to replace WPForms for your current use case while keeping complexity minimal and ensuring reliability.

