<?php
$isAdminPath = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$basePrefix = $isAdminPath ? '../' : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f172a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="RJIT Portal">
    <link rel="manifest" href="<?php echo $basePrefix; ?>manifest.webmanifest">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo $basePrefix; ?>assets/icons/app-icon-192.png">
    <link rel="apple-touch-icon" href="<?php echo $basePrefix; ?>assets/icons/app-icon-192.png">
    <title>RJIT Alumni Portal</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Roboto+Slab:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $basePrefix; ?>assets/css/variety-ui.css">
    <script src="<?php echo $basePrefix; ?>assets/js/variety-ui.js" defer></script>

    <!-- Custom Styles -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Roboto Slab', serif;
        }

        .live-indicator {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                opacity: 1;
            }
        }

        .admin-border {
            border-color: #f59e0b;
        }

        .faculty-badge {
            background-color: #3b82f6;
            color: white;
        }

        .portal-dialog-backdrop {
            background: rgba(15, 23, 42, 0.72);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .portal-dialog-card {
            background:
                radial-gradient(circle at top right, rgba(59, 130, 246, 0.18), transparent 38%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.98));
            box-shadow: 0 32px 80px rgba(15, 23, 42, 0.28);
        }

        .portal-dialog-card textarea {
            resize: vertical;
            min-height: 120px;
        }

        @media (max-width: 767px) {
            .portal-topbar .max-w-7xl {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }

            .portal-topbar .h-16 {
                height: 4.25rem;
            }

            .portal-brand-wordmark {
                font-size: 1.45rem;
                line-height: 1;
            }

            .portal-brand-mark {
                width: 2rem;
                height: 2rem;
            }

            .portal-topbar-actions {
                gap: 0.375rem;
            }

            .portal-topbar-actions .vu-theme-toggle {
                padding: 0.55rem;
                min-width: 2.5rem;
                min-height: 2.5rem;
            }

            .portal-topbar-icon-btn {
                padding: 0.55rem;
            }

            .portal-topbar-user-btn {
                gap: 0.25rem;
                padding-left: 0.35rem;
                padding-right: 0.35rem;
            }

            .portal-topbar-user-btn [data-lucide="chevron-down"] {
                display: none;
            }
        }
    </style>

    <!-- Auth Check Script -->
    <script src="<?php echo $basePrefix; ?>includes/auth-check.js?v=20260314b"></script>
    <script src="<?php echo $basePrefix; ?>assets/js/pwa.js" defer></script>
</head>

