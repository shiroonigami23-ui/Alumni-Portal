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
    <title>Reported Content - Admin</title>
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
                <h1 class="text-2xl font-semibold text-gray-900">Reported Content</h1>
                <a href="./dashboard.php" class="px-4 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-100">Back to Dashboard</a>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-3 px-3 text-sm">Post</th>
                                <th class="text-left py-3 px-3 text-sm">Reason</th>
                                <th class="text-left py-3 px-3 text-sm">Reporter</th>
                                <th class="text-left py-3 px-3 text-sm">Status</th>
                                <th class="text-left py-3 px-3 text-sm">Reported At</th>
                                <th class="text-left py-3 px-3 text-sm">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="reportsTable">
                            <tr><td colspan="6" class="py-8 text-center text-gray-500">Loading reports...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>
        lucide.createIcons();
        document.addEventListener('DOMContentLoaded', loadReportsPage);

        async function loadReportsPage() {
            const res = await makeApiCall('get_reports.php');
            const rows = Array.isArray(res) ? res : (res && Array.isArray(res.data) ? res.data : []);
            const tbody = document.getElementById('reportsTable');
            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="6" class="py-8 text-center text-gray-500">No reports found</td></tr>';
                return;
            }
            tbody.innerHTML = rows.map(r => `
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3 px-3">
                        <div class="font-medium text-gray-900">${escapeHtml(r.post_title || 'Untitled')}</div>
                        <div class="text-xs text-gray-500">Post ID: ${Number(r.post_id || 0)}</div>
                    </td>
                    <td class="py-3 px-3 text-sm">${escapeHtml(r.reason || r.custom_reason || 'N/A')}</td>
                    <td class="py-3 px-3 text-sm">${escapeHtml(r.reporter_email || 'Unknown')}</td>
                    <td class="py-3 px-3 text-sm">${badge((r.status || 'pending'), statusClass(r.status || 'pending'))}</td>
                    <td class="py-3 px-3 text-sm">${fmt(r.created_at)}</td>
                    <td class="py-3 px-3">
                        <div class="flex gap-2">
                            <a href="../profile.php?id=${Number(r.reporter_id || 0)}" target="_blank" class="px-2 py-1 border rounded hover:bg-gray-100 text-sm">Reporter</a>
                            <a href="../feed.php" class="px-2 py-1 border rounded hover:bg-gray-100 text-sm">Open Feed</a>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function fmt(v) {
            if (!v) return '-';
            return new Date(v).toLocaleString();
        }
        function statusClass(status) {
            if (status === 'resolved' || status === 'closed') return 'bg-green-100 text-green-800';
            if (status === 'rejected') return 'bg-gray-100 text-gray-700';
            return 'bg-red-100 text-red-800';
        }
        function badge(text, cls) {
            return `<span class="px-2 py-1 rounded-full text-xs ${cls}">${escapeHtml(text)}</span>`;
        }
        function escapeHtml(s) {
            return String(s || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
        }
    </script>
</body>
</html>
