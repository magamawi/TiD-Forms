from flask import Blueprint, request, jsonify
from src.models.form import db, Form, FormEntry, ConversationSession
import uuid
import re

conversation_bp = Blueprint('conversation', __name__)

@conversation_bp.route('/conversation/start', methods=['POST'])
def start_conversation():
    """Start a new conversation session"""
    session_id = str(uuid.uuid4())
    
    session = ConversationSession(
        session_id=session_id,
        state='form_name',
        form_config={}
    )
    
    db.session.add(session)
    db.session.commit()
    
    return jsonify({
        'session_id': session_id,
        'message': "👋 Hi! I'm your TiD Forms assistant. I'll help you create beautiful forms through a simple conversation. What would you like to name your form?",
        'state': 'form_name'
    })

@conversation_bp.route('/conversation/<session_id>/message', methods=['POST'])
def process_message(session_id):
    """Process user message and return bot response"""
    data = request.get_json()
    user_message = data.get('message', '').strip()
    
    if not user_message:
        return jsonify({'error': 'Message is required'}), 400
    
    # Get or create session
    session = ConversationSession.query.filter_by(session_id=session_id).first()
    if not session:
        return jsonify({'error': 'Session not found'}), 404
    
    # Process the message based on current state
    response = process_conversation_state(session, user_message)
    
    # Update session
    db.session.commit()
    
    return jsonify(response)

