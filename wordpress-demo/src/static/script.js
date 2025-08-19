// WordPress Admin JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize the admin interface
    initializeAdmin();
});

function initializeAdmin() {
    // Add click handlers for menu items
    const menuItems = document.querySelectorAll('.wp-menu-item');
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            const section = this.getAttribute('data-section');
            showSection(section);
            
            // Update active menu item
            menuItems.forEach(mi => mi.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // Show dashboard by default
    showSection('dashboard');
}

function showSection(sectionId) {
    // Hide all sections
    const sections = document.querySelectorAll('.wp-section');
    sections.forEach(section => {
        section.classList.remove('active');
    });
    
    // Show selected section
    const targetSection = document.getElementById(sectionId);
    if (targetSection) {
        targetSection.classList.add('active');
    }
}

// Form Management Functions
function createNewForm() {
    showNotification('Form creation wizard would open here', 'info');
}

function editForm(formId) {
    showNotification(`Editing form ${formId}`, 'info');
}

function viewEntries(formId) {
    // Switch to entries section and filter by form
    showSection('entries');
    document.querySelector('.wp-menu-item[data-section="entries"]').classList.add('active');
    document.querySelector('.wp-menu-item[data-section="forms"]').classList.remove('active');
    
    // Filter entries
    const filterSelect = document.getElementById('formFilter');
    if (filterSelect) {
        filterSelect.value = formId.toString();
        filterEntries();
    }
}

function previewForm(formId) {
    let formName = '';
    let previewId = '';
    
    switch(formId) {
        case 1:
            formName = 'Newsletter Subscription';
            previewId = 'newsletter';
            break;
        case 2:
            formName = 'Contributors Registration';
            previewId = 'contributors';
            break;
        case 3:
            formName = 'Contact Form';
            previewId = 'contact';
            break;
    }
    
    // Switch to frontend section and show the specific form
    showSection('frontend');
    document.querySelector('.wp-menu-item[data-section="frontend"]').classList.add('active');
    document.querySelector('.wp-menu-item[data-section="forms"]').classList.remove('active');
    
    // Show the specific preview
    showPreview(previewId);
}

// Entry Management Functions
function filterEntries() {
    const filter = document.getElementById('formFilter').value;
    const rows = document.querySelectorAll('.entries-table tbody tr');
    
    rows.forEach(row => {
        if (filter === 'all') {
            row.style.display = '';
        } else {
            const formCell = row.cells[1].textContent.toLowerCase();
            const shouldShow = 
                (filter === '1' && formCell.includes('newsletter')) ||
                (filter === '2' && formCell.includes('contributors')) ||
                (filter === '3' && formCell.includes('contact'));
            
            row.style.display = shouldShow ? '' : 'none';
        }
    });
    
    showNotification(`Filtered entries for form filter: ${filter}`, 'success');
}

function exportEntries() {
    // Simulate CSV export
    const csvContent = `ID,Form,Name,Email,Date
127,Newsletter,John Doe,john@example.com,2024-08-19 17:30
126,Contact,Jane Smith,jane@example.com,2024-08-19 16:45
125,Contributors,Mike Johnson,mike@example.com,2024-08-19 15:20`;
    
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'form-entries.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    
    showNotification('Entries exported to CSV successfully!', 'success');
}

function viewEntry(entryId) {
    const modalContent = `
        <h2>Entry Details - ID: ${entryId}</h2>
        <div style="margin-top: 20px;">
            <p><strong>Form:</strong> Newsletter Subscription</p>
            <p><strong>Name:</strong> John Doe</p>
            <p><strong>Email:</strong> john@example.com</p>
            <p><strong>Country:</strong> United States</p>
            <p><strong>Interests:</strong> Beta Readers, Updates</p>
            <p><strong>GDPR Consent:</strong> Yes</p>
            <p><strong>Submitted:</strong> 2024-08-19 17:30:15</p>
            <p><strong>IP Address:</strong> 192.168.1.100</p>
        </div>
    `;
    
    showModal(modalContent);
}

function deleteEntry(entryId) {
    if (confirm(`Are you sure you want to delete entry ${entryId}?`)) {
        // Remove the row from the table
        const rows = document.querySelectorAll('.entries-table tbody tr');
        rows.forEach(row => {
            if (row.cells[0].textContent === entryId.toString()) {
                row.remove();
            }
        });
        
        showNotification(`Entry ${entryId} deleted successfully!`, 'success');
    }
}

// Frontend Preview Functions
function showPreview(previewType) {
    // Hide all previews
    const previews = document.querySelectorAll('.form-preview');
    previews.forEach(preview => {
        preview.classList.remove('active');
    });
    
    // Show selected preview
    const targetPreview = document.getElementById(`${previewType}-preview`);
    if (targetPreview) {
        targetPreview.classList.add('active');
    }
    
    // Update tab buttons
    const tabButtons = document.querySelectorAll('.tab-btn');
    tabButtons.forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Find and activate the correct tab
    tabButtons.forEach(btn => {
        if (btn.textContent.toLowerCase().includes(previewType) || 
            (previewType === 'newsletter' && btn.textContent.includes('Newsletter')) ||
            (previewType === 'contributors' && btn.textContent.includes('Contributors')) ||
            (previewType === 'contact' && btn.textContent.includes('Contact'))) {
            btn.classList.add('active');
        }
    });
}

// Modal Functions
function showModal(content) {
    const modal = document.getElementById('formModal');
    const modalContent = document.getElementById('modalContent');
    
    modalContent.innerHTML = content;
    modal.style.display = 'block';
}

function closeModal() {
    const modal = document.getElementById('formModal');
    modal.style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('formModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}

// Notification System
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `wp-notification wp-notification-${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" style="background: none; border: none; color: inherit; cursor: pointer; margin-left: 10px;">&times;</button>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 50px;
        right: 20px;
        background: ${type === 'success' ? '#00a32a' : type === 'error' ? '#d63638' : '#2271b1'};
        color: white;
        padding: 12px 16px;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        z-index: 100001;
        display: flex;
        align-items: center;
        max-width: 300px;
        font-size: 13px;
        animation: slideIn 0.3s ease;
    `;
    
    // Add animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    `;
    document.head.appendChild(style);
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Form Submission Handlers (for demo purposes)
document.addEventListener('submit', function(e) {
    if (e.target.closest('.innovative-form')) {
        e.preventDefault();
        showNotification('Form submitted successfully! (Demo mode)', 'success');
    }
});

// Settings Save Handler
function saveSettings() {
    showNotification('Settings saved successfully!', 'success');
}

// Add some demo interactions
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to form items
    const formItems = document.querySelectorAll('.form-item');
    formItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
            this.style.transition = 'all 0.3s ease';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
        });
    });
    
    // Add click animation to buttons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(btn => {
        btn.addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
        });
    });
});

// Simulate real-time updates
setInterval(() => {
    // Update stats occasionally
    const statsNumbers = document.querySelectorAll('.stat .number');
    if (Math.random() > 0.95) { // 5% chance every interval
        const randomStat = statsNumbers[Math.floor(Math.random() * statsNumbers.length)];
        if (randomStat && randomStat.textContent !== '89%') {
            const currentValue = parseInt(randomStat.textContent);
            if (!isNaN(currentValue)) {
                randomStat.textContent = (currentValue + 1).toString();
                randomStat.style.color = '#00a32a';
                setTimeout(() => {
                    randomStat.style.color = '#2271b1';
                }, 1000);
            }
        }
    }
}, 3000);

console.log('WordPress Admin Interface Loaded - Innovative Forms Plugin Demo');

