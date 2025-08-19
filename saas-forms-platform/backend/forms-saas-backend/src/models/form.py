from src.models.user import db
from datetime import datetime
import json

class Form(db.Model):
    __tablename__ = 'forms'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(255), nullable=False)
    description = db.Column(db.Text)
    theme = db.Column(db.String(50), default='modern')
    fields = db.Column(db.Text)  # JSON string of form fields
    settings = db.Column(db.Text)  # JSON string of form settings
    embed_code = db.Column(db.Text)  # Generated embed code
    iframe_code = db.Column(db.Text)  # Generated iframe code
    is_active = db.Column(db.Boolean, default=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Relationships
    entries = db.relationship('FormEntry', backref='form', lazy=True, cascade='all, delete-orphan')
    
    def to_dict(self):
        return {
            'id': self.id,
            'name': self.name,
            'description': self.description,
            'theme': self.theme,
            'fields': json.loads(self.fields) if self.fields else [],
            'settings': json.loads(self.settings) if self.settings else {},
            'embed_code': self.embed_code,
            'iframe_code': self.iframe_code,
            'is_active': self.is_active,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None,
            'entry_count': len(self.entries)
        }
    
    def set_fields(self, fields_data):
        """Set form fields from dictionary"""
        self.fields = json.dumps(fields_data)
    
    def get_fields(self):
        """Get form fields as dictionary"""
        return json.loads(self.fields) if self.fields else []
    
    def set_settings(self, settings_data):
        """Set form settings from dictionary"""
        self.settings = json.dumps(settings_data)
    
    def get_settings(self):
        """Get form settings as dictionary"""
        return json.loads(self.settings) if self.settings else {}

class FormEntry(db.Model):
    __tablename__ = 'form_entries'
    
    id = db.Column(db.Integer, primary_key=True)
    form_id = db.Column(db.Integer, db.ForeignKey('forms.id'), nullable=False)
    data = db.Column(db.Text)  # JSON string of submitted data
    ip_address = db.Column(db.String(45))
    user_agent = db.Column(db.Text)
    submitted_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'form_id': self.form_id,
            'data': json.loads(self.data) if self.data else {},
            'ip_address': self.ip_address,
            'user_agent': self.user_agent,
            'submitted_at': self.submitted_at.isoformat() if self.submitted_at else None
        }
    
    def set_data(self, entry_data):
        """Set entry data from dictionary"""
        self.data = json.dumps(entry_data)
    
    def get_data(self):
        """Get entry data as dictionary"""
        return json.loads(self.data) if self.data else {}

class FormTemplate(db.Model):
    __tablename__ = 'form_templates'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(255), nullable=False)
    description = db.Column(db.Text)
    category = db.Column(db.String(100))
    fields = db.Column(db.Text)  # JSON string of template fields
    settings = db.Column(db.Text)  # JSON string of template settings
    is_featured = db.Column(db.Boolean, default=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'name': self.name,
            'description': self.description,
            'category': self.category,
            'fields': json.loads(self.fields) if self.fields else [],
            'settings': json.loads(self.settings) if self.settings else {},
            'is_featured': self.is_featured,
            'created_at': self.created_at.isoformat() if self.created_at else None
        }