def process_conversation_state(session, user_input):
    """Process user input based on conversation state"""
    state = session.state
    config = session.form_config
    
    if state == 'form_name':
        config['name'] = user_input
        session.form_config = config
        session.state = 'fields'
        
        return {
            'message': f'Perfect! "{user_input}" is a great name. Now, what data fields do you want to collect? (For example: Name, Email, Phone, Address, Company, etc.)',
            'state': 'fields'
        }
    
    elif state == 'fields':
        fields = [f.strip() for f in user_input.split(',') if f.strip()]
        recognized_fields = []
        unknown_fields = []
        
        # Categorize fields
        for field in fields:
            field_lower = field.lower()
            if any(known in field_lower for known in ['name', 'email', 'phone', 'telephone', 'address', 'message', 'subject']):
                recognized_fields.append(normalize_field(field))
            else:
                unknown_fields.append(field)
        
        config['recognized_fields'] = recognized_fields
        config['unknown_fields'] = unknown_fields
        session.form_config = config
        
        if unknown_fields:
            session.state = 'field_type'
            config['current_unknown_index'] = 0
            session.form_config = config
            
            return {
                'message': f'Great! I understand these fields: {", ".join([f["name"] for f in recognized_fields])}.\n\nI need help with "{unknown_fields[0]}" - what type of field is this?\n1. Text (free input)\n2. Number\n3. Date\n4. Yes/No (checkbox)\n5. Dropdown (multiple options)\n\nJust reply with the number.',
                'state': 'field_type'
            }
        else:
            config['all_fields'] = recognized_fields
            session.form_config = config
            session.state = 'gdpr'
            
            return {
                'message': 'For GDPR compliance, should I add a consent checkbox? (yes/no)',
                'state': 'gdpr'
            }
    
    elif state == 'field_type':
        field_types = ['text', 'number', 'date', 'checkbox', 'dropdown']
        try:
            selected_index = int(user_input) - 1
            if 0 <= selected_index < len(field_types):
                selected_type = field_types[selected_index]
                current_index = config.get('current_unknown_index', 0)
                current_field = config['unknown_fields'][current_index]
                
                new_field = {'name': current_field, 'type': selected_type}
                
                if selected_type == 'dropdown':
                    config['pending_dropdown_field'] = new_field
                    session.form_config = config
                    session.state = 'dropdown_options'
                    
                    return {
                        'message': f'What options should "{current_field}" have? (separate with commas, e.g., Small, Medium, Large)',
                        'state': 'dropdown_options'
                    }
                else:
                    # Add the field to recognized fields
                    if 'all_fields' not in config:
                        config['all_fields'] = config.get('recognized_fields', [])
                    config['all_fields'].append(new_field)
                    
                    # Move to next unknown field or continue
                    current_index += 1
                    if current_index < len(config['unknown_fields']):
                        config['current_unknown_index'] = current_index
                        session.form_config = config
                        next_field = config['unknown_fields'][current_index]
                        
                        return {
                            'message': f'Got it! Now for "{next_field}" - what type is this?\n1. Text (free input)\n2. Number\n3. Date\n4. Yes/No (checkbox)\n5. Dropdown (multiple options)',
                            'state': 'field_type'
                        }
                    else:
                        session.form_config = config
                        session.state = 'gdpr'
                        
                        return {
                            'message': 'For GDPR compliance, should I add a consent checkbox? (yes/no)',
                            'state': 'gdpr'
                        }
            else:
                return {
                    'message': 'Please choose a number from 1-5.',
                    'state': 'field_type'
                }
        except ValueError:
            return {
                'message': 'Please choose a number from 1-5.',
                'state': 'field_type'
            }
    
    elif state == 'dropdown_options':
        options = [o.strip() for o in user_input.split(',') if o.strip()]
        dropdown_field = config['pending_dropdown_field']
        dropdown_field['options'] = options
        
        if 'all_fields' not in config:
            config['all_fields'] = config.get('recognized_fields', [])
        config['all_fields'].append(dropdown_field)
        
        # Remove pending field
        del config['pending_dropdown_field']
        
        # Check for more unknown fields
        current_index = config.get('current_unknown_index', 0) + 1
        if current_index < len(config['unknown_fields']):
            config['current_unknown_index'] = current_index
            session.form_config = config
            session.state = 'field_type'
            next_field = config['unknown_fields'][current_index]
            
            return {
                'message': f'Perfect! Now for "{next_field}" - what type is this?\n1. Text (free input)\n2. Number\n3. Date\n4. Yes/No (checkbox)\n5. Dropdown (multiple options)',
                'state': 'field_type'
            }
        else:
            session.form_config = config
            session.state = 'gdpr'
            
            return {
                'message': 'For GDPR compliance, should I add a consent checkbox? (yes/no)',
                'state': 'gdpr'
            }
    
    elif state == 'gdpr':
        include_gdpr = user_input.lower() in ['yes', 'y', 'true', '1']
        config['gdpr'] = include_gdpr
        session.form_config = config
        session.state = 'theme'
        
        gdpr_message = 'Great! GDPR consent will be included.' if include_gdpr else 'Understood, no GDPR consent needed.'
        
        return {
            'message': f'{gdpr_message}\n\nNow choose your form theme:\n1. 🌟 Modern (Purple-Blue Gradients)\n2. 💼 Professional (Blue Corporate)\n3. ✨ Elegant (Luxury Purple)\n4. 🎨 Creative (Colorful & Vibrant)\n5. 🤍 Minimal (Clean & Simple)\n\nJust enter the number of your choice.',
            'state': 'theme'
        }
    
    elif state == 'theme':
        themes = ['modern', 'professional', 'elegant', 'creative', 'minimal']
        theme_names = ['Modern', 'Professional', 'Elegant', 'Creative', 'Minimal']
        
        try:
            selected_index = int(user_input) - 1
            if 0 <= selected_index < len(themes):
                config['theme'] = themes[selected_index]
                session.form_config = config
                session.state = 'confirmation'
                
                # Generate configuration summary
                fields_list = []
                for field in config.get('all_fields', []):
                    if isinstance(field, dict):
                        if 'options' in field:
                            fields_list.append(f"{field['name']} ({'/'.join(field['options'])})")
                        else:
                            fields_list.append(f"{field['name']} ({field['type']})")
                    else:
                        fields_list.append(field['name'] if isinstance(field, dict) else str(field))
                
                gdpr_status = '✅ **GDPR:** Consent checkbox included' if config.get('gdpr') else '❌ **GDPR:** No consent checkbox'
                
                return {
                    'message': f'Perfect! Here\'s your form configuration:\n\n📝 **Form Name:** {config["name"]}\n📋 **Fields:** {", ".join(fields_list)}\n{gdpr_status}\n🎨 **Theme:** {theme_names[selected_index]}\n\nDoes this look correct? Reply "yes" to create your form, or "no" to make changes.',
                    'state': 'confirmation'
                }
            else:
                return {
                    'message': 'Please choose a number from 1-5 for your theme.',
                    'state': 'theme'
                }
        except ValueError:
            return {
                'message': 'Please choose a number from 1-5 for your theme.',
                'state': 'theme'
            }
    
    elif state == 'confirmation':
        if user_input.lower() in ['yes', 'y', 'confirm', 'create']:
            # Create the form
            form = create_form_from_config(config)
            session.state = 'completed'
            session.form_config = config
            
            embed_code = f'<iframe src="https://forms.tid.com/embed/{form.id}" width="100%" height="600" frameborder="0"></iframe>'
            
            return {
                'message': f'🎉 **Form Created Successfully!**\n\nYour "{form.name}" form is ready! Here are your integration options:\n\n**📋 Form ID:** {form.id}\n**🔗 Direct Link:** https://forms.tid.com/form/{form.id}\n**📱 Preview:** https://forms.tid.com/form/{form.id}/preview',
                'embed_code': embed_code,
                'form_id': form.id,
                'state': 'completed'
            }
        else:
            session.state = 'modify'
            return {
                'message': 'No problem! What would you like to change? You can say "start over" to begin again, or tell me what to modify.',
                'state': 'modify'
            }
    
    elif state == 'modify':
        if 'start over' in user_input.lower():
            session.state = 'form_name'
            session.form_config = {}
            return {
                'message': "Let's start fresh! What would you like to name your new form?",
                'state': 'form_name'
            }
        else:
            # For now, just create the form
            form = create_form_from_config(config)
            session.state = 'completed'
            
            return {
                'message': f'I understand you want to make changes. For now, let me create the form and you can modify it later. Your form "{form.name}" has been created with ID {form.id}.',
                'form_id': form.id,
                'state': 'completed'
            }
    
    else:
        session.state = 'form_name'
        session.form_config = {}
        return {
            'message': "I'm not sure how to help with that. Let's start over. What would you like to name your form?",
            'state': 'form_name'
        }

def normalize_field(field_name):
    """Normalize field names to standard format"""
    field_lower = field_name.lower()
    
    if 'email' in field_lower:
        return {'name': field_name, 'type': 'email'}
    elif any(phone in field_lower for phone in ['phone', 'telephone', 'mobile']):
        return {'name': field_name, 'type': 'tel'}
    elif 'name' in field_lower:
        return {'name': field_name, 'type': 'text'}
    elif 'address' in field_lower:
        return {'name': field_name, 'type': 'textarea'}
    elif any(msg in field_lower for msg in ['message', 'comment', 'description']):
        return {'name': field_name, 'type': 'textarea'}
    elif 'subject' in field_lower:
        return {'name': field_name, 'type': 'text'}
    else:
        return {'name': field_name, 'type': 'text'}

def create_form_from_config(config):
    """Create a form from the conversation configuration"""
    form = Form(
        name=config['name'],
        description=config.get('description', ''),
        theme=config.get('theme', 'modern'),
        gdpr_enabled=config.get('gdpr', False),
        fields=config.get('all_fields', [])
    )
    
    db.session.add(form)
    db.session.commit()
    
    return form

