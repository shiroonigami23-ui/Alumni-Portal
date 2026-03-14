(function() {
    if (window.__portalAuthCheckLoaded) {
        return;
    }
    window.__portalAuthCheckLoaded = true;

    window.IS_ADMIN_ROUTE = window.location.pathname.includes('/admin/');

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

    function resolvePortalPath(path) {
        const clean = String(path || '').replace(/^\/+/, '');
        return `${getPortalPrefix()}${clean}`;
    }

    function checkAuth() {
        const token = localStorage.getItem('jwt_token');
        if (!token) {
            window.location.href = getLoginPage();
            return false;
        }
        return true;
    }

    let csrfFetchPromise = null;
    async function ensureCsrfToken() {
        const existing = localStorage.getItem('csrf_token');
        if (existing) return existing;
        if (csrfFetchPromise) return csrfFetchPromise;

        csrfFetchPromise = (async () => {
            try {
                const token = localStorage.getItem('jwt_token');
                if (!token) return '';

                const response = await fetch(`${getApiBase()}/csrf_token.php`, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        Authorization: `Bearer ${token}`
                    }
                });

                const raw = await response.text();
                let data = {};
                try {
                    data = raw ? JSON.parse(raw) : {};
                } catch (_error) {
                    data = {};
                }

                const csrf = String(data?.csrf_token || '').trim();
                if (csrf) {
                    localStorage.setItem('csrf_token', csrf);
                }
                return csrf;
            } catch (_error) {
                return '';
            } finally {
                csrfFetchPromise = null;
            }
        })();

        return csrfFetchPromise;
    }

    async function makeApiCall(endpoint, method = 'GET', body = null) {
        const token = localStorage.getItem('jwt_token');
        if (!token) {
            return { success: false, status: 'error', unauthorized: true, message: 'Not authenticated' };
        }

        const headers = {
            Authorization: `Bearer ${token}`
        };

        const config = {
            method,
            headers,
            credentials: 'same-origin'
        };

        if (body && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
            const csrfToken = await ensureCsrfToken();
            if (csrfToken) {
                headers['X-CSRF-TOKEN'] = csrfToken;
            }

            if (body instanceof FormData) {
                if (csrfToken && !body.has('csrf_token')) {
                    body.append('csrf_token', csrfToken);
                }
                config.body = body;
            } else {
                headers['Content-Type'] = 'application/json';
                const payload = (body && typeof body === 'object') ? { ...body } : body;
                if (
                    csrfToken &&
                    payload &&
                    typeof payload === 'object' &&
                    !Object.prototype.hasOwnProperty.call(payload, 'csrf_token')
                ) {
                    payload.csrf_token = csrfToken;
                }
                config.body = JSON.stringify(payload);
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
                    localStorage.removeItem('csrf_token');
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
            return { success: false, status: 'error', message: 'Network error occurred' };
        }
    }

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

    const PORTAL_TIME_ZONE = 'Asia/Kolkata';
    const PORTAL_TIME_LOCALE = 'en-IN';

    function parsePortalDate(dateInput) {
        if (!dateInput) return null;
        if (dateInput instanceof Date) {
            return Number.isNaN(dateInput.getTime()) ? null : new Date(dateInput.getTime());
        }
        if (typeof dateInput === 'number') {
            const numericDate = new Date(dateInput);
            return Number.isNaN(numericDate.getTime()) ? null : numericDate;
        }
        let normalized = String(dateInput).trim();
        if (!normalized) return null;
        normalized = normalized.replace(' ', 'T');
        if (/^\d{4}-\d{2}-\d{2}$/.test(normalized)) {
            normalized += 'T00:00:00Z';
        } else if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2}(\.\d{1,6})?)?$/.test(normalized)) {
            normalized += 'Z';
        }
        const parsed = new Date(normalized);
        return Number.isNaN(parsed.getTime()) ? null : parsed;
    }

    function formatPortalParts(dateInput, options) {
        const parsed = parsePortalDate(dateInput);
        if (!parsed) return '';
        return parsed.toLocaleString(PORTAL_TIME_LOCALE, {
            timeZone: PORTAL_TIME_ZONE,
            ...options
        });
    }

    function formatRelativePortalDate(dateInput) {
        const parsed = parsePortalDate(dateInput);
        if (!parsed) return '';
        const diffMs = Date.now() - parsed.getTime();
        const safeDiff = Number.isFinite(diffMs) ? Math.max(0, diffMs) : 0;
        const diffMins = Math.floor(safeDiff / 60000);
        const diffHours = Math.floor(safeDiff / 3600000);
        const diffDays = Math.floor(safeDiff / 86400000);

        if (diffMins < 1) return 'just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;
        return formatPortalParts(parsed, { month: 'short', day: 'numeric' });
    }

    function formatDate(dateInput, format = 'relative') {
        if (format && typeof format === 'object') {
            return formatPortalParts(dateInput, format);
        }
        switch (format) {
            case 'relative':
                return formatRelativePortalDate(dateInput);
            case 'MMMM YYYY':
                return formatPortalParts(dateInput, { month: 'long', year: 'numeric' });
            case 'short-date':
                return formatPortalParts(dateInput, { month: 'short', day: 'numeric' });
            case 'date':
                return formatPortalParts(dateInput, { year: 'numeric', month: 'short', day: 'numeric' });
            case 'date-time':
                return formatPortalParts(dateInput, {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            case 'time':
                return formatPortalParts(dateInput, {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });
            case 'HH:mm:ss':
                return formatPortalParts(dateInput, {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                });
            default:
                return formatPortalParts(dateInput, {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
        }
    }

    function getUserRole() {
        const userData = localStorage.getItem('user_data');
        return userData ? JSON.parse(userData).role : null;
    }

    function logout() {
        localStorage.removeItem('jwt_token');
        localStorage.removeItem('user_data');
        localStorage.removeItem('csrf_token');
        window.location.href = getLoginPage();
    }

    window.getPortalPrefix = getPortalPrefix;
    window.getApiBase = getApiBase;
    window.resolvePortalPath = resolvePortalPath;
    window.API_BASE = getApiBase();
    window.LOGIN_PAGE = getLoginPage();
    window.checkAuth = checkAuth;
    window.ensureCsrfToken = ensureCsrfToken;
    window.makeApiCall = makeApiCall;
    window.fetchTextContent = fetchTextContent;
    window.formatDate = formatDate;
    window.parsePortalDate = parsePortalDate;
    window.portalTime = {
        timeZone: PORTAL_TIME_ZONE,
        locale: PORTAL_TIME_LOCALE,
        parse: parsePortalDate,
        format: formatDate,
        formatRelative: formatRelativePortalDate,
        formatDate: (dateInput) => formatDate(dateInput, 'date'),
        formatShortDate: (dateInput) => formatDate(dateInput, 'short-date'),
        formatDateTime: (dateInput) => formatDate(dateInput, 'date-time'),
        formatTime: (dateInput) => formatDate(dateInput, 'time')
    };
    window.getUserRole = getUserRole;
    window.logout = logout;
})();
