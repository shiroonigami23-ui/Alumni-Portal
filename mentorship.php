<?php
session_start();

$pageTitle = "Mentorship - RJIT Alumni Portal";
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="md:pl-64 pb-20 md:pb-0">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl p-8 text-white mb-8">
            <div class="max-w-3xl">
                <h1 class="text-3xl font-bold mb-4">Mentorship</h1>
                <p class="text-lg mb-6">Connect mentors and mentees from the RJIT community. No demo cards are shown here.</p>
                <div class="flex flex-wrap gap-3">
                    <button id="findMentorBtn" class="bg-white text-blue-700 px-5 py-2.5 rounded-lg font-semibold hover:bg-gray-100">Find a Mentor</button>
                    <button id="becomeMentorBtn" class="bg-blue-700/30 border border-white/50 text-white px-5 py-2.5 rounded-lg font-semibold hover:bg-blue-700/50">Become a Mentor</button>
                </div>
                <p id="mentorRoleHint" class="text-sm mt-4 text-blue-100"></p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 text-center">
                <div class="text-3xl font-bold text-blue-600 mb-2" id="mentorsCount">0</div>
                <p class="text-gray-700">Active Mentors</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 text-center">
                <div class="text-3xl font-bold text-green-600 mb-2" id="requestsCount">0</div>
                <p class="text-gray-700">Open Requests</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 text-center">
                <div class="text-3xl font-bold text-purple-600 mb-2" id="acceptedCount">0</div>
                <p class="text-gray-700">Accepted Matches</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Mentor Directory</h2>
                <div id="mentorList" class="space-y-4">
                    <div class="text-sm text-gray-500">Loading mentors...</div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4" id="requestsPanelTitle">Mentorship Requests</h2>
                <div id="requestsList" class="space-y-3">
                    <div class="text-sm text-gray-500">No requests yet.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="mentorshipRequestModal" class="hidden fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black bg-opacity-40"></div>
    <div class="relative max-w-lg mx-auto mt-24 bg-white rounded-xl shadow-lg border border-gray-200 p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-3">Request Mentorship</h3>
        <form id="mentorshipRequestForm" class="space-y-4">
            <input type="hidden" id="requestMentorId">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Message to mentor</label>
                <textarea id="requestMessage" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Please guide me in..."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button id="requestCancelBtn" type="button" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button id="requestSubmitBtn" type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Send Request</button>
            </div>
        </form>
    </div>
</div>

