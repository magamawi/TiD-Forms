from flask import Flask, render_template, request, jsonify, send_from_directory, make_response
from flask_cors import CORS
import sqlite3
import json
from datetime import datetime
import csv
import io
import os

app = Flask(__name__)
CORS(app)

# Database setup
def init_db():
    conn = sqlite3.connect('forms.db')
    cursor = conn.cursor()
    
    # Forms table
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS forms (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            theme TEXT DEFAULT 'modern',
            fields TEXT DEFAULT '[]',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ''')
    
    # Form entries table
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS form_entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            form_id INTEGER,
            data TEXT,
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (form_id) REFERENCES forms (id)
        )
    ''')
    
    conn.commit()
    conn.close()

# Initialize database
init_db()

# Conversation state storage (in production, use Redis or database)
conversation_states = {}

class ConversationManager:
    def __init__(self):
        self.states = {}
    
    def start_conversation(self, session_id):
        self.states[session_id] = {
            'step': 'form_name',
            'form_data': {},
            'fields': [],
            'current_field': None,
            'messages': []
        }
        return self.get_welcome_message()
    
    def get_welcome_message(self):
        return {
            'message': "👋 Hi! I'm your TiD Forms assistant. I'll help you create beautiful forms through a simple conversation. What would you like to name your form?",
            'type': 'bot'
        }
    
    def process_message(self, session_id, user_message):
        if session_id not in self.states:
            self.start_conversation(session_id)
        
        state = self.states[session_id]
        state['messages'].append({'message': user_message, 'type': 'user'})
        
        response = self.handle_conversation_step(state, user_message)
        state['messages'].append(response)
        
        return response
    
    def handle_conversation_step(self, state, user_message):
        step = state['step']
        
        if step == 'form_name':
            state['form_data']['name'] = user_message.strip()
            state['step'] = 'form_description'
            return {
                'message': f'Perfect! "{state["form_data"]["name"]}" is a great name. Now, what\'s the purpose of this form? (e.g., "Contact form for customer inquiries", "Newsletter signup", etc.)',
                'type': 'bot'
            }
        
        elif step == 'form_description':
            state['form_data']['description'] = user_message.strip()
            state['step'] = 'collect_fields'
            return {
                'message': 'Great! Now, what data fields do you want to collect? (For example: Name, Email, Phone, Address, Company, Message, etc.)',
                'type': 'bot'
            }
        
        elif step == 'collect_fields':
            fields_text = user_message.strip()
            field_names = [field.strip() for field in fields_text.split(',')]
            
            # Process each field and detect types
            processed_fields = []
            unclear_fields = []
            
            for field_name in field_names:
                field_type = self.detect_field_type(field_name.lower())
                if field_type == 'unknown':
                    unclear_fields.append(field_name)
                else:
                    processed_fields.append({
                        'name': field_name,
                        'type': field_type,
                        'label': field_name,
                        'required': True,
                        'placeholder': self.get_placeholder(field_name, field_type)
                    })
            
            state['fields'] = processed_fields
            
            if unclear_fields:
                state['step'] = 'clarify_field_type'
                state['current_field'] = unclear_fields[0]
                state['unclear_fields'] = unclear_fields[1:]
                return {
                    'message': f'Great! I understand these fields: {", ".join([f["name"] for f in processed_fields])}.\n\nI need help with "{unclear_fields[0]}" - what type of field is this?\n1. Text (free input)\n2. Number\n3. Date\n4. Yes/No (checkbox)\n5. Dropdown (multiple options)\n6. Long text (textarea)\n\nJust reply with the number.',
                    'type': 'bot'
                }
            else:
                state['step'] = 'gdpr_question'
                return {
                    'message': f'Perfect! I\'ve set up these fields: {", ".join([f["name"] for f in processed_fields])}.\n\nFor GDPR compliance, should I add a consent checkbox? (yes/no)',
                    'type': 'bot'
                }
        
        elif step == 'clarify_field_type':
            try:
                choice = int(user_message.strip())
                field_types = {
                    1: 'text',
                    2: 'number',
                    3: 'date',
                    4: 'checkbox',
                    5: 'select',
                    6: 'textarea'
                }
                
                if choice in field_types:
                    field_name = state['current_field']
                    field_type = field_types[choice]
                    
                    if field_type == 'select':
                        state['step'] = 'collect_options'
                        return {
                            'message': f'What options should "{field_name}" have? (separate with commas, e.g., Small, Medium, Large)',
                            'type': 'bot'
                        }
                    elif field_type == 'checkbox':
                        state['step'] = 'checkbox_text'
                        return {
                            'message': f'What should the checkbox text say? (e.g., "I agree to the terms and conditions")',
                            'type': 'bot'
                        }
                    else:
                        # Add the field and continue
                        state['fields'].append({
                            'name': field_name,
                            'type': field_type,
                            'label': field_name,
                            'required': True,
                            'placeholder': self.get_placeholder(field_name, field_type)
                        })
                        
                        return self.continue_field_clarification(state)
                else:
                    return {
                        'message': 'Please enter a number between 1 and 6.',
                        'type': 'bot'
                    }
            except ValueError:
                return {
                    'message': 'Please enter a number between 1 and 6.',
                    'type': 'bot'
                }
        
        elif step == 'collect_options':
            options = [opt.strip() for opt in user_message.split(',')]
            field_name = state['current_field']
            
            state['fields'].append({
                'name': field_name,
                'type': 'select',
                'label': field_name,
                'required': True,
                'options': options
            })
            
            return self.continue_field_clarification(state)
        
        elif step == 'checkbox_text':
            field_name = state['current_field']
            
            state['fields'].append({
                'name': field_name,
                'type': 'checkbox',
                'label': field_name,
                'required': True,
                'text': user_message.strip()
            })
            
            return self.continue_field_clarification(state)
        
        elif step == 'gdpr_question':
            response = user_message.lower().strip()
            if response in ['yes', 'y', 'true', '1']:
                state['form_data']['gdpr'] = True
                state['fields'].append({
                    'name': 'gdpr_consent',
                    'type': 'checkbox',
                    'label': 'Privacy Consent',
                    'required': True,
                    'text': 'I agree to the processing of my personal data in accordance with the privacy policy'
                })
            else:
                state['form_data']['gdpr'] = False
            
            state['step'] = 'theme_selection'
            return {
                'message': f'{"Great! GDPR consent will be included." if state["form_data"]["gdpr"] else "No problem, no consent checkbox will be added."}\n\nNow choose your form theme:\n1. 🌟 Modern (Purple-Blue Gradients)\n2. 💼 Professional (Blue Corporate)\n3. ✨ Elegant (Luxury Purple)\n4. 🎨 Creative (Colorful & Vibrant)\n5. ⚪ Minimal (Clean & Simple)\n\nJust enter the number of your choice.',
                'type': 'bot'
            }
        
        elif step == 'theme_selection':
            try:
                choice = int(user_message.strip())
                themes = {
                    1: ('modern', 'Modern'),
                    2: ('professional', 'Professional'),
                    3: ('elegant', 'Elegant'),
                    4: ('creative', 'Creative'),
                    5: ('minimal', 'Minimal')
                }
                
                if choice in themes:
                    theme_id, theme_name = themes[choice]
                    state['form_data']['theme'] = theme_id
                    state['step'] = 'confirmation'
                    
                    # Generate configuration summary
                    fields_summary = []
                    for field in state['fields']:
                        if field['type'] == 'select':
                            fields_summary.append(f"{field['label']} ({'/'.join(field['options'])})")
                        elif field['type'] == 'checkbox':
                            fields_summary.append(f"{field['label']} (checkbox)")
                        else:
                            fields_summary.append(f"{field['label']} ({field['type']})")
                    
                    return {
                        'message': f'Perfect! Here\'s your form configuration:\n\n📝 **Form Name:** {state["form_data"]["name"]}\n📋 **Fields:** {", ".join(fields_summary)}\n{"✅ **GDPR:** Consent checkbox included" if state["form_data"]["gdpr"] else ""}\n🎨 **Theme:** {theme_name}\n\nDoes this look correct? Reply "yes" to create your form, or "no" to make changes.',
                        'type': 'bot'
                    }
                else:
                    return {
                        'message': 'Please enter a number between 1 and 5.',
                        'type': 'bot'
                    }
            except ValueError:
                return {
                    'message': 'Please enter a number between 1 and 5.',
                    'type': 'bot'
                }
        
        elif step == 'confirmation':
            response = user_message.lower().strip()
            if response in ['yes', 'y', 'confirm', 'create']:
                # Create the form
                form_id = self.create_form_from_conversation(state)
                state['step'] = 'completed'
                
                return {
                    'message': f'🎉 **Form Created Successfully!**\n\nYour "{state["form_data"]["name"]}" form is ready! Here are your integration options:\n\n**📋 Form ID:** {form_id}\n**🔗 Direct Link:** https://forms.tid.com/form/{form_id}\n**👁️ Preview:** https://forms.tid.com/form/{form_id}/preview',
                    'type': 'bot',
                    'form_id': form_id,
                    'embed_code': f'<iframe src="https://forms.tid.com/embed/{form_id}" width="100%" height="600" frameborder="0"></iframe>'
                }
            else:
                state['step'] = 'form_name'
                return {
                    'message': 'No problem! Let\'s start over. What would you like to name your form?',
                    'type': 'bot'
                }
        
        return {
            'message': 'I didn\'t understand that. Could you please try again?',
            'type': 'bot'
        }
    
    def continue_field_clarification(self, state):
        if state.get('unclear_fields') and len(state['unclear_fields']) > 0:
            next_field = state['unclear_fields'].pop(0)
            state['current_field'] = next_field
            return {
                'message': f'Great! Now I need help with "{next_field}" - what type of field is this?\n1. Text (free input)\n2. Number\n3. Date\n4. Yes/No (checkbox)\n5. Dropdown (multiple options)\n6. Long text (textarea)\n\nJust reply with the number.',
                'type': 'bot'
            }
        else:
            state['step'] = 'gdpr_question'
            return {
                'message': f'Perfect! I\'ve set up all your fields: {", ".join([f["name"] for f in state["fields"]])}.\n\nFor GDPR compliance, should I add a consent checkbox? (yes/no)',
                'type': 'bot'
            }
    
    def detect_field_type(self, field_name):
        field_name = field_name.lower()
        
        if 'email' in field_name or 'e-mail' in field_name:
            return 'email'
        elif 'phone' in field_name or 'telephone' in field_name or 'mobile' in field_name:
            return 'tel'
        elif 'message' in field_name or 'comment' in field_name or 'description' in field_name or 'bio' in field_name:
            return 'textarea'
        elif 'name' in field_name or 'title' in field_name or 'subject' in field_name:
            return 'text'
        elif 'age' in field_name or 'number' in field_name or 'count' in field_name:
            return 'number'
        elif 'date' in field_name or 'birth' in field_name or 'dob' in field_name:
            return 'date'
        elif 'agree' in field_name or 'consent' in field_name or 'terms' in field_name:
            return 'checkbox'
        elif 'country' in field_name or 'state' in field_name or 'size' in field_name or 'type' in field_name or 'category' in field_name:
            return 'select'
        else:
            return 'unknown'
    
    def get_placeholder(self, field_name, field_type):
        if field_type == 'email':
            return 'your@email.com'
        elif field_type == 'tel':
            return '+1 (555) 123-4567'
        elif field_type == 'textarea':
            return f'Enter your {field_name.lower()}...'
        elif field_type == 'number':
            return 'Enter a number'
        elif field_type == 'date':
            return 'YYYY-MM-DD'
        else:
            return f'Enter your {field_name.lower()}'
    
    def create_form_from_conversation(self, state):
        conn = sqlite3.connect('forms.db')
        cursor = conn.cursor()
        
        cursor.execute('''
            INSERT INTO forms (name, description, theme, fields) 
            VALUES (?, ?, ?, ?)
        ''', (
            state['form_data']['name'],
            state['form_data']['description'],
            state['form_data']['theme'],
            json.dumps(state['fields'])
        ))
        
        form_id = cursor.lastrowid
        conn.commit()
        conn.close()
        
        return form_id

