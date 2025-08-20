from flask_sqlalchemy import SQLAlchemy
from datetime import datetime
import json

db = SQLAlchemy()

class Form(db.Model):
    __tablename__ = 'forms'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(255), nullable=False)
    description = db.Column(db.Text)
    theme = db.Column(db.String(50), default='modern')
    gdpr_enabled = db.Column(db.Boolean, default=False)
    fields_json = db.Column(db.Text)  # Store fields as JSON
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Relationship to entries
    entries = db.relationship('FormEntry', backref='form', lazy=True, cascade='all, delete-orphan')
    
    @property
    def fields(self):
        """Parse fields from JSON"""
        if self.fields_json:
            return json.loads(self.fields_json)
        return []
    
    @fields.setter
    def fields(self, value):
        """Store fields as JSON"""
        self.fields_json = json.dumps(value)
    
    @property
    def entry_count(self):
        """Get count of entries for this form"""
        return len(self.entries)
    
    def to_dict(self):
        return {
            'id': self.id,
            'name': self.name,
            'description': self.description,
            'theme': self.theme,
            'gdpr_enabled': self.gdpr_enabled,
            'fields': self.fields,
            'entry_count': self.entry_count,
            'created_at': self.created_at.isoformat(),
            'updated_at': self.updated_at.isoformat()
        }

class FormEntry(db.Model):
    __tablename__ = 'form_entries'
    
    id = db.Column(db.Integer, primary_key=True)
    form_id = db.Column(db.Integer, db.ForeignKey('forms.id'), nullable=False)
    data_json = db.Column(db.Text, nullable=False)  # Store form data as JSON
    ip_address = db.Column(db.String(45))
    user_agent = db.Column(db.Text)
    submitted_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    @property
    def data(self):
        """Parse data from JSON"""
        if self.data_json:
            return json.loads(self.data_json)
        return {}
    
    @data.setter
    def data(self, value):
        """Store data as JSON"""
        self.data_json = json.dumps(value)
    
    def to_dict(self):
        return {
            'id': self.id,
            'form_id': self.form_id,
            'data': self.data,
            'ip_address': self.ip_address,
            'submitted_at': self.submitted_at.isoformat()
        }

class ConversationSession(db.Model):
    __tablename__ = 'conversation_sessions'
    
    id = db.Column(db.Integer, primary_key=True)
    session_id = db.Column(db.String(255), unique=True, nullable=False)
    state = db.Column(db.String(50), default='form_name')
    form_config_json = db.Column(db.Text)  # Store current form configuration
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    @property
    def form_config(self):
        """Parse form config from JSON"""
        if self.form_config_json:
            return json.loads(self.form_config_json)
        return {}
    
    @form_config.setter
    def form_config(self, value):
        """Store form config as JSON"""
        self.form_config_json = json.dumps(value)
    
    def to_dict(self):
        return {
            'id': self.id,
            'session_id': self.session_id,
            'state': self.state,
            'form_config': self.form_config,
            'created_at': self.created_at.isoformat(),
            'updated_at': self.updated_at.isoformat()
        }