<script>
    lucide.createIcons();
    let currentUser = null;
    let currentRole = '';

    async function loadCurrentUser() {
        try {
            const res = await makeApiCall('me.php');
            if (res && (res.success || res.status === 'success') && res.data) {
                currentUser = res.data;
            } else {
                currentUser = JSON.parse(localStorage.getItem('user_data') || '{}');
            }
        } catch (_e) {
            currentUser = JSON.parse(localStorage.getItem('user_data') || '{}');
        }
        currentRole = String(currentUser?.role || '').toLowerCase();
    }

    function initRoleBasedActions() {
        const hint = document.getElementById('mentorRoleHint');
        const becomeBtn = document.getElementById('becomeMentorBtn');
        const requestsTitle = document.getElementById('requestsPanelTitle');
        if (['faculty', 'alumni', 'admin'].includes(currentRole)) {
            hint.textContent = 'You can become a mentor and accept student requests.';
            becomeBtn.disabled = false;
            becomeBtn.classList.remove('opacity-60', 'cursor-not-allowed');
            requestsTitle.textContent = 'Incoming Requests';
        } else if (currentRole === 'student') {
            hint.textContent = 'Students can find mentors and send join requests.';
            becomeBtn.disabled = true;
            becomeBtn.classList.add('opacity-60', 'cursor-not-allowed');
            requestsTitle.textContent = 'My Requests';
        } else {
            hint.textContent = 'Please sign in with a valid role to use mentorship actions.';
            becomeBtn.disabled = true;
            becomeBtn.classList.add('opacity-60', 'cursor-not-allowed');
            requestsTitle.textContent = 'Mentorship Requests';
        }
    }

    async function loadMentorshipStats() {
        try {
            const token = localStorage.getItem('jwt_token');
            if (!token) return;
            const mentorsRes = await fetch('api/mentorship.php?action=list_mentors', {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const mentorsPayload = await mentorsRes.json();
            const mentors = Array.isArray(mentorsPayload?.data) ? mentorsPayload.data : [];

            const reqAction = currentRole === 'student' ? 'list_my_requests' : 'list_requests';
            const reqRes = await fetch(`api/mentorship.php?action=${reqAction}`, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const reqPayload = await reqRes.json();
            const reqs = Array.isArray(reqPayload?.data) ? reqPayload.data : [];

            document.getElementById('mentorsCount').textContent = String(mentors.length);
            document.getElementById('requestsCount').textContent = String(reqs.filter((r) => r.status === 'pending').length);
            document.getElementById('acceptedCount').textContent = String(reqs.filter((r) => r.status === 'accepted').length);
        } catch (e) {
            console.error('Failed to load mentorship stats:', e);
        }
    }

    async function loadMentorList() {
        const listEl = document.getElementById('mentorList');
        try {
            const token = localStorage.getItem('jwt_token');
            const res = await fetch('api/mentorship.php?action=list_mentors', {
                headers: token ? { 'Authorization': `Bearer ${token}` } : {}
            });
            const payload = await res.json();
            const mentors = Array.isArray(payload?.data) ? payload.data : [];
            if (!mentors.length) {
                listEl.innerHTML = '<div class="text-sm text-gray-500">No active mentors yet.</div>';
                return;
            }
            listEl.innerHTML = mentors.map((m) => `
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="font-semibold text-gray-900">${m.mentor_name || 'Mentor'}</p>
                            <p class="text-sm text-gray-600 capitalize">${m.role || ''}</p>
                            <p class="text-sm text-gray-500 mt-1">${m.headline || 'Available for mentorship'}</p>
                            ${m.expertise ? `<p class="text-xs text-gray-500 mt-1">Expertise: ${m.expertise}</p>` : ''}
                        </div>
                        ${currentRole === 'student' ? `<button class="request-mentor-btn bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700" data-mentor-id="${m.mentor_id}">Join</button>` : ''}
                    </div>
                </div>
            `).join('');
            bindMentorRequestButtons();
        } catch (e) {
            listEl.innerHTML = '<div class="text-sm text-red-600">Unable to load mentors.</div>';
        }
    }

    async function loadRequestsPanel() {
        const listEl = document.getElementById('requestsList');
        try {
            const token = localStorage.getItem('jwt_token');
            if (!token) {
                listEl.innerHTML = '<div class="text-sm text-gray-500">Sign in to view requests.</div>';
                return;
            }
            const action = currentRole === 'student' ? 'list_my_requests' : 'list_requests';
            const res = await fetch(`api/mentorship.php?action=${action}`, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const payload = await res.json();
            const rows = Array.isArray(payload?.data) ? payload.data : [];
            if (!rows.length) {
                listEl.innerHTML = '<div class="text-sm text-gray-500">No requests yet.</div>';
                return;
            }
            listEl.innerHTML = rows.map((r) => `
                <div class="border border-gray-200 rounded-lg p-3">
                    <p class="font-medium text-gray-900 text-sm">${currentRole === 'student' ? (r.mentor_name || 'Mentor') : (r.mentee_name || 'Student')}</p>
                    <p class="text-xs text-gray-500 mt-1">${r.message || ''}</p>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-xs px-2 py-0.5 rounded-full ${r.status === 'accepted' ? 'bg-green-100 text-green-700' : (r.status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700')}">${r.status}</span>
                        ${(['faculty', 'alumni', 'admin'].includes(currentRole) && r.status === 'pending') ? `
                            <div class="flex gap-2">
                                <button class="mentor-respond-btn text-xs px-2 py-1 bg-green-600 text-white rounded" data-request-id="${r.request_id}" data-status="accepted">Accept</button>
                                <button class="mentor-respond-btn text-xs px-2 py-1 bg-red-600 text-white rounded" data-request-id="${r.request_id}" data-status="rejected">Reject</button>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `).join('');
            bindMentorRespondButtons();
        } catch (e) {
            listEl.innerHTML = '<div class="text-sm text-red-600">Unable to load requests.</div>';
        }
    }

    function bindMentorRequestButtons() {
        document.querySelectorAll('.request-mentor-btn').forEach((btn) => {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', () => {
                document.getElementById('requestMentorId').value = btn.getAttribute('data-mentor-id') || '';
                document.getElementById('mentorshipRequestModal').classList.remove('hidden');
            });
        });
    }

    function bindMentorRespondButtons() {
        document.querySelectorAll('.mentor-respond-btn').forEach((btn) => {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', async () => {
                const request_id = Number(btn.getAttribute('data-request-id') || 0);
                const status = btn.getAttribute('data-status') || '';
                if (!request_id || !status) return;
                const res = await makeApiCall('mentorship.php?action=respond', 'POST', { request_id, status });
                if (res && res.success) {
                    await loadRequestsPanel();
                    await loadMentorshipStats();
                } else {
                    alert((res && res.message) || 'Failed to update request');
                }
            });
        });
    }

    function initMentorshipActions() {
        const findBtn = document.getElementById('findMentorBtn');
        const becomeBtn = document.getElementById('becomeMentorBtn');
        const modal = document.getElementById('mentorshipRequestModal');
        const cancelBtn = document.getElementById('requestCancelBtn');
        const form = document.getElementById('mentorshipRequestForm');

        if (findBtn) {
            findBtn.addEventListener('click', () => {
                const list = document.getElementById('mentorList');
                if (list) list.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }

        if (becomeBtn) {
            becomeBtn.addEventListener('click', async () => {
                if (!['faculty', 'alumni', 'admin'].includes(currentRole)) return;
                const headline = prompt('Add a short mentor headline (optional):', '') || '';
                const expertise = prompt('Expertise (optional):', '') || '';
                const res = await makeApiCall('mentorship.php?action=become_mentor', 'POST', { headline, expertise });
                if (res && res.success) {
                    alert('You are now listed as a mentor.');
                    await loadMentorList();
                    await loadMentorshipStats();
                } else {
                    alert((res && res.message) || 'Unable to become mentor');
                }
            });
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => modal.classList.add('hidden'));
        }
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const mentor_id = Number(document.getElementById('requestMentorId').value || 0);
                const message = (document.getElementById('requestMessage').value || '').trim() || 'I want to join under your mentorship.';
                if (!mentor_id) return;
                const res = await makeApiCall('mentorship.php?action=request', 'POST', { mentor_id, message });
                if (res && res.success) {
                    alert('Mentorship request sent.');
                    form.reset();
                    modal.classList.add('hidden');
                    await loadRequestsPanel();
                    await loadMentorshipStats();
                } else {
                    alert((res && res.message) || 'Failed to send request');
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', async () => {
        await loadCurrentUser();
        initRoleBasedActions();
        initMentorshipActions();
        await loadMentorList();
        await loadRequestsPanel();
        await loadMentorshipStats();
    });
</script>

<?php include 'includes/footer.php'; ?>
