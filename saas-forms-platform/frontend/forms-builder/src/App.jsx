import { useState, useEffect } from 'react'
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom'
import { Button } from '@/components/ui/button.jsx'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card.jsx'
import { Input } from '@/components/ui/input.jsx'
import { Label } from '@/components/ui/label.jsx'
import { Textarea } from '@/components/ui/textarea.jsx'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select.jsx'
import { Badge } from '@/components/ui/badge.jsx'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs.jsx'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog.jsx'
import { 
  Plus, 
  Edit, 
  Eye, 
  Trash2, 
  Download, 
  Code, 
  Share2, 
  Settings, 
  BarChart3,
  Users,
  FileText,
  Palette,
  Zap,
  Globe,
  Smartphone,
  Monitor
} from 'lucide-react'
import './App.css'

// API Configuration
const API_BASE = 'http://localhost:5000/api'

function App() {
  const [forms, setForms] = useState([])
  const [selectedForm, setSelectedForm] = useState(null)
  const [currentView, setCurrentView] = useState('dashboard')
  const [loading, setLoading] = useState(false)

  // Fetch forms on component mount
  useEffect(() => {
    fetchForms()
  }, [])

  const fetchForms = async () => {
    try {
      setLoading(true)
      const response = await fetch(`${API_BASE}/forms`)
      const data = await response.json()
      if (data.success) {
        setForms(data.forms)
      }
    } catch (error) {
      console.error('Error fetching forms:', error)
    } finally {
      setLoading(false)
    }
  }

  return (
    <Router>
      <div className="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
        {/* Header */}
        <header className="bg-white border-b border-gray-200 shadow-sm">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="flex justify-between items-center h-16">
              <div className="flex items-center space-x-4">
                <div className="flex items-center space-x-2">
                  <div className="w-8 h-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                    <FileText className="w-5 h-5 text-white" />
                  </div>
                  <h1 className="text-xl font-bold text-gray-900">TiD Forms Builder</h1>
                </div>
                <Badge variant="secondary" className="bg-blue-100 text-blue-800">SaaS Platform</Badge>
              </div>
              
              <nav className="flex items-center space-x-4">
                <Button 
                  variant={currentView === 'dashboard' ? 'default' : 'ghost'}
                  onClick={() => setCurrentView('dashboard')}
                  className="flex items-center space-x-2"
                >
                  <BarChart3 className="w-4 h-4" />
                  <span>Dashboard</span>
                </Button>
                <Button 
                  variant={currentView === 'forms' ? 'default' : 'ghost'}
                  onClick={() => setCurrentView('forms')}
                  className="flex items-center space-x-2"
                >
                  <FileText className="w-4 h-4" />
                  <span>Forms</span>
                </Button>
                <Button 
                  variant={currentView === 'templates' ? 'default' : 'ghost'}
                  onClick={() => setCurrentView('templates')}
                  className="flex items-center space-x-2"
                >
                  <Palette className="w-4 h-4" />
                  <span>Templates</span>
                </Button>
              </nav>
            </div>
          </div>
        </header>

        {/* Main Content */}
        <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <Routes>
            <Route path="/" element={<Navigate to="/dashboard" replace />} />
            <Route path="/dashboard" element={
              <Dashboard 
                forms={forms} 
                onViewChange={setCurrentView}
                onFormSelect={setSelectedForm}
              />
            } />
            <Route path="/forms" element={
              <FormsManager 
                forms={forms} 
                onFormsChange={fetchForms}
                selectedForm={selectedForm}
                onFormSelect={setSelectedForm}
              />
            } />
            <Route path="/templates" element={<TemplatesView />} />
            <Route path="/form/:id" element={<FormBuilder />} />
          </Routes>
        </main>
      </div>
    </Router>
  )
}

