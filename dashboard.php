<?php
session_start();
$pageTitle = 'Dashboard - RJIT Alumni Portal';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Main Content -->
<div class="md:pl-64">
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <style>
                @media (max-width: 767px) {
                    .dashboard-stats-grid {
                        gap: 1rem;
                    }

                    .dashboard-stat-card {
                        padding: 1.1rem;
                    }

                    .dashboard-stat-card .dashboard-stat-icon {
                        padding: 0.8rem;
                    }

                    .dashboard-mobile-secondary {
                        display: none;
                    }

                    .dashboard-hero-copy {
                        max-width: 17rem;
                    }
                }
            </style>
            <!-- Welcome Section -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div class="dashboard-hero-copy">
                        <h1 class="text-2xl font-bold text-gray-900" id="welcomeMessage">Welcome back!</h1>
                        <p class="text-gray-600 mt-1">Here's what's happening in your community</p>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="dashboard-stats-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="dashboard-stat-card bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="dashboard-stat-icon p-3 rounded-lg bg-blue-100">
                            <i data-lucide="users" class="h-6 w-6 text-blue-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Connections</p>
                            <p id="connectionsCount" class="text-2xl font-bold text-gray-900">0</p>
                        </div>
                    </div>
                </div>

                <div class="dashboard-stat-card bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="dashboard-stat-icon p-3 rounded-lg bg-green-100">
                            <i data-lucide="message-square" class="h-6 w-6 text-green-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Unread Messages</p>
                            <p id="unreadMessagesCount" class="text-2xl font-bold text-gray-900">0</p>
                        </div>
                    </div>
                </div>

                <div class="dashboard-stat-card bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="dashboard-stat-icon p-3 rounded-lg bg-purple-100">
                            <i data-lucide="calendar" class="h-6 w-6 text-purple-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Upcoming Events</p>
                            <p id="upcomingEventsCount" class="text-2xl font-bold text-gray-900">0</p>
                        </div>
                    </div>
                </div>

                <div class="dashboard-stat-card dashboard-mobile-secondary bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="dashboard-stat-icon p-3 rounded-lg bg-amber-100">
                            <i data-lucide="bell" class="h-6 w-6 text-amber-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Notifications</p>
                            <p id="notificationsCount" class="text-2xl font-bold text-gray-900">0</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Jump Back In -->
            <div class="mb-8 rounded-xl bg-white shadow-sm">
                <button type="button" class="dashboard-toggle flex w-full items-center justify-between px-6 py-5 text-left" data-target="dashboardQuickActions">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Jump Back In</h2>
                        <p class="mt-1 text-sm text-gray-500">Shortcuts for the parts of the portal you'll likely open next.</p>
                    </div>
                    <i data-lucide="chevron-down" class="h-5 w-5 text-gray-400 transition-transform"></i>
                </button>
                <div id="dashboardQuickActions" class="hidden px-6 pb-6">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <a href="feed.php" class="flex items-center gap-3 rounded-xl border border-transparent bg-slate-50 p-4 text-left transition duration-300 hover:border-blue-200 hover:bg-white hover:shadow-md">
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100">
                            <i data-lucide="newspaper" class="h-6 w-6 text-blue-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">View Feed</p>
                            <p class="mt-1 text-sm text-gray-500">See latest posts</p>
                        </div>
                    </a>

                    <a href="discovery.php" class="flex items-center gap-3 rounded-xl border border-transparent bg-slate-50 p-4 text-left transition duration-300 hover:border-blue-200 hover:bg-white hover:shadow-md">
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-lg bg-green-100">
                            <i data-lucide="search" class="h-6 w-6 text-green-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Find Alumni</p>
                            <p class="mt-1 text-sm text-gray-500">Connect with others</p>
                        </div>
                    </a>

                    <a href="events.php" class="flex items-center gap-3 rounded-xl border border-transparent bg-slate-50 p-4 text-left transition duration-300 hover:border-blue-200 hover:bg-white hover:shadow-md">
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100">
                            <i data-lucide="calendar" class="h-6 w-6 text-purple-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Events</p>
                            <p class="mt-1 text-sm text-gray-500">Join upcoming events</p>
                        </div>
                    </a>

                    <a href="jobs.php" class="flex items-center gap-3 rounded-xl border border-transparent bg-slate-50 p-4 text-left transition duration-300 hover:border-blue-200 hover:bg-white hover:shadow-md">
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-lg bg-amber-100">
                            <i data-lucide="briefcase" class="h-6 w-6 text-amber-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Jobs</p>
                            <p class="mt-1 text-sm text-gray-500">Find opportunities</p>
                        </div>
                    </a>
                </div>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Recent Activity -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <div class="flex items-center justify-between">
                            <button type="button" class="dashboard-toggle flex items-center gap-3 text-left" data-target="recentActivityWrap">
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">Recent Activity</h2>
                                    <p class="text-sm text-gray-500">Posts and engagement worth checking.</p>
                                </div>
                                <i data-lucide="chevron-down" class="h-5 w-5 text-gray-400 transition-transform"></i>
                            </button>
                            <a href="feed.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View All</a>
                        </div>

                        <div id="recentActivityWrap" class="mt-6">
                        <div id="recentActivity" class="space-y-4">
                            <!-- Activity items will be loaded here -->
                            <div class="text-center py-8">
                                <i data-lucide="loader" class="h-8 w-8 animate-spin text-blue-600 mx-auto mb-3"></i>
                                <p class="text-gray-500">Loading recent activity...</p>
                            </div>
                        </div>
                        </div>
                    </div>

                    <!-- Quick Post (Only if can_post) -->
                    <div id="quickPostSection" class="mt-6 bg-white rounded-xl shadow-sm p-6 hidden">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Share an update</h3>
                        <form id="quickPostForm">
                            <textarea id="quickPostContent"
                                rows="3"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                                placeholder="What's on your mind?"></textarea>

                            <div class="flex items-center justify-between mt-4">
                                <div class="flex items-center space-x-4">
                                    <label for="postImage" class="cursor-pointer text-gray-600 hover:text-blue-600">
                                        <i data-lucide="image" class="h-5 w-5"></i>
                                        <input type="file" id="postImage" name="image" accept="image/*" class="hidden">
                                    </label>
                                    <label for="postFile" class="cursor-pointer text-gray-600 hover:text-blue-600">
                                        <i data-lucide="paperclip" class="h-5 w-5"></i>
                                        <input type="file" id="postFile" name="file" class="hidden">
                                    </label>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="allowComments" name="allow_comments" checked class="h-4 w-4 text-blue-600 rounded">
                                        <label for="allowComments" class="ml-2 text-sm text-gray-600">Allow comments</label>
                                    </div>
                                </div>
                                <button type="submit"
                                    class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-medium">
                                    Post
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Upcoming Events & Notifications -->
                <div class="space-y-6">
                    <!-- Upcoming Events -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <div class="flex items-center justify-between">
                            <button type="button" class="dashboard-toggle flex items-center gap-3 text-left" data-target="upcomingEventsWrap">
                                <h3 class="font-semibold text-gray-900">Upcoming Events</h3>
                                <i data-lucide="chevron-down" class="h-5 w-5 text-gray-400 transition-transform"></i>
                            </button>
                            <a href="events.php" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
                        </div>

                        <div id="upcomingEventsWrap" class="mt-4">
                        <div id="upcomingEvents" class="space-y-4">
                            <!-- Events will be loaded here -->
                            <div class="text-center py-4">
                                <i data-lucide="loader" class="h-6 w-6 animate-spin text-blue-600 mx-auto mb-2"></i>
                                <p class="text-sm text-gray-500">Loading events...</p>
                            </div>
                        </div>
                        </div>
                    </div>

                    <!-- Recent Notifications -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <div class="flex items-center justify-between">
                            <button type="button" class="dashboard-toggle flex items-center gap-3 text-left" data-target="recentNotificationsWrap">
                                <h3 class="font-semibold text-gray-900">Recent Notifications</h3>
                                <i data-lucide="chevron-down" class="h-5 w-5 text-gray-400 transition-transform"></i>
                            </button>
                            <a href="javascript:void(0)" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
                        </div>

                        <div id="recentNotificationsWrap" class="mt-4">
                        <div id="recentNotifications" class="space-y-3">
                            <!-- Notifications will be loaded here -->
                            <div class="text-center py-4">
                                <i data-lucide="loader" class="h-6 w-6 animate-spin text-blue-600 mx-auto mb-2"></i>
                                <p class="text-sm text-gray-500">Loading notifications...</p>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
        </main>
    </div>

    <script>
        // --- AUTH CHECK ---
        const token = localStorage.getItem('jwt_token');
        if (!token) {
            window.location.href = 'login.php';
        }

        // --- API HELPERS ---
        async function dashboardApiCall(endpoint, method = 'GET', body = null) {
            if (window.makeApiCall && typeof window.makeApiCall === 'function') {
                return window.makeApiCall(endpoint, method, body);
            }
            const headers = {
                'Authorization': `Bearer ${token}`
            };
            const apiBase = (window.getApiBase ? window.getApiBase() : ((window.PORTAL_BASE_PREFIX || '') + 'api'));

            if (body && !(body instanceof FormData)) {
                headers['Content-Type'] = 'application/json';
                body = JSON.stringify(body);
            }

            try {
                const res = await fetch(`${apiBase}/${endpoint}`, {
                    method,
                    headers,
                    body,
                    credentials: 'same-origin'
                });
                if (res.status === 401) {
                    window.location.href = 'login.php';
                    return null;
                }
                const rawText = await res.text();
                try {
                    return rawText ? JSON.parse(rawText) : {};
                } catch (_error) {
                    return {
                        success: false,
                        status: 'error',
                        message: rawText ? rawText.slice(0, 300) : 'Invalid server response'
                    };
                }
            } catch (e) {
                console.error('Dashboard API Error:', endpoint, e);
                return {
                    success: false
                };
            }
        }

        async function fetchTextContent(filePath) {
            try {
                const res = await fetch(filePath);
                return res.ok ? await res.text() : 'Content unavailable';
            } catch (e) {
                console.error('Fetch Error:', e);
                return '';
            }
        }

        // Date formatting helper
        function formatDate(dateString) {
            return window.portalTime ? window.portalTime.format(dateString, 'relative') : window.formatDate(dateString, 'relative');
        }

        // Initialize Lucide icons
        lucide.createIcons();

        // Load dashboard data
        document.addEventListener('DOMContentLoaded', async function() {
            await loadUserData();
            await loadDashboardStats();
            await loadRecentActivity();
            await loadUpcomingEvents();
            await loadRecentNotifications();
            setupQuickPost();
            setupDashboardToggles();
        });

        function setupDashboardToggles() {
            const collapseOnMobile = new Set(['dashboardQuickActions', 'recentActivityWrap', 'recentNotificationsWrap']);
            document.querySelectorAll('.dashboard-toggle').forEach((button) => {
                const targetId = button.getAttribute('data-target');
                const target = targetId ? document.getElementById(targetId) : null;
                const icon = button.querySelector('svg, i');
                if (!target) return;
                if (window.innerWidth < 768 && collapseOnMobile.has(targetId)) {
                    target.classList.add('hidden');
                }
                if (icon && target.classList.contains('hidden')) {
                    icon.classList.add('-rotate-90');
                }
                button.addEventListener('click', () => {
                    target.classList.toggle('hidden');
                    if (icon) icon.classList.toggle('-rotate-90');
                });
            });
        }

        async function loadUserData() {
            try {
                const userData = localStorage.getItem('user_data');
                if (userData) {
                    const user = JSON.parse(userData);

                    // Update welcome message
                    const welcomeMessage = document.getElementById('welcomeMessage');
                    welcomeMessage.textContent = `Welcome back, ${user.full_name || user.email}!`;

                    // Show quick post section only for roles allowed to create top-level posts
                    const quickPostSection = document.getElementById('quickPostSection');
                    const role = String(user.role || '').toLowerCase();
                    const canCreateTopLevelPost = ['admin', 'faculty', 'alumni'].includes(role) && !!user.can_post;
                    if (canCreateTopLevelPost && quickPostSection) {
                        quickPostSection.classList.remove('hidden');
                    } else if (quickPostSection) {
                        quickPostSection.classList.add('hidden');
                    }
                }
            } catch (error) {
                console.error('Error loading user data:', error);
            }
        }

        async function loadDashboardStats() {
            try {
                // Load connections count
                const connectionsResponse = await dashboardApiCall('get_connections_count.php');
                if (connectionsResponse && connectionsResponse.success) {
                    document.getElementById('connectionsCount').textContent = connectionsResponse.count || 0;
                }

                // Load unread messages count
                const messagesResponse = await dashboardApiCall('get_unread_messages_count.php');
                if (messagesResponse && messagesResponse.success) {
                    document.getElementById('unreadMessagesCount').textContent = messagesResponse.count || 0;
                }

                // Load upcoming events count
                const eventsResponse = await dashboardApiCall('get_upcoming_events_count.php');
                if (eventsResponse && eventsResponse.success) {
                    document.getElementById('upcomingEventsCount').textContent = eventsResponse.count || 0;
                }

                // Load notifications count
                const notificationsResponse = await dashboardApiCall('get_unread_notifications_count.php');
                if (notificationsResponse && notificationsResponse.success) {
                    document.getElementById('notificationsCount').textContent = notificationsResponse.count || 0;
                }
            } catch (error) {
                console.error('Error loading dashboard stats:', error);
            }
        }

        async function loadRecentActivity() {
            try {
                const response = await dashboardApiCall('get_feed.php?limit=5');
                const container = document.getElementById('recentActivity');

                if (response && response.success && response.data && response.data.length > 0) {
                    container.innerHTML = '';

                    for (const post of response.data.slice(0, 5)) {
                        const postElement = document.createElement('div');
                        postElement.className = 'flex items-start p-4 rounded-lg border border-gray-100 hover:bg-gray-50';

                        // Fetch content from file path
                        let content = 'Shared an update...';
                        if (post.content) {
                            content = post.content.length > 100 ? post.content.substring(0, 100) + '...' : post.content;
                        }

                        postElement.innerHTML = `
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center overflow-hidden">
                                    ${post.author_avatar ? 
                                        `<img src="${post.author_avatar}" alt="${post.author_name}" class="h-10 w-10 rounded-full" onerror="this.onerror=null;this.parentElement.innerHTML='&lt;i data-lucide=&quot;user&quot; class=&quot;h-5 w-5 text-blue-600&quot;&gt;&lt;/i&gt;'; lucide.createIcons();">` : 
                                        `<i data-lucide="user" class="h-5 w-5 text-blue-600"></i>`}
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="flex items-center justify-between">
                                    <p class="font-medium text-gray-900">${post.author_name || 'User'}</p>
                                    <span class="text-xs text-gray-500">${formatDate(post.created_at)}</span>
                                </div>
                                <p class="text-sm text-gray-600 mt-1">${content}</p>
                                <div class="flex items-center mt-2 space-x-4 text-sm text-gray-500">
                                    <span class="flex items-center">
                                        <i data-lucide="heart" class="h-4 w-4 mr-1"></i>
                                        ${post.likes_count || 0}
                                    </span>
                                    <span class="flex items-center">
                                        <i data-lucide="message-square" class="h-4 w-4 mr-1"></i>
                                        ${post.comments_count || 0}
                                    </span>
                                </div>
                            </div>
                        `;

                        container.appendChild(postElement);
                    }

                    // Re-initialize icons
                    lucide.createIcons();
                } else {
                    container.innerHTML = `
                        <div class="text-center py-8">
                            <i data-lucide="newspaper" class="h-12 w-12 text-gray-300 mx-auto mb-3"></i>
                            <p class="text-gray-500">No recent activity</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading recent activity:', error);
                document.getElementById('recentActivity').innerHTML = `
                    <div class="text-center py-8">
                        <i data-lucide="alert-circle" class="h-12 w-12 text-red-300 mx-auto mb-3"></i>
                        <p class="text-gray-500">Unable to load activity</p>
                    </div>
                `;
            }
        }

        async function loadUpcomingEvents() {
            try {
                const response = await dashboardApiCall('get_upcoming_events.php?limit=3');
                const container = document.getElementById('upcomingEvents');
                const events = Array.isArray(response) ? response : (Array.isArray(response?.data) ? response.data : []);

                if (events.length > 0) {
                    container.innerHTML = '';

                    for (const event of events.slice(0, 3)) {
                        const eventElement = document.createElement('div');
                        eventElement.className = 'p-3 rounded-lg border border-gray-100';

                        eventElement.innerHTML = `
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="h-10 w-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                        <i data-lucide="calendar" class="h-5 w-5 text-purple-600"></i>
                                    </div>
                                </div>
                                <div class="ml-3 flex-1">
                                    <h4 class="font-medium text-gray-900 text-sm">${event.title || 'Event'}</h4>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i data-lucide="clock" class="h-3 w-3 inline mr-1"></i>
                                        ${formatDate(event.event_date || event.start_date)}
                                    </p>
                                    <div class="mt-2">
                                        <a href="events.php?id=${event.event_id || event.id}" 
                                           class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `;

                        container.appendChild(eventElement);
                    }

                    // Re-initialize icons
                    lucide.createIcons();
                } else {
                    container.innerHTML = `
                        <div class="text-center py-4">
                            <i data-lucide="calendar" class="h-8 w-8 text-gray-300 mx-auto mb-2"></i>
                            <p class="text-sm text-gray-500">No upcoming events</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading upcoming events:', error);
            }
        }

        async function loadRecentNotifications() {
            try {
                const response = await dashboardApiCall('get_notifications.php?limit=4');
                const container = document.getElementById('recentNotifications');
                const notifications = Array.isArray(response?.data) ? response.data : (Array.isArray(response?.notifications) ? response.notifications : []);

                if (notifications.length > 0) {
                    container.innerHTML = '';

                    for (const notif of notifications.slice(0, 4)) {
                        const notifElement = document.createElement('div');
                        notifElement.className = 'flex items-start p-3 rounded-lg bg-gray-50';

                        notifElement.innerHTML = `
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i data-lucide="${notif.icon || 'bell'}" class="h-4 w-4 text-blue-600"></i>
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-gray-800">${notif.message || 'Notification'}</p>
                                <p class="text-xs text-gray-500 mt-1">${formatDate(notif.created_at)}</p>
                            </div>
                        `;

                        container.appendChild(notifElement);
                    }

                    // Re-initialize icons
                    lucide.createIcons();
                } else {
                    container.innerHTML = `
                        <div class="text-center py-4">
                            <i data-lucide="bell" class="h-8 w-8 text-gray-300 mx-auto mb-2"></i>
                            <p class="text-sm text-gray-500">No notifications</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading notifications:', error);
            }
        }

        function setupQuickPost() {
            const quickPostForm = document.getElementById('quickPostForm');
            const postImage = document.getElementById('postImage');
            const postFile = document.getElementById('postFile');

            if (quickPostForm) {
                quickPostForm.addEventListener('submit', async function(e) {
                    e.preventDefault();

                    const content = document.getElementById('quickPostContent').value.trim();
                    const allowComments = document.getElementById('allowComments').checked;

                    if (!content) {
                        await window.appAlert('Please enter some content', {
                            title: 'Post content required',
                            icon: 'triangle-alert',
                            iconTone: 'danger'
                        });
                        return;
                    }

                    const formData = new FormData();
                    formData.append('content', content);
                    formData.append('allow_comments', allowComments ? '1' : '0');

                    // Add image if selected
                    if (postImage.files[0]) {
                        formData.append('image', postImage.files[0]);
                    }

                    // Add file if selected
                    if (postFile.files[0]) {
                        formData.append('file', postFile.files[0]);
                    }

                    try {
                        const response = await dashboardApiCall('create_post.php', 'POST', formData);

                        if (response && response.success) {
                            await window.appAlert('Post created successfully!', {
                                title: 'Post published',
                                icon: 'badge-check',
                                iconTone: 'success'
                            });
                            quickPostForm.reset();
                            loadRecentActivity(); // Refresh activity feed
                        } else {
                            await window.appAlert(response.message || 'Failed to create post', {
                                title: 'Post failed',
                                icon: 'triangle-alert',
                                iconTone: 'danger'
                            });
                        }
                    } catch (error) {
                        console.error('Error creating post:', error);
                        await window.appAlert('Error creating post', {
                            title: 'Post failed',
                            icon: 'triangle-alert',
                            iconTone: 'danger'
                        });
                    }
                });
            }

            // Preview image
            if (postImage) {
                postImage.addEventListener('change', function() {
                    if (this.files[0]) {
                        // You could add image preview logic here
                        console.log('Image selected:', this.files[0].name);
                    }
                });
            }
        }
    </script>
