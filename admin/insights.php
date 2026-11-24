<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = "Insights & Control Centre - Admin";
?>
<script>
    (function () {
        const token = localStorage.getItem('jwt_token');
        const user = JSON.parse(localStorage.getItem('user_data') || '{}');
        if (!token) { window.location.href = '../login.php'; return; }
        if (user.role && user.role !== 'admin') { window.location.href = '../dashboard.php'; }
    })();
</script>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<style>
    .ins-card { border-radius: 16px; }
    .ins-kpi:hover { transform: translateY(-2px); }
    .ins-kpi { transition: transform .15s ease; }
    .ins-badge { font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 999px; white-space: nowrap; }
    .ins-rate_limited { background: rgba(245,158,11,.15); color: #b45309; }
    .ins-blocked_upload, .ins-forbidden { background: rgba(239,68,68,.15); color: #b91c1c; }
    .ins-login_failed { background: rgba(168,85,247,.15); color: #7e22ce; }
    .ins-bad_method, .ins-bad_request, .ins-too_large { background: rgba(100,116,139,.18); color: #475569; }
    [data-theme="dark"] .ins-rate_limited { color: #fbbf24; } [data-theme="dark"] .ins-blocked_upload, [data-theme="dark"] .ins-forbidden { color: #fca5a5; } [data-theme="dark"] .ins-login_failed { color: #d8b4fe; }
    .ins-bar-s { fill: #38bdf8; } .ins-bar-a { fill: #6366f1; } .ins-bar-f { fill: #f59e0b; }
    .ins-axis { fill: currentColor; opacity: .55; font-size: 11px; }
    .ins-banner-info { background: #1d4ed8; } .ins-banner-success { background: #15803d; } .ins-banner-warning { background: #b45309; } .ins-banner-danger { background: #b91c1c; }
    table.ins-table td, table.ins-table th { padding: 8px 10px; text-align: left; vertical-align: top; }
    table.ins-table tr + tr td { border-top: 1px solid var(--surface-border, #e5e7eb); }
</style>

<div class="md:pl-64 pb-20 md:pb-0">
    <main id="main-content" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] mb-1"><span class="vu-shimmer-text">Admin · Control centre</span></p>
                <h1 class="text-3xl font-bold text-gray-900">Insights &amp; Control Centre</h1>
                <p class="text-gray-600 text-sm">Growth, safety and one-click controls for the whole portal.</p>
            </div>
            <div class="flex items-center gap-2 text-sm">
                <label class="inline-flex items-center gap-2 text-gray-600"><span class="vu-live" aria-hidden="true"></span><input type="checkbox" id="autoRefresh" checked> Live</label>
                <button id="refreshBtn" class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 inline-flex items-center gap-1"><i data-lucide="refresh-cw" class="h-4 w-4"></i>Refresh</button>
                <span id="updatedAt" class="text-xs text-gray-400"></span>
            </div>
        </div>

        <!-- KPIs -->
        <div id="kpis" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 mb-6"></div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
            <!-- Growth -->
            <section class="ins-card bg-white border border-gray-200 p-5 xl:col-span-2">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold text-gray-900">Sign-ups · last 12 weeks</h2>
                    <div class="flex gap-3 text-xs text-gray-600">
                        <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm" style="background:#38bdf8"></span>Students</span>
                        <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm" style="background:#6366f1"></span>Alumni</span>
                        <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm" style="background:#f59e0b"></span>Faculty</span>
                    </div>
                </div>
                <div id="growthChart" class="text-gray-700" role="img" aria-label="Weekly sign-ups chart"></div>
            </section>
            <!-- Engagement -->
            <section class="ins-card bg-white border border-gray-200 p-5">
                <h2 class="font-semibold text-gray-900 mb-3">Last 7 days</h2>
                <div id="engagement" class="grid grid-cols-2 gap-3"></div>
                <h3 class="font-semibold text-gray-900 mt-5 mb-2 text-sm">Members by role</h3>
                <div id="roles" class="space-y-2"></div>
            </section>
        </div>

        <!-- Quick actions -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <section class="ins-card bg-white border border-gray-200 p-5">
                <h2 class="font-semibold text-gray-900 mb-1 flex items-center gap-2"><i data-lucide="megaphone" class="h-4 w-4"></i>Site-wide banner</h2>
                <p class="text-xs text-gray-500 mb-3">Shows at the top of every page until it expires or you clear it.</p>
                <form id="bannerForm" class="space-y-2">
                    <textarea name="message" rows="2" maxlength="280" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Alumni Meet 2026 registrations are open!"></textarea>
                    <div class="grid grid-cols-2 gap-2">
                        <select name="tone" class="px-3 py-2 border border-gray-300 rounded-lg text-sm"><option value="info">Info</option><option value="success">Success</option><option value="warning">Warning</option><option value="danger">Critical</option></select>
                        <input name="expires_at" type="date" class="px-3 py-2 border border-gray-300 rounded-lg text-sm" title="Expires on">
                        <input name="link" placeholder="events.php or https://…" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <input name="link_text" placeholder="Button text" maxlength="40" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div id="bannerPreview" class="hidden rounded-lg text-white text-sm px-3 py-2"></div>
                    <div class="flex gap-2">
                        <button class="flex-1 bg-blue-600 text-white rounded-lg py-2 text-sm font-medium">Publish banner</button>
                        <button type="button" id="clearBanner" class="px-3 rounded-lg border border-gray-300 text-sm text-gray-700">Clear</button>
                    </div>
                    <p id="bannerCurrent" class="text-xs text-gray-500"></p>
                </form>
            </section>

            <section class="ins-card bg-white border border-gray-200 p-5">
                <h2 class="font-semibold text-gray-900 mb-1 flex items-center gap-2"><i data-lucide="send" class="h-4 w-4"></i>Broadcast notification</h2>
                <p class="text-xs text-gray-500 mb-3">Lands in members' notification bell.</p>
                <form id="broadcastForm" class="space-y-2">
                    <select name="audience" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="all">Everyone</option><option value="student">Students</option><option value="alumni">Alumni</option><option value="faculty">Faculty</option>
                    </select>
                    <textarea name="message" rows="4" maxlength="500" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Placement drive with Infosys on Friday — alumni volunteers needed for mock interviews."></textarea>
                    <button class="w-full bg-blue-600 text-white rounded-lg py-2 text-sm font-medium">Send broadcast</button>
                </form>
            </section>

            <section class="ins-card bg-white border border-gray-200 p-5">
                <h2 class="font-semibold text-gray-900 mb-1 flex items-center gap-2"><i data-lucide="download" class="h-4 w-4"></i>Export &amp; shortcuts</h2>
                <p class="text-xs text-gray-500 mb-3">CSV opens in Excel or Google Sheets.</p>
                <div class="grid grid-cols-2 gap-2 mb-4">
                    <button class="ins-export px-3 py-2 rounded-lg border border-gray-300 text-sm text-gray-700" data-q="">All members</button>
                    <button class="ins-export px-3 py-2 rounded-lg border border-gray-300 text-sm text-gray-700" data-q="role=alumni">Alumni</button>
                    <button class="ins-export px-3 py-2 rounded-lg border border-gray-300 text-sm text-gray-700" data-q="role=student">Students</button>
                    <button class="ins-export px-3 py-2 rounded-lg border border-gray-300 text-sm text-gray-700" data-q="status=pending">Pending</button>
                </div>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <a href="users.php" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-800 text-center">Manage users</a>
                    <a href="reports.php" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-800 text-center">Reports</a>
                    <a href="../moderation.php" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-800 text-center">Mod queue</a>
                    <a href="tokens.php" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-800 text-center">Invite tokens</a>
                    <a href="../referrals.php" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-800 text-center">Referrals</a>
                    <a href="../atlas.php" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-800 text-center">Atlas</a>
                </div>
            </section>
        </div>

        <!-- Security + health -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
            <section class="ins-card bg-white border border-gray-200 p-5 xl:col-span-2">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold text-gray-900 flex items-center gap-2"><i data-lucide="shield-alert" class="h-4 w-4 text-red-500"></i>Security events</h2>
                    <div id="secCounts" class="flex flex-wrap gap-1 justify-end"></div>
                </div>
                <div class="overflow-x-auto max-h-[360px] overflow-y-auto">
                    <table class="ins-table w-full text-sm"><thead class="text-xs text-gray-500 uppercase"><tr><th>When</th><th>Event</th><th>IP</th><th>Where</th><th>Detail</th></tr></thead><tbody id="secRows"></tbody></table>
                </div>
            </section>
            <section class="ins-card bg-white border border-gray-200 p-5">
                <h2 class="font-semibold text-gray-900 mb-3 flex items-center gap-2"><i data-lucide="activity" class="h-4 w-4 text-green-500"></i>System health</h2>
                <dl id="health" class="text-sm space-y-2"></dl>
                <h3 class="font-semibold text-gray-900 mt-5 mb-2 text-sm">Top alumni companies</h3>
                <div id="companies" class="space-y-1 text-sm"></div>
            </section>
        </div>

        <section class="ins-card bg-white border border-gray-200 p-5">
            <h2 class="font-semibold text-gray-900 mb-3 flex items-center gap-2"><i data-lucide="scroll-text" class="h-4 w-4"></i>Audit log</h2>
            <div class="overflow-x-auto max-h-[420px] overflow-y-auto">
                <table class="ins-table w-full text-sm"><thead class="text-xs text-gray-500 uppercase"><tr><th>When</th><th>Who</th><th>Action</th><th>Details</th><th>IP</th></tr></thead><tbody id="auditRows"></tbody></table>
            </div>
        </section>
    </main>
</div>

<script>
(function () {
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const when = (d) => window.formatDate ? window.formatDate(d) : String(d || '').replace('T', ' ').slice(0, 16);
    const fmt = (n) => n === null || n === undefined ? '—' : Number(n).toLocaleString('en-IN');
    const notify = (m, t) => window.toast ? window.toast(m, t) : window.appAlert?.(m);
    const LABELS = { rate_limited: 'Rate limited', blocked_upload: 'Blocked upload', forbidden: 'Forbidden', login_failed: 'Failed login', bad_method: 'Bad method', bad_request: 'Bad request', too_large: 'Too large' };

    function kpi(icon, label, value, href, tone = 'blue') {
        const tag = href ? 'a' : 'div';
        return `<${tag} ${href ? `href="${href}"` : ''} class="ins-kpi ins-card bg-white border border-gray-200 p-4 block">
            <div class="flex items-center justify-between mb-2"><span class="text-xs text-gray-500">${label}</span><i data-lucide="${icon}" class="h-4 w-4 text-${tone}-500"></i></div>
            <div class="text-2xl font-bold text-gray-900" data-countup>${fmt(value)}</div></${tag}>`;
    }

    function growthChart(rows) {
        const W = 720, H = 220, pad = 28, bw = (W - pad * 2) / rows.length;
        const max = Math.max(4, ...rows.map(r => r.total));
        let bars = '';
        rows.forEach((r, i) => {
            const x = pad + i * bw + bw * 0.18, w = bw * 0.64;
            let y = H - pad;
            [['student', 's'], ['alumni', 'a'], ['faculty', 'f']].forEach(([k, c]) => {
                const h = (r[k] / max) * (H - pad * 2);
                if (h > 0) { y -= h; bars += `<rect class="ins-bar-${c}" x="${x}" y="${y}" width="${w}" height="${h}" rx="3"><title>${esc(r.week)} · ${k}: ${r[k]}</title></rect>`; }
            });
            const other = r.total - r.student - r.alumni - r.faculty;
            if (other > 0) { const h = (other / max) * (H - pad * 2); y -= h; bars += `<rect fill="#94a3b8" x="${x}" y="${y}" width="${w}" height="${h}" rx="3"><title>other: ${other}</title></rect>`; }
            if (r.total) bars += `<text class="ins-axis" x="${x + w / 2}" y="${y - 4}" text-anchor="middle">${r.total}</text>`;
            if (i % 2 === 0) bars += `<text class="ins-axis" x="${x + w / 2}" y="${H - 8}" text-anchor="middle">${esc(r.week)}</text>`;
        });
        $('growthChart').innerHTML = `<svg viewBox="0 0 ${W} ${H}" width="100%" preserveAspectRatio="none" style="max-height:260px">
            <line x1="${pad}" x2="${W - pad}" y1="${H - pad}" y2="${H - pad}" stroke="currentColor" opacity=".15"/>${bars}</svg>`;
    }

    function render(d) {
        const t = d.totals;
        $('kpis').innerHTML = [
            kpi('users', 'Members', t.users, 'users.php'),
            kpi('user-check', 'Active', t.active, 'users.php', 'green'),
            kpi('hourglass', 'Awaiting approval', t.pending, 'users.php', 'amber'),
            kpi('flag', 'Open reports', t.open_reports, 'reports.php', 'red'),
            kpi('handshake', 'Referral offers', t.referral_offers, '../referrals.php', 'indigo'),
            kpi('graduation-cap', 'Active mentorships', t.mentorship_active, '../mentorship.php', 'purple'),
        ].join('');

        growthChart(d.growth);
        const e = d.engagement;
        $('engagement').innerHTML = [['New members', e.new_users_7d], ['Posts', e.posts_7d], ['Comments', e.comments_7d], ['Active logins', e.logins_7d]]
            .map(([l, v]) => `<div class="rounded-xl bg-gray-100 p-3"><div class="text-xl font-bold text-gray-900" data-countup>${fmt(v)}</div><div class="text-xs text-gray-500">${l}</div></div>`).join('');
        const rmax = Math.max(1, ...d.roles.map(r => +r.n));
        $('roles').innerHTML = d.roles.map(r => `<div><div class="flex justify-between text-xs mb-0.5"><span class="capitalize text-gray-700">${esc(r.role)}</span><span class="text-gray-500">${fmt(r.n)}</span></div>
            <div class="bg-gray-100 rounded-full h-2"><div class="h-2 rounded-full" style="width:${Math.max(3, r.n / rmax * 100)}%;background:linear-gradient(90deg,#2563eb,#7c3aed)"></div></div></div>`).join('');

        const sc = d.security.last_24h || {};
        $('secCounts').innerHTML = Object.keys(sc).length ? Object.entries(sc).map(([k, v]) => `<span class="ins-badge ins-${esc(k)}">${esc(LABELS[k] || k)} · ${v}</span>`).join('') : '<span class="ins-badge bg-green-100 text-green-700">All quiet · 24h</span>';
        $('secRows').innerHTML = d.security.events.length ? d.security.events.map(ev => `<tr>
            <td class="whitespace-nowrap text-gray-500">${esc(when(ev.t))}</td>
            <td><span class="ins-badge ins-${esc(ev.type)}">${esc(LABELS[ev.type] || ev.type)}</span></td>
            <td class="font-mono text-xs">${esc(ev.ip)}</td><td class="font-mono text-xs">${esc(ev.method)} ${esc(ev.path)}</td>
            <td class="text-gray-600">${esc(ev.detail)}</td></tr>`).join('') : '<tr><td colspan="5" class="text-center text-gray-500 py-6">No security events recorded yet. 🛡️</td></tr>';

        $('auditRows').innerHTML = d.audit.length ? d.audit.map(a => `<tr>
            <td class="whitespace-nowrap text-gray-500">${esc(when(a.created_at))}</td><td>${esc(a.actor || 'System')}</td>
            <td><span class="ins-badge bg-gray-100 text-gray-700">${esc(a.action)}</span></td><td class="text-gray-600">${esc(a.details)}</td>
            <td class="font-mono text-xs">${esc(a.ip_address)}</td></tr>`).join('') : '<tr><td colspan="5" class="text-center text-gray-500 py-6">No activity yet.</td></tr>';

        const h = d.health;
        const ok = (b) => b ? '<span class="text-green-600">●</span>' : '<span class="text-red-500">●</span>';
        $('health').innerHTML = [
            ['Database', `${ok(true)} ${esc(h.db_driver)} · ${h.db_latency_ms} ms`],
            ['PHP', esc(h.php_version)], ['Environment', esc(h.app_env)],
            ['Debug mode', h.debug ? '<span class="text-amber-600">on — turn off in production</span>' : `${ok(true)} off`],
            ['HTTPS', h.https ? `${ok(true)} on` : '<span class="text-amber-600">off (add a certificate)</span>'],
            ['Storage writable', `${ok(h.storage_writable)} ${h.storage_writable ? 'yes' : 'no'}`],
            ['Disk', h.disk_free_gb !== null ? `${h.disk_free_gb} GB free (${h.disk_used_pct}% used)` : '—'],
            ['Rate-limit buckets', fmt(h.rate_limited_clients)],
        ].map(([k, v]) => `<div class="flex justify-between gap-3"><dt class="text-gray-500">${k}</dt><dd class="text-gray-900 text-right">${v}</dd></div>`).join('');

        $('companies').innerHTML = d.companies.length ? d.companies.map(c => `<div class="flex justify-between"><span class="text-gray-700 truncate">${esc(c.company)}</span><span class="text-gray-500">${fmt(c.n)}</span></div>`).join('') : '<p class="text-gray-500">No companies listed yet.</p>';

        const b = d.banner;
        $('bannerCurrent').textContent = b && b.message ? `Live now: “${b.message}”${b.expires_at ? ` · until ${b.expires_at}` : ''}` : 'No banner is showing.';
        $('updatedAt').textContent = 'Updated ' + new Date().toLocaleTimeString();
        window.lucide?.createIcons();
    }

    async function load() {
        const r = await window.makeApiCall('admin_insights.php');
        if (r?.success) render(r.data); else notify(r?.message || 'Could not load insights.', 'error');
    }

    // Banner preview
    const bf = $('bannerForm');
    bf.addEventListener('input', () => {
        const m = bf.message.value.trim(), p = $('bannerPreview');
        p.classList.toggle('hidden', !m);
        p.className = `rounded-lg text-white text-sm px-3 py-2 ins-banner-${bf.tone.value}` + (m ? '' : ' hidden');
        p.textContent = m + (bf.link.value ? `  →  ${bf.link_text.value || 'Learn more'}` : '');
    });
    bf.addEventListener('submit', async (e) => {
        e.preventDefault();
        const r = await window.makeApiCall('admin_insights.php', 'POST', { action: 'set_banner', ...Object.fromEntries(new FormData(bf)) });
        notify(r?.message || 'Failed.', r?.success ? 'success' : 'error');
        if (r?.success) { localStorage.removeItem('vu_banner_dismissed'); load(); }
    });
    $('clearBanner').addEventListener('click', async () => {
        const r = await window.makeApiCall('admin_insights.php', 'POST', { action: 'clear_banner' });
        notify(r?.message || 'Failed.', r?.success ? 'success' : 'error'); load();
    });
    $('broadcastForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target));
        const who = e.target.audience.selectedOptions[0].text.toLowerCase();
        const okay = window.appConfirm ? await window.appConfirm(`Send this notification to ${who}?\n\n“${data.message}”`, { title: 'Broadcast', confirmText: 'Send', icon: 'send', iconTone: 'info' }) : confirm('Send?');
        if (!okay) return;
        const r = await window.makeApiCall('admin_insights.php', 'POST', { action: 'broadcast', ...data });
        notify(r?.message || 'Failed.', r?.success ? 'success' : 'error');
        if (r?.success) e.target.reset();
    });
    document.addEventListener('click', async (e) => {
        const b = e.target.closest('.ins-export');
        if (!b) return;
        b.disabled = true;
        try {
            const res = await fetch(`${window.getApiBase()}/admin_export_users.php?${b.dataset.q}`, { headers: { Authorization: 'Bearer ' + localStorage.getItem('jwt_token') } });
            if (!res.ok) throw new Error('Export failed');
            const url = URL.createObjectURL(await res.blob());
            const a = Object.assign(document.createElement('a'), { href: url, download: `rjit-members-${b.dataset.q.replace(/\W+/g, '-') || 'all'}.csv` });
            document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);
            notify('CSV downloaded.', 'success');
        } catch (_) { notify('Could not export right now.', 'error'); }
        b.disabled = false;
    });

    $('refreshBtn').addEventListener('click', load);
    setInterval(() => { if ($('autoRefresh').checked && !document.hidden) load(); }, 60000);
    load();
})();
</script>
</body>
</html>
