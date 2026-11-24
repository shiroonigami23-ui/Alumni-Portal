<?php
session_start();
$pageTitle = "Referral Board - RJIT Alumni Portal";
include 'includes/header.php';
include 'includes/sidebar.php';
?>
<style>
    .ref-tab[aria-selected="true"] { background: var(--brand-1, #2563eb); color: #fff !important; border-color: transparent; }
    .ref-chip { display: inline-flex; align-items: center; gap: 4px; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 500; }
    .ref-status-pending { background: rgba(245, 158, 11, .15); color: #b45309; }
    .ref-status-accepted { background: rgba(59, 130, 246, .15); color: #1d4ed8; }
    .ref-status-referred { background: rgba(34, 197, 94, .15); color: #15803d; }
    .ref-status-declined, .ref-status-withdrawn { background: rgba(148, 163, 184, .18); color: #64748b; }
    [data-theme="dark"] .ref-status-pending { color: #fbbf24; } [data-theme="dark"] .ref-status-accepted { color: #93c5fd; } [data-theme="dark"] .ref-status-referred { color: #86efac; }
    .ref-logo { background: linear-gradient(135deg, #2563eb, #7c3aed); }
    .ref-progress { height: 6px; border-radius: 999px; background: linear-gradient(90deg, #22c55e, #16a34a); }
    .ref-modal { background: rgba(2, 6, 23, .6); backdrop-filter: blur(4px); }
</style>

<div class="md:pl-64 pb-20 md:pb-0">
    <main id="main-content" class="container mx-auto px-4 py-8 max-w-6xl">
        <div class="vu-hero-anim relative overflow-hidden rounded-2xl p-6 md:p-8 mb-6 text-white" style="background:linear-gradient(135deg,#1d4ed8 0%,#6d28d9 50%,#db2777 100%)">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-blue-100 mb-2">Referral board</p>
                    <h1 class="text-3xl font-bold">Get referred by RJITians</h1>
                    <p class="text-blue-100 mt-1 max-w-xl">Alumni share where they can refer you. Send a short, sharp pitch — they take it from there.</p>
                </div>
                <button id="openOfferBtn" type="button" class="hidden bg-white text-blue-700 font-semibold px-5 py-3 rounded-xl hover:bg-blue-50 self-start">
                    + Offer a referral
                </button>
            </div>
            <div class="relative z-10 grid grid-cols-3 gap-3 mt-6 max-w-lg">
                <div><div id="sOpen" class="text-2xl font-bold" data-countup>—</div><div class="text-xs text-blue-100">Open offers</div></div>
                <div><div id="sCompanies" class="text-2xl font-bold" data-countup>—</div><div class="text-xs text-blue-100">Companies</div></div>
                <div><div id="sReferred" class="text-2xl font-bold" data-countup>—</div><div class="text-xs text-blue-100">People referred</div></div>
            </div>
            <i data-lucide="handshake" class="absolute -right-6 -bottom-8 h-48 w-48 opacity-10"></i>
        </div>

        <div class="flex flex-wrap items-center gap-2 mb-4" role="tablist">
            <button class="ref-tab px-4 py-2 rounded-full text-sm font-medium border border-gray-200 text-gray-700" data-tab="browse" aria-selected="true">Browse offers</button>
            <button class="ref-tab px-4 py-2 rounded-full text-sm font-medium border border-gray-200 text-gray-700" data-tab="requests" aria-selected="false">My requests <span id="cRequests" class="ml-1 text-xs opacity-80"></span></button>
            <button class="ref-tab hidden px-4 py-2 rounded-full text-sm font-medium border border-gray-200 text-gray-700" data-tab="offers" aria-selected="false" id="offersTabBtn">My offers <span id="cInbox" class="ml-1 text-xs opacity-80"></span></button>
        </div>

        <!-- Browse -->
        <section data-panel="browse">
            <form id="refSearch" class="bg-white rounded-xl border border-gray-200 p-3 mb-4 flex flex-col sm:flex-row gap-2">
                <input id="qInput" type="search" placeholder="Search company, role or city…" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <select id="expInput" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Any experience</option><option value="intern">Internship</option><option value="fresher">Fresher</option>
                    <option value="0-2">0–2 years</option><option value="2-5">2–5 years</option><option value="5+">5+ years</option>
                </select>
                <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium">Search</button>
            </form>
            <div id="offerGrid" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
        </section>

        <!-- My requests -->
        <section data-panel="requests" class="hidden">
            <div id="requestList" class="space-y-3"></div>
        </section>

        <!-- My offers -->
        <section data-panel="offers" class="hidden">
            <div id="myOfferList" class="space-y-4"></div>
        </section>

        <!-- Tips -->
        <div class="mt-8 bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="font-semibold text-gray-900 mb-2 flex items-center gap-2"><i data-lucide="lightbulb" class="h-4 w-4 text-amber-500"></i>How to get referred</h2>
            <ul class="text-sm text-gray-600 grid md:grid-cols-3 gap-3">
                <li>🎯 <strong>Be specific</strong> — name the exact role and job link if you have one.</li>
                <li>📄 <strong>Share a résumé link</strong> (Drive/GitHub/portfolio) that opens without sign-in.</li>
                <li>🙏 <strong>Say thanks</strong> — referrers are volunteering their reputation for you.</li>
            </ul>
        </div>
    </main>
</div>

<!-- Offer modal -->
<div id="offerModal" class="ref-modal fixed inset-0 z-[150] hidden items-end sm:items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="offerModalTitle">
    <form id="offerForm" class="bg-white rounded-2xl w-full max-w-lg p-6 space-y-3 max-h-[90vh] overflow-y-auto">
        <h2 id="offerModalTitle" class="text-xl font-bold text-gray-900">Offer a referral</h2>
        <label class="block"><span class="text-sm font-medium text-gray-700">Company *</span><input name="company" required maxlength="160" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="e.g. Microsoft"></label>
        <label class="block"><span class="text-sm font-medium text-gray-700">Roles you can refer for *</span><input name="roles" required maxlength="400" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="SDE-1, Data Analyst, SDE Intern"></label>
        <div class="grid grid-cols-2 gap-3">
            <label class="block"><span class="text-sm font-medium text-gray-700">Locations</span><input name="locations" maxlength="255" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Bengaluru / Remote"></label>
            <label class="block"><span class="text-sm font-medium text-gray-700">Experience</span>
                <select name="experience" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="any">Any</option><option value="intern">Internship</option><option value="fresher">Fresher</option><option value="0-2">0–2 years</option><option value="2-5">2–5 years</option><option value="5+">5+ years</option>
                </select></label>
            <label class="block"><span class="text-sm font-medium text-gray-700">How many people?</span><input name="slots" type="number" min="1" max="50" value="3" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg"></label>
            <label class="block"><span class="text-sm font-medium text-gray-700">Open until</span><input name="expires_at" type="date" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg"></label>
        </div>
        <label class="block"><span class="text-sm font-medium text-gray-700">What should applicants include?</span><textarea name="notes" rows="3" maxlength="2000" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Job ID, your strongest project, CGPA if required…"></textarea></label>
        <div class="flex justify-end gap-2 pt-2">
            <button type="button" data-close class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700">Cancel</button>
            <button class="bg-blue-600 text-white px-5 py-2 rounded-lg font-medium">Publish offer</button>
        </div>
    </form>
</div>

<!-- Request modal -->
<div id="requestModal" class="ref-modal fixed inset-0 z-[150] hidden items-end sm:items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="requestModalTitle">
    <form id="requestForm" class="bg-white rounded-2xl w-full max-w-lg p-6 space-y-3">
        <h2 id="requestModalTitle" class="text-xl font-bold text-gray-900">Request a referral</h2>
        <p id="requestFor" class="text-sm text-gray-600"></p>
        <input type="hidden" name="offer_id">
        <label class="block"><span class="text-sm font-medium text-gray-700">Your pitch *</span>
            <textarea name="message" required minlength="30" maxlength="1200" rows="5" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Hi! I'm a 2026 CSE grad. I'm applying for SDE-1 (Job ID 12345). I built … and interned at …"></textarea>
            <span class="text-xs text-gray-500"><span id="msgCount">0</span>/1200 · at least 30 characters</span></label>
        <label class="block"><span class="text-sm font-medium text-gray-700">Résumé / portfolio link</span><input name="resume_url" type="url" pattern="https://.*" maxlength="500" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="https://drive.google.com/…"></label>
        <div class="flex justify-end gap-2 pt-2">
            <button type="button" data-close class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700">Cancel</button>
            <button class="bg-blue-600 text-white px-5 py-2 rounded-lg font-medium">Send request</button>
        </div>
    </form>
</div>

<script>
(function () {
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const safeUrl = (u) => /^https:\/\//i.test(String(u || '')) ? esc(u) : '';
    const when = (d) => window.formatDate ? window.formatDate(d) : String(d || '').slice(0, 10);
    const notify = (m, t = 'success') => window.toast ? window.toast(m, t) : window.appAlert?.(m);
    const EXP = { any: 'Any experience', intern: 'Internship', fresher: 'Fresher', '0-2': '0–2 yrs', '2-5': '2–5 yrs', '5+': '5+ yrs' };
    const STATUS = { pending: 'Pending', accepted: 'Accepted', referred: 'Referred 🎉', declined: 'Declined', withdrawn: 'Withdrawn' };
    let perms = { can_offer: false, can_request: false, is_admin: false };

    const chip = (s) => `<span class="ref-chip ref-status-${esc(s)}">${STATUS[s] || esc(s)}</span>`;
    const logo = (name) => `<div class="ref-logo h-11 w-11 rounded-xl text-white font-bold flex items-center justify-center shrink-0" style="background:linear-gradient(135deg,#2563eb,#7c3aed) !important">${esc(String(name || '?').trim().charAt(0).toUpperCase())}</div>`;

    function openModal(id) { const m = $(id); m.classList.remove('hidden'); m.classList.add('flex'); m.querySelector('input:not([type=hidden]),textarea')?.focus(); }
    function closeModal(m) { m.classList.add('hidden'); m.classList.remove('flex'); }
    document.querySelectorAll('.ref-modal').forEach(m => {
        m.addEventListener('click', (e) => { if (e.target === m || e.target.closest('[data-close]')) closeModal(m); });
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') document.querySelectorAll('.ref-modal.flex').forEach(closeModal); });

    function offerCard(o) {
        const left = Math.max(0, o.slots - o.filled);
        const pct = Math.min(100, Math.round(o.filled / Math.max(1, o.slots) * 100));
        let action = '';
        if (o.is_mine) action = `<span class="text-xs text-gray-500">Your offer · ${Number(o.pending || 0)} pending</span>`;
        else if (o.my_status && o.my_status !== 'withdrawn') action = chip(o.my_status);
        else if (perms.can_request) action = `<button class="ref-request bg-blue-600 text-white text-sm px-4 py-2 rounded-lg font-medium" data-id="${o.offer_id}" data-company="${esc(o.company)}" data-roles="${esc(o.roles)}">Request referral</button>`;
        const admin = perms.is_admin && !o.is_mine ? `<button class="ref-remove text-xs text-red-500 hover:underline" data-id="${o.offer_id}">Remove</button>` : '';
        return `<article class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col gap-3 hover:border-blue-300 transition">
            <div class="flex items-start gap-3">${logo(o.company)}
                <div class="min-w-0 flex-1">
                    <h3 class="font-semibold text-gray-900 text-lg leading-tight">${esc(o.company)}</h3>
                    <p class="text-sm text-gray-600">${esc(o.roles)}</p>
                </div>${admin}
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                <span class="ref-chip bg-gray-100 text-gray-700">${EXP[o.experience] || 'Any'}</span>
                ${o.locations ? `<span class="ref-chip bg-gray-100 text-gray-700">📍 ${esc(o.locations)}</span>` : ''}
                ${o.expires_at ? `<span class="ref-chip bg-gray-100 text-gray-700">⏳ until ${esc(String(o.expires_at).slice(0, 10))}</span>` : ''}
            </div>
            ${o.notes ? `<p class="text-sm text-gray-600 whitespace-pre-line">${esc(o.notes)}</p>` : ''}
            <div>
                <div class="flex justify-between text-xs text-gray-500 mb-1"><span>${left} of ${Number(o.slots || 0)} spots left</span><span>${Number(o.pending || 0)} waiting</span></div>
                <div class="bg-gray-100 rounded-full"><div class="ref-progress" style="width:${Math.max(3, pct)}%"></div></div>
            </div>
            <div class="flex items-center justify-between pt-1 border-t border-gray-100">
                <a href="profile.php?id=${o.owner_id}" class="text-sm text-gray-700 hover:text-blue-600">by <strong>${esc(o.owner_name)}</strong>${o.owner_batch ? ` · ${esc(o.owner_batch)}` : ''}</a>
                ${action}
            </div>
        </article>`;
    }

    async function loadStats() {
        const r = await window.makeApiCall('referrals.php?action=stats');
        if (!r?.success) return;
        $('sOpen').textContent = r.data.open_offers; $('sCompanies').textContent = r.data.companies; $('sReferred').textContent = r.data.referred;
        $('cRequests').textContent = r.data.my_active_requests ? `(${r.data.my_active_requests})` : '';
        $('cInbox').textContent = r.data.awaiting_my_review ? `(${r.data.awaiting_my_review} new)` : '';
    }

    async function loadOffers() {
        const p = new URLSearchParams({ action: 'list' });
        if ($('qInput').value.trim()) p.set('q', $('qInput').value.trim());
        if ($('expInput').value) p.set('experience', $('expInput').value);
        $('offerGrid').innerHTML = '<p class="text-sm text-gray-500">Loading offers…</p>';
        const r = await window.makeApiCall('referrals.php?' + p);
        if (!r?.success) { $('offerGrid').innerHTML = `<p class="text-sm text-red-500">${esc(r?.message || 'Could not load offers.')}</p>`; return; }
        perms = r.permissions || perms;
        $('openOfferBtn').classList.toggle('hidden', !perms.can_offer);
        $('offersTabBtn').classList.toggle('hidden', !perms.can_offer);
        $('offerGrid').innerHTML = r.data.length ? r.data.map(offerCard).join('') : `
            <div class="col-span-full text-center py-14 bg-white rounded-xl border border-dashed border-gray-300">
                <p class="text-4xl mb-2">🤝</p><p class="font-medium text-gray-800">No open referral offers${$('qInput').value ? ' match that search' : ' yet'}.</p>
                <p class="text-sm text-gray-500">${perms.can_offer ? 'Be the first — tap “Offer a referral”.' : 'Check back soon, or ask an alumnus from the Atlas.'}</p>
            </div>`;
        window.lucide?.createIcons();
    }

    async function loadRequests() {
        const r = await window.makeApiCall('referrals.php?action=my_requests');
        const list = r?.success ? r.data : [];
        $('requestList').innerHTML = list.length ? list.map(q => `
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col sm:flex-row sm:items-center gap-3">
                ${logo(q.company)}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap"><strong class="text-gray-900">${esc(q.company)}</strong>${chip(q.status)}</div>
                    <p class="text-sm text-gray-600 truncate">${esc(q.roles)} · via ${esc(q.owner_name)} · ${esc(when(q.updated_at))}</p>
                    ${q.owner_note ? `<p class="text-sm text-gray-700 mt-1">💬 ${esc(q.owner_note)}</p>` : ''}
                </div>
                ${['pending', 'accepted'].includes(q.status) ? `<button class="ref-withdraw text-sm text-gray-500 hover:text-red-500" data-id="${q.request_id}">Withdraw</button>` : ''}
            </div>`).join('') : '<p class="text-center text-gray-500 py-10">You haven\'t requested any referrals yet. Browse offers to get started!</p>';
    }

    async function loadMyOffers() {
        const r = await window.makeApiCall('referrals.php?action=list&mine=1');
        const list = r?.success ? r.data : [];
        $('myOfferList').innerHTML = list.length ? list.map(o => `
            <div class="bg-white rounded-xl border border-gray-200 p-5" data-offer="${o.offer_id}">
                <div class="flex flex-wrap items-center gap-3">
                    ${logo(o.company)}
                    <div class="flex-1 min-w-0"><strong class="text-gray-900">${esc(o.company)}</strong> <span class="ref-chip bg-gray-100 text-gray-700">${esc(o.status)}</span>
                        <p class="text-sm text-gray-600">${esc(o.roles)} · ${Number(o.filled || 0)}/${Number(o.slots || 0)} filled · ${Number(o.pending || 0)} pending</p></div>
                    <div class="flex gap-2 text-sm">
                        <button class="ref-load px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700" data-id="${o.offer_id}">Requests</button>
                        ${o.status === 'open' ? `<button class="ref-status px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700" data-id="${o.offer_id}" data-status="paused">Pause</button>`
                                            : `<button class="ref-status px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700" data-id="${o.offer_id}" data-status="open">Reopen</button>`}
                        <button class="ref-status px-3 py-1.5 rounded-lg border border-red-200 text-red-600" data-id="${o.offer_id}" data-status="closed">Close</button>
                    </div>
                </div>
                <div class="ref-requests mt-4 space-y-3 hidden"></div>
            </div>`).join('') : '<p class="text-center text-gray-500 py-10">You have no offers yet. Tap “Offer a referral” to help juniors.</p>';
    }

    async function loadOfferRequests(offerId, box) {
        box.classList.remove('hidden');
        box.innerHTML = '<p class="text-sm text-gray-500">Loading…</p>';
        const r = await window.makeApiCall('referrals.php?action=offer_requests&offer_id=' + offerId);
        const list = r?.success ? r.data : [];
        box.innerHTML = list.length ? list.map(q => `
            <div class="rounded-xl border border-gray-200 p-4">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <a href="profile.php?id=${q.requester_id}" class="font-semibold text-gray-900 hover:text-blue-600">${esc(q.requester_name)}</a>
                    <span class="text-xs text-gray-500">${esc([q.requester_role, q.branch, q.graduation_year].filter(Boolean).join(' · '))}</span>
                    ${chip(q.status)}
                </div>
                <p class="text-sm text-gray-700 whitespace-pre-line">${esc(q.message)}</p>
                <div class="flex flex-wrap gap-3 text-sm mt-2">
                    ${safeUrl(q.resume_url) ? `<a href="${safeUrl(q.resume_url)}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">📄 Résumé</a>` : ''}
                    ${safeUrl(q.linkedin_url) ? `<a href="${safeUrl(q.linkedin_url)}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">LinkedIn</a>` : ''}
                    ${safeUrl(q.github_url) ? `<a href="${safeUrl(q.github_url)}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">GitHub</a>` : ''}
                </div>
                ${['pending', 'accepted'].includes(q.status) ? `<div class="flex flex-wrap gap-2 mt-3">
                    ${q.status === 'pending' ? `<button class="ref-respond px-3 py-1.5 rounded-lg bg-blue-600 text-white text-sm" data-id="${q.request_id}" data-status="accepted">Accept</button>` : ''}
                    <button class="ref-respond px-3 py-1.5 rounded-lg bg-green-600 text-white text-sm" data-id="${q.request_id}" data-status="referred">Mark referred</button>
                    <button class="ref-respond px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 text-sm" data-id="${q.request_id}" data-status="declined">Decline</button>
                </div>` : ''}
            </div>`).join('') : '<p class="text-sm text-gray-500">No requests yet.</p>';
        box.dataset.offer = offerId;
    }

    async function post(payload) {
        const r = await window.makeApiCall('referrals.php', 'POST', payload);
        notify(r?.message || 'Something went wrong.', r?.success ? 'success' : 'error');
        return r?.success;
    }

    document.addEventListener('click', async (e) => {
        const t = e.target;
        const tab = t.closest('.ref-tab');
        if (tab) {
            document.querySelectorAll('.ref-tab').forEach(b => b.setAttribute('aria-selected', String(b === tab)));
            document.querySelectorAll('[data-panel]').forEach(p => p.classList.toggle('hidden', p.dataset.panel !== tab.dataset.tab));
            if (tab.dataset.tab === 'requests') loadRequests();
            if (tab.dataset.tab === 'offers') loadMyOffers();
            return;
        }
        const rq = t.closest('.ref-request');
        if (rq) {
            const f = $('requestForm'); f.reset(); f.offer_id.value = rq.dataset.id; $('msgCount').textContent = '0';
            $('requestFor').textContent = `${rq.dataset.company} — ${rq.dataset.roles}`;
            openModal('requestModal'); return;
        }
        const wd = t.closest('.ref-withdraw');
        if (wd && await (window.appConfirm ? window.appConfirm('Withdraw this referral request?') : confirm('Withdraw?'))) {
            if (await post({ action: 'withdraw', request_id: +wd.dataset.id })) { loadRequests(); loadStats(); }
            return;
        }
        const ld = t.closest('.ref-load');
        if (ld) { loadOfferRequests(ld.dataset.id, ld.closest('[data-offer]').querySelector('.ref-requests')); return; }
        const st = t.closest('.ref-status');
        if (st && await post({ action: 'update_offer', offer_id: +st.dataset.id, status: st.dataset.status })) { loadMyOffers(); loadStats(); return; }
        const rs = t.closest('.ref-respond');
        if (rs) {
            let note = '';
            if (rs.dataset.status !== 'accepted' && window.appPrompt) {
                const v = await window.appPrompt('Add a short note for them (optional)', { title: 'Message', confirmText: 'Send', inputLabel: 'Note' });
                if (v === null || v === false) return; // cancelled
                note = String(v || '');
            }
            if (await post({ action: 'respond', request_id: +rs.dataset.id, status: rs.dataset.status, note })) {
                const box = rs.closest('.ref-requests'); loadOfferRequests(box.dataset.offer, box); loadStats();
            }
            return;
        }
        const rm = t.closest('.ref-remove');
        if (rm && await (window.appConfirm ? window.appConfirm('Remove this offer for everyone? The owner will be notified.') : confirm('Remove?'))) {
            if (await post({ action: 'update_offer', offer_id: +rm.dataset.id, status: 'removed' })) loadOffers();
        }
    });

    $('openOfferBtn').addEventListener('click', () => { $('offerForm').reset(); openModal('offerModal'); });
    $('offerForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target));
        data.action = 'create_offer'; data.slots = +data.slots || 3;
        if (await post(data)) { closeModal($('offerModal')); loadOffers(); loadStats(); }
    });
    $('requestForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target));
        data.action = 'request'; data.offer_id = +data.offer_id;
        if (await post(data)) { closeModal($('requestModal')); loadOffers(); loadStats(); }
    });
    $('requestForm').message.addEventListener('input', (e) => { $('msgCount').textContent = e.target.value.length; });
    $('refSearch').addEventListener('submit', (e) => { e.preventDefault(); loadOffers(); });

    const qs = new URLSearchParams(location.search);
    if (qs.get('q')) $('qInput').value = qs.get('q');
    loadOffers(); loadStats();
    if (location.hash === '#requests') document.querySelector('[data-tab=requests]').click();
    if (location.hash === '#offers') document.querySelector('[data-tab=offers]').click();
})();
</script>
<?php include 'includes/footer.php'; ?>
