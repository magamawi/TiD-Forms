import React, { useState, useEffect } from 'react';
import { Download, Star, Shield, Palette, Smartphone, BarChart3, Code, Zap, CheckCircle, ArrowRight, Github, ExternalLink } from 'lucide-react';
import './App.css';

function App() {
  const [activeTheme, setActiveTheme] = useState('modern');
  const [isVisible, setIsVisible] = useState({});

  useEffect(() => {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            setIsVisible(prev => ({ ...prev, [entry.target.id]: true }));
          }
        });
      },
      { threshold: 0.1 }
    );

    document.querySelectorAll('[id]').forEach((el) => {
      observer.observe(el);
    });

    return () => observer.disconnect();
  }, []);

  const themes = {
    modern: {
      name: 'Modern',
      description: 'Gradient borders and smooth animations',
      primary: '#667eea',
      secondary: '#764ba2',
      preview: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
    },
    professional: {
      name: 'Professional',
      description: 'Clean, corporate design',
      primary: '#2c3e50',
      secondary: '#3498db',
      preview: 'linear-gradient(135deg, #2c3e50 0%, #3498db 100%)'
    },
    elegant: {
      name: 'Elegant',
      description: 'Sophisticated glass effects',
      primary: '#8e44ad',
      secondary: '#9b59b6',
      preview: 'linear-gradient(135deg, #8e44ad 0%, #9b59b6 100%)'
    },
    creative: {
      name: 'Creative',
      description: 'Bold, colorful design',
      primary: '#e74c3c',
      secondary: '#f39c12',
      preview: 'linear-gradient(135deg, #e74c3c 0%, #f39c12 100%)'
    },
    minimal: {
      name: 'Minimal',
      description: 'Ultra-clean design',
      primary: '#34495e',
      secondary: '#95a5a6',
      preview: 'linear-gradient(135deg, #34495e 0%, #95a5a6 100%)'
    }
  };

  const features = [
    {
      icon: <Palette className="w-8 h-8" />,
      title: "5 Stunning Themes",
      description: "Beautiful pre-designed themes with customizable colors and animations"
    },
    {
      icon: <Smartphone className="w-8 h-8" />,
      title: "Fully Responsive",
      description: "Perfect display on all devices with touch-friendly interactions"
    },
    {
      icon: <Shield className="w-8 h-8" />,
      title: "Security First",
      description: "Built-in spam protection, GDPR compliance, and data sanitization"
    },
    {
      icon: <BarChart3 className="w-8 h-8" />,
      title: "Analytics Dashboard",
      description: "Simple admin interface with entry management and CSV export"
    },
    {
      icon: <Zap className="w-8 h-8" />,
      title: "Smooth Animations",
      description: "Delightful micro-interactions and entrance animations"
    },
    {
      icon: <Code className="w-8 h-8" />,
      title: "Easy Integration",
      description: "Simple shortcode system and WordPress-native architecture"
    }
  ];

  const stats = [
    { number: "5", label: "Beautiful Themes" },
    { number: "15+", label: "Field Types" },
    { number: "100%", label: "Mobile Ready" },
    { number: "0", label: "Monthly Fees" }
  ];

  return (
    <div className="App">
      {/* Header */}
      <header className="header">
        <nav className="nav">
          <div className="nav-brand">
            <span className="brand-icon">🦕</span>
            <span className="brand-text">Innovative Forms</span>
          </div>
          <div className="nav-links">
            <a href="#features">Features</a>
            <a href="#themes">Themes</a>
            <a href="#demo">Demo</a>
            <a href="#download" className="nav-cta">Download</a>
          </div>
        </nav>
      </header>

      {/* Hero Section */}
      <section className="hero">
        <div className="hero-background"></div>
        <div className="container">
          <div className={`hero-content ${isVisible.hero ? 'animate-in' : ''}`} id="hero">
            <h1 className="hero-title">
              Beautiful WordPress Forms
              <span className="gradient-text">That Users Love</span>
            </h1>
            <p className="hero-description">
              Replace WPForms with stunning, modern forms featuring impressive UI/UX design, 
              smooth animations, and powerful functionality. Built for The Innovative Dinosaur.
            </p>
            <div className="hero-buttons">
              <button className="btn btn-primary">
                <Download className="w-5 h-5" />
                Download Plugin
              </button>
              <button className="btn btn-secondary">
                <ExternalLink className="w-5 h-5" />
                View Demo
              </button>
            </div>
            <div className="hero-stats">
              {stats.map((stat, index) => (
                <div key={index} className="stat-item">
                  <div className="stat-number">{stat.number}</div>
                  <div className="stat-label">{stat.label}</div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section id="features" className="features">
        <div className="container">
          <div className={`section-header ${isVisible.features ? 'animate-in' : ''}`}>
            <h2>Powerful Features</h2>
            <p>Everything you need to create beautiful, functional forms</p>
          </div>
          <div className="features-grid">
            {features.map((feature, index) => (
              <div 
                key={index} 
                className={`feature-card ${isVisible.features ? 'animate-in' : ''}`}
                style={{ animationDelay: `${index * 0.1}s` }}
              >
                <div className="feature-icon">{feature.icon}</div>
                <h3>{feature.title}</h3>
                <p>{feature.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Themes Section */}
      <section id="themes" className="themes">
        <div className="container">
          <div className={`section-header ${isVisible.themes ? 'animate-in' : ''}`}>
            <h2>5 Stunning Themes</h2>
            <p>Choose from beautiful pre-designed themes or customize your own</p>
          </div>
          
          <div className="theme-selector">
            {Object.entries(themes).map(([key, theme]) => (
              <button
                key={key}
                className={`theme-button ${activeTheme === key ? 'active' : ''}`}
                onClick={() => setActiveTheme(key)}
                style={{ background: theme.preview }}
              >
                <span className="theme-name">{theme.name}</span>
              </button>
            ))}
          </div>

          <div className="theme-preview">
            <div className="theme-info">
              <h3>{themes[activeTheme].name} Theme</h3>
              <p>{themes[activeTheme].description}</p>
            </div>
            
            <div 
              className="form-preview"
              style={{
                '--primary-color': themes[activeTheme].primary,
                '--secondary-color': themes[activeTheme].secondary,
                background: themes[activeTheme].preview
              }}
            >
              <div className="preview-form">
                <h4>Newsletter Subscription</h4>
                <div className="preview-field">
                  <label>Email Address</label>
                  <input type="email" placeholder="Enter your email" />
                </div>
                <div className="preview-field">
                  <label>Interests</label>
                  <div className="checkbox-group">
                    <label className="checkbox-label">
                      <input type="checkbox" />
                      <span>Beta Readers</span>
                    </label>
                    <label className="checkbox-label">
                      <input type="checkbox" />
                      <span>Updates</span>
                    </label>
                  </div>
                </div>
                <button className="preview-submit">Subscribe</button>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Demo Section */}
      <section id="demo" className="demo">
        <div className="container">
          <div className={`section-header ${isVisible.demo ? 'animate-in' : ''}`}>
            <h2>See It In Action</h2>
            <p>Experience the beautiful forms and smooth interactions</p>
          </div>
          
          <div className="demo-container">
            <div className="demo-form-container">
              <div className="demo-form">
                <div className="form-header">
                  <h3>Contact Form Demo</h3>
                  <p>Try our interactive form demo</p>
                </div>
                
                <form className="interactive-form">
                  <div className="form-field">
                    <label>Full Name</label>
                    <input type="text" placeholder="Enter your name" />
                  </div>
                  
                  <div className="form-field">
                    <label>Email Address</label>
                    <input type="email" placeholder="Enter your email" />
                  </div>
                  
                  <div className="form-field">
                    <label>Message</label>
                    <textarea placeholder="Enter your message..." rows="4"></textarea>
                  </div>
                  
                  <button type="submit" className="demo-submit">
                    Send Message
                    <ArrowRight className="w-4 h-4" />
                  </button>
                </form>
              </div>
            </div>
            
            <div className="demo-features">
              <div className="demo-feature">
                <CheckCircle className="w-6 h-6 text-green-500" />
                <span>Real-time validation</span>
              </div>
              <div className="demo-feature">
                <CheckCircle className="w-6 h-6 text-green-500" />
                <span>Smooth animations</span>
              </div>
              <div className="demo-feature">
                <CheckCircle className="w-6 h-6 text-green-500" />
                <span>Mobile responsive</span>
              </div>
              <div className="demo-feature">
                <CheckCircle className="w-6 h-6 text-green-500" />
                <span>GDPR compliant</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Download Section */}
      <section id="download" className="download">
        <div className="container">
          <div className={`download-content ${isVisible.download ? 'animate-in' : ''}`}>
            <h2>Ready to Transform Your Forms?</h2>
            <p>Download the complete WordPress plugin and start creating beautiful forms today.</p>
            
            <div className="download-options">
              <div className="download-card">
                <div className="download-header">
                  <h3>WordPress Plugin</h3>
                  <div className="price">Free</div>
                </div>
                <ul className="download-features">
                  <li><CheckCircle className="w-5 h-5" /> 5 Beautiful Themes</li>
                  <li><CheckCircle className="w-5 h-5" /> 15+ Field Types</li>
                  <li><CheckCircle className="w-5 h-5" /> CSV Export</li>
                  <li><CheckCircle className="w-5 h-5" /> GDPR Compliance</li>
                  <li><CheckCircle className="w-5 h-5" /> Spam Protection</li>
                  <li><CheckCircle className="w-5 h-5" /> Mobile Responsive</li>
                </ul>
                <button className="download-btn">
                  <Download className="w-5 h-5" />
                  Download Plugin
                </button>
              </div>
              
              <div className="download-info">
                <h4>What's Included:</h4>
                <ul>
                  <li>Complete WordPress plugin (.zip)</li>
                  <li>Installation guide</li>
                  <li>Documentation</li>
                  <li>Demo examples</li>
                  <li>Source code</li>
                </ul>
                
                <div className="requirements">
                  <h4>Requirements:</h4>
                  <ul>
                    <li>WordPress 5.0+</li>
                    <li>PHP 7.4+</li>
                    <li>MySQL 5.6+</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="footer">
        <div className="container">
          <div className="footer-content">
            <div className="footer-brand">
              <span className="brand-icon">🦕</span>
              <span className="brand-text">Innovative Forms</span>
              <p>Beautiful WordPress forms for the modern web</p>
            </div>
            
            <div className="footer-links">
              <div className="footer-section">
                <h4>Product</h4>
                <a href="#features">Features</a>
                <a href="#themes">Themes</a>
                <a href="#demo">Demo</a>
                <a href="#download">Download</a>
              </div>
              
              <div className="footer-section">
                <h4>Resources</h4>
                <a href="#">Documentation</a>
                <a href="#">Installation Guide</a>
                <a href="#">Support</a>
                <a href="#">GitHub</a>
              </div>
              
              <div className="footer-section">
                <h4>Company</h4>
                <a href="https://theinnovativedinosaur.com">The Innovative Dinosaur</a>
                <a href="#">About</a>
                <a href="#">Contact</a>
                <a href="#">Privacy</a>
              </div>
            </div>
          </div>
          
          <div className="footer-bottom">
            <p>&copy; 2024 The Innovative Dinosaur. All rights reserved.</p>
            <p>Built with ❤️ for beautiful WordPress forms</p>
          </div>
        </div>
      </footer>
    </div>
  );
}

export default App;

