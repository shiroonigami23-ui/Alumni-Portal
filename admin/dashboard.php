<?php
// Check authentication and admin role
require_once '../includes/auth-check.js';

// Check if user is admin
$userData = json_decode(localStorage.getItem('user_data') || '{}', true);
if ($userData.role !== 'admin') {
    header('Location: ../dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - RJIT Alumni Portal</title>
    
    <?php include '../includes/header.php'; ?>
    
    <style>
        .admin-card {
            border-left: 4px solid #f59e0b;
        }
        
        .terminal {
            background-color: #1a1a1a;
            color: #00ff00;
            font-family: 'Courier New', monospace;
        }
        
        .terminal-line {
            animation: typing 3s steps(40, end);
        }
        
        @keyframes typing {
            from { width: 0; }
            to { width: 100%; }
        }
        
        .stats-card {
            transition: transform 0.2s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
        
        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .badge-approved {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .badge-rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="md:pl-64">
        <!-- Admin Header -->
        <div class="bg-amber-50 border-b border-amber-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center">
                    <i data-lucide="shield" class="h-6 w-6 text-amber-600 mr-3"></i>
                    <h1 class="text-lg font-semibold text-amber-800">Administrator Dashboard</h1>
                    <span class="ml-auto text-sm text-amber-700">
                        <i data-lucide="clock" class="h-4 w-4 inline mr-1"></i>
                        <?php echo date('F j, Y, h:i A'); ?>
                    </span>
                </div>
            </div>
        </div>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="stats-card bg-white rounded-xl shadow-sm p-6 admin-card">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-blue-100">
                            <i data-lucide="users" class="h-6 w-6 text-blue-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Users</p>
                            <p id="totalUsers" class="text-2xl font-bold text-gray-900">0</p>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="flex justify-between text-sm">
                            <span class="text-green-600">
                                <i data-lucide="trending-up" class="h-4 w-4 inline mr-1"></i>
                                <span id="usersGrowth">0%</span>
                            </span>
                            <span class="text-gray-500">This month</span>
                        </div>
                    </div>
                </div>
                
                <div class="stats-card bg-white rounded-xl shadow-sm p-6 admin-card">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-amber-100">
                            <i data-lucide="user-clock" class="h-6 w-6 text-amber-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Pending Approvals</p>
                            <p id="pendingUsers" class="text-2xl font-bold text-gray-900">0</p>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="flex justify-between text-sm">
                            <span class="text-amber-600">
                                <i data-lucide="alert-circle" class="h-4 w-4 inline mr-1"></i>
                                Needs attention
                            </span>
                            <a href="#pendingTable" class="text-blue-600 hover:text-blue-800">View all</a>
                        </div>
                    </div>
                </div>
                
                <div class="stats-card bg-white rounded-xl shadow-sm p-6 admin-card">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-red-100">
                            <i data-lucide="flag" class="h-6 w-6 text-red-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Active Reports</p>
                            <p id="activeReports" class="text-2xl font-bold text-gray-900">0</p>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="flex justify-between text-sm">
                            <span class="text-red-600">
                                <i data-lucide="alert-triangle" class="h-4 w-4 inline mr-1"></i>
                                Action required
                            </span>
                            <a href="#" class="text-blue-600 hover:text-blue-800">Review</a>
                        </div>
                    </div>
                </div>
                
                <div class="stats-card bg-white rounded-xl shadow-sm p-6 admin-card">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-green-100">
                            <i data-lucide="activity" class="h-6 w-6 text-green-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">System Health</p>
                            <p id="systemHealth" class="text-2xl font-bold text-gray-900">100%</p>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="flex justify-between text-sm">
                            <span class="text-green-600">
                                <i data-lucide="check-circle" class="h-4 w-4 inline mr-1"></i>
                                All systems normal
                            </span>
                            <a href="#terminal" class="text-blue-600 hover:text-blue-800">View logs</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                <!-- Left Column: Recent Activity & Terminal -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- System Terminal -->
                    <div class="bg-gray-900 rounded-xl shadow-lg overflow-hidden">
                        <div class="bg-gray-800 px-4 py-3 flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex space-x-2 mr-4">
                                    <div class="h-3 w-3 bg-red-500 rounded-full"></div>
                                    <div class="h-3 w-3 bg-amber-500 rounded-full"></div>
                                    <div class="h-3 w-3 bg-green-500 rounded-full"></div>
                                </div>
                                <span class="text-gray-300 font-mono text-sm">system_logs.sh</span>
                            </div>
                            <button onclick="refreshLogs()" class="text-gray-400 hover:text-white">
                                <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                            </button>
                        </div>
                        <div id="terminal" class="terminal p-4 h-64 overflow-y-auto font-mono text-sm">
                            <div class="mb-2 text-green-400">$ Loading system logs...</div>
                            <div id="logContent">
                                <!-- Logs will be loaded here -->
                            </div>
                            <div class="flex items-center mt-2">
                                <span class="text-green-400">$</span>
                                <span class="ml-2 animate-pulse">_</span>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Admin Activity -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-lg font-semibold text-gray-900">Recent Admin Actions</h2>
                            <button onclick="loadAdminActivity()" class="text-gray-500 hover:text-gray-700">
                                <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                            </button>
                        </div>
                        
                        <div id="adminActivity" class="space-y-4">
                            <div class="text-center py-8">
                                <i data-lucide="loader" class="h-8 w-8 animate-spin text-blue-600 mx-auto mb-4"></i>
                                <p class="text-gray-500">Loading activity...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Quick Actions -->
                <div class="space-y-6">
                    <!-- Quick Actions -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
                        
                        <div class="space-y-3">
                            <button onclick="openUserApproval()" 
                                    class="w-full flex items-center p-3 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-700">
                                <i data-lucide="user-check" class="h-5 w-5 text-green-600 mr-3"></i>
                                <span>Approve Pending Users</span>
                                <span id="pendingBadge" class="ml-auto bg-amber-100 text-amber-800 text-xs px-2 py-1 rounded-full">0</span>
                            </button>
                            
                            <button onclick="openTokenGenerator()" 
                                    class="w-full flex items-center p-3 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-700">
                                <i data-lucide="key" class="h-5 w-5 text-blue-600 mr-3"></i>
                                <span>Generate Invite Tokens</span>
                            </button>
                            
                            <button onclick="openContentModeration()" 
                                    class="w-full flex items-center p-3 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-700">
                                <i data-lucide="shield-alert" class="h-5 w-5 text-red-600 mr-3"></i>
                                <span>Content Moderation</span>
                                <span id="reportsBadge" class="ml-auto bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">0</span>
                            </button>
                            
                            <button onclick="openSystemSettings()" 
                                    class="w-full flex items-center p-3 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-700">
                                <i data-lucide="settings" class="h-5 w-5 text-gray-600 mr-3"></i>
                                <span>System Settings</span>
                            </button>
                        </div>
                    </div>

                    <!-- System Status -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">System Status</h2>
                        
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm text-gray-600">API Health</span>
                                    <span class="text-sm font-medium text-green-600" id="apiHealth">100%</span>
                                </div>
                                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div id="apiHealthBar" class="h-full bg-green-500 rounded-full" style="width: 100%"></div>
                                </div>
                            </div>
                            
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm text-gray-600">Database</span>
                                    <span class="text-sm font-medium text-green-600" id="dbHealth">Healthy</span>
                                </div>
                                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-green-500 rounded-full w-full"></div>
                                </div>
                            </div>
                            
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm text-gray-600">Storage</span>
                                    <span class="text-sm font-medium text-gray-600" id="storageUsage">65%</span>
                                </div>
                                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div id="storageBar" class="h-full bg-blue-500 rounded-full" style="width: 65%"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6 pt-6 border-t border-gray-100">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Last updated:</span>
                                <span id="lastUpdate" class="font-medium">Just now</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Approvals Table -->
            <div id="pendingTable" class="bg-white rounded-xl shadow-sm p-6 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-semibold text-gray-900">Pending User Approvals</h2>
                    <div class="flex items-center space-x-3">
                        <button onclick="exportPendingUsers()" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                            Export CSV
                        </button>
                        <button onclick="loadPendingUsers()" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                            Refresh
                        </button>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">User</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Type</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Registered</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Details</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Status</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="pendingUsersTable">
                            <!-- Users will be loaded here -->
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-500">
                                    <i data-lucide="loader" class="h-8 w-8 animate-spin text-blue-600 mx-auto mb-2"></i>
                                    <p>Loading pending users...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div id="noPendingUsers" class="hidden text-center py-8">
                    <i data-lucide="check-circle" class="h-12 w-12 text-green-300 mx-auto mb-4"></i>
                    <h3 class="font-medium text-gray-900 mb-2">No pending approvals</h3>
                    <p class="text-gray-500">All users have been processed</p>
                </div>
            </div>

            <!-- Token Generator -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-6">Alumni Invite Token Generator</h2>
                
                <div class="grid md:grid-cols-2 gap-8">
                    <!-- Generate Form -->
                    <div>
                        <h3 class="font-medium text-gray-900 mb-4">Generate New Token</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <input type="email" 
                                       id="tokenEmail"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="alumni@example.com">
                                <p class="mt-1 text-sm text-gray-500">Token will be sent to this email</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Expiration</label>
                                <select id="tokenExpiry" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="7">7 days</option>
                                    <option value="30" selected>30 days</option>
                                    <option value="90">90 days</option>
                                    <option value="365">1 year</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Usage Limit</label>
                                <select id="tokenLimit" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="1">Single use</option>
                                    <option value="5">5 uses</option>
                                    <option value="10" selected>10 uses</option>
                                    <option value="unlimited">Unlimited</option>
                                </select>
                            </div>
                            
                            <button onclick="generateToken()" 
                                    class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 font-medium">
                                Generate Token
                            </button>
                        </div>
                    </div>
                    
                    <!-- Recent Tokens -->
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-medium text-gray-900">Recent Tokens</h3>
                            <button onclick="loadRecentTokens()" class="text-sm text-blue-600 hover:text-blue-800">
                                Refresh
                            </button>
                        </div>
                        
                        <div id="recentTokens" class="space-y-3 max-h-64 overflow-y-auto">
                            <!-- Tokens will be loaded here -->
                            <div class="text-center py-4">
                                <i data-lucide="loader" class="h-6 w-6 animate-spin text-blue-600 mx-auto mb-2"></i>
                                <p class="text-sm text-gray-500">Loading tokens...</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Generated Token Display -->
                <div id="tokenResult" class="hidden mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-medium text-gray-900 mb-1">Generated Token</h4>
                            <p class="text-sm text-gray-600">Copy this token and share it with the alumni</p>
                        </div>
                        <button onclick="copyToken()" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                            Copy Token
                        </button>
                    </div>
                    <div class="mt-3">
                        <div class="flex items-center p-3 bg-white rounded-lg border border-gray-300">
                            <code id="generatedToken" class="font-mono text-sm text-gray-800 flex-1"></code>
                            <button onclick="copyToken()" class="ml-3 text-gray-500 hover:text-gray-700">
                                <i data-lucide="copy" class="h-4 w-4"></i>
                            </button>
                        </div>
                        <div class="mt-2 grid grid-cols-2 gap-4 text-sm text-gray-600">
                            <div>
                                <span class="font-medium">Expires:</span>
                                <span id="tokenExpiryDate"></span>
                            </div>
                            <div>
                                <span class="font-medium">For:</span>
                                <span id="tokenForEmail"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <!-- User Approval Modal -->
    <div id="approvalModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Approve User</h3>
                    <button onclick="closeApprovalModal()" class="p-2 rounded-full hover:bg-gray-100">
                        <i data-lucide="x" class="h-5 w-5 text-gray-500"></i>
                    </button>
                </div>
                
                <div id="approvalContent">
                    <!-- Content loaded dynamically -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Load admin dashboard data
        document.addEventListener('DOMContentLoaded', function() {
            loadAdminStats();
            loadPendingUsers();
            loadSystemLogs();
            loadRecentTokens();
            loadAdminActivity();
            
            // Auto-refresh logs every 30 seconds
            setInterval(loadSystemLogs, 30000);
            
            // Auto-update stats every minute
            setInterval(loadAdminStats, 60000);
        });
        
        async function loadAdminStats() {
            try {
                const response = await makeApiCall('admin_stats.php');
                
                if (response && response.success) {
                    const stats = response.data;
                    
                    // Update quick stats
                    document.getElementById('totalUsers').textContent = stats.total_users?.toLocaleString() || '0';
                    document.getElementById('pendingUsers').textContent = stats.pending_users || '0';
                    document.getElementById('activeReports').textContent = stats.active_reports || '0';
                    document.getElementById('usersGrowth').textContent = `${stats.user_growth || 0}%`;
                    
                    // Update badges
                    document.getElementById('pendingBadge').textContent = stats.pending_users || '0';
                    document.getElementById('reportsBadge').textContent = stats.active_reports || '0';
                    
                    // Update system health
                    const health = stats.system_health || 100;
                    document.getElementById('systemHealth').textContent = `${health}%`;
                    document.getElementById('apiHealth').textContent = `${health}%`;
                    document.getElementById('apiHealthBar').style.width = `${health}%`;
                    
                    // Update storage
                    const storage = stats.storage_usage || 65;
                    document.getElementById('storageUsage').textContent = `${storage}%`;
                    document.getElementById('storageBar').style.width = `${storage}%`;
                    
                    // Update last update time
                    document.getElementById('lastUpdate').textContent = 'Just now';
                }
            } catch (error) {
                console.error('Error loading admin stats:', error);
            }
        }
        
        async function loadPendingUsers() {
            try {
                const response = await makeApiCall('admin_stats.php?type=pending');
                
                const tableBody = document.getElementById('pendingUsersTable');
                const noPendingDiv = document.getElementById('noPendingUsers');
                
                if (response && response.success && response.data && response.data.length > 0) {
                    tableBody.innerHTML = '';
                    
                    response.data.forEach(user => {
                        const row = document.createElement('tr');
                        row.className = 'border-b border-gray-100 hover:bg-gray-50';
                        
                        row.innerHTML = `
                            <td class="py-4 px-4">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 bg-gray-100 rounded-full flex items-center justify-center mr-3">
                                        ${user.avatar ? 
                                            `<img src="${user.avatar}" alt="${user.name}" class="h-10 w-10 rounded-full">` : 
                                            `<i data-lucide="user" class="h-5 w-5 text-gray-400"></i>`}
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">${user.name}</div>
                                        <div class="text-sm text-gray-500">${user.email}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <span class="px-2 py-1 rounded-full text-xs ${getRoleBadgeClass(user.role)}">
                                    ${user.role || 'user'}
                                </span>
                            </td>
                            <td class="py-4 px-4 text-sm text-gray-600">
                                ${formatDate(user.created_at)}
                            </td>
                            <td class="py-4 px-4">
                                <div class="text-sm">
                                    ${user.branch ? `<div>${user.branch}</div>` : ''}
                                    ${user.graduation_year ? `<div>Class of ${user.graduation_year}</div>` : ''}
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <span class="badge-pending px-2 py-1 rounded-full text-xs font-medium">
                                    Pending
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex space-x-2">
                                    <button onclick="approveUser(${user.id})" 
                                            class="px-3 py-1 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">
                                        Approve
                                    </button>
                                    <button onclick="rejectUser(${user.id})" 
                                            class="px-3 py-1 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">
                                        Reject
                                    </button>
                                    <button onclick="viewUserDetails(${user.id})" 
                                            class="px-3 py-1 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                                        View
                                    </button>
                                </div>
                            </td>
                        `;
                        
                        tableBody.appendChild(row);
                    });
                    
                    tableBody.parentElement.parentElement.classList.remove('hidden');
                    noPendingDiv.classList.add('hidden');
                    
                    lucide.createIcons();
                } else {
                    tableBody.parentElement.parentElement.classList.add('hidden');
                    noPendingDiv.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Error loading pending users:', error);
            }
        }
        
        function getRoleBadgeClass(role) {
            switch(role) {
                case 'admin': return 'bg-amber-100 text-amber-800';
                case 'faculty': return 'bg-blue-100 text-blue-800';
                case 'alumni': return 'bg-green-100 text-green-800';
                case 'student': return 'bg-purple-100 text-purple-800';
                default: return 'bg-gray-100 text-gray-800';
            }
        }
        
        async function loadSystemLogs() {
            try {
                const response = await makeApiCall('view_logs.php');
                const logContent = document.getElementById('logContent');
                
                if (response && response.success) {
                    const logs = response.data || [];
                    
                    if (logs.length > 0) {
                        logContent.innerHTML = '';
                        
                        // Show last 20 log entries
                        logs.slice(-20).forEach(log => {
                            const logLine = document.createElement('div');
                            logLine.className = 'terminal-line mb-1';
                            
                            // Color code based on log level
                            let colorClass = 'text-gray-300';
                            if (log.level === 'ERROR') colorClass = 'text-red-400';
                            else if (log.level === 'WARNING') colorClass = 'text-yellow-400';
                            else if (log.level === 'INFO') colorClass = 'text-blue-400';
                            else if (log.level === 'SUCCESS') colorClass = 'text-green-400';
                            
                            logLine.innerHTML = `
                                <span class="text-gray-500">[${formatDate(log.timestamp, 'HH:mm:ss')}]</span>
                                <span class="${colorClass} ml-2">${log.message}</span>
                            `;
                            
                            logContent.appendChild(logLine);
                        });
                        
                        // Auto-scroll to bottom
                        const terminal = document.getElementById('terminal');
                        terminal.scrollTop = terminal.scrollHeight;
                    } else {
                        logContent.innerHTML = '<div class="text-gray-500">No logs available</div>';
                    }
                }
            } catch (error) {
                console.error('Error loading system logs:', error);
                document.getElementById('logContent').innerHTML = 
                    '<div class="text-red-400">Error loading logs. Check API connection.</div>';
            }
        }
        
        async function loadRecentTokens() {
            try {
                const response = await makeApiCall('generate_token.php?action=list');
                const container = document.getElementById('recentTokens');
                
                if (response && response.success && response.data) {
                    container.innerHTML = '';
                    
                    response.data.slice(0, 5).forEach(token => {
                        const tokenElement = document.createElement('div');
                        tokenElement.className = 'p-3 bg-gray-50 rounded-lg';
                        
                        tokenElement.innerHTML = `
                            <div class="flex justify-between items-start mb-1">
                                <span class="font-mono text-sm text-gray-800 truncate">${token.token.substring(0, 12)}...</span>
                                <span class="text-xs ${token.is_active ? 'text-green-600' : 'text-gray-500'}">
                                    ${token.is_active ? 'Active' : 'Expired'}
                                </span>
                            </div>
                            <div class="text-xs text-gray-600">
                                <div>For: ${token.email || 'N/A'}</div>
                                <div>Uses: ${token.used_count || 0}/${token.usage_limit || '∞'}</div>
                                <div>Expires: ${formatDate(token.expires_at)}</div>
                            </div>
                        `;
                        
                        container.appendChild(tokenElement);
                    });
                } else {
                    container.innerHTML = '<div class="text-center py-4 text-sm text-gray-500">No tokens generated yet</div>';
                }
            } catch (error) {
                console.error('Error loading tokens:', error);
            }
        }
        
        async function loadAdminActivity() {
            try {
                // This would come from an admin activity API endpoint
                const activities = [
                    { action: 'Approved user registration', admin: 'System Admin', time: '2 minutes ago' },
                    { action: 'Generated alumni token', admin: 'John Doe', time: '15 minutes ago' },
                    { action: 'Removed inappropriate content', admin: 'System Admin', time: '1 hour ago' },
                    { action: 'Updated system settings', admin: 'Jane Smith', time: '2 hours ago' },
                    { action: 'Exported user data', admin: 'System Admin', time: '5 hours ago' }
                ];
                
                const container = document.getElementById('adminActivity');
                container.innerHTML = '';
                
                activities.forEach(activity => {
                    const activityElement = document.createElement('div');
                    activityElement.className = 'flex items-start p-3 rounded-lg border border-gray-100';
                    
                    activityElement.innerHTML = `
                        <div class="flex-shrink-0">
                            <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <i data-lucide="activity" class="h-4 w-4 text-blue-600"></i>
                            </div>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm text-gray-800">${activity.action}</p>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-xs text-gray-500">By ${activity.admin}</span>
                                <span class="text-xs text-gray-500">${activity.time}</span>
                            </div>
                        </div>
                    `;
                    
                    container.appendChild(activityElement);
                });
                
                lucide.createIcons();
            } catch (error) {
                console.error('Error loading admin activity:', error);
            }
        }
        
        async function approveUser(userId) {
            if (!confirm('Are you sure you want to approve this user?')) return;
            
            try {
                const response = await makeApiCall('approve_user.php', 'POST', {
                    user_id: userId,
                    action: 'approve'
                });
                
                if (response && response.success) {
                    alert('User approved successfully!');
                    loadPendingUsers();
                    loadAdminStats();
                } else {
                    alert(response.message || 'Failed to approve user');
                }
            } catch (error) {
                console.error('Error approving user:', error);
                alert('Error approving user');
            }
        }
        
        async function rejectUser(userId) {
            const reason = prompt('Please enter reason for rejection:', '');
            if (reason === null) return;
            
            try {
                const response = await makeApiCall('approve_user.php', 'POST', {
                    user_id: userId,
                    action: 'reject',
                    reason: reason
                });
                
                if (response && response.success) {
                    alert('User rejected successfully!');
                    loadPendingUsers();
                    loadAdminStats();
                } else {
                    alert(response.message || 'Failed to reject user');
                }
            } catch (error) {
                console.error('Error rejecting user:', error);
                alert('Error rejecting user');
            }
        }
        
        async function generateToken() {
            const email = document.getElementById('tokenEmail').value.trim();
            const expiry = document.getElementById('tokenExpiry').value;
            const limit = document.getElementById('tokenLimit').value;
            
            if (!email) {
                alert('Please enter an email address');
                return;
            }
            
            if (!validateEmail(email)) {
                alert('Please enter a valid email address');
                return;
            }
            
            try {
                const response = await makeApiCall('generate_token.php', 'POST', {
                    email: email,
                    expires_in: expiry,
                    usage_limit: limit
                });
                
                if (response && response.success) {
                    // Show generated token
                    document.getElementById('generatedToken').textContent = response.data.token;
                    document.getElementById('tokenForEmail').textContent = email;
                    
                    // Calculate expiry date
                    const expiryDate = new Date();
                    expiryDate.setDate(expiryDate.getDate() + parseInt(expiry));
                    document.getElementById('tokenExpiryDate').textContent = formatDate(expiryDate);
                    
                    // Show result
                    document.getElementById('tokenResult').classList.remove('hidden');
                    
                    // Clear form
                    document.getElementById('tokenEmail').value = '';
                    
                    // Reload recent tokens
                    loadRecentTokens();
                    
                    // Scroll to result
                    document.getElementById('tokenResult').scrollIntoView({ behavior: 'smooth' });
                } else {
                    alert(response.message || 'Failed to generate token');
                }
            } catch (error) {
                console.error('Error generating token:', error);
                alert('Error generating token');
            }
        }
        
        function copyToken() {
            const token = document.getElementById('generatedToken').textContent;
            navigator.clipboard.writeText(token).then(() => {
                alert('Token copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy token:', err);
                alert('Failed to copy token');
            });
        }
        
        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
        
        function formatDate(dateString, format = 'relative') {
            const date = new Date(dateString);
            
            if (format === 'relative') {
                const now = new Date();
                const diffMs = now - date;
                const diffMins = Math.floor(diffMs / 60000);
                const diffHours = Math.floor(diffMs / 3600000);
                const diffDays = Math.floor(diffMs / 86400000);
                
                if (diffMins < 60) {
                    return `${diffMins}m ago`;
                } else if (diffHours < 24) {
                    return `${diffHours}h ago`;
                } else if (diffDays < 7) {
                    return `${diffDays}d ago`;
                } else {
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }
            } else if (format === 'HH:mm:ss') {
                return date.toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
            } else {
                return date.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        }
        
        function refreshLogs() {
            loadSystemLogs();
        }
        
        function openUserApproval() {
            document.getElementById('pendingTable').scrollIntoView({ behavior: 'smooth' });
        }
        
        function openTokenGenerator() {
            document.querySelector('#tokenGenerator').scrollIntoView({ behavior: 'smooth' });
        }
        
        function openContentModeration() {
            alert('Content moderation panel coming soon!');
        }
        
        function openSystemSettings() {
            alert('System settings panel coming soon!');
        }
        
        function exportPendingUsers() {
            alert('Export feature coming soon!');
        }
        
        function closeApprovalModal() {
            document.getElementById('approvalModal').classList.add('hidden');
        }
        
        function viewUserDetails(userId) {
            window.open(`../profile.php?id=${userId}`, '_blank');
        }
    </script>
</body>
</html>