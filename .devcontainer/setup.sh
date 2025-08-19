#!/bin/bash

echo "🚀 Setting up TiD Forms development environment..."

# Setup WordPress Demo
echo "📝 Setting up WordPress Demo..."
cd wordpress-demo
python -m venv venv
source venv/bin/activate
pip install -r requirements.txt
cd ..

# Setup Marketing Website
echo "🎨 Setting up Marketing Website..."
cd marketing-website
npm install
cd ..

echo "✅ Setup complete!"
echo ""
echo "🎯 Quick Start Commands:"
echo "  WordPress Demo:    cd wordpress-demo && source venv/bin/activate && python src/main.py"
echo "  Marketing Website: cd marketing-website && npm run dev"
echo ""
echo "🌐 The WordPress plugin is ready to install in the wordpress-plugin/ directory"
echo ""
echo "📖 Check README.md for detailed instructions"

