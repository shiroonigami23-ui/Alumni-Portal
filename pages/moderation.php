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
    <title>Moderation Queue - RJIT Alumni Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="assets/css/variety-ui.css">
    <script src="includes/auth-check.js"></script>
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <div class="md:pl-64">
        <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Alumni Post Moderation Queue</h1>
                    <p class="text-sm text-gray-600 mt-1">Admins and moderators can approve or reject alumni posts pending verification.</p>
                </div>
                <button id="refreshBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Refresh</button>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-4 mb-4 flex items-center justify-between">
                <div class="text-sm text-gray-600">Pending items</div>
                <div id="pendingCount" class="text-lg font-semibold text-amber-700">0</div>
            </div>

            <div id="queueContainer" class="space-y-4"></div>
        </main>
    </div>

    <script>
        lucide.createIcons();

        document.addEventListener('DOMContentLoaded', async () => {
            const user = JSON.parse(localStorage.getItem('user_data') || '{}');
            if (!localStorage.getItem('jwt_token')) {
                window.location.href = 'login.php';
                return;
            }
            const isModerator = user.role === 'admin' || !!user.is_moderator;
            if (!isModerator) {
                window.location.href = 'dashboard.php';
                return;
            }
            document.getElementById('refreshBtn').addEventListener('click', loadQueue);
            await loadQueue();
        });

        function visibilityLabel(scope) {
            const map = {
                all: 'Everyone',
                alumni: 'Alumni only',
                faculty: 'Faculty only',
                students: 'Students only',
                faculty_alumni: 'Faculty + Alumni',
                students_alumni: 'Students + Alumni',
                faculty_students: 'Faculty + Students'
            };
            return map[String(scope || 'all')] || 'Everyone';
        }

        async function loadQueue() {
            const res = await makeApiCall('moderation_queue.php');
            const container = document.getElementById('queueContainer');
            if (!res || !res.success) {
                container.innerHTML = '<div class="bg-white rounded-xl shadow-sm p-8 text-center text-red-600">Failed to load moderation queue.</div>';
                return;
            }
            document.getElementById('pendingCount').textContent = String(res.pending_count || 0);
            const rows = Array.isArray(res.data) ? res.data : [];
            if (!rows.length) {
                container.innerHTML = '<div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">No alumni posts are waiting for review.</div>';
                return;
            }

            container.innerHTML = rows.map((item) => `
                <article class="bg-white rounded-xl shadow-sm p-5" id="queue-post-${item.post_id}">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">${escapeHtml(item.title || 'Untitled post')}</h3>
                            <p class="text-sm text-gray-600 mt-1">
                                ${escapeHtml(item.author_name)} (${escapeHtml(item.author_email)}) · ${fmt(item.queued_at || item.created_at)}
                            </p>
                            <p class="text-xs text-blue-700 mt-2">Audience: ${escapeHtml(visibilityLabel(item.visibility_scope))}</p>
                        </div>
                        <div class="px-2 py-1 rounded-full text-xs bg-amber-100 text-amber-800">
                            ${item.queue_item_type === 'edit_revision' ? 'Pending Edit' : 'Pending New Post'}
                        </div>
                    </div>
                    ${item.queue_item_type === 'edit_revision' ? `
                        <div class="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-3">
                            <p class="text-xs font-semibold text-gray-600 mb-1">Current live version</p>
                            <p class="text-gray-700 whitespace-pre-wrap text-sm">${escapeHtml(item.current_live_content || '')}</p>
                        </div>
                        <div class="mt-3 rounded-lg border border-blue-200 bg-blue-50 p-3">
                            <p class="text-xs font-semibold text-blue-700 mb-1">Proposed revision #${item.pending_revision_no || ''}</p>
                            <p class="text-gray-800 whitespace-pre-wrap">${escapeHtml(item.content || '')}</p>
                        </div>
                    ` : `<p class="text-gray-800 mt-3 whitespace-pre-wrap">${escapeHtml(item.content || '')}</p>`}
                    ${renderAttachments(item.attachments)}
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button class="px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700" onclick="reviewPost(${item.post_id}, 'approve')">Approve</button>
                        <button class="px-3 py-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700" onclick="reviewPost(${item.post_id}, 'reject')">Reject</button>
                        <a class="px-3 py-1.5 border rounded-lg hover:bg-gray-100" target="_blank" href="profile.php?id=${item.author_id}">Open author profile</a>
                    </div>
                </article>
            `).join('');
        }

        function renderAttachments(attachments) {
            if (!Array.isArray(attachments) || !attachments.length) return '';
            const chips = attachments.map((a) => `<span class="px-2 py-1 bg-gray-100 text-xs rounded">${escapeHtml(a.name || a.type || 'attachment')}</span>`).join('');
            return `<div class="mt-3 flex flex-wrap gap-2">${chips}</div>`;
        }

        async function reviewPost(postId, action) {
            const ok = await window.appConfirm(`Confirm ${action} for post #${postId}?`, {
                title: 'Moderation action',
                confirmText: action === 'approve' ? 'Approve' : 'Reject'
            });
            if (!ok) return;
            const res = await makeApiCall('moderation_queue.php', 'POST', { post_id: postId, action });
            if (!res || !res.success) {
                await window.appAlert((res && res.message) || 'Action failed', {
                    title: 'Moderation failed',
                    icon: 'triangle-alert',
                    iconTone: 'danger'
                });
                return;
            }
            const row = document.getElementById(`queue-post-${postId}`);
            if (row) row.remove();
            await loadQueue();
        }

        function fmt(v) {
            if (!v) return '-';
            return window.portalTime ? window.portalTime.format(v, 'date-time') : window.formatDate(v, 'date-time');
        }

        function escapeHtml(s) {
            return String(s ?? '').replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
        }
    </script>
</body>
</html>
