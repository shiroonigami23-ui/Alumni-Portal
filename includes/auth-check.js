// Authentication and API helper functions
const IS_ADMIN_ROUTE = window.location.pathname.includes('/admin/');

function normalizePrefix(prefix) {
    let value = String(prefix || '').trim();
    if (!value) return '/';
    if (!value.startsWith('/')) value = '/' + value;
    if (!value.endsWith('/')) value += '/';
    return value;
}

function inferPortalPrefix() {
    const path = window.location.pathname || '/';
    const adminIdx = path.indexOf('/admin/');
    if (adminIdx >= 0) {
        return normalizePrefix(path.slice(0, adminIdx + 1));
    }
    // e.g. /alumni_portal/feed.php => /alumni_portal/
    const lastSlash = path.lastIndexOf('/');
    const inferred = lastSlash >= 0 ? path.slice(0, lastSlash + 1) : '/';
    return normalizePrefix(inferred);
}

function getPortalPrefix() {
    const explicit = window.PORTAL_BASE_PREFIX;
    if (typeof explicit === 'string' && explicit.trim() !== '') {
        return normalizePrefix(explicit);
    }
    return inferPortalPrefix();
}

function getApiBase() {
    return `${getPortalPrefix()}api`;
}

function getLoginPage() {
    return `${getPortalPrefix()}login.php`;
}

// Expose shared URL helpers for page scripts.
window.getPortalPrefix = getPortalPrefix;
window.getApiBase = getApiBase;
window.resolvePortalPath = function(path) {
    const clean = String(path || '').replace(/^\/+/, '');
    return `${getPortalPrefix()}${clean}`;
};
// Backward-compatible globals used by existing pages.
const API_BASE = getApiBase();
const LOGIN_PAGE = getLoginPage();
window.API_BASE = API_BASE;
window.LOGIN_PAGE = LOGIN_PAGE;

// Check authentication on page load
function checkAuth() {
    const token = localStorage.getItem('jwt_token');
    if (!token) {
        window.location.href = getLoginPage();
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
        const cleanEndpoint = String(endpoint || '').replace(/^\/+/, '');
        const response = await fetch(`${getApiBase()}/${cleanEndpoint}`, config);
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
                window.location.href = getLoginPage();
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
    window.location.href = getLoginPage();
}