# Initialize conversation manager
conversation_manager = ConversationManager()

@app.route('/')
def index():
    return send_from_directory('static', 'index.html')

@app.route('/api/conversation/start', methods=['POST'])
def start_conversation():
    data = request.json
    session_id = data.get('session_id', 'default')
    
    welcome_message = conversation_manager.start_conversation(session_id)
    return jsonify({
        'session_id': session_id,
        'message': welcome_message
    })

@app.route('/api/conversation/message', methods=['POST'])
def send_message():
    data = request.json
    session_id = data.get('session_id', 'default')
    user_message = data.get('message', '')
    
    response = conversation_manager.process_message(session_id, user_message)
    return jsonify({
        'session_id': session_id,
        'response': response
    })

@app.route('/api/conversation/history/<session_id>')
def get_conversation_history(session_id):
    if session_id in conversation_manager.states:
        return jsonify({
            'messages': conversation_manager.states[session_id]['messages']
        })
    return jsonify({'messages': []})

@app.route('/api/forms', methods=['GET'])
def get_forms():
    conn = sqlite3.connect('forms.db')
    cursor = conn.cursor()
    cursor.execute('''
        SELECT f.*, COUNT(fe.id) as entry_count 
        FROM forms f 
        LEFT JOIN form_entries fe ON f.id = fe.form_id 
        GROUP BY f.id
        ORDER BY f.created_at DESC
    ''')
    forms = []
    for row in cursor.fetchall():
        forms.append({
            'id': row[0],
            'name': row[1],
            'description': row[2],
            'theme': row[3],
            'fields': json.loads(row[4]) if row[4] else [],
            'created_at': row[5],
            'entry_count': row[6] or 0
        })
    conn.close()
    return jsonify(forms)