<body class="bg-gray-50">
    <!-- Live Stream Indicator (Hidden by default) -->
    <div id="liveStreamIndicator" class="hidden bg-red-600 text-white text-center py-2">
        <div class="flex items-center justify-center gap-2">
            <span class="live-indicator w-2 h-2 bg-white rounded-full"></span>
            <span>LIVE Stream is active!</span>
            <a href="#" class="underline ml-2">Join Now</a>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="bg-white shadow-md portal-topbar relative z-[80]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo and Brand -->
                <div class="flex min-w-0 items-center">
                    <div class="flex-shrink-0 flex items-center min-w-0">
                        <a href="#" onclick="goToDashboard(); return false;" class="flex min-w-0 items-center cursor-pointer hover:opacity-80 transition">
                            <i data-lucide="graduation-cap" class="portal-brand-mark h-8 w-8 flex-shrink-0 text-blue-600"></i>
                            <span class="portal-brand-wordmark ml-2 hidden truncate text-xl font-bold text-gray-900 sm:inline">RJIT Alumni Portal</span>
                            <span class="portal-brand-wordmark ml-2 truncate text-lg font-bold text-gray-900 sm:hidden">RJIT Portal</span>
                        </a>
                    </div>
                    <div id="live-indicator-container"></div>
                    <!-- Desktop Search -->
                    <div class="hidden md:ml-6 md:flex md:items-center md:space-x-4">
                        <div class="relative">
                            <input type="text"
                                id="globalSearch"
                                placeholder="Search alumni, posts, or events..."
                                class="w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <i data-lucide="search" class="absolute left-3 top-2.5 h-5 w-5 text-gray-400"></i>
                        </div>
                    </div>
                </div>

                <!-- Right Side Navigation -->
                <div class="portal-topbar-actions flex items-center space-x-4">
                    <button id="themeModeBtn" class="vu-theme-toggle inline-flex" type="button">
                        <i data-lucide="palette" class="h-4 w-4"></i>
                    </button>
                    <button id="installAppBtn" class="hidden px-3 py-1.5 border border-gray-300 rounded-full text-sm text-gray-700 hover:bg-gray-100">
                        Install App
                    </button>
                    <!-- Notifications -->
                    <div class="relative">
                        <button id="notificationBtn" class="portal-topbar-icon-btn p-2 rounded-full hover:bg-gray-100">
                            <i data-lucide="bell"></i>
                            <span id="notificationCount" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">0</span>
                        </button>
                        <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-[120]">
                            <div class="p-4">
                                <h3 class="font-semibold text-gray-900 mb-3">Notifications</h3>
                                <div id="notificationList" class="space-y-3 max-h-64 overflow-y-auto">
                                    <!-- Notifications will be loaded here -->
                                </div>
                                <a href="#" class="block text-center text-blue-600 hover:text-blue-800 text-sm mt-3">View All</a>
                            </div>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="relative">
                        <button id="userMenuBtn" class="portal-topbar-user-btn flex items-center space-x-2 rounded-lg p-2 hover:bg-gray-100">
                            <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center overflow-hidden">
                                <img id="userAvatarImage" src="" alt="Profile" class="h-8 w-8 rounded-full object-cover hidden">
                                <i id="userAvatarIcon" data-lucide="user"></i>
                            </div>
                            <span id="userName" class="hidden md:inline text-gray-700">Loading...</span>
                            <i data-lucide="chevron-down" class="h-4 w-4 text-gray-500"></i>
                        </button>

                        <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-[120]">
                            <div class="py-2">
                                <a href="<?php echo $basePrefix; ?>profile.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i data-lucide="user" class="h-4 w-4 mr-3"></i> My Profile
                                </a>
                                <a href="<?php echo $basePrefix; ?>settings.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i data-lucide="settings" class="h-4 w-4 mr-3"></i> Settings
                                </a>
                                <div class="border-t border-gray-100 my-1"></div>
                                <a href="#" onclick="logout()" class="flex items-center px-4 py-2 text-red-600 hover:bg-gray-100">
                                    <i data-lucide="log-out" class="h-4 w-4 mr-3"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div id="portalDialog" class="portal-dialog-backdrop fixed inset-0 z-[180] hidden items-end justify-center p-4 sm:items-center">
        <div class="portal-dialog-dismiss absolute inset-0" data-dialog-close="1"></div>
        <div class="portal-dialog-card relative z-[181] w-full max-w-lg overflow-hidden rounded-[28px] border border-white/50 p-6 sm:p-7">
            <div class="flex items-start gap-4">
                <div id="portalDialogIconWrap" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-blue-700 ring-1 ring-blue-200">
                    <i id="portalDialogIcon" data-lucide="message-square" class="h-5 w-5"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p id="portalDialogEyebrow" class="text-[11px] font-semibold uppercase tracking-[0.24em] text-blue-600">RJIT Portal</p>
                    <h3 id="portalDialogTitle" class="mt-1 text-xl font-semibold text-slate-900">Notice</h3>
                    <p id="portalDialogMessage" class="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-600"></p>
                </div>
            </div>
            <div id="portalDialogInputWrap" class="mt-5 hidden">
                <label id="portalDialogInputLabel" for="portalDialogInput" class="mb-2 block text-sm font-medium text-slate-700">Update</label>
                <textarea id="portalDialogInput" class="w-full rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-sm text-slate-800 shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200"></textarea>
            </div>
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button id="portalDialogCancelBtn" type="button" class="rounded-full border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                <button id="portalDialogConfirmBtn" type="button" class="rounded-full bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Okay</button>
            </div>
        </div>
    </div>

    <script>
        window.PORTAL_BASE_PREFIX = "<?php echo $basePrefix; ?>";
        function sanitizeCachedAvatarUrl(url) {
            const value = String(url || '').trim();
            if (!value) return '';
            if (value.startsWith('data:image/')) return value;
            if (/\.php(\?|$)/i.test(value)) return value;
            const normalized = value.replace(/\\/g, '/').toLowerCase();
            if (normalized.includes('storage/profiles/') || normalized.includes('storage/covers/')) return '';
            if (!/^https?:\/\//i.test(value) && !value.startsWith('/') && !normalized.startsWith('api/asset.php')) {
                return '';
            }
            try {
                const resolved = new URL(value, window.location.origin);
                if (
                    resolved.origin === window.location.origin &&
                    (
                        resolved.pathname.toLowerCase().includes('/storage/profiles/') ||
                        resolved.pathname.toLowerCase().includes('/storage/covers/')
                    )
                ) {
                    return '';
                }
                return resolved.href;
            } catch (_) {
                return '';
            }
        }
        // Initialize Lucide icons
        lucide.createIcons();
        let __lastNotifKey = '';
        let __portalDialogState = null;

        function closePortalDialog(result) {
            const root = document.getElementById('portalDialog');
            if (!root) return;
            root.classList.add('hidden');
            root.classList.remove('flex');
            const resolver = __portalDialogState && typeof __portalDialogState.resolve === 'function'
                ? __portalDialogState.resolve
                : null;
            __portalDialogState = null;
            if (resolver) {
                resolver(result);
            }
        }

        function openPortalDialog(options = {}) {
            const root = document.getElementById('portalDialog');
            const title = document.getElementById('portalDialogTitle');
            const message = document.getElementById('portalDialogMessage');
            const eyebrow = document.getElementById('portalDialogEyebrow');
            const iconWrap = document.getElementById('portalDialogIconWrap');
            const icon = document.getElementById('portalDialogIcon');
            const inputWrap = document.getElementById('portalDialogInputWrap');
            const input = document.getElementById('portalDialogInput');
            const inputLabel = document.getElementById('portalDialogInputLabel');
            const confirmBtn = document.getElementById('portalDialogConfirmBtn');
            const cancelBtn = document.getElementById('portalDialogCancelBtn');
            if (!root || !title || !message || !eyebrow || !iconWrap || !icon || !inputWrap || !input || !inputLabel || !confirmBtn || !cancelBtn) {
                return Promise.resolve(null);
            }

            const mode = String(options.mode || 'alert');
            title.textContent = String(options.title || 'Notice');
            message.textContent = String(options.message || '');
            eyebrow.textContent = String(options.eyebrow || 'RJIT Portal');
            icon.setAttribute('data-lucide', String(options.icon || 'message-square'));
            iconWrap.className = `flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl ring-1 ${
                options.iconTone === 'danger'
                    ? 'bg-rose-100 text-rose-700 ring-rose-200'
                    : options.iconTone === 'success'
                        ? 'bg-emerald-100 text-emerald-700 ring-emerald-200'
                        : 'bg-blue-100 text-blue-700 ring-blue-200'
            }`;
            confirmBtn.textContent = String(options.confirmText || 'Okay');
            cancelBtn.textContent = String(options.cancelText || 'Cancel');
            cancelBtn.classList.toggle('hidden', mode === 'alert');
            inputWrap.classList.toggle('hidden', mode !== 'prompt');
            inputLabel.textContent = String(options.inputLabel || 'Update');
            input.value = String(options.defaultValue || '');
            root.classList.remove('hidden');
            root.classList.add('flex');
            lucide.createIcons();

            if (mode === 'prompt') {
                setTimeout(() => input.focus(), 0);
            } else {
                setTimeout(() => confirmBtn.focus(), 0);
            }

            return new Promise((resolve) => {
                __portalDialogState = { mode, resolve };
            });
        }

        document.addEventListener('click', function(event) {
            const root = document.getElementById('portalDialog');
            if (!root || root.classList.contains('hidden')) return;
            if (event.target && event.target.matches('[data-dialog-close="1"]')) {
                closePortalDialog(__portalDialogState && __portalDialogState.mode === 'alert' ? true : null);
            }
        });

        document.addEventListener('keydown', function(event) {
            const root = document.getElementById('portalDialog');
            if (!root || root.classList.contains('hidden')) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                closePortalDialog(__portalDialogState && __portalDialogState.mode === 'alert' ? true : null);
            }
        });

        window.portalDialog = {
            alert: function(options = {}) {
                return openPortalDialog({ ...options, mode: 'alert' }).then(() => true);
            },
            confirm: function(options = {}) {
                return openPortalDialog({ ...options, mode: 'confirm' }).then((value) => value === true);
            },
            prompt: function(options = {}) {
                return openPortalDialog({ ...options, mode: 'prompt' }).then((value) => {
                    if (typeof value !== 'string') return null;
                    return value;
                });
            }
        };

        // Load user data
        document.addEventListener('DOMContentLoaded', function() {
            const userData = localStorage.getItem('user_data');
            if (userData) {
                const user = JSON.parse(userData);
                document.getElementById('userName').textContent = user.email || user.name || 'Member';
                const avatar = sanitizeCachedAvatarUrl(user.profile_picture || user.profile_picture_url || '');
                if (avatar) {
                    const img = document.getElementById('userAvatarImage');
                    const icon = document.getElementById('userAvatarIcon');
                    img.onerror = function() {
                        this.onerror = null;
                        this.src = '';
                        this.classList.add('hidden');
                        if (icon) icon.classList.remove('hidden');
                    };
                    img.src = avatar;
                    img.classList.remove('hidden');
                    if (icon) icon.classList.add('hidden');
                }

                // Add role badge if admin or faculty
                if (user.role === 'admin') {
                    const userMenu = document.getElementById('userMenuBtn');
                    userMenu.classList.add('admin-border', 'border-2');
                }
            }

            // Setup search functionality
            const searchInput = document.getElementById('globalSearch');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && this.value.trim()) {
                        window.location.href = `${window.PORTAL_BASE_PREFIX}discovery.php?search=${encodeURIComponent(this.value.trim())}`;
                    }
                });
            }

            // Setup notification dropdown
            const notificationBtn = document.getElementById('notificationBtn');
            const notificationDropdown = document.getElementById('notificationDropdown');

            notificationBtn.addEventListener('click', async function(e) {
                e.stopPropagation();
                const isOpening = notificationDropdown.classList.contains('hidden');
                notificationDropdown.classList.toggle('hidden');
                if (isOpening) {
                    const countElement = document.getElementById('notificationCount');
                    if (countElement) {
                        countElement.textContent = '0';
                        countElement.classList.add('hidden');
                    }
                    try {
                        await makeApiCall('mark_notif_read.php', 'POST', {});
                    } catch (error) {
                        console.error('Error marking notifications as read:', error);
                    }
                }
                loadNotifications();
            });

            // Setup user dropdown
            const userMenuBtn = document.getElementById('userMenuBtn');
            const userDropdown = document.getElementById('userDropdown');

            userMenuBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdown.classList.toggle('hidden');
            });

            // Close dropdowns when clicking outside
            document.addEventListener('click', function() {
                notificationDropdown.classList.add('hidden');
                userDropdown.classList.add('hidden');
            });

            const dialogConfirmBtn = document.getElementById('portalDialogConfirmBtn');
            const dialogCancelBtn = document.getElementById('portalDialogCancelBtn');
            const dialogInput = document.getElementById('portalDialogInput');

            if (dialogConfirmBtn) {
                dialogConfirmBtn.addEventListener('click', function() {
                    if (!__portalDialogState) return;
                    if (__portalDialogState.mode === 'prompt') {
                        closePortalDialog(dialogInput ? dialogInput.value : '');
                        return;
                    }
                    closePortalDialog(true);
                });
            }

            if (dialogCancelBtn) {
                dialogCancelBtn.addEventListener('click', function() {
                    closePortalDialog(__portalDialogState && __portalDialogState.mode === 'alert' ? true : null);
                });
            }

            if (dialogInput) {
                dialogInput.addEventListener('keydown', function(event) {
                    if (!__portalDialogState || __portalDialogState.mode !== 'prompt') return;
                    if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                        event.preventDefault();
                        closePortalDialog(dialogInput.value);
                    }
                });
            }

            // Check for live streams
            checkLiveStreams();
            setInterval(checkLiveStreams, 30000);
            loadNotifications();
            setInterval(loadNotifications, 20000);

            refreshHeaderUser();
        });

        async function refreshHeaderUser() {
            try {
                const response = await makeApiCall('me.php');
                if (!response || !response.success || !response.data) return;
                const user = response.data;
                localStorage.setItem('user_data', JSON.stringify(user));
                document.getElementById('userName').textContent = user.email || user.name || 'Member';
                const avatar = sanitizeCachedAvatarUrl(user.profile_picture || user.profile_picture_url || '');
                if (avatar) {
                    const img = document.getElementById('userAvatarImage');
                    const icon = document.getElementById('userAvatarIcon');
                    img.onerror = function() {
                        this.onerror = null;
                        this.src = '';
                        this.classList.add('hidden');
                        if (icon) icon.classList.remove('hidden');
                    };
                    img.src = avatar;
                    img.classList.remove('hidden');
                    if (icon) icon.classList.add('hidden');
                } else {
                    const img = document.getElementById('userAvatarImage');
                    const icon = document.getElementById('userAvatarIcon');
                    if (img) img.classList.add('hidden');
                    if (icon) icon.classList.remove('hidden');
                }

                if (user.role === 'admin') {
                    const userMenu = document.getElementById('userMenuBtn');
                    userMenu.classList.add('admin-border', 'border-2');
                }
            } catch (e) {
                console.error('Unable to refresh header user', e);
            }
        }

        async function loadNotifications() {
            try {
                const response = await makeApiCall('get_notifications.php');
                if (response && (response.success || response.status === 'success')) {
                    const notificationList = document.getElementById('notificationList');
                    const countElement = document.getElementById('notificationCount');
                    const notifications = response.data || response.notifications || [];

                    if (notifications.length > 0) {
                        notificationList.innerHTML = notifications.map(notif => `
                            <div
                                class="p-3 ${(notif.read_at ? 'bg-gray-50' : 'bg-blue-50/70')} rounded-lg notification-item cursor-pointer"
                                data-notification-id="${notif.notification_id || ''}"
                                data-action-type="${notif.action_type || ''}"
                                data-call-type="${notif.call_type || ''}"
                                data-target-url="${encodeURIComponent(notif.target_url || '')}">
                                <div class="flex items-start">
                                    <i data-lucide="${notif.icon || 'bell'}" class="h-4 w-4 mt-1 mr-3 text-blue-600"></i>
                                    <div>
                                        <p class="text-sm text-gray-800">${notif.message}</p>
                                        ${notif.action_type === 'join-call' ? `<span class="mt-2 inline-flex items-center rounded-full bg-white px-2 py-1 text-[11px] font-semibold text-blue-700 ring-1 ring-blue-100">Join ${(notif.call_type === 'video' ? 'video' : 'audio')} call</span>` : ''}
                                        <p class="text-xs text-gray-500 mt-1">${formatDate(notif.created_at)}</p>
                                    </div>
                                </div>
                            </div>
                        `).join('');

                        countElement.textContent = String(response.unread_count ?? notifications.filter(n => !n.read_at).length);
                        countElement.classList.remove('hidden');
                        bindNotificationReadActions();
                        notifyDesktopIfNeeded(notifications, response.unread_count ?? notifications.filter(n => !n.read_at).length);
                    } else {
                        notificationList.innerHTML = '<p class="text-gray-500 text-center py-4">No notifications</p>';
                        countElement.classList.add('hidden');
                    }

                    // Re-initialize icons
                    lucide.createIcons();
                }
            } catch (error) {
                console.error('Error loading notifications:', error);
            }
        }

        function bindNotificationReadActions() {
            document.querySelectorAll('.notification-item').forEach((item) => {
                if (item.dataset.bound === '1') return;
                item.dataset.bound = '1';
                item.addEventListener('click', async () => {
                    const id = Number(item.getAttribute('data-notification-id') || 0);
                    const actionType = String(item.getAttribute('data-action-type') || '');
                    const encodedTarget = String(item.getAttribute('data-target-url') || '');
                    const targetUrl = encodedTarget ? decodeURIComponent(encodedTarget) : '';
                    if (id) {
                        await makeApiCall('mark_notif_read.php', 'POST', { notification_id: id });
                    }
                    if (actionType === 'join-call' && targetUrl) {
                        window.open(targetUrl, '_blank');
                        loadNotifications();
                        return;
                    }
                    if (targetUrl) {
                        window.location.href = targetUrl;
                        return;
                    }
                    loadNotifications();
                });
            });
        }

        function notifyDesktopIfNeeded(notifications, unreadCount) {
            if (!('Notification' in window) || Notification.permission !== 'granted') return;
            if (!Array.isArray(notifications) || notifications.length === 0) return;
            const latest = notifications[0];
            if (!latest || latest.read_at) return;
            const key = `${latest.notification_id || ''}|${latest.created_at || ''}|${unreadCount}`;
            if (key === __lastNotifKey) return;
            __lastNotifKey = key;
            const n = new Notification('RJIT Alumni Portal', {
                body: latest.message || 'You have a new notification',
                icon: `${window.PORTAL_BASE_PREFIX}assets/icons/app-icon-192.png`
            });
            n.onclick = () => {
                window.focus();
                if (latest.action_type === 'join-call' && latest.target_url) {
                    window.open(latest.target_url, '_blank');
                    return;
                }
                if (latest.target_url) {
                    window.location.href = latest.target_url;
                    return;
                }
                window.location.href = `${window.PORTAL_BASE_PREFIX}feed.php`;
            };
        }

        async function checkLiveStreams() {
            try {
                const response = await makeApiCall('get_active_streams.php');
                if (response && response.success && response.data && response.data.length > 0) {
                    const indicator = document.getElementById('liveStreamIndicator');
                    indicator.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Error checking live streams:', error);
            }
        }
    </script>

    <script>
        // Role-based dashboard navigation
        function goToDashboard() {
            const userData = localStorage.getItem('user_data');
            if (userData) {
                const user = JSON.parse(userData);
                if (user.role === 'admin') {
                    window.location.href = `${window.PORTAL_BASE_PREFIX}admin/dashboard.php`;
                } else {
                    window.location.href = `${window.PORTAL_BASE_PREFIX}dashboard.php`;
                }
            } else {
                window.location.href = `${window.PORTAL_BASE_PREFIX}dashboard.php`;
            }
        }
    </script>
