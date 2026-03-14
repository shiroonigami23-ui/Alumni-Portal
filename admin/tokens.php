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
    <title>Invite Tokens - Admin</title>
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
                <h1 class="text-2xl font-semibold text-gray-900">Alumni Invite Tokens</h1>
                <a href="./dashboard.php" class="px-4 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-100">Back to Dashboard</a>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Generate New Token</h2>
                    <button onclick="generateToken()" class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Generate Token</button>
                    <div id="tokenResult" class="hidden mt-4 p-3 rounded-lg bg-blue-50 border border-blue-200">
                        <p class="text-sm text-blue-900 mb-1">Generated token</p>
                        <div class="flex items-center gap-2">
                            <code id="tokenValue" class="font-mono text-blue-900 text-sm flex-1 break-all"></code>
                            <button onclick="copyToken()" class="px-2 py-1 border rounded text-sm">Copy</button>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Recent Tokens</h2>
                        <button onclick="loadTokens()" class="px-3 py-1 border rounded text-sm hover:bg-gray-100">Refresh</button>
                    </div>
                    <div id="tokensList" class="space-y-2 max-h-96 overflow-auto text-sm text-gray-700">
                        Loading...
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        lucide.createIcons();
        document.addEventListener('DOMContentLoaded', loadTokens);

        async function generateToken() {
            const res = await makeApiCall('generate_token.php', 'POST', {});
            if (!res || res.success === false) {
                await window.appAlert((res && res.message) ? res.message : 'Failed to generate token', {
                    title: 'Token generation failed',
                    icon: 'triangle-alert',
                    iconTone: 'danger'
                });
                return;
            }
            const token = (res.data && res.data.token) || res.token || '';
            document.getElementById('tokenValue').textContent = token;
            document.getElementById('tokenResult').classList.remove('hidden');
            loadTokens();
        }

        async function loadTokens() {
            const res = await makeApiCall('generate_token.php?action=list');
            const list = document.getElementById('tokensList');
            const rows = (res && res.success && Array.isArray(res.data)) ? res.data : [];
            if (!rows.length) {
                list.innerHTML = '<div class="text-gray-500">No tokens available.</div>';
                return;
            }
            list.innerHTML = rows.map(t => `
                <div class="p-3 border rounded-lg">
                    <div class="font-mono text-xs break-all">${escapeHtml(t.token)}</div>
                    <div class="mt-1 text-xs text-gray-500">
                        <span>${t.is_active ? 'Active' : 'Used'}</span>
                        <span class="mx-1">•</span>
                        <span>${window.portalTime ? window.portalTime.format(t.created_at, 'date-time') : window.formatDate(t.created_at, 'date-time')}</span>
                        ${t.email ? `<span class="mx-1">•</span><span>${escapeHtml(t.email)}</span>` : ''}
                    </div>
                </div>
            `).join('');
        }

        function copyToken() {
            const value = document.getElementById('tokenValue').textContent;
            navigator.clipboard.writeText(value);
        }
        function escapeHtml(s) {
            return String(s || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
        }
    </script>
</body>
</html>
