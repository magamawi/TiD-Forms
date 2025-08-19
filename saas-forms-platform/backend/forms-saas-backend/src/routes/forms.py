from flask import Blueprint, request, jsonify
from src.models.form import db, Form, FormEntry, FormTemplate
from datetime import datetime
import uuid
import csv
import io
from flask import make_response

forms_bp = Blueprint('forms', __name__)

# Forms CRUD Operations
@forms_bp.route('/forms', methods=['GET'])
def get_forms():
    """Get all forms"""
    try:
        forms = Form.query.filter_by(is_active=True).order_by(Form.created_at.desc()).all()
        return jsonify({
            'success': True,
            'forms': [form.to_dict() for form in forms]
        })
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@forms_bp.route('/forms', methods=['POST'])
def create_form():
    """Create a new form"""
    try:
        data = request.get_json()
        
        form = Form(
            name=data.get('name', 'Untitled Form'),
            description=data.get('description', ''),
            theme=data.get('theme', 'modern')
        )
        
        # Set fields and settings
        if 'fields' in data:
            form.set_fields(data['fields'])
        if 'settings' in data:
            form.set_settings(data['settings'])
        
        db.session.add(form)
        db.session.commit()
        
        # Generate embed codes
        generate_embed_codes(form)
        db.session.commit()
        
        return jsonify({
            'success': True,
            'form': form.to_dict()
        }), 201
    except Exception as e:
        db.session.rollback()
        return jsonify({'success': False, 'error': str(e)}), 500

@forms_bp.route('/forms/<int:form_id>', methods=['GET'])
def get_form(form_id):
    """Get a specific form"""
    try:
        form = Form.query.get_or_404(form_id)
        return jsonify({
            'success': True,
            'form': form.to_dict()
        })
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@forms_bp.route('/forms/<int:form_id>', methods=['PUT'])
def update_form(form_id):
    """Update a form"""
    try:
        form = Form.query.get_or_404(form_id)
        data = request.get_json()
        
        # Update basic fields
        if 'name' in data:
            form.name = data['name']
        if 'description' in data:
            form.description = data['description']
        if 'theme' in data:
            form.theme = data['theme']
        if 'is_active' in data:
            form.is_active = data['is_active']
        
        # Update fields and settings
        if 'fields' in data:
            form.set_fields(data['fields'])
        if 'settings' in data:
            form.set_settings(data['settings'])
        
        form.updated_at = datetime.utcnow()
        
        # Regenerate embed codes
        generate_embed_codes(form)
        
        db.session.commit()
        
        return jsonify({
            'success': True,
            'form': form.to_dict()
        })
    except Exception as e:
        db.session.rollback()
        return jsonify({'success': False, 'error': str(e)}), 500

@forms_bp.route('/forms/<int:form_id>', methods=['DELETE'])
def delete_form(form_id):
    """Delete a form (soft delete)"""
    try:
        form = Form.query.get_or_404(form_id)
        form.is_active = False
        db.session.commit()
        
        return jsonify({
            'success': True,
            'message': 'Form deleted successfully'
        })
    except Exception as e:
        db.session.rollback()
        return jsonify({'success': False, 'error': str(e)}), 500

# Form Submissions
@forms_bp.route('/forms/<int:form_id>/submit', methods=['POST'])
def submit_form(form_id):
    """Submit form data"""
    try:
        form = Form.query.get_or_404(form_id)
        
        if not form.is_active:
            return jsonify({'success': False, 'error': 'Form is not active'}), 400
        
        data = request.get_json()
        
        # Create form entry
        entry = FormEntry(
            form_id=form_id,
            ip_address=request.remote_addr,
            user_agent=request.headers.get('User-Agent', '')
        )
        entry.set_data(data.get('data', {}))
        
        db.session.add(entry)
        db.session.commit()
        
        return jsonify({
            'success': True,
            'message': 'Form submitted successfully',
            'entry_id': entry.id
        }), 201
    except Exception as e:
        db.session.rollback()
        return jsonify({'success': False, 'error': str(e)}), 500

# Form Entries Management
@forms_bp.route('/forms/<int:form_id>/entries', methods=['GET'])
def get_form_entries(form_id):
    """Get all entries for a form"""
    try:
        form = Form.query.get_or_404(form_id)
        entries = FormEntry.query.filter_by(form_id=form_id).order_by(FormEntry.submitted_at.desc()).all()
        
        return jsonify({
            'success': True,
            'entries': [entry.to_dict() for entry in entries],
            'total': len(entries)
        })
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@forms_bp.route('/forms/<int:form_id>/entries/export', methods=['GET'])
def export_form_entries(form_id):
    """Export form entries as CSV"""
    try:
        form = Form.query.get_or_404(form_id)
        entries = FormEntry.query.filter_by(form_id=form_id).order_by(FormEntry.submitted_at.desc()).all()
        
        # Create CSV
        output = io.StringIO()
        
        if entries:
            # Get all field names from the first entry
            first_entry_data = entries[0].get_data()
            fieldnames = ['ID', 'Submitted At', 'IP Address'] + list(first_entry_data.keys())
            
            writer = csv.DictWriter(output, fieldnames=fieldnames)
            writer.writeheader()
            
            for entry in entries:
                row = {
                    'ID': entry.id,
                    'Submitted At': entry.submitted_at.strftime('%Y-%m-%d %H:%M:%S') if entry.submitted_at else '',
                    'IP Address': entry.ip_address or ''
                }
                row.update(entry.get_data())
                writer.writerow(row)
        
        # Create response
        response = make_response(output.getvalue())
        response.headers['Content-Type'] = 'text/csv'
        response.headers['Content-Disposition'] = f'attachment; filename=form_{form_id}_entries.csv'
        
        return response
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

