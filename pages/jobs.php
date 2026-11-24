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
                        <option value="remote">Remote</option>
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
    const JOBS_API_BASE = (window.getApiBase ? window.getApiBase() : ((window.PORTAL_BASE_PREFIX || '') + 'api'));

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
        canApplyJobs = ['student', 'alumni'].includes(currentRole);

        const titleEl = document.getElementById('jobsActionTitle');
        const hintEl = document.getElementById('jobsActionHint');
        const postBtn = document.getElementById('openPostJobBtn');
        if (!titleEl || !hintEl || !postBtn) return;

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

    let allJobs = [];
    const jobEsc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const jobUrl = (u) => /^https?:\/\//i.test(String(u || '')) ? jobEsc(u) : '';
    const TYPE_LABEL = { 'full-time': 'Full time', 'part-time': 'Part time', internship: 'Internship', contract: 'Contract', remote: 'Remote', freelance: 'Freelance' };

    function jobLevel(job) {
        const t = `${job.title} ${job.description}`.toLowerCase();
        if (/intern|fresher|graduate|trainee|entry|sde-?1|junior/.test(t)) return 'entry';
        if (/senior|lead|principal|staff|manager|sde-?3/.test(t)) return 'senior';
        return 'mid';
    }

    function jobCard(job) {
        const when = window.formatDate ? window.formatDate(job.posted_at) : '';
        const closed = job.status !== 'open';
        let actions = '';
        if (job.is_mine || currentRole === 'admin') {
            actions += `<button class="job-applicants-btn px-3 py-2 rounded-lg border border-gray-300 text-sm" data-job-id="${job.job_id}" data-title="${jobEsc(job.title)}">Applicants${job.applicant_count !== null ? ` (${job.applicant_count})` : ''}</button>
                <button class="job-manage-btn px-3 py-2 rounded-lg border border-gray-300 text-sm" data-job-id="${job.job_id}" data-action="${closed ? 'reopen' : 'close'}">${closed ? 'Reopen' : 'Close'}</button>
                <button class="job-manage-btn px-3 py-2 rounded-lg border border-red-200 text-red-600 text-sm" data-job-id="${job.job_id}" data-action="delete">Delete</button>`;
        } else if (job.applied) {
            actions += `<span class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-green-100 text-green-700 text-sm font-medium">✓ Applied</span>`;
        } else if (canApplyJobs && !closed) {
            actions += `<button class="join-job-btn bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm font-medium" data-job-id="${job.job_id}" data-title="${jobEsc(job.title)}" data-company="${jobEsc(job.company)}">Apply with résumé</button>`;
        }
        if (jobUrl(job.application_url)) {
            actions += `<a href="${jobUrl(job.application_url)}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">Company page ↗</a>`;
        }
        return `<article class="job-card bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col ${closed ? 'opacity-70' : ''}">
            <div class="flex items-start gap-3">
                <div class="h-12 w-12 rounded-xl text-white font-bold flex items-center justify-center shrink-0" style="background:linear-gradient(135deg,#2563eb,#7c3aed) !important">${jobEsc(String(job.company || '?').charAt(0).toUpperCase())}</div>
                <div class="min-w-0 flex-1">
                    <h3 class="font-bold text-gray-900 text-lg leading-tight">${jobEsc(job.title || 'Untitled role')}</h3>
                    <p class="text-gray-600">${jobEsc(job.company || 'Unknown company')}${job.location ? ' · ' + jobEsc(job.location) : ''}</p>
                </div>
                ${closed ? '<span class="text-xs font-semibold px-2 py-1 rounded-full bg-gray-200 text-gray-700">Closed</span>' : ''}
            </div>
            <div class="flex flex-wrap gap-2 mt-3 text-xs">
                <span class="px-2.5 py-1 rounded-full bg-blue-100 text-blue-700 font-medium">${jobEsc(TYPE_LABEL[job.type] || job.type || 'Job')}</span>
                ${job.salary && job.salary !== 'Not disclosed' ? `<span class="px-2.5 py-1 rounded-full bg-green-100 text-green-700 font-medium">₹ ${jobEsc(job.salary)}</span>` : ''}
            </div>
            <p class="text-sm text-gray-700 mt-3 whitespace-pre-line line-clamp-4">${jobEsc(job.description || '')}</p>
            <p class="text-xs text-gray-500 mt-3">Posted by <a href="profile.php?id=${job.poster_id}" class="font-medium hover:text-blue-600">${jobEsc(job.posted_by)}</a>${when ? ' · ' + jobEsc(when) : ''}</p>
            <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap gap-2">${actions}</div>
        </article>`;
    }

    function renderJobs() {
        const q = (document.getElementById('jobSearch')?.value || '').trim().toLowerCase();
        const type = document.getElementById('jobTypeFilter')?.value || '';
        const level = document.getElementById('experienceFilter')?.value || '';
        const rows = allJobs.filter((j) =>
            (!q || `${j.title} ${j.company} ${j.location} ${j.description}`.toLowerCase().includes(q)) &&
            (!type || j.type === type || (type === 'remote' && /remote/i.test(j.location || ''))) &&
            (!level || jobLevel(j) === level));
        const list = document.getElementById('jobsList');
        const empty = document.getElementById('jobsEmptyState');
        list.innerHTML = rows.map(jobCard).join('');
        empty.classList.toggle('hidden', rows.length > 0);
        if (!rows.length && allJobs.length) {
            empty.querySelector('p.text-lg').textContent = 'No jobs match your filters';
            empty.querySelector('p.text-sm').textContent = 'Try a different keyword or clear the filters.';
        }
        window.lucide?.createIcons();
    }

    async function loadJobs() {
        try {
            const res = await makeApiCall('get_jobs.php' + (canPostJobs ? '?all=1' : ''));
            allJobs = Array.isArray(res?.records) ? res.records : [];
            const open = allJobs.filter((r) => r.status === 'open');
            document.getElementById('activeJobsCount').textContent = String(open.length);
            document.getElementById('companiesCount').textContent = String(new Set(open.map((r) => (r.company || '').trim().toLowerCase()).filter(Boolean)).size);
            document.getElementById('remoteJobsCount').textContent = String(open.filter((r) => /remote/i.test(String(r.location || ''))).length);
            document.getElementById('internshipsCount').textContent = String(open.filter((r) => /intern/i.test(String(r.type || ''))).length);
            renderJobs();
        } catch (e) {
            console.error('Failed to load jobs:', e);
            window.toast?.('Could not load jobs. Please refresh.', 'error');
        }
    }

    async function showApplicants(jobId, title) {
        const res = await makeApiCall(`get_applicants.php?job_id=${encodeURIComponent(jobId)}`);
        const list = Array.isArray(res?.applicants) ? res.applicants : (Array.isArray(res?.data) ? res.data : []);
        const body = list.length ? list.map((a) => {
            const resume = String(a.resume_path || '');
            const href = /^api\/asset\.php\?token=/.test(resume) || /^storage\//.test(resume) ? jobEsc(resolvePortalPath ? resolvePortalPath(resume) : resume) : '';
            return `• ${a.full_name || a.name || a.email || 'Applicant'}${a.created_at ? ' — ' + (window.formatDate ? window.formatDate(a.created_at) : a.created_at) : ''}${a.cover_letter ? '\n   “' + String(a.cover_letter).slice(0, 160) + '”' : ''}${href ? '\n   Résumé: ' + location.origin + '/' + href.replace(/^\//, '') : ''}`;
        }).join('\n\n') : 'No applications yet.';
        await window.appAlert(body, { title: `Applicants · ${title}`, icon: 'users', iconTone: 'info' });
    }

    document.addEventListener('click', async (e) => {
        const apply = e.target.closest('.join-job-btn');
        if (apply) {
            document.getElementById('applyJobIdInput').value = apply.dataset.jobId || '';
            const h = document.querySelector('#applyJobModal h3');
            if (h) h.textContent = `Apply · ${apply.dataset.title} at ${apply.dataset.company}`;
            document.getElementById('applyJobModal').classList.remove('hidden');
            return;
        }
        const ap = e.target.closest('.job-applicants-btn');
        if (ap) { showApplicants(ap.dataset.jobId, ap.dataset.title); return; }
        const m = e.target.closest('.job-manage-btn');
        if (m) {
            const action = m.dataset.action;
            if (action === 'delete' && !(await window.appConfirm('Delete this job and all its applications? This cannot be undone.', { title: 'Delete job', confirmText: 'Delete' }))) return;
            const res = await makeApiCall('manage_job.php', 'POST', { job_id: +m.dataset.jobId, action });
            window.toast?.(res?.message || 'Something went wrong.', res?.success ? 'success' : 'error');
            if (res?.success) loadJobs();
        }
    });
    ['jobSearch', 'jobTypeFilter', 'experienceFilter'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.addEventListener(el.tagName === 'INPUT' ? 'input' : 'change', renderJobs);
    });

    function bindJobApplyButtons() { /* handled by delegated listener above */ }

    function initPostJobPanel() {
        const openBtn = document.getElementById('openPostJobBtn');
        const panel = document.getElementById('postJobPanel');
        const cancelBtn = document.getElementById('cancelPostJobBtn');
        const form = document.getElementById('postJobForm');

        if (openBtn && panel) {
            openBtn.addEventListener('click', () => panel.classList.toggle('hidden'));
        }
        if (cancelBtn && panel) {
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
                    await window.appAlert('Job posted successfully.', {
                        title: 'Job posted',
                        icon: 'briefcase-business',
                        iconTone: 'success'
                    });
                    form.reset();
                    panel.classList.add('hidden');
                    await loadJobs();
                } catch (err) {
                    await window.appAlert(err.message || 'Failed to post job', {
                        title: 'Job post failed',
                        icon: 'triangle-alert',
                        iconTone: 'danger'
                    });
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
        if (cancelBtn && modal) {
            cancelBtn.addEventListener('click', () => modal.classList.add('hidden'));
        }
        if (form && modal) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (!canApplyJobs) return;
                const jobId = document.getElementById('applyJobIdInput').value;
                const resume = document.getElementById('applyResumeInput').files[0];
                const cover = document.getElementById('applyCoverInput').value.trim();
                if (!jobId || !resume) {
                    await window.appAlert('Please select resume and try again.', {
                        title: 'Resume required',
                        icon: 'triangle-alert',
                        iconTone: 'danger'
                    });
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
                    const res = await fetch(`${JOBS_API_BASE}/apply_job.php`, {
                        method: 'POST',
                        headers: token ? { 'Authorization': `Bearer ${token}` } : {},
                        body: fd
                    });
                    const payload = await res.json();
                    if (!res.ok) {
                        throw new Error(payload?.message || 'Failed to apply');
                    }
                    loadJobs();
                    await window.appAlert('Application submitted.', {
                        title: 'Application submitted',
                        icon: 'send',
                        iconTone: 'success'
                    });
                    form.reset();
                    modal.classList.add('hidden');
                } catch (err) {
                    await window.appAlert(err.message || 'Failed to apply', {
                        title: 'Application failed',
                        icon: 'triangle-alert',
                        iconTone: 'danger'
                    });
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
