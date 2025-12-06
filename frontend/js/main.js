/**
 * DCROP System - Main JavaScript Utilities
 * File: frontend/js/main.js
 * Common functions used across the application
 */

// API Base URL
const API_BASE_URL = 'http://localhost:8080/DCROP/backend';

/**
 * Check if user is authenticated
 * @returns {boolean}
 */
function isAuthenticated() {
    const user = localStorage.getItem('user');
    const role = localStorage.getItem('userRole');
    return user !== null && role !== null;
}

/**
 * Get current user data
 * @returns {object|null}
 */
function getCurrentUser() {
    const userStr = localStorage.getItem('user');
    return userStr ? JSON.parse(userStr) : null;
}

/**
 * Get current user role
 * @returns {string|null}
 */
function getCurrentUserRole() {
    return localStorage.getItem('userRole');
}

/**
 * Logout user
 */
async function logout() {
    const user = getCurrentUser();
    const role = getCurrentUserRole();

    if (user && role) {
        try {
            // Call logout endpoint
            await fetch(`${API_BASE_URL}/index.php?endpoint=logout`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_id: user.id,
                    role: role
                })
            });
        } catch (error) {
            console.error('Logout error:', error);
        }
    }

    // Clear storage
    localStorage.removeItem('user');
    localStorage.removeItem('userRole');
    
    // Redirect to login
    window.location.href = '/DCROP/frontend/index.html';
}

/**
 * Show section in dashboard
 * @param {string} sectionName
 */
function showSection(sectionName) {
    // Hide all sections
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
    });

    // Remove active from all nav items
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });

    // Show selected section
    const targetSection = document.getElementById(sectionName + 'Section');
    if (targetSection) {
        targetSection.classList.add('active');
    }

    // Add active to clicked nav item
    const navItem = document.querySelector(`[data-section="${sectionName}"]`);
    if (navItem) {
        navItem.classList.add('active');
    }

    // Update page title
    const titles = {
        'overview': 'Dashboard Overview',
        'students': 'Students',
        'coordinators': 'Coordinators',
        'admins': 'Administrators',
        'attendance': 'Attendance',
        'messages': 'Messages',
        'reports': 'Reports',
        'upload': 'Upload Data',
        'activity': 'Activity Logs',
        'settings': 'System Settings',
        'profile': 'My Profile',
        'history': 'Attendance History'
    };

    const pageTitle = document.getElementById('pageTitle');
    if (pageTitle && titles[sectionName]) {
        pageTitle.textContent = titles[sectionName];
    }
}

/**
 * Initialize navigation
 */
function initializeNavigation() {
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.dataset.section;
            if (section) {
                showSection(section);
            }
        });
    });
}

/**
 * Format date to readable string
 * @param {string} dateString
 * @returns {string}
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

/**
 * Format date and time to readable string
 * @param {string} dateString
 * @returns {string}
 */
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Get time ago format
 * @param {string} timestamp
 * @returns {string}
 */
function getTimeAgo(timestamp) {
    const now = new Date();
    const then = new Date(timestamp);
    const diffMs = now - then;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} min${diffMins > 1 ? 's' : ''} ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
    return formatDate(timestamp);
}

/**
 * Show notification/message
 * @param {string} message
 * @param {string} type - 'success', 'error', 'warning', 'info'
 * @param {number} duration - milliseconds
 */
