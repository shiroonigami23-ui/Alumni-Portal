<?php
session_start();
$pageTitle = "Alumni Atlas - RJIT Alumni Portal";
include 'includes/header.php';
include 'includes/sidebar.php';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<style>
    #atlasMap { height: 520px; border-radius: 18px; z-index: 0; background: #0b1220; }
    @media (max-width: 767px) { #atlasMap { height: 360px; } }
    .atlas-stat { transition: transform .15s ease; }
    .atlas-stat:hover { transform: translateY(-2px); }
    .atlas-tab[aria-selected="true"] { background: var(--brand-1, #2563eb); color: #fff !important; }
    .atlas-bar { height: 8px; border-radius: 999px; background: linear-gradient(90deg, #2563eb, #7c3aed); }
    .atlas-avatar { background: linear-gradient(135deg, #2563eb, #7c3aed); }
    .leaflet-popup-content-wrapper { border-radius: 14px; }
    .leaflet-popup-content { margin: 12px 14px; font-family: Inter, system-ui, sans-serif; }
    .atlas-pulse { animation: atlasPulse 2.4s ease-out infinite; }
    @keyframes atlasPulse { 0% { opacity: .9 } 70% { opacity: .35 } 100% { opacity: .9 } }
    @media (prefers-reduced-motion: reduce) { .atlas-pulse { animation: none; } }
</style>

<div class="md:pl-64 pb-20 md:pb-0">
    <main id="main-content" class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] mb-2"><span class="vu-shimmer-text">Alumni Atlas</span></p>
                <h1 class="text-3xl font-bold text-gray-900">Where RJITians are today</h1>
                <p class="text-gray-600 mt-1">Explore alumni by city, company, batch and branch — then reach out.</p>
            </div>
            <a href="referrals.php" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-lg font-medium hover:bg-blue-700 self-start md:self-auto">
                <i data-lucide="handshake" class="h-4 w-4"></i> Referral board
            </a>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6" id="atlasStats">
            <?php foreach ([['users', 'Alumni', 'statAlumni'], ['map-pin', 'Cities', 'statCities'], ['building-2', 'Companies', 'statCompanies'], ['graduation-cap', 'Batches', 'statBatches']] as [$icon, $label, $id]): ?>
            <div class="atlas-stat bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center"><i data-lucide="<?= $icon ?>" class="h-5 w-5"></i></div>
                <div>
                    <div id="<?= $id ?>" class="text-2xl font-bold text-gray-900" data-countup>—</div>
                    <div class="text-xs text-gray-500"><?= $label ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Filters -->
        <form id="atlasFilters" class="bg-white rounded-xl border border-gray-200 p-4 mb-6 grid grid-cols-2 md:grid-cols-5 gap-3 items-end" autocomplete="off">
            <label class="col-span-2 md:col-span-1">
                <span class="block text-xs font-medium text-gray-600 mb-1">Company</span>
                <input id="fCompany" type="search" placeholder="e.g. Google" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </label>
            <label>
                <span class="block text-xs font-medium text-gray-600 mb-1">Branch</span>
                <select id="fBranch" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"><option value="">All branches</option></select>
            </label>
            <label>
                <span class="block text-xs font-medium text-gray-600 mb-1">Batch from</span>
                <input id="fFrom" type="number" min="1980" max="2100" placeholder="2010" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </label>
            <label>
                <span class="block text-xs font-medium text-gray-600 mb-1">Batch to</span>
                <input id="fTo" type="number" min="1980" max="2100" placeholder="2026" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </label>
            <div class="flex gap-2 col-span-2 md:col-span-1">
                <button type="submit" class="flex-1 bg-blue-600 text-white px-3 py-2 rounded-lg text-sm font-medium">Apply</button>
                <button type="button" id="fReset" class="px-3 py-2 rounded-lg text-sm border border-gray-300 text-gray-700">Reset</button>
            </div>
        </form>

        <!-- Tabs -->
        <div class="flex gap-2 mb-4 overflow-x-auto" role="tablist" aria-label="Atlas views">
            <?php foreach ([['map', 'map', 'Map'], ['companies', 'building-2', 'Companies'], ['batches', 'calendar-range', 'Batches'], ['branches', 'git-branch', 'Branches']] as $i => [$key, $icon, $label]): ?>
            <button type="button" role="tab" class="atlas-tab inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium border border-gray-200 text-gray-700 whitespace-nowrap" data-tab="<?= $key ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
                <i data-lucide="<?= $icon ?>" class="h-4 w-4"></i><?= $label ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Map view -->
        <section data-panel="map" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl border border-gray-200 p-2 relative">
                    <div id="atlasMap" aria-label="Map of alumni by city"></div>
                    <div id="atlasMapFallback" class="hidden absolute inset-2 rounded-2xl flex items-center justify-center text-center p-6 text-gray-600 text-sm">
                        The interactive map couldn't load on this network. The city list on the right still works.
                    </div>
                </div>
                <p id="atlasUnmapped" class="text-xs text-gray-500 mt-2"></p>
            </div>
            <aside class="space-y-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <h2 class="font-semibold text-gray-900 mb-3 flex items-center gap-2"><i data-lucide="trophy" class="h-4 w-4 text-amber-500"></i>Top cities</h2>
                    <ol id="cityList" class="space-y-2 max-h-[420px] overflow-y-auto pr-1"></ol>
                </div>
            </aside>
            <div id="peoplePanel" class="hidden lg:col-span-3 bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900"><span id="peopleCity"></span> <span id="peopleCount" class="text-sm font-normal text-gray-500"></span></h2>
                    <button type="button" id="peopleClose" class="text-sm text-gray-500 hover:text-gray-800">Close</button>
                </div>
                <div id="peopleGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3"></div>
            </div>
        </section>

        <!-- Companies -->
        <section data-panel="companies" class="hidden bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="font-semibold text-gray-900 mb-4">Where alumni work</h2>
            <div id="companyList" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3"></div>
        </section>

        <!-- Batches -->
        <section data-panel="batches" class="hidden">
            <div id="batchGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3"></div>
        </section>

        <!-- Branches -->
        <section data-panel="branches" class="hidden bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="font-semibold text-gray-900 mb-4">Alumni by branch</h2>
            <div id="branchList" class="space-y-3"></div>
        </section>

        <div id="atlasEmpty" class="hidden text-center py-16 text-gray-500">
            <i data-lucide="map" class="h-10 w-10 mx-auto mb-3 opacity-60"></i>
            <p class="font-medium text-gray-700">No alumni match these filters yet.</p>
            <p class="text-sm">Ask alumni to add their city and company in Settings → Profile.</p>
        </div>
    </main>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
(function () {
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const initials = (n) => String(n || '?').split(/\s+/).filter(Boolean).slice(0, 2).map(w => w[0].toUpperCase()).join('');
    const $ = (id) => document.getElementById(id);
    let map = null, layer = null, data = null, branchesLoaded = false;

    function filters() {
        const p = new URLSearchParams();
        if ($('fCompany').value.trim()) p.set('company', $('fCompany').value.trim());
        if ($('fBranch').value) p.set('branch', $('fBranch').value);
        if ($('fFrom').value) p.set('year_from', $('fFrom').value);
        if ($('fTo').value) p.set('year_to', $('fTo').value);
        return p;
    }

    function initMap() {
        if (map || typeof L === 'undefined') {
            if (typeof L === 'undefined') $('atlasMapFallback').classList.remove('hidden');
            return;
        }
        const dark = document.documentElement.getAttribute('data-theme') === 'dark';
        map = L.map('atlasMap', { scrollWheelZoom: false, worldCopyJump: true }).setView([22.5, 79], 5);
        L.tileLayer(`https://{s}.basemaps.cartocdn.com/${dark ? 'dark_all' : 'light_all'}/{z}/{x}/{y}{r}.png`, {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
        }).addTo(map);
        layer = L.layerGroup().addTo(map);
        map.on('click', () => map.scrollWheelZoom.enable());
    }

    function drawMap() {
        if (!map) return;
        layer.clearLayers();
        const max = Math.max(1, ...data.cities.map(c => c.count));
        const bounds = [];
        data.cities.forEach(c => {
            const r = 8 + 26 * Math.sqrt(c.count / max);
            const m = L.circleMarker([c.lat, c.lng], {
                radius: r, color: '#60a5fa', weight: 2, fillColor: '#2563eb', fillOpacity: 0.55, className: 'atlas-pulse'
            }).addTo(layer);
            m.bindTooltip(`${esc(c.city)} · ${c.count}`, { direction: 'top' });
            m.bindPopup(`<strong>${esc(c.city)}</strong><br>${c.count} alumni` +
                (c.top_companies.length ? `<br><span style="color:#64748b">${c.top_companies.map(esc).join(' · ')}</span>` : '') +
                `<br><a href="#" data-city="${esc(c.city)}" class="atlas-see" style="color:#2563eb;font-weight:600">See alumni →</a>`);
            bounds.push([c.lat, c.lng]);
        });
        if (bounds.length > 1) map.fitBounds(bounds, { padding: [40, 40], maxZoom: 6 });
        else if (bounds.length === 1) map.setView(bounds[0], 8);
    }

    function bar(label, count, max, extra = '') {
        const pct = Math.max(4, Math.round(count / max * 100));
        return `<div><div class="flex justify-between text-sm mb-1"><span class="text-gray-800 font-medium truncate pr-3">${label}</span><span class="text-gray-500">${count}${extra}</span></div><div class="bg-gray-100 rounded-full"><div class="atlas-bar" style="width:${pct}%"></div></div></div>`;
    }

    function render() {
        const t = data.totals;
        $('statAlumni').textContent = t.alumni.toLocaleString('en-IN');
        $('statCities').textContent = t.cities;
        $('statCompanies').textContent = t.companies;
        $('statBatches').textContent = t.batches;
        $('atlasEmpty').classList.toggle('hidden', t.alumni > 0);

        const max = Math.max(1, ...data.cities.map(c => c.count));
        $('cityList').innerHTML = data.cities.length ? data.cities.map((c, i) =>
            `<li><button type="button" class="w-full text-left hover:bg-gray-50 rounded-lg p-2 atlas-see" data-city="${esc(c.city)}">
                <div class="flex items-center gap-3"><span class="w-6 text-xs font-semibold text-gray-400">${i + 1}</span><div class="flex-1">${bar(esc(c.city), c.count, max)}</div></div>
            </button></li>`).join('') : '<li class="text-sm text-gray-500">No mapped cities yet.</li>';

        $('atlasUnmapped').textContent = data.unmapped.length
            ? `Also in: ${data.unmapped.slice(0, 8).map(u => `${u.city} (${u.count})`).join(', ')}${data.unmapped.length > 8 ? '…' : ''}`
            : '';

        const cmax = Math.max(1, ...data.companies.map(c => c.count));
        $('companyList').innerHTML = data.companies.length ? data.companies.map(c =>
            `<a href="discovery.php?search=${encodeURIComponent(c.company)}" class="block hover:opacity-80">${bar(esc(c.company), c.count, cmax)}</a>`).join('')
            : '<p class="text-sm text-gray-500">No companies listed yet.</p>';

        $('batchGrid').innerHTML = data.batches.length ? data.batches.map(b =>
            `<a href="discovery.php?search=${b.year}" class="bg-white rounded-xl border border-gray-200 p-4 text-center hover:border-blue-400 transition">
                <div class="text-xs uppercase tracking-wider text-gray-500">Batch of</div>
                <div class="text-2xl font-bold text-gray-900">${b.year}</div>
                <div class="text-sm text-blue-600 font-medium">${b.count} alumni</div>
            </a>`).join('') : '<p class="text-sm text-gray-500 col-span-full">No batch data yet.</p>';

        const bmax = Math.max(1, ...data.branches.map(b => b.count));
        $('branchList').innerHTML = data.branches.map(b => bar(esc(b.branch), b.count, bmax)).join('') || '<p class="text-sm text-gray-500">No branch data yet.</p>';

        if (!branchesLoaded) {
            const sel = $('fBranch');
            data.branches.forEach(b => sel.insertAdjacentHTML('beforeend', `<option value="${esc(b.branch)}">${esc(b.branch)}</option>`));
            branchesLoaded = true;
        }
        drawMap();
    }

    async function load() {
        const res = await window.makeApiCall('alumni_atlas.php?' + filters().toString());
        if (!res || !res.success) {
            window.toast ? window.toast(res?.message || 'Could not load the atlas.', 'error') : null;
            return;
        }
        data = res.data;
        render();
    }

    async function showCity(city) {
        const p = filters(); p.set('city', city);
        $('peoplePanel').classList.remove('hidden');
        $('peopleCity').textContent = city;
        $('peopleCount').textContent = 'Loading…';
        $('peopleGrid').innerHTML = '';
        const res = await window.makeApiCall('alumni_atlas.php?' + p.toString());
        const people = (res && res.success && res.data.people) || [];
        const c = data.cities.find(x => x.city === city);
        if (map && c) map.flyTo([c.lat, c.lng], 9, { duration: 0.8 });
        const hidden = (c ? c.count : people.length) - people.length;
        $('peopleCount').textContent = `· ${people.length} shown${hidden > 0 ? ` (${hidden} private)` : ''}`;
        $('peopleGrid').innerHTML = people.length ? people.map(p => `
            <a href="profile.php?id=${p.user_id}" class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 hover:border-blue-400 transition">
                <div class="atlas-avatar h-11 w-11 rounded-full text-white font-semibold flex items-center justify-center shrink-0">${esc(initials(p.name))}</div>
                <div class="min-w-0">
                    <div class="font-medium text-gray-900 truncate">${esc(p.name)}</div>
                    <div class="text-xs text-gray-500 truncate">${esc([p.role, p.company].filter(Boolean).join(' @ ') || 'RJIT alumnus')}</div>
                    <div class="text-xs text-gray-400">${esc([p.branch, p.year ? 'Batch ' + p.year : ''].filter(Boolean).join(' · '))}</div>
                </div>
            </a>`).join('') : '<p class="text-sm text-gray-500">Everyone here has a private profile.</p>';
        $('peoplePanel').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    document.addEventListener('click', (e) => {
        const see = e.target.closest('.atlas-see');
        if (see) { e.preventDefault(); showCity(see.dataset.city); }
        const tab = e.target.closest('.atlas-tab');
        if (tab) {
            document.querySelectorAll('.atlas-tab').forEach(t => t.setAttribute('aria-selected', String(t === tab)));
            document.querySelectorAll('[data-panel]').forEach(p => p.classList.toggle('hidden', p.dataset.panel !== tab.dataset.tab));
            if (tab.dataset.tab === 'map' && map) setTimeout(() => map.invalidateSize(), 50);
        }
    });
    $('peopleClose').addEventListener('click', () => $('peoplePanel').classList.add('hidden'));
    $('atlasFilters').addEventListener('submit', (e) => { e.preventDefault(); $('peoplePanel').classList.add('hidden'); load(); });
    $('fReset').addEventListener('click', () => { $('atlasFilters').reset(); load(); });

    const qs = new URLSearchParams(location.search);
    ['company', 'branch'].forEach(k => { if (qs.get(k)) $(k === 'company' ? 'fCompany' : 'fBranch').value = qs.get(k); });
    let started = false;
    const start = () => { if (started) return; started = true; initMap(); load(); };
    document.addEventListener('DOMContentLoaded', start);
    if (document.readyState !== 'loading') start();
})();
</script>
<?php include 'includes/footer.php'; ?>