@app.route('/api/forms', methods=['POST'])
def create_form():
    data = request.json
    conn = sqlite3.connect('forms.db')
    cursor = conn.cursor()
    
    # Default fields for new forms
    default_fields = [
        {'type': 'text', 'label': 'Name', 'required': True, 'placeholder': 'Your full name'},
        {'type': 'email', 'label': 'Email', 'required': True, 'placeholder': 'your@email.com'},
        {'type': 'textarea', 'label': 'Message', 'required': True, 'placeholder': 'Your message...'}
    ]
    
    cursor.execute('''
        INSERT INTO forms (name, description, theme, fields) 
        VALUES (?, ?, ?, ?)
    ''', (data['name'], data.get('description', ''), data.get('theme', 'modern'), json.dumps(default_fields)))
    
    form_id = cursor.lastrowid
    conn.commit()
    conn.close()
    
    return jsonify({'id': form_id, 'message': 'Form created successfully'})

@app.route('/api/templates')
def get_templates():
    templates = [
        {
            'id': 'newsletter',
            'name': 'Newsletter Signup',
            'description': 'Collect email subscribers with name and preferences',
            'fields': [
                {'type': 'text', 'label': 'Name', 'required': True, 'placeholder': 'Your full name'},
                {'type': 'email', 'label': 'Email', 'required': True, 'placeholder': 'your@email.com'},
                {'type': 'select', 'label': 'Country', 'required': False, 'options': ['United States', 'Canada', 'United Kingdom', 'Australia', 'Other']},
                {'type': 'checkbox', 'label': 'Newsletter Consent', 'required': True, 'text': 'I agree to receive newsletters'}
            ]
        },
        {
            'id': 'contact',
            'name': 'Contact Form',
            'description': 'General contact form with message field',
            'fields': [
                {'type': 'text', 'label': 'Name', 'required': True, 'placeholder': 'Your full name'},
                {'type': 'email', 'label': 'Email', 'required': True, 'placeholder': 'your@email.com'},
                {'type': 'text', 'label': 'Subject', 'required': True, 'placeholder': 'Message subject'},
                {'type': 'textarea', 'label': 'Message', 'required': True, 'placeholder': 'Your message...'}
            ]
        },
        {
            'id': 'feedback',
            'name': 'Feedback Form',
            'description': 'Collect user feedback and ratings',
            'fields': [
                {'type': 'text', 'label': 'Name', 'required': False, 'placeholder': 'Your name (optional)'},
                {'type': 'email', 'label': 'Email', 'required': False, 'placeholder': 'your@email.com (optional)'},
                {'type': 'select', 'label': 'Rating', 'required': True, 'options': ['Excellent', 'Good', 'Average', 'Poor']},
                {'type': 'textarea', 'label': 'Comments', 'required': True, 'placeholder': 'Your feedback...'}
            ]
        }
    ]
    return jsonify(templates)

