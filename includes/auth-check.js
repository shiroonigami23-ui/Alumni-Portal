// Authentication and API helper functions
const IS_ADMIN_ROUTE = window.location.pathname.includes('/admin/');
const API_BASE = IS_ADMIN_ROUTE ? '../api' : 'api';
const LOGIN_PAGE = IS_ADMIN_ROUTE ? '../login.php' : 'login.php';

// Check authentication on page load
function checkAuth() {
    const token = localStorage.getItem('jwt_token');
    if (!token) {
        window.location.href = LOGIN_PAGE;
        return false;
    }
    return true;
}

// Make authenticated API calls
async function makeApiCall(endpoint, method = 'GET', body = null) {
    const token = localStorage.getItem('jwt_token');
    if (!token) {
        return { success: false, status: 'error', unauthorized: true, message: 'Not authenticated' };
    }

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
        const rawText = await response.text();
        let data = null;

        try {
            data = rawText ? JSON.parse(rawText) : {};
        } catch (_error) {
            data = {
                success: false,
                status: 'error',
                message: rawText ? rawText.slice(0, 300) : 'Invalid server response'
            };
        }

        if (response.status === 401) {
            const msg = String(data?.message || '').toLowerCase();
            const likelyExpiredSession = (
                msg.includes('token') ||
                msg.includes('authorization') ||
                msg.includes('unauthorized') ||
                msg.includes('session') ||
                msg.includes('expired')
            );

            if (likelyExpiredSession) {
                localStorage.removeItem('jwt_token');
                localStorage.removeItem('user_data');
                window.location.href = LOGIN_PAGE;
                return null;
            }

            return {
                ...(data || {}),
                success: false,
                status: 'error',
                unauthorized: true,
                message: data?.message || 'Access denied'
            };
        }

        if (!response.ok) {
            return {
                ...(data || {}),
                success: false,
                status: 'error',
                message: data?.message || `Request failed (${response.status})`
            };
        }

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
        try {
            const parsed = JSON.parse(text);
            if (parsed && typeof parsed === 'object' && Object.prototype.hasOwnProperty.call(parsed, 'content')) {
                return String(parsed.content || '');
            }
        } catch (_ignored) {}
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
    window.location.href = LOGIN_PAGE;
}