function showNotification(message, type = 'info', duration = 5000) {
    // Remove existing notifications
    const existing = document.querySelector('.notification-toast');
    if (existing) {
        existing.remove();
    }

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification-toast notification-${type}`;
    notification.textContent = message;

    // Add to body
    document.body.appendChild(notification);

    // Show notification
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);

    // Auto hide
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, duration);
}

/**
 * Validate email format
 * @param {string} email
 * @returns {boolean}
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Validate password strength
 * @param {string} password
 * @returns {object} {valid: boolean, message: string}
 */
function validatePassword(password) {
    if (password.length < 8) {
        return { valid: false, message: 'Password must be at least 8 characters long' };
    }
    if (!/[A-Z]/.test(password)) {
        return { valid: false, message: 'Password must contain at least one uppercase letter' };
    }
    if (!/[a-z]/.test(password)) {
        return { valid: false, message: 'Password must contain at least one lowercase letter' };
    }
    if (!/[0-9]/.test(password)) {
        return { valid: false, message: 'Password must contain at least one number' };
    }
    return { valid: true, message: 'Password is strong' };
}

/**
 * Debounce function
 * @param {function} func
 * @param {number} wait
 * @returns {function}
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Copy text to clipboard
 * @param {string} text
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showNotification('Copied to clipboard!', 'success', 2000);
    } catch (err) {
        console.error('Failed to copy:', err);
        showNotification('Failed to copy', 'error', 2000);
    }
}

/**
 * Download data as JSON file
 * @param {object} data
 * @param {string} filename
 */
function downloadJSON(data, filename) {
    const dataStr = JSON.stringify(data, null, 2);
    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename + '.json';
    link.click();
    URL.revokeObjectURL(url);
}

/**
 * Download data as CSV file
 * @param {array} data
 * @param {string} filename
 */
function downloadCSV(data, filename) {
    if (!data || data.length === 0) {
        showNotification('No data to export', 'warning');
        return;
    }

    // Get headers from first object
    const headers = Object.keys(data[0]);
    
    // Create CSV content
    let csv = headers.join(',') + '\n';
    
    data.forEach(row => {
        const values = headers.map(header => {
            const value = row[header] || '';
            // Escape commas and quotes
            return `"${String(value).replace(/"/g, '""')}"`;
        });
        csv += values.join(',') + '\n';
    });

    // Download
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename + '.csv';
    link.click();
    URL.revokeObjectURL(url);
}

/**
 * Confirm dialog wrapper
 * @param {string} message
 * @returns {boolean}
 */
function confirmAction(message) {
    return confirm(message);
}

/**
 * Handle API errors
 * @param {Error} error
 * @param {string} context
 */
function handleAPIError(error, context = '') {
    console.error(`API Error ${context}:`, error);
    
    if (error.message.includes('Failed to fetch')) {
        showNotification('Connection error. Please check your internet connection.', 'error');
    } else {
        showNotification(`An error occurred: ${error.message}`, 'error');
    }
}

/**
 * Initialize page (call on DOMContentLoaded)
 */
function initializePage() {
    // Initialize navigation if exists
    if (document.querySelector('.nav-item')) {
        initializeNavigation();
    }

    // Initialize logout button if exists
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirmAction('Are you sure you want to logout?')) {
                logout();
            }
        });
    }
}

/**
 * Safe fetch wrapper with error handling
 * @param {string} url
 * @param {object} options
 * @returns {Promise}
 */
async function safeFetch(url, options = {}) {
    try {
        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        return { success: true, data };
    } catch (error) {
        console.error('Fetch error:', error);
        return { success: false, error: error.message };
    }
}

/**
 * Get status badge HTML
 * @param {string} status
 * @returns {string}
 */
function getStatusBadge(status) {
    const badges = {
        'present': '<span class="status-badge status-present">Present</span>',
        'absent': '<span class="status-badge status-absent">Absent</span>',
        'pending': '<span class="status-badge status-pending">Pending</span>',
        'verified': '<span class="status-badge status-verified">Verified</span>',
        'unverified': '<span class="status-badge status-unverified">Unverified</span>'
    };
    return badges[status] || status;
}

/**
 * Format number with commas
 * @param {number} num
 * @returns {string}
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/**
 * Calculate percentage
 * @param {number} part
 * @param {number} whole
 * @returns {string}
 */
function calculatePercentage(part, whole) {
    if (whole === 0) return '0%';
    return ((part / whole) * 100).toFixed(1) + '%';
}

/**
 * Truncate text
 * @param {string} text
 * @param {number} maxLength
 * @returns {string}
 */
function truncateText(text, maxLength) {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

/**
 * Initialize on page load
 */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePage);
} else {
    initializePage();
}

// Export functions for use in other scripts
window.DCROP = {
    isAuthenticated,
    getCurrentUser,
    getCurrentUserRole,
    logout,
    showSection,
    formatDate,
    formatDateTime,
    getTimeAgo,
    showNotification,
    validateEmail,
    validatePassword,
    debounce,
    copyToClipboard,
    downloadJSON,
    downloadCSV,
    confirmAction,
    handleAPIError,
    safeFetch,
    getStatusBadge,
    formatNumber,
    calculatePercentage,
    truncateText,
    API_BASE_URL
};