@app.route('/api/templates/<template_id>/create', methods=['POST'])
def create_from_template(template_id):
    data = request.json
    templates = {
        'newsletter': {
            'fields': [
                {'type': 'text', 'label': 'Name', 'required': True, 'placeholder': 'Your full name'},
                {'type': 'email', 'label': 'Email', 'required': True, 'placeholder': 'your@email.com'},
                {'type': 'select', 'label': 'Country', 'required': False, 'options': ['United States', 'Canada', 'United Kingdom', 'Australia', 'Other']},
                {'type': 'checkbox', 'label': 'Newsletter Consent', 'required': True, 'text': 'I agree to receive newsletters'}
            ]
        },
        'contact': {
            'fields': [
                {'type': 'text', 'label': 'Name', 'required': True, 'placeholder': 'Your full name'},
                {'type': 'email', 'label': 'Email', 'required': True, 'placeholder': 'your@email.com'},
                {'type': 'text', 'label': 'Subject', 'required': True, 'placeholder': 'Message subject'},
                {'type': 'textarea', 'label': 'Message', 'required': True, 'placeholder': 'Your message...'}
            ]
        },
        'feedback': {
            'fields': [
                {'type': 'text', 'label': 'Name', 'required': False, 'placeholder': 'Your name (optional)'},
                {'type': 'email', 'label': 'Email', 'required': False, 'placeholder': 'your@email.com (optional)'},
                {'type': 'select', 'label': 'Rating', 'required': True, 'options': ['Excellent', 'Good', 'Average', 'Poor']},
                {'type': 'textarea', 'label': 'Comments', 'required': True, 'placeholder': 'Your feedback...'}
            ]
        }
    }
    
    if template_id not in templates:
        return jsonify({'error': 'Template not found'}), 404
    
    conn = sqlite3.connect('forms.db')
    cursor = conn.cursor()
    
    cursor.execute('''
        INSERT INTO forms (name, description, theme, fields) 
        VALUES (?, ?, ?, ?)
    ''', (data['name'], f'Created from {template_id} template', 'modern', json.dumps(templates[template_id]['fields'])))
    
    form_id = cursor.lastrowid
    conn.commit()
    conn.close()
    
    return jsonify({'id': form_id, 'message': 'Form created from template successfully'})

