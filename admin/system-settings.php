<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="../assets/css/variety-ui.css">
    <script src="../includes/auth-check.js"></script>
</head>
<body class="bg-gray-50">
    <script>
        (function () {
            const token = localStorage.getItem('jwt_token');
            const user = JSON.parse(localStorage.getItem('user_data') || '{}');
            if (!token) { window.location.href = '../login.php'; return; }
            if (user.role !== 'admin') { window.location.href = '../dashboard.php'; }
        })();
    </script>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    <div class="md:pl-64">
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-semibold text-gray-900">System Settings</h1>
                <a href="./dashboard.php" class="px-4 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-100">Back to Dashboard</a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">System Health</h2>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between"><span>Total users</span><span id="statUsers">-</span></div>
                        <div class="flex justify-between"><span>Pending approvals</span><span id="statPending">-</span></div>
                        <div class="flex justify-between"><span>Active reports</span><span id="statReports">-</span></div>
                        <div class="flex justify-between"><span>System health</span><span id="statHealth">-</span></div>
                        <div class="flex justify-between"><span>Storage usage</span><span id="statStorage">-</span></div>
                    </div>
                    <button onclick="loadStats()" class="mt-4 px-3 py-2 border rounded-lg text-sm hover:bg-gray-100">Refresh metrics</button>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Admin Controls</h2>
                    <div class="space-y-3">
                        <a href="../settings.php" class="block px-4 py-3 border rounded-lg hover:bg-gray-50">Open Global Settings Page</a>
                        <a href="./users.php" class="block px-4 py-3 border rounded-lg hover:bg-gray-50">Manage Users</a>
                        <a href="./reports.php" class="block px-4 py-3 border rounded-lg hover:bg-gray-50">Review Reported Content</a>
                        <a href="./tokens.php" class="block px-4 py-3 border rounded-lg hover:bg-gray-50">Generate Invite Tokens</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        lucide.createIcons();
        document.addEventListener('DOMContentLoaded', loadStats);
        async function loadStats() {
            const res = await makeApiCall('admin_stats.php');
            const stats = (res && res.success && res.data) ? res.data : null;
            if (!stats) return;
            document.getElementById('statUsers').textContent = stats.total_users ?? 0;
            document.getElementById('statPending').textContent = stats.pending_users ?? 0;
            document.getElementById('statReports').textContent = stats.active_reports ?? 0;
            document.getElementById('statHealth').textContent = `${stats.system_health ?? 100}%`;
            document.getElementById('statStorage').textContent = `${stats.storage_usage ?? 0}%`;
        }
    </script>
</body>
</html>

