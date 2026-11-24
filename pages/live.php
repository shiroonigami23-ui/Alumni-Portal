<?php
session_start();
$pageTitle = "Live - RJIT Alumni Portal";
include 'includes/header.php';
include 'includes/sidebar.php';
?>
<style>
    .live-dot { width: 10px; height: 10px; border-radius: 999px; background: #ef4444; box-shadow: 0 0 0 0 rgba(239, 68, 68, .6); animation: liveDot 1.6s infinite; display: inline-block; }
    @keyframes liveDot { 70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }
    @media (prefers-reduced-motion: reduce) { .live-dot { animation: none; } }
    .live-player { aspect-ratio: 16 / 9; width: 100%; border: 0; border-radius: 16px; background: #000; }
    .live-tab[aria-selected="true"] { background: var(--brand-1, #2563eb); color: #fff !important; border-color: transparent; }
</style>

<div class="md:pl-64 pb-20 md:pb-0">
    <main id="main-content" class="container mx-auto px-4 py-8 max-w-6xl">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] mb-2 flex items-center gap-2"><span class="live-dot"></span><span class="vu-shimmer-text">Live now</span></p>
                <h1 class="text-3xl font-bold text-gray-900">Live sessions</h1>
                <p class="text-gray-600 mt-1">AMAs, talks and office hours from RJIT alumni and faculty — join with one click.</p>
            </div>
            <button id="goLiveBtn" type="button" class="hidden bg-red-600 text-white px-5 py-2.5 rounded-lg font-semibold hover:bg-red-700 self-start inline-flex items-center gap-2">
                <i data-lucide="radio" class="h-4 w-4"></i> Go live
            </button>
        </div>

        <!-- My session -->
        <section id="mySession" class="hidden mb-6 rounded-2xl p-5 text-white vu-hero-anim" style="background:linear-gradient(135deg,#b91c1c,#7c3aed,#1d4ed8)">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-widest text-red-100 flex items-center gap-2"><span class="live-dot" style="background:#fff"></span>You are live</p>
                    <h2 id="myTitle" class="text-xl font-bold mt-1"></h2>
                    <p class="text-sm text-red-100" id="myProvider"></p>
                </div>
                <div class="flex gap-2">
                    <a id="myOpen" target="_blank" rel="noopener noreferrer" class="bg-white text-red-700 font-semibold px-4 py-2 rounded-lg">Open room ↗</a>
                    <button id="endLiveBtn" type="button" class="bg-black/30 border border-white/40 px-4 py-2 rounded-lg font-semibold">End session</button>
                </div>
            </div>
        </section>

        <!-- Player -->
        <section id="playerWrap" class="hidden mb-6 bg-white rounded-2xl border border-gray-200 p-3">
            <iframe id="player" class="live-player" allow="camera; microphone; display-capture; autoplay; fullscreen; encrypted-media; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin" title="Live session"></iframe>
            <div class="flex flex-wrap items-center justify-between gap-2 px-2 pt-3">
                <div>
                    <h2 id="playerTitle" class="font-semibold text-gray-900"></h2>
                    <p id="playerHost" class="text-sm text-gray-500"></p>
                </div>
                <div class="flex gap-2">
                    <a id="playerOpen" target="_blank" rel="noopener noreferrer" class="px-3 py-2 rounded-lg border border-gray-300 text-sm text-gray-700">Open in new tab ↗</a>
                    <button id="playerClose" type="button" class="px-3 py-2 rounded-lg border border-gray-300 text-sm text-gray-700">Close player</button>
                </div>
            </div>
        </section>

        <div id="liveGrid" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
        <div id="liveEmpty" class="hidden text-center py-16 bg-white rounded-2xl border border-dashed border-gray-300">
            <p class="text-5xl mb-3">📡</p>
            <p class="font-semibold text-gray-800">Nobody is live right now</p>
            <p class="text-sm text-gray-500 mt-1">Check back soon — or start a session if you're alumni or faculty.</p>
        </div>
    </main>
</div>

<!-- Go-live modal -->
<div id="goLiveModal" class="fixed inset-0 z-[150] hidden items-end sm:items-center justify-center p-4" style="background:rgba(2,6,23,.6)" role="dialog" aria-modal="true" aria-labelledby="goLiveTitle">
    <form id="goLiveForm" class="bg-white rounded-2xl w-full max-w-lg p-6 space-y-3">
        <h2 id="goLiveTitle" class="text-xl font-bold text-gray-900">Start a live session</h2>
        <label class="block"><span class="text-sm font-medium text-gray-700">Title *</span>
            <input name="title" required minlength="3" maxlength="120" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="AMA: Cracking product-company interviews"></label>
        <label class="block"><span class="text-sm font-medium text-gray-700">What's it about?</span>
            <textarea name="description" rows="2" maxlength="600" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Bring your questions on DSA, system design and referrals."></textarea></label>
        <div class="flex gap-2" role="tablist">
            <button type="button" class="live-tab flex-1 px-3 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700" data-provider="jitsi" aria-selected="true">🎥 Video room (Jitsi)</button>
            <button type="button" class="live-tab flex-1 px-3 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700" data-provider="youtube" aria-selected="false">▶️ YouTube Live</button>
        </div>
        <p id="jitsiHint" class="text-xs text-gray-500">We'll create a private Jitsi room. Viewers can join with camera and mic or just watch.</p>
        <label id="ytField" class="hidden block"><span class="text-sm font-medium text-gray-700">YouTube Live link *</span>
            <input name="url" type="url" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="https://www.youtube.com/live/…"></label>
        <div class="flex justify-end gap-2 pt-2">
            <button type="button" data-close class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700">Cancel</button>
            <button class="bg-red-600 text-white px-5 py-2 rounded-lg font-semibold">Go live</button>
        </div>
    </form>
</div>

<script>
(function () {
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const safe = (u) => /^https:\/\/(meet\.jit\.si|www\.youtube-nocookie\.com|www\.youtube\.com)\//.test(String(u || '')) ? u : '';
    const notify = (m, t = 'success') => window.toast ? window.toast(m, t) : window.appAlert?.(m);
    let provider = 'jitsi', sessions = [];
    const role = (() => { try { return JSON.parse(localStorage.getItem('user_data') || '{}').role || ''; } catch (_) { return ''; } })();
    if (['alumni', 'faculty', 'admin'].includes(role)) $('goLiveBtn').classList.remove('hidden');

    function since(d) {
        const t = window.parsePortalDate ? window.parsePortalDate(d) : new Date(String(d).replace(' ', 'T') + 'Z');
        const m = Math.max(0, Math.round((Date.now() - t.getTime()) / 60000));
        return m < 1 ? 'just started' : m < 60 ? `${m} min` : `${Math.floor(m / 60)} h ${m % 60} min`;
    }

    function play(s) {
        const url = safe(s.embed_url);
        if (!url) return;
        $('player').src = url;
        $('playerTitle').textContent = s.title;
        $('playerHost').textContent = `${s.host_name}${s.host_company ? ' · ' + s.host_company : ''} · live for ${since(s.started_at)}`;
        $('playerOpen').href = safe(s.watch_url) || url;
        $('playerWrap').classList.remove('hidden');
        $('playerWrap').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    async function load() {
        const r = await window.makeApiCall('get_active_streams.php');
        if (!r?.success) { notify(r?.message || 'Could not load live sessions.', 'error'); return; }
        sessions = r.data || [];
        const mine = r.mine && r.mine.status === 'live' ? r.mine : null;
        $('mySession').classList.toggle('hidden', !mine);
        if (mine) {
            $('myTitle').textContent = mine.title;
            $('myProvider').textContent = mine.provider === 'youtube' ? 'Streaming via YouTube' : 'Jitsi room · share the link with your audience';
            $('myOpen').href = safe(mine.watch_url) || '#';
        }
        $('liveEmpty').classList.toggle('hidden', sessions.length > 0);
        $('liveGrid').innerHTML = sessions.map((s, i) => `
            <article class="bg-white rounded-2xl border border-gray-200 p-5 flex flex-col gap-3">
                <div class="flex items-center gap-2 text-xs font-semibold text-red-600"><span class="live-dot"></span> LIVE · ${esc(since(s.started_at))}</div>
                <h3 class="text-lg font-bold text-gray-900 leading-tight">${esc(s.title)}</h3>
                ${s.description ? `<p class="text-sm text-gray-600">${esc(s.description)}</p>` : ''}
                <p class="text-sm text-gray-500">Hosted by <a href="profile.php?id=${s.user_id}" class="font-medium text-gray-800 hover:text-blue-600">${esc(s.host_name)}</a>${s.host_company ? ' · ' + esc(s.host_company) : ''}
                    · <span class="capitalize">${esc(s.host_role)}</span></p>
                <div class="flex gap-2 mt-auto">
                    <button class="live-join bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-semibold" data-i="${i}">${s.provider === 'youtube' ? '▶ Watch' : '🎥 Join room'}</button>
                    <a href="${esc(safe(s.watch_url))}" target="_blank" rel="noopener noreferrer" class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-700">New tab ↗</a>
                </div>
            </article>`).join('');
        const want = new URLSearchParams(location.search).get('host');
        if (want) { const s = sessions.find((x) => String(x.user_id) === want); if (s) play(s); }
    }

    document.addEventListener('click', async (e) => {
        const j = e.target.closest('.live-join');
        if (j) { play(sessions[+j.dataset.i]); return; }
        const tab = e.target.closest('.live-tab');
        if (tab) {
            provider = tab.dataset.provider;
            document.querySelectorAll('.live-tab').forEach((t) => t.setAttribute('aria-selected', String(t === tab)));
            $('ytField').classList.toggle('hidden', provider !== 'youtube');
            $('jitsiHint').classList.toggle('hidden', provider !== 'jitsi');
            $('goLiveForm').url.required = provider === 'youtube';
        }
    });
    const modal = $('goLiveModal');
    const close = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); };
    $('goLiveBtn').addEventListener('click', () => { modal.classList.remove('hidden'); modal.classList.add('flex'); $('goLiveForm').title.focus(); });
    modal.addEventListener('click', (e) => { if (e.target === modal || e.target.closest('[data-close]')) close(); });
    $('goLiveForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const f = e.target;
        const r = await window.makeApiCall('toggle_stream.php', 'POST', { action: 'start', title: f.title.value, description: f.description.value, provider, url: f.url.value });
        notify(r?.message || 'Could not go live.', r?.success ? 'success' : 'error');
        if (r?.success) { close(); f.reset(); await load(); if (r.data?.provider === 'jitsi' && safe(r.data.watch_url)) window.open(r.data.watch_url, '_blank', 'noopener'); }
    });
    $('endLiveBtn').addEventListener('click', async () => {
        if (!(await window.appConfirm('End your live session for everyone?', { title: 'End session', confirmText: 'End' }))) return;
        const r = await window.makeApiCall('toggle_stream.php', 'POST', { action: 'stop' });
        notify(r?.message || 'Done.', r?.success ? 'success' : 'error');
        load();
    });
    $('playerClose').addEventListener('click', () => { $('player').src = 'about:blank'; $('playerWrap').classList.add('hidden'); });

    load();
    setInterval(() => { if (!document.hidden) load(); }, 30000);
})();
</script>
<?php include 'includes/footer.php'; ?>
