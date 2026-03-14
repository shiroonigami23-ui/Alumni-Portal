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
                <div id="mentorStatusBanner" class="hidden mt-4 rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-sm text-blue-50"></div>
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
            <div class="space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4" id="requestsPanelTitle">Mentorship Requests</h2>
                    <div id="requestsList" class="space-y-3">
                        <div class="text-sm text-gray-500">No requests yet.</div>
                    </div>
                </div>
                <div id="activeMentorshipPanel" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4" id="activeMentorshipTitle">Active Mentorship</h2>
                    <div id="activeMentorshipList" class="space-y-3">
                        <div class="text-sm text-gray-500">Loading current mentor details...</div>
                    </div>
                </div>
                <div id="mentorApplicationsPanel" class="hidden bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Mentor Applications</h2>
                    <div id="mentorApplicationsList" class="space-y-3">
                        <div class="text-sm text-gray-500">No pending mentor applications.</div>
                    </div>
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
    let mentorStatus = null;
    let activeMentorshipRows = [];
    let currentActiveMatch = null;
    let currentAdminGroupMembership = null;
    const MENTORSHIP_API_BASE = (window.getApiBase ? window.getApiBase() : ((window.PORTAL_BASE_PREFIX || '') + 'api'));

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

    async function loadMentorStatus() {
        try {
            const res = await makeApiCall('mentorship.php?action=get_status');
            mentorStatus = res && res.success ? (res.data || null) : null;
        } catch (_e) {
            mentorStatus = null;
        }
    }

    function showMentorBanner(message, tone = 'info') {
        const banner = document.getElementById('mentorStatusBanner');
        if (!banner) return;
        if (!message) {
            banner.classList.add('hidden');
            banner.textContent = '';
            banner.className = 'hidden mt-4 rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-sm text-blue-50';
            return;
        }
        banner.className = 'mt-4 rounded-xl px-4 py-3 text-sm';
        if (tone === 'success') {
            banner.classList.add('border', 'border-emerald-300/40', 'bg-emerald-500/15', 'text-emerald-50');
        } else if (tone === 'warning') {
            banner.classList.add('border', 'border-amber-300/40', 'bg-amber-500/15', 'text-amber-50');
        } else {
            banner.classList.add('border', 'border-white/20', 'bg-white/10', 'text-blue-50');
        }
        banner.textContent = message;
        banner.classList.remove('hidden');
    }

    function initRoleBasedActions() {
        const hint = document.getElementById('mentorRoleHint');
        const becomeBtn = document.getElementById('becomeMentorBtn');
        const requestsTitle = document.getElementById('requestsPanelTitle');
        const activeTitle = document.getElementById('activeMentorshipTitle');
        const appsPanel = document.getElementById('mentorApplicationsPanel');
        const profile = mentorStatus?.mentor_profile || null;
        currentActiveMatch = mentorStatus?.active_match || null;
        currentAdminGroupMembership = mentorStatus?.admin_group_membership || null;

        if (currentRole === 'student') {
            hint.textContent = 'Students can browse approved mentors and request to join them.';
            becomeBtn.classList.add('hidden');
            requestsTitle.textContent = 'My Requests';
            if (activeTitle) activeTitle.textContent = 'Current Mentor';
            if (appsPanel) appsPanel.classList.add('hidden');
            showMentorBanner('');
            return;
        }

        becomeBtn.classList.remove('hidden');

        if (mentorStatus?.can_review_applications) {
            if (appsPanel) appsPanel.classList.remove('hidden');
        } else if (appsPanel) {
            appsPanel.classList.add('hidden');
        }

        if (currentRole === 'faculty' || currentRole === 'admin') {
            hint.textContent = currentRole === 'admin'
                ? 'Admins can mentor immediately, review alumni mentor applications, and manage any mentor group.'
                : 'Faculty can mentor immediately, review alumni mentor applications, and can also join admin-led mentor groups.';
            becomeBtn.disabled = false;
            becomeBtn.classList.remove('opacity-60', 'cursor-not-allowed');
            becomeBtn.textContent = profile?.is_active ? 'Update Mentor Profile' : 'Become a Mentor';
            requestsTitle.textContent = 'Incoming Requests';
            if (activeTitle) activeTitle.textContent = 'Mentor Group';
            showMentorBanner(profile?.is_active ? 'Your mentor profile is active. Students can request to join your mentor group.' : '');
            return;
        }

        if (currentRole === 'alumni') {
            requestsTitle.textContent = profile?.is_active ? 'Incoming Requests' : 'My Requests';
            if (activeTitle) activeTitle.textContent = profile?.is_active ? 'Mentor Group' : 'Current Mentor';
            if (profile?.approval_status === 'pending') {
                hint.textContent = 'Alumni mentor applications need faculty or admin approval before students can see them.';
                becomeBtn.disabled = true;
                becomeBtn.textContent = 'Application Pending';
                becomeBtn.classList.add('opacity-60', 'cursor-not-allowed');
                showMentorBanner('Your mentor application is pending faculty/admin approval.', 'warning');
                return;
            }
            if (profile?.approval_status === 'approved') {
                hint.textContent = 'Your mentor profile is approved. Students can send requests, and accepted students join your mentor group.';
                becomeBtn.disabled = false;
                becomeBtn.textContent = 'Update Mentor Profile';
                becomeBtn.classList.remove('opacity-60', 'cursor-not-allowed');
                showMentorBanner('Your mentor profile is approved and active.', 'success');
                return;
            }
            if (profile?.approval_status === 'rejected') {
                hint.textContent = 'Alumni need faculty or admin approval before they can mentor.';
                becomeBtn.disabled = false;
                becomeBtn.textContent = 'Reapply to Be a Mentor';
                becomeBtn.classList.remove('opacity-60', 'cursor-not-allowed');
                showMentorBanner('Your previous mentor application was not approved. You can reapply with updated details.', 'warning');
                return;
            }

            hint.textContent = 'Alumni can apply to become mentors, request one regular mentor at a time, and may also join one admin-led mentor group.';
            becomeBtn.disabled = false;
            becomeBtn.textContent = 'Apply to Become a Mentor';
            becomeBtn.classList.remove('opacity-60', 'cursor-not-allowed');
            showMentorBanner('');
            return;
        }

        hint.textContent = 'Please sign in with a valid role to use mentorship actions.';
        becomeBtn.disabled = true;
        becomeBtn.classList.add('opacity-60', 'cursor-not-allowed');
        requestsTitle.textContent = 'Mentorship Requests';
    }

    async function loadMentorshipStats() {
        try {
            const token = localStorage.getItem('jwt_token');
            if (!token) return;
            const mentorsRes = await fetch(`${MENTORSHIP_API_BASE}/mentorship.php?action=list_mentors`, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const mentorsPayload = await mentorsRes.json();
            const mentors = Array.isArray(mentorsPayload?.data) ? mentorsPayload.data : [];

            const reqAction = (currentRole === 'student' || (currentRole === 'alumni' && !mentorStatus?.mentor_profile?.is_active))
                ? 'list_my_requests'
                : 'list_requests';
            const reqRes = await fetch(`${MENTORSHIP_API_BASE}/mentorship.php?action=${reqAction}`, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const reqPayload = await reqRes.json();
            const reqs = Array.isArray(reqPayload?.data) ? reqPayload.data : [];

            const matchesRes = await fetch(`${MENTORSHIP_API_BASE}/mentorship.php?action=list_active_matches`, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const matchesPayload = await matchesRes.json();
            const matches = Array.isArray(matchesPayload?.data) ? matchesPayload.data : [];

            document.getElementById('mentorsCount').textContent = String(mentors.length);
            document.getElementById('requestsCount').textContent = String(reqs.filter((r) => r.status === 'pending').length);
            document.getElementById('acceptedCount').textContent = String(matches.length);
        } catch (e) {
            console.error('Failed to load mentorship stats:', e);
        }
    }

    async function loadMentorList() {
        const listEl = document.getElementById('mentorList');
        try {
            const token = localStorage.getItem('jwt_token');
            const res = await fetch(`${MENTORSHIP_API_BASE}/mentorship.php?action=list_mentors`, {
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
                        ${renderMentorActionButton(m)}
                    </div>
                </div>
            `).join('');
            bindMentorRequestButtons();
        } catch (e) {
            listEl.innerHTML = '<div class="text-sm text-red-600">Unable to load mentors.</div>';
        }
    }

    function renderMentorActionButton(mentor) {
        if (!mentorStatus?.can_request) {
            return '';
        }
        const mentorRole = String(mentor.role || '').toLowerCase();
        const isAdminGroup = mentorRole === 'admin';
        const selfMentorActive = Boolean(mentorStatus?.mentor_profile?.is_active);
        if (isAdminGroup && currentRole === 'student') {
            return '<span class="text-xs text-gray-400 font-medium">Admin-led group is not available to students</span>';
        }
        if (currentRole === 'faculty' && !isAdminGroup) {
            return '<span class="text-xs text-amber-600 font-medium">Faculty can only join admin-led groups</span>';
        }
        if (!isAdminGroup && selfMentorActive) {
            return '<span class="text-xs text-amber-600 font-medium">You already run your own mentor group</span>';
        }
        if (currentAdminGroupMembership && Number(currentAdminGroupMembership.mentor_id) === Number(mentor.mentor_id)) {
            return mentor.group_id
                ? `<a href="messages.php?group_id=${mentor.group_id}" class="bg-indigo-600 text-white px-3 py-2 rounded-lg hover:bg-indigo-700">Open Admin GC</a>`
                : '<span class="text-xs text-indigo-600 font-medium">Already in admin group</span>';
        }
        if (currentAdminGroupMembership && isAdminGroup) {
            return '<span class="text-xs text-amber-600 font-medium">Leave your current admin group first</span>';
        }
        if (currentActiveMatch && Number(currentActiveMatch.mentor_id) === Number(mentor.mentor_id)) {
            return mentor.group_id
                ? `<a href="messages.php?group_id=${mentor.group_id}" class="bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700">Open Group</a>`
                : '<span class="text-xs text-green-600 font-medium">Current mentor</span>';
        }
        if (currentActiveMatch) {
            return '<span class="text-xs text-amber-600 font-medium">Leave your current mentor first</span>';
        }
        return `<button class="request-mentor-btn bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700" data-mentor-id="${mentor.mentor_id}">Request to Join</button>`;
    }

    async function loadRequestsPanel() {
        const listEl = document.getElementById('requestsList');
        try {
            const token = localStorage.getItem('jwt_token');
            if (!token) {
                listEl.innerHTML = '<div class="text-sm text-gray-500">Sign in to view requests.</div>';
                return;
            }
        const action = (mentorStatus?.can_request && !mentorStatus?.mentor_profile?.is_active)
                ? 'list_my_requests'
                : 'list_requests';
            const res = await fetch(`${MENTORSHIP_API_BASE}/mentorship.php?action=${action}`, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const payload = await res.json();
            const rows = Array.isArray(payload?.data) ? payload.data : [];
            const acceptedRequest = mentorStatus?.can_request
                ? rows.find((row) => String(row.status || '').toLowerCase() === 'accepted')
                : null;
            if (acceptedRequest && !currentActiveMatch) {
                currentActiveMatch = {
                    mentor_id: Number(acceptedRequest.mentor_id || 0),
                    mentor_name: acceptedRequest.mentor_name || 'Current mentor',
                    mentor_role: acceptedRequest.mentor_role || '',
                    group_id: Number(acceptedRequest.group_id || 0),
                    joined_at: acceptedRequest.created_at || new Date().toISOString()
                };
            }
            if (!rows.length) {
                listEl.innerHTML = '<div class="text-sm text-gray-500">No requests yet.</div>';
                return;
            }
            listEl.innerHTML = rows.map((r) => `
                <div class="border border-gray-200 rounded-lg p-3">
                    <p class="font-medium text-gray-900 text-sm">${(action === 'list_my_requests') ? (r.mentor_name || 'Mentor') : (r.mentee_name || 'Student')}</p>
                    <p class="text-xs text-gray-500 mt-1">${r.message || ''}</p>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-xs px-2 py-0.5 rounded-full ${r.status === 'accepted' ? 'bg-green-100 text-green-700' : (r.status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700')}">${r.status}</span>
                        ${((['faculty', 'admin'].includes(currentRole) || (currentRole === 'alumni' && mentorStatus?.mentor_profile?.is_active)) && r.status === 'pending') ? `
                            <div class="flex gap-2">
                                <button class="mentor-respond-btn text-xs px-2 py-1 bg-green-600 text-white rounded" data-request-id="${r.request_id}" data-status="accepted">Accept</button>
                                <button class="mentor-respond-btn text-xs px-2 py-1 bg-red-600 text-white rounded" data-request-id="${r.request_id}" data-status="rejected">Reject</button>
                            </div>
                        ` : (r.status === 'accepted' && r.group_id ? `<a href="messages.php?group_id=${r.group_id}" class="text-xs text-blue-600 hover:text-blue-800">Open mentor group</a>` : '')}
                    </div>
                </div>
            `).join('');
            bindMentorRespondButtons();
        } catch (e) {
            listEl.innerHTML = '<div class="text-sm text-red-600">Unable to load requests.</div>';
        }
    }

    async function refreshMentorshipViews() {
        await loadMentorStatus();
        initRoleBasedActions();
        await loadRequestsPanel();
        await loadActiveMentorshipPanel();
        await loadMentorList();
        await loadMentorshipStats();
        await loadMentorApplications();
    }

    async function loadMentorApplications() {
        const panel = document.getElementById('mentorApplicationsPanel');
        const listEl = document.getElementById('mentorApplicationsList');
        if (!panel || !listEl) return;
        if (!mentorStatus?.can_review_applications) {
            panel.classList.add('hidden');
            return;
        }

        panel.classList.remove('hidden');

        try {
            const res = await makeApiCall('mentorship.php?action=list_mentor_applications');
            const rows = Array.isArray(res?.data) ? res.data : [];
            if (!rows.length) {
                listEl.innerHTML = '<div class="text-sm text-gray-500">No pending mentor applications.</div>';
                return;
            }

            listEl.innerHTML = rows.map((row) => `
                <div class="border border-gray-200 rounded-lg p-3">
                    <p class="font-medium text-gray-900 text-sm">${row.applicant_name || row.email || 'Alumni applicant'}</p>
                    <p class="text-xs text-gray-500 mt-1">${row.headline || 'No headline added yet.'}</p>
                    ${row.expertise ? `<p class="text-xs text-gray-500 mt-1">Expertise: ${row.expertise}</p>` : ''}
                    <div class="flex items-center justify-end gap-2 mt-3">
                        <button class="review-app-btn text-xs px-3 py-1.5 bg-green-600 text-white rounded" data-mentor-user-id="${row.applicant_id}" data-status="approved">Approve</button>
                        <button class="review-app-btn text-xs px-3 py-1.5 bg-red-600 text-white rounded" data-mentor-user-id="${row.applicant_id}" data-status="rejected">Reject</button>
                    </div>
                </div>
            `).join('');
            bindMentorApplicationButtons();
        } catch (e) {
            listEl.innerHTML = '<div class="text-sm text-red-600">Unable to load mentor applications.</div>';
        }
    }

    async function loadActiveMentorshipPanel() {
        const listEl = document.getElementById('activeMentorshipList');
        if (!listEl) return;

        try {
            const res = await makeApiCall('mentorship.php?action=list_active_matches');
            activeMentorshipRows = Array.isArray(res?.data) ? res.data : [];
            if (!currentActiveMatch && activeMentorshipRows.length && mentorStatus?.can_request) {
                currentActiveMatch = activeMentorshipRows[0];
            }

            if (!activeMentorshipRows.length) {
                const adminGroupCard = currentAdminGroupMembership ? `
                    <div class="border border-indigo-200 bg-indigo-50 rounded-lg p-4 mb-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Admin Mentor Group</p>
                        <p class="mt-2 font-semibold text-gray-900">${currentAdminGroupMembership.mentor_name || 'Admin mentor group'}</p>
                        <p class="text-sm text-gray-600 capitalize mt-1">${currentAdminGroupMembership.mentor_role || ''}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            ${currentAdminGroupMembership.group_id ? `<a href="messages.php?group_id=${currentAdminGroupMembership.group_id}" class="text-xs px-3 py-1.5 bg-indigo-600 text-white rounded">Open admin GC</a>` : ''}
                            <button class="leave-group-btn text-xs px-3 py-1.5 border border-amber-300 text-amber-700 rounded" data-group-id="${currentAdminGroupMembership.group_id}">Leave group</button>
                        </div>
                    </div>` : '';
                if (mentorStatus?.can_request && currentActiveMatch) {
                    listEl.innerHTML = `
                        ${adminGroupCard}
                        <div class="border border-gray-200 rounded-lg p-4">
                            <p class="font-semibold text-gray-900">${currentActiveMatch.mentor_name || 'Current mentor'}</p>
                            <p class="text-sm text-gray-600 capitalize mt-1">${currentActiveMatch.mentor_role || ''}</p>
                            <p class="text-xs text-gray-500 mt-2">Joined ${new Date(currentActiveMatch.joined_at).toLocaleDateString()}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                ${currentActiveMatch.group_id ? `<a href="messages.php?group_id=${currentActiveMatch.group_id}" class="text-xs px-3 py-1.5 bg-blue-600 text-white rounded">Open mentor group</a>` : ''}
                                <button class="leave-current-mentor-btn text-xs px-3 py-1.5 border border-red-300 text-red-600 rounded">Leave current mentor</button>
                            </div>
                        </div>
                    `;
                    bindMentorGroupButtons();
                    return;
                }
                listEl.innerHTML = adminGroupCard || `<div class="text-sm text-gray-500">${mentorStatus?.can_request ? 'You are not under any mentor right now.' : 'No active mentor group members yet.'}</div>`;
                return;
            }

            if (mentorStatus?.can_request && !mentorStatus?.mentor_profile?.is_active) {
                const match = activeMentorshipRows[0];
                currentActiveMatch = match;
                listEl.innerHTML = `
                    ${currentAdminGroupMembership ? `
                    <div class="border border-indigo-200 bg-indigo-50 rounded-lg p-4 mb-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Admin Mentor Group</p>
                        <p class="mt-2 font-semibold text-gray-900">${currentAdminGroupMembership.mentor_name || 'Admin mentor group'}</p>
                        <p class="text-sm text-gray-600 capitalize mt-1">${currentAdminGroupMembership.mentor_role || ''}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            ${currentAdminGroupMembership.group_id ? `<a href="messages.php?group_id=${currentAdminGroupMembership.group_id}" class="text-xs px-3 py-1.5 bg-indigo-600 text-white rounded">Open admin GC</a>` : ''}
                            <button class="leave-group-btn text-xs px-3 py-1.5 border border-amber-300 text-amber-700 rounded" data-group-id="${currentAdminGroupMembership.group_id}">Leave group</button>
                        </div>
                    </div>` : ''}
                    <div class="border border-gray-200 rounded-lg p-4">
                        <p class="font-semibold text-gray-900">${match.mentor_name || 'Current mentor'}</p>
                        <p class="text-sm text-gray-600 capitalize mt-1">${match.mentor_role || ''}</p>
                        <p class="text-xs text-gray-500 mt-2">Joined ${new Date(match.joined_at).toLocaleDateString()}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            ${match.group_id ? `<a href="messages.php?group_id=${match.group_id}" class="text-xs px-3 py-1.5 bg-blue-600 text-white rounded">Open mentor group</a>` : ''}
                            <button class="leave-current-mentor-btn text-xs px-3 py-1.5 border border-red-300 text-red-600 rounded">Leave current mentor</button>
                        </div>
                    </div>
                `;
            } else {
                const groupId = Number(activeMentorshipRows[0].group_id || 0);
                const menteeCard = (mentorStatus?.can_request && currentActiveMatch && Number(currentActiveMatch.mentor_id) !== Number(currentUser?.user_id || 0)) ? `
                    <div class="border border-blue-200 bg-blue-50 rounded-lg p-4 mb-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Your current mentor</p>
                        <p class="mt-2 font-semibold text-gray-900">${currentActiveMatch.mentor_name || 'Current mentor'}</p>
                        <p class="text-sm text-gray-600 capitalize mt-1">${currentActiveMatch.mentor_role || ''}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            ${currentActiveMatch.group_id ? `<a href="messages.php?group_id=${currentActiveMatch.group_id}" class="text-xs px-3 py-1.5 bg-blue-600 text-white rounded">Open mentor group</a>` : ''}
                            <button class="leave-current-mentor-btn text-xs px-3 py-1.5 border border-red-300 text-red-600 rounded">Leave current mentor</button>
                        </div>
                    </div>
                ` : '';
                listEl.innerHTML = `
                    ${currentAdminGroupMembership ? `
                    <div class="border border-indigo-200 bg-indigo-50 rounded-lg p-4 mb-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Admin Mentor Group</p>
                        <p class="mt-2 font-semibold text-gray-900">${currentAdminGroupMembership.mentor_name || 'Admin mentor group'}</p>
                        <p class="text-sm text-gray-600 capitalize mt-1">${currentAdminGroupMembership.mentor_role || ''}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            ${currentAdminGroupMembership.group_id ? `<a href="messages.php?group_id=${currentAdminGroupMembership.group_id}" class="text-xs px-3 py-1.5 bg-indigo-600 text-white rounded">Open admin GC</a>` : ''}
                            <button class="leave-group-btn text-xs px-3 py-1.5 border border-amber-300 text-amber-700 rounded" data-group-id="${currentAdminGroupMembership.group_id}">Leave group</button>
                        </div>
                    </div>` : ''}
                    ${menteeCard}
                    ${groupId ? `<div class="flex flex-wrap gap-2 mb-3"><a href="messages.php?group_id=${groupId}" class="text-xs px-3 py-1.5 bg-blue-600 text-white rounded">Open mentor group</a><button class="leave-group-btn text-xs px-3 py-1.5 border border-amber-300 text-amber-700 rounded" data-group-id="${groupId}">Leave group</button>${!mentorStatus?.can_request ? `<button class="disband-group-btn text-xs px-3 py-1.5 border border-red-300 text-red-600 rounded" data-group-id="${groupId}">Disband group</button>` : ''}</div>` : ''}
                    ${activeMentorshipRows.map((row) => `
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-gray-900">${row.mentee_name || 'Member'}</p>
                                    <p class="text-sm text-gray-600 capitalize">${row.mentee_role || ''}</p>
                                    <p class="text-xs text-gray-500 mt-1">Joined ${new Date(row.joined_at).toLocaleDateString()}</p>
                                    ${Number(row.is_banned) ? `<p class="text-xs text-red-600 mt-1">${Number(row.is_permanent) ? 'Permanently removed from this group.' : `Muted until ${new Date(row.banned_until).toLocaleString()}`}</p>` : ''}
                                </div>
                                <div class="flex flex-wrap justify-end gap-2">
                                    ${Number(row.is_banned)
                                        ? `<button class="moderate-member-btn text-xs px-3 py-1.5 border border-green-300 text-green-700 rounded" data-group-id="${row.group_id}" data-member-id="${row.mentee_id}" data-action="unban">Unban</button>`
                                        : `<button class="moderate-member-btn text-xs px-3 py-1.5 border border-amber-300 text-amber-700 rounded" data-group-id="${row.group_id}" data-member-id="${row.mentee_id}" data-action="ban">Ban 7d</button>`
                                    }
                                    <button class="moderate-member-btn text-xs px-3 py-1.5 border border-red-300 text-red-600 rounded" data-group-id="${row.group_id}" data-member-id="${row.mentee_id}" data-action="kick">Kick</button>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                `;
            }

            bindMentorGroupButtons();
        } catch (e) {
            listEl.innerHTML = '<div class="text-sm text-red-600">Unable to load active mentorship details.</div>';
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

    function bindMentorGroupButtons() {
        document.querySelectorAll('.leave-current-mentor-btn').forEach((btn) => {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', async () => {
                if (!await window.appConfirm('Leave your current mentor? You can request another mentor after this.', {
                    title: 'Leave current mentor',
                    confirmText: 'Leave mentor'
                })) return;
                const res = await makeApiCall('mentorship.php?action=leave_current', 'POST', {});
                if (res && res.success) {
                    await refreshMentorshipViews();
                } else {
                    await window.appAlert((res && res.message) || 'Failed to leave your current mentor.', {
                        title: 'Leave failed',
                        icon: 'triangle-alert',
                        iconTone: 'danger'
                    });
                }
            });
        });

        document.querySelectorAll('.leave-group-btn').forEach((btn) => {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', async () => {
                const group_id = Number(btn.getAttribute('data-group-id') || 0);
                if (!group_id) return;
                if (!await window.appConfirm('Leave this mentor group? If you are the admin and no eligible non-student successor exists, the group will be disbanded.', {
                    title: 'Leave mentor group',
                    confirmText: 'Leave group'
                })) return;
                const res = await makeApiCall('mentorship.php?action=leave_group', 'POST', { group_id });
                if (res && res.success) {
                    await refreshMentorshipViews();
                } else {
                    await window.appAlert((res && res.message) || 'Failed to leave this mentor group.', {
                        title: 'Leave failed',
                        icon: 'triangle-alert',
                        iconTone: 'danger'
                    });
                }
            });
        });

        document.querySelectorAll('.moderate-member-btn').forEach((btn) => {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', async () => {
                const group_id = Number(btn.getAttribute('data-group-id') || 0);
                const member_user_id = Number(btn.getAttribute('data-member-id') || 0);
                const moderation_action = btn.getAttribute('data-action') || '';
                if (!group_id || !member_user_id || !moderation_action) return;
                if (!await window.appConfirm(`Proceed with ${moderation_action} for this member?`, {
                    title: 'Moderate member',
                    confirmText: 'Continue'
                })) return;
                const payload = { group_id, member_user_id, moderation_action };
                if (moderation_action === 'ban') payload.ban_days = 7;
                const res = await makeApiCall('mentorship.php?action=moderate_member', 'POST', payload);
                if (res && res.success) {
                    await refreshMentorshipViews();
                } else {
                    await window.appAlert((res && res.message) || 'Failed to update member access.', {
                        title: 'Member update failed',
                        icon: 'triangle-alert',
                        iconTone: 'danger'
                    });
                }
            });
        });

        document.querySelectorAll('.disband-group-btn').forEach((btn) => {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', async () => {
                const group_id = Number(btn.getAttribute('data-group-id') || 0);
                if (!group_id) return;
                if (!await window.appConfirm('Disband this mentor group? All members will be removed and the group chat will be deleted.', {
                    title: 'Disband mentor group',
                    confirmText: 'Disband'
                })) return;
                const res = await makeApiCall('mentorship.php?action=disband_group', 'POST', { group_id });
                if (res && res.success) {
                    await refreshMentorshipViews();
                } else {
                    await window.appAlert((res && res.message) || 'Failed to disband this mentor group.', {
                        title: 'Disband failed',
                        icon: 'triangle-alert',
                        iconTone: 'danger'
                    });
                }
            });
        });
    }

    function bindMentorApplicationButtons() {
        document.querySelectorAll('.review-app-btn').forEach((btn) => {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', async () => {
                const mentor_user_id = Number(btn.getAttribute('data-mentor-user-id') || 0);
                const status = btn.getAttribute('data-status') || '';
                if (!mentor_user_id || !status) return;
                const res = await makeApiCall('mentorship.php?action=review_application', 'POST', { mentor_user_id, status });
                if (res && res.success) {
                    await refreshMentorshipViews();
                } else {
                    await window.appAlert((res && res.message) || 'Failed to review mentor application', {
                        title: 'Review failed',
                        icon: 'triangle-alert',
                        iconTone: 'danger'
                    });
                }
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
                    await refreshMentorshipViews();
                } else {
                    await window.appAlert((res && res.message) || 'Failed to update request', {
                        title: 'Request update failed',
                        icon: 'triangle-alert',
                        iconTone: 'danger'
                    });
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
                if (!['faculty', 'alumni', 'admin'].includes(currentRole) || becomeBtn.disabled) return;
                const headline = (await window.appPrompt('Add a short mentor headline (optional):', {
                    title: 'Mentor headline',
                    inputLabel: 'Headline',
                    confirmText: 'Next',
                    defaultValue: ''
                })) || '';
                const expertise = (await window.appPrompt('Expertise (optional):', {
                    title: 'Mentor expertise',
                    inputLabel: 'Expertise',
                    confirmText: 'Save',
                    defaultValue: ''
                })) || '';
                const res = await makeApiCall('mentorship.php?action=become_mentor', 'POST', { headline, expertise });
                if (res && res.success) {
                    await window.appAlert(res.message || 'Mentor profile updated.', {
                        title: 'Mentor profile updated',
                        icon: 'badge-check',
                        iconTone: 'success'
                    });
                    await refreshMentorshipViews();
                } else {
                    await window.appAlert((res && res.message) || 'Unable to become mentor', {
                        title: 'Mentor update failed',
                        icon: 'triangle-alert',
                        iconTone: 'danger'
                    });
                }
            });
        }

        if (cancelBtn && modal) {
            cancelBtn.addEventListener('click', () => modal.classList.add('hidden'));
        }
        if (form && modal) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const mentor_id = Number(document.getElementById('requestMentorId').value || 0);
                const message = (document.getElementById('requestMessage').value || '').trim() || 'I want to join under your mentorship.';
                if (!mentor_id) return;
                const res = await makeApiCall('mentorship.php?action=request', 'POST', { mentor_id, message });
                if (res && res.success) {
                    await window.appAlert(res.message || 'Mentorship request sent.', {
                        title: 'Request sent',
                        icon: 'send',
                        iconTone: 'success'
                    });
                    form.reset();
                    modal.classList.add('hidden');
                    await refreshMentorshipViews();
                } else {
                    await window.appAlert((res && res.message) || 'Failed to send request', {
                        title: 'Request failed',
                        icon: 'triangle-alert',
                        iconTone: 'danger'
                    });
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', async () => {
        await loadCurrentUser();
        initMentorshipActions();
        await refreshMentorshipViews();
    });
</script>

<?php include 'includes/footer.php'; ?>
