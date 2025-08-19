# Contributing to TiD Forms

Thank you for your interest in contributing to TiD Forms! This document provides guidelines and information for contributors.

## 🚀 Getting Started

### Development Environment

1. **GitHub Codespaces (Recommended)**
   - Click "Open in GitHub Codespaces" in the README
   - Everything is pre-configured

2. **Local Development**
   ```bash
   git clone https://github.com/magamawi/TiD-Forms.git
   cd TiD-Forms
   bash .devcontainer/setup.sh
   ```

### Project Structure

- `wordpress-plugin/` - WordPress plugin code
- `marketing-website/` - React marketing site
- `wordpress-demo/` - Flask demo environment
- `docs/` - Documentation files

## 🛠️ Development Workflow

### 1. Fork & Clone
```bash
git clone https://github.com/YOUR_USERNAME/TiD-Forms.git
cd TiD-Forms
git remote add upstream https://github.com/magamawi/TiD-Forms.git
```

### 2. Create Feature Branch
```bash
git checkout -b feature/your-feature-name
```

### 3. Make Changes
- Follow existing code style
- Add tests if applicable
- Update documentation

### 4. Test Your Changes
```bash
# Test WordPress Demo
cd wordpress-demo
source venv/bin/activate
python src/main.py

# Test Marketing Website
cd marketing-website
npm run dev
```

### 5. Commit & Push
```bash
git add .
git commit -m "feat: add amazing new feature"
git push origin feature/your-feature-name
```

### 6. Create Pull Request
- Use descriptive title and description
- Reference any related issues
- Include screenshots if UI changes

## 📝 Code Style

### PHP (WordPress Plugin)
- Follow WordPress Coding Standards
- Use proper sanitization and validation
- Include proper documentation

### JavaScript/React
- Use ES6+ features
- Follow React best practices
- Use meaningful component names

### Python (Demo)
- Follow PEP 8
- Use type hints where appropriate
- Include docstrings

### CSS
- Use modern CSS features
- Follow BEM methodology
- Ensure responsive design

## 🧪 Testing

### WordPress Plugin
- Test in actual WordPress environment
- Verify all shortcodes work
- Check admin interface functionality

### Marketing Website
- Test responsive design
- Verify all interactive elements
- Check cross-browser compatibility

### WordPress Demo
- Test all admin functions
- Verify form submissions
- Check CSV export functionality

## 📖 Documentation

- Update README.md if needed
- Add inline code comments
- Update relevant documentation files
- Include examples for new features

## 🐛 Bug Reports

When reporting bugs, please include:

1. **Environment Details**
   - Browser/WordPress version
   - Operating system
   - Plugin version

2. **Steps to Reproduce**
   - Clear, numbered steps
   - Expected vs actual behavior
   - Screenshots if applicable

3. **Additional Context**
   - Error messages
   - Console logs
   - Related issues

## 💡 Feature Requests

For new features:

1. **Check Existing Issues**
   - Avoid duplicates
   - Add to existing discussions

2. **Provide Context**
   - Use case description
   - Expected behavior
   - Mockups if applicable

3. **Consider MVP Scope**
   - Focus on core functionality
   - Avoid feature creep

## 🎯 Priority Areas

Current focus areas for contributions:

### High Priority
- Bug fixes and stability
- Performance improvements
- Security enhancements
- Documentation improvements

### Medium Priority
- New form field types
- Additional themes
- Accessibility improvements
- Mobile optimizations

### Future Enhancements
- Email notifications
- Payment processing
- Advanced conditional logic
- Third-party integrations

## 🔍 Code Review Process

1. **Automated Checks**
   - Code style validation
   - Basic functionality tests

2. **Manual Review**
   - Code quality assessment
   - Feature functionality
   - Documentation review

3. **Testing**
   - Local testing by reviewers
   - Integration testing

## 📋 Pull Request Checklist

Before submitting:

- [ ] Code follows project style guidelines
- [ ] Self-review completed
- [ ] Tests added/updated if applicable
- [ ] Documentation updated
- [ ] No breaking changes (or clearly documented)
- [ ] Commit messages are descriptive
- [ ] PR description explains changes

## 🤝 Community Guidelines

- Be respectful and inclusive
- Provide constructive feedback
- Help newcomers get started
- Share knowledge and best practices
- Focus on the project goals

## 📞 Getting Help

- **Issues**: GitHub Issues for bugs/features
- **Discussions**: GitHub Discussions for questions
- **Documentation**: Check docs/ folder first

## 🏆 Recognition

Contributors will be:
- Listed in project contributors
- Mentioned in release notes
- Credited for significant contributions

Thank you for contributing to TiD Forms! 🎉

