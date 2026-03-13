<?php
session_start();

$pageTitle = "Jobs & Opportunities - RJIT Alumni Portal";
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="md:pl-64 pb-20 md:pb-0">
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Jobs & Opportunities</h1>
            <p class="text-gray-600">Live postings from alumni and companies will appear here.</p>
        </div>

        <div id="jobsActionBar" class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-6 flex items-center justify-between">
            <div>
                <p class="font-semibold text-gray-900" id="jobsActionTitle">Jobs Portal</p>
                <p class="text-sm text-gray-600" id="jobsActionHint">Loading access permissions...</p>
            </div>
            <button id="openPostJobBtn" type="button" class="hidden bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Post Job Opening
            </button>
        </div>

        <div id="postJobPanel" class="hidden bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Create Job Opening</h2>
            <form id="postJobForm" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Company</label>
                        <input id="jobCompanyInput" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                        <input id="jobTitleInput" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                        <input id="jobLocationInput" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Remote / City">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                        <select id="jobTypeInput" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="full-time">Full Time</option>
                            <option value="part-time">Part Time</option>
                            <option value="internship">Internship</option>
                            <option value="contract">Contract</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Salary Range</label>
                        <input id="jobSalaryInput" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Optional">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Application URL</label>
                        <input id="jobApplyUrlInput" type="url" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Optional">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="jobDescriptionInput" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required></textarea>
                </div>
                <div class="flex items-center gap-3">
                    <button id="submitPostJobBtn" type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700">Publish Job</button>
                    <button id="cancelPostJobBtn" type="button" class="px-5 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <i data-lucide="briefcase" class="h-6 w-6 text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Active Jobs</p>
                        <p class="text-2xl font-bold text-gray-900" id="activeJobsCount">0</p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <i data-lucide="users" class="h-6 w-6 text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Hiring Companies</p>
                        <p class="text-2xl font-bold text-gray-900" id="companiesCount">0</p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <i data-lucide="map-pin" class="h-6 w-6 text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Remote Jobs</p>
                        <p class="text-2xl font-bold text-gray-900" id="remoteJobsCount">0</p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <i data-lucide="clock" class="h-6 w-6 text-yellow-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Internships</p>
                        <p class="text-2xl font-bold text-gray-900" id="internshipsCount">0</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3 top-3 h-5 w-5 text-gray-400"></i>
                        <input type="text" id="jobSearch" placeholder="Search jobs by title, company, or skills..." class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div>
                    <select id="jobTypeFilter" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Job Type</option>
                        <option value="full-time">Full Time</option>
                        <option value="part-time">Part Time</option>
                        <option value="internship">Internship</option>
                        <option value="contract">Contract</option>
                    </select>
                </div>
                <div>
                    <select id="experienceFilter" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Experience Level</option>
                        <option value="entry">Entry Level</option>
                        <option value="mid">Mid Level</option>
                        <option value="senior">Senior Level</option>
                    </select>
                </div>
            </div>
        </div>

        <div id="jobsList" class="grid grid-cols-1 lg:grid-cols-2 gap-6"></div>

        <div id="jobsEmptyState" class="bg-white rounded-xl shadow-sm border border-gray-200 p-10 text-center">
            <i data-lucide="briefcase" class="h-12 w-12 text-gray-300 mx-auto mb-3"></i>
            <p class="text-lg font-semibold text-gray-800">No job postings yet</p>
            <p class="text-sm text-gray-500 mt-1">Faculty/alumni can post jobs. This section is currently clean.</p>
        </div>
    </div>
</div>

<div id="applyJobModal" class="hidden fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black bg-opacity-40"></div>
    <div class="relative max-w-xl mx-auto mt-20 bg-white rounded-xl shadow-lg border border-gray-200 p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-3">Apply for Job</h3>
        <form id="applyJobForm" class="space-y-4">
            <input id="applyJobIdInput" type="hidden">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Resume (PDF/DOC/DOCX)</label>
                <input id="applyResumeInput" type="file" accept=".pdf,.doc,.docx" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cover Letter (optional)</label>
                <textarea id="applyCoverInput" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
            </div>
            <div class="flex items-center justify-end gap-3">
                <button id="applyCancelBtn" type="button" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button id="applySubmitBtn" type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Submit Application</button>
            </div>
        </form>
    </div>
</div>