# Theme styles
THEME_STYLES = {
    'modern': {
        'background': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'card_bg': 'rgba(255, 255, 255, 0.95)',
        'primary_color': '#667eea',
        'text_color': '#333333'
    },
    'professional': {
        'background': 'linear-gradient(135deg, #1e3c72 0%, #2a5298 100%)',
        'card_bg': 'rgba(255, 255, 255, 0.95)',
        'primary_color': '#2a5298',
        'text_color': '#333333'
    },
    'elegant': {
        'background': 'linear-gradient(135deg, #8360c3 0%, #2ebf91 100%)',
        'card_bg': 'rgba(255, 255, 255, 0.95)',
        'primary_color': '#8360c3',
        'text_color': '#333333'
    },
    'creative': {
        'background': 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 50%, #fecfef 100%)',
        'card_bg': 'rgba(255, 255, 255, 0.95)',
        'primary_color': '#ff9a9e',
        'text_color': '#333333'
    },
    'minimal': {
        'background': 'linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%)',
        'card_bg': 'rgba(255, 255, 255, 0.95)',
        'primary_color': '#6c757d',
        'text_color': '#333333'
    }
}

@app.route('/form/<int:form_id>/preview')
def preview_form(form_id):
    try:
        conn = sqlite3.connect('forms.db')
        cursor = conn.cursor()
        cursor.execute('SELECT * FROM forms WHERE id = ?', (form_id,))
        form = cursor.fetchone()
        conn.close()
        
        if not form:
            return "Form not found", 404
        
        form_data = {
            'id': form[0],
            'name': form[1],
            'description': form[2],
            'theme': form[3],
            'fields': json.loads(form[4]) if form[4] else []
        }
        
        theme_style = THEME_STYLES.get(form_data['theme'], THEME_STYLES['modern'])
        
        # Generate HTML for form preview
        html = f'''
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{form_data['name']} - Preview</title>
    <style>
        * {{
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }}
        
        body {{
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: {theme_style['background']};
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }}
        
        .form-container {{
            background: {theme_style['card_bg']};
            border-radius: 16px;
            padding: 2rem;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }}
        
        .form-header {{
            text-align: center;
            margin-bottom: 2rem;
        }}
        
        .form-title {{
            font-size: 1.75rem;
            font-weight: 700;
            color: {theme_style['text_color']};
            margin-bottom: 0.5rem;
        }}
        
        .form-description {{
            color: #666;
            font-size: 1rem;
        }}
        
        .form-group {{
            margin-bottom: 1.5rem;
        }}
        
        .form-label {{
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: {theme_style['text_color']};
        }}
        
        .form-input,
        .form-textarea,
        .form-select {{
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s ease;
        }}
        
        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {{
            outline: none;
            border-color: {theme_style['primary_color']};
        }}
        
        .form-textarea {{
            resize: vertical;
            min-height: 100px;
        }}
        
        .form-checkbox {{
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }}
        
        .form-checkbox input {{
            width: auto;
        }}
        
        .form-submit {{
            width: 100%;
            background: {theme_style['primary_color']};
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }}
        
        .form-submit:hover {{
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }}
        
        .required {{
            color: #ef4444;
        }}
        
        .preview-badge {{
            position: fixed;
            top: 1rem;
            right: 1rem;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
        }}
    </style>
</head>
<body>
    <div class="preview-badge">👁️ Preview Mode</div>
    <div class="form-container">
        <div class="form-header">
            <h1 class="form-title">{form_data['name']}</h1>
            {f'<p class="form-description">{form_data["description"]}</p>' if form_data['description'] else ''}
        </div>
        
        <form id="preview-form">
        '''
        
        # Generate form fields
        for field in form_data['fields']:
            required_mark = '<span class="required">*</span>' if field.get('required') else ''
            
            html += f'<div class="form-group">'
            html += f'<label class="form-label">{field["label"]}{required_mark}</label>'
            
            if field['type'] == 'text' or field['type'] == 'email':
                html += f'<input type="{field["type"]}" class="form-input" placeholder="{field.get("placeholder", "")}" {"required" if field.get("required") else ""}>'
            elif field['type'] == 'textarea':
                html += f'<textarea class="form-textarea" placeholder="{field.get("placeholder", "")}" {"required" if field.get("required") else ""}></textarea>'
            elif field['type'] == 'select':
                html += f'<select class="form-select" {"required" if field.get("required") else ""}>'
                html += f'<option value="">Choose an option</option>'
                for option in field.get('options', []):
                    html += f'<option value="{option}">{option}</option>'
                html += '</select>'
            elif field['type'] == 'checkbox':
                html += f'<div class="form-checkbox">'
                html += f'<input type="checkbox" {"required" if field.get("required") else ""}>'
                html += f'<span>{field.get("text", field["label"])}</span>'
                html += '</div>'
            
            html += '</div>'
        
        html += '''
            <button type="submit" class="form-submit">Submit Form</button>
        </form>
    </div>
    
    <script>
        document.getElementById('preview-form').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('This is a preview! Form submission is disabled in preview mode.');
        });
    </script>
</body>
</html>
        '''
        
        return html
        
    except Exception as e:
        return f"Error loading form preview: {str(e)}", 500

