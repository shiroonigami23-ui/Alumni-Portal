<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RJIT Alumni Portal</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Roboto+Slab:wght@400;500;600;700&display=swap" rel="stylesheet">

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
    </style>

    <!-- Auth Check Script -->
    <script src="includes/auth-check.js"></script>
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
    <nav class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo and Brand -->
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="#" onclick="goToDashboard(); return false;" class="flex items-center cursor-pointer hover:opacity-80 transition">
                            <i data-lucide="graduation-cap" class="h-8 w-8 text-blue-600"></i>
                            <span class="ml-2 text-xl font-bold text-gray-900">RJIT Alumni Portal</span>
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
                <div class="flex items-center space-x-4">
                    <!-- Notifications -->
                    <div class="relative">
                        <button id="notificationBtn" class="p-2 rounded-full hover:bg-gray-100">
                            <i data-lucide="bell"></i>
                            <span id="notificationCount" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">0</span>
                        </button>
                        <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
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
                        <button id="userMenuBtn" class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100">
                            <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <i data-lucide="user"></i>
                            </div>
                            <span id="userName" class="hidden md:inline text-gray-700">Loading...</span>
                            <i data-lucide="chevron-down" class="h-4 w-4 text-gray-500"></i>
                        </button>

                        <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                            <div class="py-2">
                                <a href="profile.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i data-lucide="user" class="h-4 w-4 mr-3"></i> My Profile
                                </a>
                                <a href="settings.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100">
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

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Load user data
        document.addEventListener('DOMContentLoaded', function() {
            const userData = localStorage.getItem('user_data');
            if (userData) {
                const user = JSON.parse(userData);
                document.getElementById('userName').textContent = user.name || user.email;

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
                        window.location.href = `discovery.php?search=${encodeURIComponent(this.value.trim())}`;
                    }
                });
            }

            // Setup notification dropdown
            const notificationBtn = document.getElementById('notificationBtn');
            const notificationDropdown = document.getElementById('notificationDropdown');

            notificationBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationDropdown.classList.toggle('hidden');
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

            // Check for live streams
            checkLiveStreams();
            setInterval(checkLiveStreams, 30000); // Check every 30 seconds
        });

        async function loadNotifications() {
            try {
                const response = await makeApiCall('get_notifications.php');
                if (response && response.success) {
                    const notificationList = document.getElementById('notificationList');
                    const countElement = document.getElementById('notificationCount');

                    if (response.data && response.data.length > 0) {
                        notificationList.innerHTML = response.data.map(notif => `
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-start">
                                    <i data-lucide="${notif.icon || 'bell'}" class="h-4 w-4 mt-1 mr-3 text-blue-600"></i>
                                    <div>
                                        <p class="text-sm text-gray-800">${notif.message}</p>
                                        <p class="text-xs text-gray-500 mt-1">${formatDate(notif.created_at)}</p>
                                    </div>
                                </div>
                            </div>
                        `).join('');

                        countElement.textContent = response.data.length;
                        countElement.classList.remove('hidden');
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
                    window.location.href = 'admin/dashboard.php';
                } else {
                    window.location.href = 'dashboard.php';
                }
            } else {
                window.location.href = 'dashboard.php';
            }
        }

        async function checkLiveStreams() {
            try {
                const response = await fetch('api/get_active_streams.php');
                const result = await response.json();

                const container = document.getElementById('live-indicator-container');
                if (result.status === 'success' && result.data.length > 0) {
                    const stream = result.data[0];
                    container.innerHTML = `
                <div class="live-badge-card" style="background: #ff0000; color: white; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                    <strong>🔴 LIVE NOW:</strong> ${stream.title} by ${stream.full_name}
                    <a href="live.php?id=${stream.stream_id}" style="color: yellow; margin-left: 15px; font-weight: bold;">WATCH NOW</a>
                </div>
            `;
                } else {
                    container.innerHTML = ''; // Hide if nothing is live
                }
            } catch (error) {
                console.error('Error checking streams:', error);
            }
        }

        // Check every 30 seconds
        checkLiveStreams();
        setInterval(checkLiveStreams, 30000);
    </script>