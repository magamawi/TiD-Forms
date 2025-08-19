#!/bin/bash

echo "🚀 TiD Forms - Automated WordPress Plugin Testing Setup"
echo "=================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if we're in the right directory
if [ ! -d "wordpress-plugin" ]; then
    print_error "wordpress-plugin directory not found!"
    print_info "Please run this script from the TiD-Forms root directory"
    exit 1
fi

print_info "Setting up WordPress for TiD Forms plugin testing..."

# Create WordPress test directory
print_info "Creating WordPress test environment..."
mkdir -p wordpress-test
cd wordpress-test

# Download WordPress
print_info "Downloading WordPress..."
wget -q --show-progress https://wordpress.org/latest.tar.gz
if [ $? -eq 0 ]; then
    print_status "WordPress downloaded successfully"
else
    print_error "Failed to download WordPress"
    exit 1
fi

# Extract WordPress
print_info "Extracting WordPress..."
tar -xzf latest.tar.gz
mv wordpress/* .
rm -rf wordpress latest.tar.gz
print_status "WordPress extracted"

# Download SQLite plugin for database
print_info "Setting up SQLite database plugin..."
wget -q https://downloads.wordpress.org/plugin/sqlite-database-integration.2.1.13.zip
unzip -q sqlite-database-integration.2.1.13.zip -d wp-content/plugins/
rm sqlite-database-integration.2.1.13.zip
print_status "SQLite plugin installed"

# Copy our plugin
print_info "Installing TiD Forms plugin..."
cp -r ../wordpress-plugin wp-content/plugins/innovative-forms
chmod -R 755 wp-content/plugins/innovative-forms
print_status "TiD Forms plugin copied to WordPress"

# Create wp-config.php
print_info "Configuring WordPress..."
cat > wp-config.php << 'EOF'
<?php
// SQLite Database Configuration
define('DB_NAME', 'wordpress');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

// Use SQLite instead of MySQL
define('USE_MYSQL', false);

// Table prefix
$table_prefix = 'wp_';

// Debug settings
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Security keys (simplified for testing)
define('AUTH_KEY',         'test-key-1');
define('SECURE_AUTH_KEY',  'test-key-2');
define('LOGGED_IN_KEY',    'test-key-3');
define('NONCE_KEY',        'test-key-4');
define('AUTH_SALT',        'test-salt-1');
define('SECURE_AUTH_SALT', 'test-salt-2');
define('LOGGED_IN_SALT',   'test-salt-3');
define('NONCE_SALT',       'test-salt-4');

// Absolute path
if ( !defined('ABSPATH') )
    define('ABSPATH', dirname(__FILE__) . '/');

require_once(ABSPATH . 'wp-settings.php');
EOF

print_status "WordPress configuration created"

# Create .htaccess for pretty permalinks
cat > .htaccess << 'EOF'
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
EOF

print_status ".htaccess created for pretty permalinks"

# Create a test page with form shortcode
mkdir -p wp-content/themes/twentytwentythree
cat > test-form-page.html << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>TiD Forms Test Page</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <h1>TiD Forms Plugin Test</h1>
    <p>This page will display the form once WordPress is set up and the plugin is activated.</p>
    
    <h2>Newsletter Form</h2>
    [innovative_form id="1"]
    
    <h2>Contributors Form</h2>
    [innovative_form id="2"]
    
    <h2>Contact Form</h2>
    [innovative_form id="3"]
</body>
</html>
EOF

print_status "Test page created"

# Start PHP server
print_info "Starting WordPress server..."
print_warning "Server will start on port 8000"
print_info "Codespaces will automatically forward this port"

echo ""
print_status "🎉 WordPress setup complete!"
echo ""
print_info "📋 Next Steps:"
echo "1. Server will start automatically"
echo "2. Codespaces will show 'Application running on port 8000'"
echo "3. Click 'Open in Browser' when prompted"
echo "4. Complete WordPress installation wizard:"
echo "   - Site Title: TiD Forms Test"
echo "   - Username: admin"
echo "   - Password: (choose a strong password)"
echo "   - Email: your email"
echo "5. Login to WordPress admin"
echo "6. Go to Plugins → Activate 'Innovative Forms'"
echo "7. Go to Innovative Forms menu to test"
echo ""
print_warning "🔧 Testing Checklist:"
echo "□ Plugin activates without errors"
echo "□ Admin menu appears"
echo "□ Forms can be created"
echo "□ Shortcodes work on frontend"
echo "□ Form submissions process"
echo "□ Entries appear in admin"
echo "□ CSV export works"
echo ""

# Start the server
print_info "🌐 Starting PHP development server..."
php -S 0.0.0.0:8000