@app.route('/form/<int:form_id>/embed')
def embed_form(form_id):
    try:
        conn = sqlite3.connect('forms.db')
        cursor = conn.cursor()
        cursor.execute('SELECT * FROM forms WHERE id = ?', (form_id,))
        form = cursor.fetchone()
        conn.close()
        
        if not form:
            return "Form not found", 404
        
        form_data = {
            'id': form[0],
            'name': form[1],
            'description': form[2],
            'theme': form[3],
            'fields': json.loads(form[4]) if form[4] else []
        }
        
        theme_style = THEME_STYLES.get(form_data['theme'], THEME_STYLES['modern'])
        
        # Generate HTML for embedded form (similar to preview but with working submission)
        html = f'''
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{form_data['name']}</title>
    <style>
        * {{
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }}
        
        body {{
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: transparent;
            padding: 1rem;
        }}
        
        .form-container {{
            background: {theme_style['card_bg']};
            border-radius: 16px;
            padding: 2rem;
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }}
        
        .form-header {{
            text-align: center;
            margin-bottom: 2rem;
        }}
        
        .form-title {{
            font-size: 1.75rem;
            font-weight: 700;
            color: {theme_style['text_color']};
            margin-bottom: 0.5rem;
        }}
        
        .form-description {{
            color: #666;
            font-size: 1rem;
        }}
        
        .form-group {{
            margin-bottom: 1.5rem;
        }}
        
        .form-label {{
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: {theme_style['text_color']};
        }}
        
        .form-input,
        .form-textarea,
        .form-select {{
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s ease;
        }}
        
        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {{
            outline: none;
            border-color: {theme_style['primary_color']};
        }}
        
        .form-textarea {{
            resize: vertical;
            min-height: 100px;
        }}
        
        .form-checkbox {{
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }}
        
        .form-checkbox input {{
            width: auto;
        }}
        
        .form-submit {{
            width: 100%;
            background: {theme_style['primary_color']};
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }}
        
        .form-submit:hover {{
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }}
        
        .required {{
            color: #ef4444;
        }}
        
        .success-message {{
            background: #10b981;
            color: white;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 1rem;
            display: none;
        }}
        
        .error-message {{
            background: #ef4444;
            color: white;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 1rem;
            display: none;
        }}
    </style>
</head>
<body>
    <div class="form-container">
        <div class="success-message" id="success-message">
            ✅ Thank you! Your form has been submitted successfully.
        </div>
        <div class="error-message" id="error-message">
            ❌ There was an error submitting your form. Please try again.
        </div>
        
        <div class="form-header">
            <h1 class="form-title">{form_data['name']}</h1>
            {f'<p class="form-description">{form_data["description"]}</p>' if form_data['description'] else ''}
        </div>
        
        <form id="embed-form">
        '''
        
        # Generate form fields
        for field in form_data['fields']:
            required_mark = '<span class="required">*</span>' if field.get('required') else ''
            
            html += f'<div class="form-group">'
            html += f'<label class="form-label">{field["label"]}{required_mark}</label>'
            
            if field['type'] == 'text' or field['type'] == 'email':
                html += f'<input type="{field["type"]}" name="{field["label"].lower().replace(" ", "_")}" class="form-input" placeholder="{field.get("placeholder", "")}" {"required" if field.get("required") else ""}>'
            elif field['type'] == 'textarea':
                html += f'<textarea name="{field["label"].lower().replace(" ", "_")}" class="form-textarea" placeholder="{field.get("placeholder", "")}" {"required" if field.get("required") else ""}></textarea>'
            elif field['type'] == 'select':
                html += f'<select name="{field["label"].lower().replace(" ", "_")}" class="form-select" {"required" if field.get("required") else ""}>'
                html += f'<option value="">Choose an option</option>'
                for option in field.get('options', []):
                    html += f'<option value="{option}">{option}</option>'
                html += '</select>'
            elif field['type'] == 'checkbox':
                html += f'<div class="form-checkbox">'
                html += f'<input type="checkbox" name="{field["label"].lower().replace(" ", "_")}" {"required" if field.get("required") else ""}>'
                html += f'<span>{field.get("text", field["label"])}</span>'
                html += '</div>'
            
            html += '</div>'
        
        html += f'''
            <button type="submit" class="form-submit">Submit Form</button>
        </form>
    </div>
    
    <script>
        document.getElementById('embed-form').addEventListener('submit', async function(e) {{
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {{}};
            
            for (let [key, value] of formData.entries()) {{
                data[key] = value;
            }}
            
            try {{
                const response = await fetch('/api/forms/{form_id}/submit', {{
                    method: 'POST',
                    headers: {{
                        'Content-Type': 'application/json'
                    }},
                    body: JSON.stringify(data)
                }});
                
                if (response.ok) {{
                    document.getElementById('success-message').style.display = 'block';
                    document.getElementById('error-message').style.display = 'none';
                    this.reset();
                }} else {{
                    throw new Error('Submission failed');
                }}
            }} catch (error) {{
                document.getElementById('error-message').style.display = 'block';
                document.getElementById('success-message').style.display = 'none';
            }}
        }});
    </script>
</body>
</html>
        '''
        
        return html
        
    except Exception as e:
        return f"Error loading form: {str(e)}", 500

