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
    <title>Admin Users - RJIT Alumni Portal</title>
    <link rel="stylesheet" href="../assets/css/tailwind.css?v=3">
    <script src="https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js"></script>
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
            <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                <h1 class="text-2xl font-semibold text-gray-900">User Management</h1>
                <a href="./dashboard.php" class="px-4 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-100">Back to Dashboard</a>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <input id="searchInput" type="text" placeholder="Search by name or email" class="px-3 py-2 border rounded-lg">
                    <select id="roleFilter" class="px-3 py-2 border rounded-lg">
                        <option value="">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="faculty">Faculty</option>
                        <option value="alumni">Alumni</option>
                        <option value="student">Student</option>
                    </select>
                    <select id="statusFilter" class="px-3 py-2 border rounded-lg">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="rejected">Rejected</option>
                        <option value="suspended">Suspended</option>
                        <option value="banned">Banned</option>
                    </select>
                    <button onclick="loadUsers(1)" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Apply Filters</button>
                </div>
            </div>

            <div id="bulkBar" class="hidden sticky top-2 z-20 mb-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 flex flex-wrap items-center gap-2 shadow-sm">
                <span class="text-sm font-semibold text-blue-900"><span id="bulkCount">0</span> selected</span>
                <button class="px-3 py-1.5 rounded-lg bg-green-600 text-white text-sm hover:bg-green-700" onclick="bulkAction('approve')">Approve sign-ups</button>
                <button class="px-3 py-1.5 rounded-lg bg-gray-700 text-white text-sm hover:bg-gray-800" onclick="bulkAction('reject')">Reject sign-ups</button>
                <button class="px-3 py-1.5 rounded-lg bg-red-600 text-white text-sm hover:bg-red-700" onclick="bulkAction('ban')">Ban</button>
                <button class="px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-sm hover:bg-gray-50" onclick="bulkAction('unban')">Unban / unsuspend</button>
                <button class="ml-auto text-sm text-blue-800 hover:underline" onclick="clearBulk()">Clear</button>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="py-3 px-3 w-8"><input type="checkbox" id="bulkAll" title="Select all on this page" aria-label="Select all on this page"></th>
                                <th class="text-left py-3 px-3 text-sm">User</th>
                                <th class="text-left py-3 px-3 text-sm">Role</th>
                                <th class="text-left py-3 px-3 text-sm">Status</th>
                                <th class="text-left py-3 px-3 text-sm">Branch</th>
                                <th class="text-left py-3 px-3 text-sm">Created</th>
                                <th class="text-left py-3 px-3 text-sm">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTable">
                            <tr><td colspan="7" class="py-8 text-center text-gray-500">Loading users...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex items-center justify-between text-sm">
                    <div id="tableMeta" class="text-gray-600"></div>
                    <div class="space-x-2">
                        <button id="prevBtn" class="px-3 py-1 border rounded disabled:opacity-40">Prev</button>
                        <button id="nextBtn" class="px-3 py-1 border rounded disabled:opacity-40">Next</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
        let currentPage = 1;
        let total = 0;
        const perPage = 25;

        document.addEventListener('DOMContentLoaded', () => {
            const qp = new URLSearchParams(window.location.search);
            const status = qp.get('status');
            if (status) document.getElementById('statusFilter').value = status;
            loadUsers(1);
            document.getElementById('prevBtn').addEventListener('click', () => loadUsers(currentPage - 1));
            document.getElementById('nextBtn').addEventListener('click', () => loadUsers(currentPage + 1));
        });

        async function loadUsers(page) {
            if (page < 1) return;
            const search = document.getElementById('searchInput').value.trim();
            const role = document.getElementById('roleFilter').value;
            const status = document.getElementById('statusFilter').value;
            const params = new URLSearchParams({ page, limit: perPage });
            if (search) params.set('search', search);
            if (role) params.set('role', role);
            if (status) params.set('status', status);

            const res = await makeApiCall(`admin_users.php?${params.toString()}`);
            const tbody = document.getElementById('usersTable');
            if (!res || !res.success) {
                tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-red-600">Failed to load users</td></tr>';
                return;
            }

            currentPage = res.page || page;
            total = res.total || 0;
            const rows = res.data || [];
            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-gray-500">No users found</td></tr>';
            } else {
                tbody.innerHTML = rows.map(user => `
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-3">${user.role !== 'admin' ? `<input type="checkbox" class="bulk-check" value="${Number(user.id)}" aria-label="Select ${escapeHtml(user.name)}">` : ''}</td>
                        <td class="py-3 px-3">
                            <div class="font-medium">${escapeHtml(user.name)}</div>
                            <div class="text-xs text-gray-500">${escapeHtml(user.email)}</div>
                        </td>
                        <td class="py-3 px-3 text-sm">
                            ${badge(user.role, roleClass(user.role))}
                            ${user.is_moderator ? '<span class="ml-2 px-2 py-1 rounded-full text-xs bg-indigo-100 text-indigo-800">MODERATOR</span>' : ''}
                        </td>
                        <td class="py-3 px-3 text-sm">${badge(user.status, statusClass(user.status))}</td>
                        <td class="py-3 px-3 text-sm text-gray-700">${escapeHtml(user.branch || '-')}</td>
                        <td class="py-3 px-3 text-sm text-gray-700">${fmt(user.created_at)}</td>
                        <td class="py-3 px-3 text-sm">
                            <div class="flex flex-wrap gap-2">
                                <a class="px-2 py-1 border rounded hover:bg-gray-100" href="../profile.php?id=${escapeHtml(user.id)}" target="_blank">View</a>
                                ${user.status === 'pending' ? `<button class="px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700" onclick="updateUser(${escapeHtml(user.id)},'approve')">Approve</button>` : ''}
                                ${user.status === 'pending' ? `<button class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700" onclick="updateUser(${escapeHtml(user.id)},'reject')">Reject</button>` : ''}
                                ${!user.is_banned ? `<button class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700" onclick="adminUserAction(${escapeHtml(user.id)}, 'ban_user')">Ban</button>` : ''}
                                ${user.is_banned ? `<button class="px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700" onclick="adminUserAction(${escapeHtml(user.id)}, 'unban_user')">Unban</button>` : ''}
                                ${!user.is_banned && !user.shadow_banned ? `<button class="px-2 py-1 bg-gray-800 text-white rounded hover:bg-gray-900" onclick="adminUserAction(${escapeHtml(user.id)}, 'shadow_ban')">Shadow 7d</button>` : ''}
                                ${!user.is_banned && user.shadow_banned ? `<button class="px-2 py-1 border rounded hover:bg-gray-100" onclick="adminUserAction(${escapeHtml(user.id)}, 'lift_shadow_ban')">Lift Shadow</button>` : ''}
                                ${!user.is_banned && !user.shadow_banned && !user.messaging_restricted ? `<button class="px-2 py-1 bg-slate-700 text-white rounded hover:bg-slate-800" onclick="adminUserAction(${escapeHtml(user.id)}, 'restrict_messaging')">Mute DM 7d</button>` : ''}
                                ${!user.is_banned && !user.shadow_banned && user.messaging_restricted ? `<button class="px-2 py-1 border rounded hover:bg-gray-100" onclick="adminUserAction(${escapeHtml(user.id)}, 'lift_messaging_restriction')">Unmute DM</button>` : ''}
                                ${user.role !== 'admin' && !user.is_moderator ? `<button class="px-2 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700" onclick="adminUserAction(${escapeHtml(user.id)}, 'promote_moderator')">Make Moderator</button>` : ''}
                                ${user.role !== 'admin' && user.is_moderator ? `<button class="px-2 py-1 border rounded hover:bg-gray-100" onclick="adminUserAction(${escapeHtml(user.id)}, 'revoke_moderator')">Remove Moderator</button>` : ''}
                                ${user.role !== 'admin' && user.status !== 'suspended' && !user.is_banned ? `<button class="px-2 py-1 bg-orange-600 text-white rounded hover:bg-orange-700" onclick="suspendUser(${Number(user.id)})">Suspend…</button>` : ''}
                                ${user.status === 'suspended' ? `<button class="px-2 py-1 border rounded hover:bg-gray-100" onclick="adminUserAction(${Number(user.id)}, 'unsuspend')">Unsuspend</button>` : ''}
                                ${user.role !== 'admin' ? `<button class="px-2 py-1 border rounded hover:bg-gray-100" data-email="${escapeHtml(user.email)}" onclick="changeEmail(${Number(user.id)}, this.dataset.email)">Change email…</button>` : ''}
                                <button class="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700" onclick="resetUserPassword(${escapeHtml(user.id)}, '${escapeHtml(escapeJs(user.email))}')">Reset Password</button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }

            const start = total === 0 ? 0 : ((currentPage - 1) * perPage) + 1;
            const end = Math.min(currentPage * perPage, total);
            document.getElementById('tableMeta').textContent = `Showing ${start}-${end} of ${total}`;
            document.getElementById('prevBtn').disabled = currentPage <= 1;
            document.getElementById('nextBtn').disabled = end >= total;
        }

        // ---- bulk selection
        function selectedIds() { return [...document.querySelectorAll('.bulk-check:checked')].map(c => Number(c.value)); }
        function syncBulkBar() {
            const n = selectedIds().length;
            document.getElementById('bulkCount').textContent = n;
            document.getElementById('bulkBar').classList.toggle('hidden', n === 0);
        }
        function clearBulk() {
            document.querySelectorAll('.bulk-check, #bulkAll').forEach(c => { c.checked = false; });
            syncBulkBar();
        }
        document.addEventListener('change', (e) => {
            if (e.target.id === 'bulkAll') document.querySelectorAll('.bulk-check').forEach(c => { c.checked = e.target.checked; });
            if (e.target.id === 'bulkAll' || e.target.classList.contains('bulk-check')) syncBulkBar();
        });
        async function bulkAction(kind) {
            const ids = selectedIds();
            const words = { approve: 'approve the pending sign-ups among', reject: 'reject the pending sign-ups among', ban: 'ban', unban: 'unban / unsuspend' };
            if (!ids.length || !await window.appConfirm(`Really ${words[kind]} ${ids.length} selected account${ids.length === 1 ? '' : 's'}? Accounts already in that state are skipped.`, {
                title: 'Bulk action', confirmText: 'Apply', iconTone: kind === 'ban' ? 'danger' : 'info'
            })) return;
            const res = await makeApiCall('admin_user_actions.php', 'POST', { action: 'bulk', bulk_action: kind, user_ids: ids });
            window.toast?.((res && res.message) || 'Done', res && res.success === false ? 'error' : 'success');
            clearBulk();
            loadUsers(currentPage);
        }

        async function suspendUser(userId) {
            const days = await window.appPrompt?.('Suspend for how many days? (1–30). They are signed out and can\'t sign in until it ends; it lifts by itself.', {
                title: 'Suspend account', confirmText: 'Suspend', inputLabel: 'Days', defaultValue: '3'
            });
            if (days === null || days === undefined) return;
            const n = Math.max(1, Math.min(30, parseInt(days, 10) || 3));
            const reason = await window.appPrompt?.('Reason (shown to them, optional).', { title: 'Suspend account', confirmText: 'Suspend', inputLabel: 'Reason' });
            if (reason === null) return;
            const res = await makeApiCall('admin_user_actions.php', 'POST', { action: 'suspend', target_user_id: userId, days: n, reason: String(reason || '') });
            window.toast?.((res && res.message) || 'Done', res && res.success === false ? 'error' : 'success');
            loadUsers(currentPage);
        }

        async function changeEmail(userId, currentEmail) {
            const next = await window.appPrompt?.(`New sign-in email for this account (now ${currentEmail}). Their old password still works; old reset links stop working.`, {
                title: 'Change sign-in email', confirmText: 'Change email', inputLabel: 'New email', defaultValue: ''
            });
            if (!next) return;
            const res = await makeApiCall('admin_user_actions.php', 'POST', { action: 'change_email', target_user_id: userId, new_email: String(next).trim() });
            if (res && res.success === false) {
                await window.appAlert(res.message || 'Could not change the email.', { title: 'Change email', icon: 'triangle-alert', iconTone: 'danger' });
                return;
            }
            window.toast?.(res.message);
            loadUsers(currentPage);
        }

        async function updateUser(userId, action) {
            const ok = await window.appConfirm(`Confirm ${action} for user #${userId}?`, {
                title: 'Confirm user action',
                confirmText: 'Continue'
            });
            if (!ok) return;
            const res = await makeApiCall('approve_user.php', 'POST', { target_user_id: userId, action });
            if (!res || res.success === false) {
                await window.appAlert((res && res.message) ? res.message : 'Failed to update user', {
                    title: 'Update failed',
                    icon: 'triangle-alert',
                    iconTone: 'danger'
                });
                return;
            }
            loadUsers(currentPage);
        }

        async function adminUserAction(userId, action) {
            const payload = { action, target_user_id: userId };
            if (action === 'shadow_ban' || action === 'restrict_messaging' || action === 'restrict_posting') {
                payload.duration_hours = 168;
            }
            const ok = await window.appConfirm(`Run ${action} for user #${userId}?`, {
                title: 'Confirm admin action',
                confirmText: 'Run action'
            });
            if (!ok) return;
            const res = await makeApiCall('admin_user_actions.php', 'POST', payload);
            if (!res || res.success === false) {
                await window.appAlert((res && res.message) ? res.message : 'Admin action failed', {
                    title: 'Admin action failed',
                    icon: 'triangle-alert',
                    iconTone: 'danger'
                });
                return;
            }
            loadUsers(currentPage);
        }

        async function resetUserPassword(userId, email) {
            const newPassword = await window.appPrompt(`Enter a new password for ${email}:`, {
                title: 'Reset user password',
                inputLabel: 'New password',
                confirmText: 'Reset password'
            });
            if (!newPassword) return;
            const res = await makeApiCall('admin_user_actions.php', 'POST', {
                action: 'reset_password',
                target_user_id: userId,
                new_password: newPassword
            });
            if (!res || res.success === false) {
                await window.appAlert((res && res.message) ? res.message : 'Password reset failed', {
                    title: 'Reset failed',
                    icon: 'triangle-alert',
                    iconTone: 'danger'
                });
                return;
            }
            await window.appAlert('Password reset successfully.', {
                title: 'Password reset',
                icon: 'shield-check',
                iconTone: 'success'
            });
        }

        function fmt(v) {
            if (!v) return '-';
            return window.portalTime ? window.portalTime.format(v, 'date-time') : window.formatDate(v, 'date-time');
        }
        function roleClass(role) {
            if (role === 'admin') return 'bg-amber-100 text-amber-800';
            if (role === 'faculty') return 'bg-blue-100 text-blue-800';
            if (role === 'alumni') return 'bg-green-100 text-green-800';
            if (role === 'student') return 'bg-purple-100 text-purple-800';
            return 'bg-gray-100 text-gray-800';
        }
        function statusClass(status) {
            if (status === 'active') return 'bg-green-100 text-green-800';
            if (status === 'pending') return 'bg-amber-100 text-amber-800';
            if (status === 'rejected' || status === 'banned') return 'bg-red-100 text-red-800';
            return 'bg-gray-100 text-gray-800';
        }
        function badge(text, cls) {
            return `<span class="px-2 py-1 rounded-full text-xs ${escapeHtml(cls)}">${escapeHtml(text || '-')}</span>`;
        }
        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
        }
        function escapeJs(s) {
            return String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
        }
    </script>
</body>
</html>