<script>
    lucide.createIcons();
    let currentUser = null;
    let currentRole = '';
    let canPostJobs = false;
    let canApplyJobs = false;
    const API_BASE = (window.getApiBase ? window.getApiBase() : ((window.PORTAL_BASE_PREFIX || '') + 'api'));

    async function initJobsRoleAccess() {
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
        canPostJobs = ['faculty', 'alumni', 'admin'].includes(currentRole);
        canApplyJobs = ['student'].includes(currentRole);

        const titleEl = document.getElementById('jobsActionTitle');
        const hintEl = document.getElementById('jobsActionHint');
        const postBtn = document.getElementById('openPostJobBtn');

        if (canPostJobs) {
            titleEl.textContent = 'Job Publisher Access';
            hintEl.textContent = 'You can create and publish new job openings.';
            postBtn.classList.remove('hidden');
        } else if (canApplyJobs) {
            titleEl.textContent = 'Student Access';
            hintEl.textContent = 'You can only join/apply for available openings.';
            postBtn.classList.add('hidden');
        } else {
            titleEl.textContent = 'Viewer Access';
            hintEl.textContent = 'You can browse jobs.';
            postBtn.classList.add('hidden');
        }
    }

    async function loadJobs() {
        try {
            const token = localStorage.getItem('jwt_token');
            const res = await fetch(`${API_BASE}/get_jobs.php`, {
                headers: token ? { 'Authorization': `Bearer ${token}` } : {}
            });
            const payload = await res.json();
            const rows = Array.isArray(payload?.records) ? payload.records : [];
            const jobsList = document.getElementById('jobsList');
            const jobsEmptyState = document.getElementById('jobsEmptyState');

            if (!rows.length) {
                jobsList.innerHTML = '';
                jobsEmptyState.classList.remove('hidden');
                return;
            }

            jobsEmptyState.classList.add('hidden');
            jobsList.innerHTML = rows.map((job) => `
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="font-bold text-gray-900 text-lg">${job.title || 'Untitled role'}</h3>
                    <p class="text-gray-600 mt-1">${job.company || 'Unknown company'}${job.location ? ' - ' + job.location : ''}</p>
                    <p class="text-sm text-gray-700 mt-3">${job.description || ''}</p>
                    <div class="mt-4">
                        ${canApplyJobs ? `<button class="join-job-btn bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700" data-job-id="${job.job_id}">Join / Apply</button>` : ''}
                        ${!canApplyJobs && job.application_url ? `<a href="${job.application_url}" target="_blank" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Open Application Link</a>` : ''}
                    </div>
                </div>
            `).join('');

            const companies = new Set(rows.map((r) => (r.company || '').trim()).filter(Boolean));
            const remoteCount = rows.filter((r) => /remote/i.test(String(r.location || ''))).length;
            const internshipCount = rows.filter((r) => /intern/i.test(String(r.type || ''))).length;

            document.getElementById('activeJobsCount').textContent = String(rows.length);
            document.getElementById('companiesCount').textContent = String(companies.size);
            document.getElementById('remoteJobsCount').textContent = String(remoteCount);
            document.getElementById('internshipsCount').textContent = String(internshipCount);
            bindJobApplyButtons();
        } catch (e) {
            console.error('Failed to load jobs:', e);
        }
    }

    function bindJobApplyButtons() {
        document.querySelectorAll('.join-job-btn').forEach((btn) => {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', () => {
                const jobId = btn.getAttribute('data-job-id');
                document.getElementById('applyJobIdInput').value = jobId || '';
                document.getElementById('applyJobModal').classList.remove('hidden');
            });
        });
    }

    function initPostJobPanel() {
        const openBtn = document.getElementById('openPostJobBtn');
        const panel = document.getElementById('postJobPanel');
        const cancelBtn = document.getElementById('cancelPostJobBtn');
        const form = document.getElementById('postJobForm');

        if (openBtn) {
            openBtn.addEventListener('click', () => panel.classList.toggle('hidden'));
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => panel.classList.add('hidden'));
        }
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (!canPostJobs) return;
                const btn = document.getElementById('submitPostJobBtn');
                const original = btn.textContent;
                btn.disabled = true;
                btn.textContent = 'Publishing...';
                try {
                    const payload = {
                        company_name: document.getElementById('jobCompanyInput').value.trim(),
                        job_title: document.getElementById('jobTitleInput').value.trim(),
                        description: document.getElementById('jobDescriptionInput').value.trim(),
                        location: document.getElementById('jobLocationInput').value.trim(),
                        job_type: document.getElementById('jobTypeInput').value,
                        salary_range: document.getElementById('jobSalaryInput').value.trim(),
                        application_url: document.getElementById('jobApplyUrlInput').value.trim()
                    };
                    const res = await makeApiCall('post_job.php', 'POST', payload);
                    const ok = !!(
                        res &&
                        (
                            res.success === true ||
                            res.status === 'success' ||
                            /posted successfully/i.test(String(res.message || ''))
                        )
                    );
                    if (!ok) {
                        throw new Error((res && res.message) || 'Failed to post job');
                    }
                    alert('Job posted successfully.');
                    form.reset();
                    panel.classList.add('hidden');
                    await loadJobs();
                } catch (err) {
                    alert(err.message || 'Failed to post job');
                } finally {
                    btn.disabled = false;
                    btn.textContent = original;
                }
            });
        }
    }

    function initApplyModal() {
        const modal = document.getElementById('applyJobModal');
        const cancelBtn = document.getElementById('applyCancelBtn');
        const form = document.getElementById('applyJobForm');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => modal.classList.add('hidden'));
        }
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (!canApplyJobs) return;
                const jobId = document.getElementById('applyJobIdInput').value;
                const resume = document.getElementById('applyResumeInput').files[0];
                const cover = document.getElementById('applyCoverInput').value.trim();
                if (!jobId || !resume) {
                    alert('Please select resume and try again.');
                    return;
                }
                const btn = document.getElementById('applySubmitBtn');
                const original = btn.textContent;
                btn.disabled = true;
                btn.textContent = 'Submitting...';
                try {
                    const fd = new FormData();
                    fd.append('job_id', jobId);
                    fd.append('resume', resume);
                    fd.append('cover_letter', cover);
                    const token = localStorage.getItem('jwt_token');
                    const res = await fetch(`${API_BASE}/apply_job.php`, {
                        method: 'POST',
                        headers: token ? { 'Authorization': `Bearer ${token}` } : {},
                        body: fd
                    });
                    const payload = await res.json();
                    if (!res.ok) {
                        throw new Error(payload?.message || 'Failed to apply');
                    }
                    alert('Application submitted.');
                    form.reset();
                    modal.classList.add('hidden');
                } catch (err) {
                    alert(err.message || 'Failed to apply');
                } finally {
                    btn.disabled = false;
                    btn.textContent = original;
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', async () => {
        await initJobsRoleAccess();
        initPostJobPanel();
        initApplyModal();
        await loadJobs();
    });
</script>

<?php include 'includes/footer.php'; ?>