# Form Templates
@forms_bp.route('/templates', methods=['GET'])
def get_templates():
    """Get all form templates"""
    try:
        templates = FormTemplate.query.order_by(FormTemplate.is_featured.desc(), FormTemplate.created_at.desc()).all()
        return jsonify({
            'success': True,
            'templates': [template.to_dict() for template in templates]
        })
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@forms_bp.route('/forms/from-template/<int:template_id>', methods=['POST'])
def create_form_from_template(template_id):
    """Create a form from a template"""
    try:
        template = FormTemplate.query.get_or_404(template_id)
        data = request.get_json()
        
        form = Form(
            name=data.get('name', template.name),
            description=data.get('description', template.description),
            theme=data.get('theme', 'modern'),
            fields=template.fields,
            settings=template.settings
        )
        
        db.session.add(form)
        db.session.commit()
        
        # Generate embed codes
        generate_embed_codes(form)
        db.session.commit()
        
        return jsonify({
            'success': True,
            'form': form.to_dict()
        }), 201
    except Exception as e:
        db.session.rollback()
        return jsonify({'success': False, 'error': str(e)}), 500

# Embed and iframe codes
@forms_bp.route('/forms/<int:form_id>/embed', methods=['GET'])
def get_embed_codes(form_id):
    """Get embed and iframe codes for a form"""
    try:
        form = Form.query.get_or_404(form_id)
        
        return jsonify({
            'success': True,
            'embed_code': form.embed_code,
            'iframe_code': form.iframe_code,
            'form_url': f'/embed/{form_id}'
        })
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

# Public form display for embedding
@forms_bp.route('/embed/<int:form_id>', methods=['GET'])
def embed_form(form_id):
    """Public endpoint for embedded forms"""
    try:
        form = Form.query.get_or_404(form_id)
        
        if not form.is_active:
            return "Form not found or inactive", 404
        
        # Return HTML for the embedded form
        html = generate_embed_html(form)
        return html, 200, {'Content-Type': 'text/html'}
    except Exception as e:
        return f"Error loading form: {str(e)}", 500

def generate_embed_codes(form):
    """Generate embed and iframe codes for a form"""
    base_url = request.host_url.rstrip('/')
    
    # Generate embed code (JavaScript)
    embed_code = f'''<script>
(function() {{
    var script = document.createElement('script');
    script.src = '{base_url}/static/embed.js';
    script.onload = function() {{
        TiDForms.embed({{
            formId: {form.id},
            apiUrl: '{base_url}/api',
            theme: '{form.theme}'
        }});
    }};
    document.head.appendChild(script);
}})();
</script>
<div id="tid-form-{form.id}"></div>'''
    
    # Generate iframe code
    iframe_code = f'''<iframe 
    src="{base_url}/embed/{form.id}" 
    width="100%" 
    height="600" 
    frameborder="0" 
    style="border: none; border-radius: 8px;">
</iframe>'''
    
    form.embed_code = embed_code
    form.iframe_code = iframe_code

def generate_embed_html(form):
    """Generate HTML for embedded form"""
    fields = form.get_fields()
    settings = form.get_settings()
    
    # Generate form HTML based on theme and fields
    html = f'''<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{form.name}</title>
    <link rel="stylesheet" href="/static/embed-styles.css">
</head>
<body>
    <div class="tid-form-container theme-{form.theme}">
        <form id="tid-form-{form.id}" class="tid-form" data-form-id="{form.id}">
            <div class="form-header">
                <h2 class="form-title">{form.name}</h2>
                {f'<p class="form-description">{form.description}</p>' if form.description else ''}
            </div>
            <div class="form-fields">
                {generate_form_fields_html(fields)}
            </div>
            <div class="form-footer">
                <button type="submit" class="submit-btn">Submit</button>
            </div>
        </form>
    </div>
    <script src="/static/embed-form.js"></script>
</body>
</html>'''
    
    return html

def generate_form_fields_html(fields):
    """Generate HTML for form fields"""
    html = ""
    
    for field in fields:
        field_type = field.get('type', 'text')
        field_name = field.get('name', '')
        field_label = field.get('label', '')
        field_required = field.get('required', False)
        field_placeholder = field.get('placeholder', '')
        
        html += f'''
        <div class="form-field">
            <label for="{field_name}" class="field-label">
                {field_label}
                {' <span class="required">*</span>' if field_required else ''}
            </label>
        '''
        
        if field_type == 'text' or field_type == 'email':
            html += f'''
            <input 
                type="{field_type}" 
                id="{field_name}" 
                name="{field_name}" 
                placeholder="{field_placeholder}"
                {'required' if field_required else ''}
                class="field-input"
            >
            '''
        elif field_type == 'textarea':
            html += f'''
            <textarea 
                id="{field_name}" 
                name="{field_name}" 
                placeholder="{field_placeholder}"
                {'required' if field_required else ''}
                class="field-textarea"
                rows="4"
            ></textarea>
            '''
        elif field_type == 'select':
            options = field.get('options', [])
            html += f'<select id="{field_name}" name="{field_name}" {"required" if field_required else ""} class="field-select">'
            html += f'<option value="">{field_placeholder or "Select an option"}</option>'
            for option in options:
                html += f'<option value="{option}">{option}</option>'
            html += '</select>'
        
        html += '</div>'
    
    return html

