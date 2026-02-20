// Authentication and API helper functions
const IS_ADMIN_ROUTE = window.location.pathname.includes('/admin/');
const API_BASE = IS_ADMIN_ROUTE ? '../api' : 'api';

// Check authentication on page load
function checkAuth() {
    const token = localStorage.getItem('jwt_token');
    if (!token) {
        window.location.href = 'login.php';
        return false;
    }
    return true;
}

// Make authenticated API calls
async function makeApiCall(endpoint, method = 'GET', body = null) {
    const token = localStorage.getItem('jwt_token');
    const headers = {
        'Authorization': `Bearer ${token}`
    };

    const config = {
        method: method,
        headers: headers
    };

    if (body && (method === 'POST' || method === 'PUT' || method === 'PATCH' || method === 'DELETE')) {
        if (body instanceof FormData) {
            config.body = body;
        } else {
            headers['Content-Type'] = 'application/json';
            config.body = JSON.stringify(body);
        }
    }

    try {
        const response = await fetch(`${API_BASE}/${endpoint}`, config);
        
        // Handle 401 unauthorized
        if (response.status === 401) {
            localStorage.removeItem('jwt_token');
            window.location.href = 'login.php';
            return null;
        }

        const data = await response.json();
        return data;
    } catch (error) {
        console.error('API Error:', error);
        return { error: true, message: 'Network error occurred' };
    }
}

// Fetch text content from file path
async function fetchTextContent(filePath) {
    try {
        const response = await fetch(filePath);
        if (!response.ok) {
            return 'Content not available';
        }
        const text = await response.text();
        return text;
    } catch (error) {
        console.error('Error fetching text content:', error);
        return 'Content could not be loaded';
    }
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Get user role from localStorage
function getUserRole() {
    const userData = localStorage.getItem('user_data');
    return userData ? JSON.parse(userData).role : null;
}

// Logout function
function logout() {
    localStorage.removeItem('jwt_token');
    localStorage.removeItem('user_data');
    window.location.href = 'login.php';
}