@app.route('/api/forms/<int:form_id>/submit', methods=['POST'])
def submit_form(form_id):
    try:
        data = request.json
        
        conn = sqlite3.connect('forms.db')
        cursor = conn.cursor()
        
        cursor.execute('''
            INSERT INTO form_entries (form_id, data) 
            VALUES (?, ?)
        ''', (form_id, json.dumps(data)))
        
        conn.commit()
        conn.close()
        
        return jsonify({'message': 'Form submitted successfully'})
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/api/forms/<int:form_id>/entries')
def get_form_entries(form_id):
    conn = sqlite3.connect('forms.db')
    cursor = conn.cursor()
    cursor.execute('SELECT * FROM form_entries WHERE form_id = ? ORDER BY submitted_at DESC', (form_id,))
    entries = []
    for row in cursor.fetchall():
        entries.append({
            'id': row[0],
            'form_id': row[1],
            'data': json.loads(row[2]),
            'submitted_at': row[3]
        })
    conn.close()
    return jsonify(entries)

@app.route('/api/forms/<int:form_id>/entries/export')
def export_entries(form_id):
    conn = sqlite3.connect('forms.db')
    cursor = conn.cursor()
    
    # Get form name
    cursor.execute('SELECT name FROM forms WHERE id = ?', (form_id,))
    form_result = cursor.fetchone()
    if not form_result:
        return jsonify({'error': 'Form not found'}), 404
    
    form_name = form_result[0]
    
    # Get entries
    cursor.execute('SELECT * FROM form_entries WHERE form_id = ? ORDER BY submitted_at DESC', (form_id,))
    entries = cursor.fetchall()
    conn.close()
    
    if not entries:
        return jsonify({'error': 'No entries found'}), 404
    
    # Create CSV
    output = io.StringIO()
    
    # Get all unique field names
    all_fields = set()
    entry_data = []
    for entry in entries:
        data = json.loads(entry[2])
        entry_data.append({
            'id': entry[0],
            'submitted_at': entry[3],
            'data': data
        })
        all_fields.update(data.keys())
    
    fieldnames = ['ID', 'Submitted At'] + list(all_fields)
    writer = csv.DictWriter(output, fieldnames=fieldnames)
    writer.writeheader()
    
    for entry in entry_data:
        row = {
            'ID': entry['id'],
            'Submitted At': entry['submitted_at']
        }
        row.update(entry['data'])
        writer.writerow(row)
    
    # Create response
    response = make_response(output.getvalue())
    response.headers['Content-Type'] = 'text/csv'
    response.headers['Content-Disposition'] = f'attachment; filename={form_name.replace(" ", "_")}_entries.csv'
    
    return response

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5001, debug=True)