// Dashboard Component
function Dashboard({ forms, onViewChange, onFormSelect }) {
  const totalForms = forms.length
  const totalEntries = forms.reduce((sum, form) => sum + (form.entry_count || 0), 0)
  const activeForms = forms.filter(form => form.is_active).length

  return (
    <div className="space-y-8">
      {/* Hero Section */}
      <div className="text-center space-y-4">
        <h1 className="text-4xl font-bold text-gray-900">
          Create Beautiful Forms for Any Website
        </h1>
        <p className="text-xl text-gray-600 max-w-3xl mx-auto">
          Build stunning forms with our drag-and-drop builder, then embed them anywhere with a simple code snippet or iframe.
        </p>
        <div className="flex justify-center space-x-4">
          <Button 
            size="lg" 
            className="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700"
            onClick={() => onViewChange('forms')}
          >
            <Plus className="w-5 h-5 mr-2" />
            Create New Form
          </Button>
          <Button size="lg" variant="outline">
            <Eye className="w-5 h-5 mr-2" />
            View Templates
          </Button>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <Card className="bg-gradient-to-r from-blue-500 to-blue-600 text-white">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Forms</CardTitle>
            <FileText className="h-4 w-4" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{totalForms}</div>
            <p className="text-xs text-blue-100">
              {activeForms} active forms
            </p>
          </CardContent>
        </Card>

        <Card className="bg-gradient-to-r from-green-500 to-green-600 text-white">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Submissions</CardTitle>
            <Users className="h-4 w-4" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{totalEntries}</div>
            <p className="text-xs text-green-100">
              Across all forms
            </p>
          </CardContent>
        </Card>

        <Card className="bg-gradient-to-r from-purple-500 to-purple-600 text-white">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Conversion Rate</CardTitle>
            <BarChart3 className="h-4 w-4" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">87%</div>
            <p className="text-xs text-purple-100">
              Average across forms
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Features Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <FeatureCard
          icon={<Zap className="w-8 h-8 text-yellow-500" />}
          title="Lightning Fast"
          description="Create forms in minutes with our intuitive drag-and-drop builder"
        />
        <FeatureCard
          icon={<Globe className="w-8 h-8 text-blue-500" />}
          title="Embed Anywhere"
          description="Use on any website with simple embed codes or iframes"
        />
        <FeatureCard
          icon={<Smartphone className="w-8 h-8 text-green-500" />}
          title="Mobile Responsive"
          description="Forms look perfect on desktop, tablet, and mobile devices"
        />
        <FeatureCard
          icon={<Palette className="w-8 h-8 text-purple-500" />}
          title="Beautiful Themes"
          description="5 stunning themes: Modern, Professional, Elegant, Creative, Minimal"
        />
        <FeatureCard
          icon={<BarChart3 className="w-8 h-8 text-red-500" />}
          title="Analytics & Insights"
          description="Track submissions, conversion rates, and form performance"
        />
        <FeatureCard
          icon={<Download className="w-8 h-8 text-indigo-500" />}
          title="Export Data"
          description="Download submissions as CSV files for analysis"
        />
      </div>

      {/* Recent Forms */}
      {forms.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>Recent Forms</CardTitle>
            <CardDescription>Your latest form creations</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {forms.slice(0, 3).map((form) => (
                <div key={form.id} className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                  <div className="flex items-center space-x-4">
                    <div className={`w-10 h-10 rounded-lg flex items-center justify-center theme-${form.theme}`}>
                      <FileText className="w-5 h-5 text-white" />
                    </div>
                    <div>
                      <h3 className="font-medium text-gray-900">{form.name}</h3>
                      <p className="text-sm text-gray-500">{form.entry_count || 0} submissions</p>
                    </div>
                  </div>
                  <div className="flex items-center space-x-2">
                    <Badge variant={form.is_active ? 'default' : 'secondary'}>
                      {form.is_active ? 'Active' : 'Inactive'}
                    </Badge>
                    <Button 
                      size="sm" 
                      variant="outline"
                      onClick={() => {
                        onFormSelect(form)
                        onViewChange('forms')
                      }}
                    >
                      <Edit className="w-4 h-4" />
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  )
}

// Feature Card Component
function FeatureCard({ icon, title, description }) {
  return (
    <Card className="hover:shadow-lg transition-shadow duration-300">
      <CardHeader>
        <div className="flex items-center space-x-3">
          {icon}
          <CardTitle className="text-lg">{title}</CardTitle>
        </div>
      </CardHeader>
      <CardContent>
        <CardDescription className="text-base">{description}</CardDescription>
      </CardContent>
    </Card>
  )
}

// Forms Manager Component
function FormsManager({ forms, onFormsChange, selectedForm, onFormSelect }) {
  const [showCreateDialog, setShowCreateDialog] = useState(false)
  const [newFormName, setNewFormName] = useState('')
  const [newFormDescription, setNewFormDescription] = useState('')
  const [selectedTheme, setSelectedTheme] = useState('modern')

  const createForm = async () => {
    try {
      const response = await fetch(`${API_BASE}/forms`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          name: newFormName,
          description: newFormDescription,
          theme: selectedTheme,
          fields: [
            {
              type: 'text',
              name: 'name',
              label: 'Full Name',
              required: true,
              placeholder: 'Enter your name'
            },
            {
              type: 'email',
              name: 'email',
              label: 'Email Address',
              required: true,
              placeholder: 'Enter your email'
            }
          ]
        })
      })

      const data = await response.json()
      if (data.success) {
        setShowCreateDialog(false)
        setNewFormName('')
        setNewFormDescription('')
        setSelectedTheme('modern')
        onFormsChange()
      }
    } catch (error) {
      console.error('Error creating form:', error)
    }
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Forms</h1>
          <p className="text-gray-600">Manage your forms and view submissions</p>
        </div>
        <Dialog open={showCreateDialog} onOpenChange={setShowCreateDialog}>
          <DialogTrigger asChild>
            <Button className="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700">
              <Plus className="w-4 h-4 mr-2" />
              Create New Form
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Create New Form</DialogTitle>
              <DialogDescription>
                Start building your form with a name, description, and theme.
              </DialogDescription>
            </DialogHeader>
            <div className="space-y-4">
              <div>
                <Label htmlFor="form-name">Form Name</Label>
                <Input
                  id="form-name"
                  value={newFormName}
                  onChange={(e) => setNewFormName(e.target.value)}
                  placeholder="e.g., Contact Form, Newsletter Signup"
                />
              </div>
              <div>
                <Label htmlFor="form-description">Description (Optional)</Label>
                <Textarea
                  id="form-description"
                  value={newFormDescription}
                  onChange={(e) => setNewFormDescription(e.target.value)}
                  placeholder="Describe what this form is for..."
                />
              </div>
              <div>
                <Label htmlFor="form-theme">Theme</Label>
                <Select value={selectedTheme} onValueChange={setSelectedTheme}>
                  <SelectTrigger>
                    <SelectValue placeholder="Select a theme" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="modern">Modern (Purple-Blue Gradients)</SelectItem>
                    <SelectItem value="professional">Professional (Corporate Blue)</SelectItem>
                    <SelectItem value="elegant">Elegant (Sophisticated Purple)</SelectItem>
                    <SelectItem value="creative">Creative (Vibrant Colors)</SelectItem>
                    <SelectItem value="minimal">Minimal (Clean & Simple)</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <Button 
                onClick={createForm} 
                className="w-full"
                disabled={!newFormName.trim()}
              >
                Create Form
              </Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      {/* Forms Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {forms.map((form) => (
          <FormCard 
            key={form.id} 
            form={form} 
            onFormsChange={onFormsChange}
            onFormSelect={onFormSelect}
          />
        ))}
      </div>

      {forms.length === 0 && (
        <div className="text-center py-12">
          <FileText className="w-16 h-16 text-gray-300 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">No forms yet</h3>
          <p className="text-gray-500 mb-4">Create your first form to get started</p>
          <Button onClick={() => setShowCreateDialog(true)}>
            <Plus className="w-4 h-4 mr-2" />
            Create Your First Form
          </Button>
        </div>
      )}
    </div>
  )
}

// Form Card Component
function FormCard({ form, onFormsChange, onFormSelect }) {
  const [showEmbedDialog, setShowEmbedDialog] = useState(false)
  const [embedCode, setEmbedCode] = useState('')
  const [iframeCode, setIframeCode] = useState('')

  const getEmbedCodes = async () => {
    try {
      const response = await fetch(`${API_BASE}/forms/${form.id}/embed`)
      const data = await response.json()
      if (data.success) {
        setEmbedCode(data.embed_code)
        setIframeCode(data.iframe_code)
        setShowEmbedDialog(true)
      }
    } catch (error) {
      console.error('Error getting embed codes:', error)
    }
  }

  return (
    <>
      <Card className="hover:shadow-lg transition-shadow duration-300">
        <CardHeader>
          <div className="flex items-center justify-between">
            <div className={`w-10 h-10 rounded-lg flex items-center justify-center theme-${form.theme}`}>
              <FileText className="w-5 h-5 text-white" />
            </div>
            <Badge variant={form.is_active ? 'default' : 'secondary'}>
              {form.is_active ? 'Active' : 'Inactive'}
            </Badge>
          </div>
          <CardTitle className="text-lg">{form.name}</CardTitle>
          <CardDescription>{form.description || 'No description'}</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            <div className="flex justify-between text-sm text-gray-600">
              <span>Submissions: {form.entry_count || 0}</span>
              <span>Theme: {form.theme}</span>
            </div>
            
            <div className="flex space-x-2">
              <Button size="sm" variant="outline" className="flex-1">
                <Edit className="w-4 h-4 mr-1" />
                Edit
              </Button>
              <Button size="sm" variant="outline" className="flex-1">
                <Eye className="w-4 h-4 mr-1" />
                Preview
              </Button>
              <Button 
                size="sm" 
                variant="outline" 
                onClick={getEmbedCodes}
              >
                <Code className="w-4 h-4" />
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Embed Code Dialog */}
      <Dialog open={showEmbedDialog} onOpenChange={setShowEmbedDialog}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>Embed Your Form</DialogTitle>
            <DialogDescription>
              Copy and paste these codes to embed your form on any website.
            </DialogDescription>
          </DialogHeader>
          <Tabs defaultValue="embed" className="w-full">
            <TabsList className="grid w-full grid-cols-2">
              <TabsTrigger value="embed">JavaScript Embed</TabsTrigger>
              <TabsTrigger value="iframe">iframe Embed</TabsTrigger>
            </TabsList>
            <TabsContent value="embed" className="space-y-4">
              <div>
                <Label>JavaScript Embed Code</Label>
                <Textarea
                  value={embedCode}
                  readOnly
                  className="font-mono text-sm"
                  rows={8}
                />
                <p className="text-xs text-gray-500 mt-2">
                  Paste this code where you want the form to appear on your website.
                </p>
              </div>
            </TabsContent>
            <TabsContent value="iframe" className="space-y-4">
              <div>
                <Label>iframe Embed Code</Label>
                <Textarea
                  value={iframeCode}
                  readOnly
                  className="font-mono text-sm"
                  rows={6}
                />
                <p className="text-xs text-gray-500 mt-2">
                  Use this iframe code for simple embedding. Works on any website.
                </p>
              </div>
            </TabsContent>
          </Tabs>
        </DialogContent>
      </Dialog>
    </>
  )
}

// Templates View Component
function TemplatesView() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-gray-900">Form Templates</h1>
        <p className="text-gray-600">Start with a pre-built template and customize it to your needs</p>
      </div>
      
      <div className="text-center py-12">
        <Palette className="w-16 h-16 text-gray-300 mx-auto mb-4" />
        <h3 className="text-lg font-medium text-gray-900 mb-2">Templates Coming Soon</h3>
        <p className="text-gray-500">We're working on beautiful form templates for you</p>
      </div>
    </div>
  )
}

// Form Builder Component (placeholder)
function FormBuilder() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-gray-900">Form Builder</h1>
        <p className="text-gray-600">Drag and drop to build your perfect form</p>
      </div>
      
      <div className="text-center py-12">
        <Settings className="w-16 h-16 text-gray-300 mx-auto mb-4" />
        <h3 className="text-lg font-medium text-gray-900 mb-2">Form Builder Coming Soon</h3>
        <p className="text-gray-500">Advanced form builder with drag-and-drop functionality</p>
      </div>
    </div>
  )
}

export default App

