<?php
session_start();
$pageTitle = "Library - RJIT Alumni Portal";
include 'includes/header.php';
include 'includes/sidebar.php';
?>
<style>
    .lib-tab[aria-selected="true"] { background: var(--brand-1, #2563eb); color: #fff !important; border-color: transparent; }
    .lib-modal { background: rgba(2, 6, 23, .6); backdrop-filter: blur(4px); }
    .story-body { white-space: pre-line; }
</style>
<div class="md:pl-64 pb-20 md:pb-0">
    <main id="main-content" class="container mx-auto px-4 py-8 max-w-6xl">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] mb-2"><span class="vu-shimmer-text">Library</span></p>
                <h1 class="text-3xl font-bold text-gray-900">Stories &amp; resources</h1>
                <p class="text-gray-600 mt-1">Journeys from RJIT alumni and the notes, sheets and links that helped them.</p>
            </div>
            <div class="flex gap-2">
                <button id="shareStoryBtn" type="button" class="hidden bg-blue-600 text-white px-4 py-2.5 rounded-lg font-medium">✍️ Share your story</button>
                <button id="addResBtn" type="button" class="px-4 py-2.5 rounded-lg font-medium border border-gray-300 text-gray-700">+ Add resource</button>
            </div>
        </div>
        <div class="flex flex-wrap gap-2 mb-4" role="tablist">
            <button class="lib-tab px-4 py-2 rounded-full text-sm font-medium border border-gray-200 text-gray-700" data-tab="stories" aria-selected="true">🌟 Success stories</button>
            <button class="lib-tab px-4 py-2 rounded-full text-sm font-medium border border-gray-200 text-gray-700" data-tab="resources" aria-selected="false">📚 Resources</button>
            <button id="reviewTabBtn" class="lib-tab hidden px-4 py-2 rounded-full text-sm font-medium border border-gray-200 text-gray-700" data-tab="review" aria-selected="false">🛡️ Review queue <span id="reviewCount"></span></button>
        </div>

        <section data-panel="stories"><div id="storyGrid" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div></section>

        <section data-panel="resources" class="hidden">
            <form id="resSearch" class="bg-white rounded-xl border border-gray-200 p-3 mb-4 flex flex-col sm:flex-row gap-2">
                <input id="resQ" type="search" placeholder="Search resources…" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <select id="resCat" class="px-3 py-2 border border-gray-300 rounded-lg text-sm"><option value="">All categories</option></select>
                <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium">Search</button>
            </form>
            <div id="resList" class="grid grid-cols-1 md:grid-cols-2 gap-3"></div>
        </section>

        <section data-panel="review" class="hidden"><div id="reviewList" class="space-y-3"></div></section>
    </main>
</div>

<div id="storyModal" class="lib-modal fixed inset-0 z-[150] hidden items-end sm:items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="storyModalTitle">
    <form id="storyForm" class="bg-white rounded-2xl w-full max-w-xl p-6 space-y-3 max-h-[90vh] overflow-y-auto">
        <h2 id="storyModalTitle" class="text-xl font-bold text-gray-900">Share your journey</h2>
        <label class="block"><span class="text-sm font-medium text-gray-700">Headline *</span><input name="title" required minlength="5" maxlength="120" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="From the RJIT library to Microsoft Hyderabad"></label>
        <label class="block"><span class="text-sm font-medium text-gray-700">Your story *</span><textarea name="content" required minlength="50" maxlength="6000" rows="8" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="What did you do in college, what was hard, what helped, and what would you tell juniors?"></textarea></label>
        <label class="block"><span class="text-sm font-medium text-gray-700">Cover image link</span><input name="featured_image" type="url" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="https://… (optional)"></label>
        <p class="text-xs text-gray-500">One story per person — sharing again updates it. Stories appear after a quick admin review.</p>
        <div class="flex justify-end gap-2"><button type="button" data-close class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700">Cancel</button><button class="bg-blue-600 text-white px-5 py-2 rounded-lg font-medium">Submit</button></div>
    </form>
</div>
<div id="resModal" class="lib-modal fixed inset-0 z-[150] hidden items-end sm:items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="resModalTitle">
    <form id="resForm" class="bg-white rounded-2xl w-full max-w-lg p-6 space-y-3">
        <h2 id="resModalTitle" class="text-xl font-bold text-gray-900">Add a resource</h2>
        <label class="block"><span class="text-sm font-medium text-gray-700">Title *</span><input name="title" required minlength="3" maxlength="160" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Striver SDE sheet"></label>
        <label class="block"><span class="text-sm font-medium text-gray-700">Link *</span><input name="file_url" type="url" required pattern="https://.*" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="https://…"></label>
        <div class="grid grid-cols-2 gap-3">
            <label class="block"><span class="text-sm font-medium text-gray-700">Category</span><select name="category" id="resFormCat" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg"></select></label>
            <label class="block"><span class="text-sm font-medium text-gray-700">Type</span><select name="resource_type" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg"><option value="link">Link</option><option value="document">Document</option><option value="sheet">Sheet</option><option value="video">Video</option><option value="repo">Repository</option></select></label>
        </div>
        <label class="block"><span class="text-sm font-medium text-gray-700">Why is it useful?</span><textarea name="description" rows="2" maxlength="600" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea></label>
        <div class="flex justify-end gap-2"><button type="button" data-close class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700">Cancel</button><button class="bg-blue-600 text-white px-5 py-2 rounded-lg font-medium">Share</button></div>
    </form>
</div>

<script>
(function () {
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const safe = (u) => /^https:\/\//i.test(String(u || '')) ? esc(u) : '';
    const notify = (m, t = 'success') => window.toast ? window.toast(m, t) : window.appAlert?.(m);
    const role = (() => { try { return JSON.parse(localStorage.getItem('user_data') || '{}').role || ''; } catch (_) { return ''; } })();
    const LABEL = { placements: 'Placements', notes: 'Notes', 'higher-studies': 'Higher studies', projects: 'Projects', internships: 'Internships', 'interview-prep': 'Interview prep', career: 'Career', other: 'Other' };
    const ICON = { link: '🔗', document: '📄', video: '🎬', sheet: '📊', repo: '💻' };
    if (['alumni', 'faculty', 'admin'].includes(role)) $('shareStoryBtn').classList.remove('hidden');
    if (role === 'admin') $('reviewTabBtn').classList.remove('hidden');

    const storyCard = (s, review) => `
        <article class="bg-white rounded-2xl border border-gray-200 overflow-hidden flex flex-col">
            ${safe(s.featured_image_url) ? `<img src="${safe(s.featured_image_url)}" alt="" class="h-40 w-full object-cover" loading="lazy">` : ''}
            <div class="p-5 flex flex-col gap-2 flex-1">
                <h3 class="text-lg font-bold text-gray-900 leading-snug">${esc(s.title)}</h3>
                <p class="text-sm text-gray-500"><a href="profile.php?id=${s.user_id}" class="font-medium text-gray-800 hover:text-blue-600">${esc(s.full_name)}</a>
                    ${s.job_role || s.current_company ? ' · ' + esc([s.job_role, s.current_company].filter(Boolean).join(' @ ')) : ''}${s.graduation_year ? ' · Batch ' + esc(s.graduation_year) : ''}</p>
                <p class="story-body text-sm text-gray-700 line-clamp-5" data-story="${s.story_id}">${esc(s.content)}</p>
                <div class="mt-auto flex items-center gap-3 pt-2 text-sm">
                    <button class="story-more text-blue-600 font-medium" data-id="${s.story_id}">Read more</button>
                    ${review ? `<button class="story-act ml-auto bg-green-600 text-white px-3 py-1.5 rounded-lg" data-act="approve" data-id="${s.story_id}">Approve</button>
                               <button class="story-act px-3 py-1.5 rounded-lg border border-red-200 text-red-600" data-act="reject" data-id="${s.story_id}">Remove</button>`
                             : `<span class="ml-auto text-xs text-gray-400">${s.is_featured ? `👁 ${s.view_count}` : '⏳ awaiting review'}</span>`}
                </div>
            </div>
        </article>`;

    async function loadStories() {
        const [pub, mine] = await Promise.all([window.makeApiCall('success_stories.php?action=list'), window.makeApiCall('success_stories.php?action=list&mine=1')]);
        const list = (pub?.data || []);
        const own = (mine?.data || []).filter((s) => !s.is_featured);
        const all = own.concat(list);
        $('storyGrid').innerHTML = all.length ? all.map((s) => storyCard(s, false)).join('')
            : `<div class="col-span-full text-center py-14 bg-white rounded-2xl border border-dashed border-gray-300"><p class="text-4xl mb-2">🌟</p><p class="font-medium text-gray-800">No stories yet</p><p class="text-sm text-gray-500">Alumni — your journey could be the one that changes a junior's path.</p></div>`;
    }
    async function loadReview() {
        const r = await window.makeApiCall('success_stories.php?action=list&pending=1');
        const list = r?.data || [];
        $('reviewCount').textContent = list.length ? `(${list.length})` : '';
        $('reviewList').innerHTML = list.length ? list.map((s) => storyCard(s, true)).join('') : '<p class="text-center text-gray-500 py-10">Nothing waiting for review. 🎉</p>';
    }
    async function loadResources() {
        const p = new URLSearchParams({ action: 'list' });
        if ($('resQ').value.trim()) p.set('q', $('resQ').value.trim());
        if ($('resCat').value) p.set('category', $('resCat').value);
        const r = await window.makeApiCall('resources.php?' + p);
        if ($('resCat').options.length === 1 && r?.categories) {
            r.categories.forEach((c) => { $('resCat').add(new Option(LABEL[c] || c, c)); $('resFormCat').add(new Option(LABEL[c] || c, c)); });
        }
        const list = r?.data || [];
        $('resList').innerHTML = list.length ? list.map((x) => `
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex gap-3">
                <div class="text-2xl">${ICON[x.resource_type] || '🔗'}</div>
                <div class="min-w-0 flex-1">
                    <button class="res-open text-left font-semibold text-gray-900 hover:text-blue-600" data-id="${x.resource_id}">${esc(x.title)}</button>
                    ${x.description ? `<p class="text-sm text-gray-600">${esc(x.description)}</p>` : ''}
                    <p class="text-xs text-gray-400 mt-1">${esc(LABEL[x.category] || x.category)} · shared by ${esc(x.uploader_name)} · ${x.download_count} opens</p>
                </div>
                ${x.can_delete ? `<button class="res-del text-xs text-red-500 self-start" data-id="${x.resource_id}">Remove</button>` : ''}
            </div>`).join('') : '<p class="col-span-full text-center text-gray-500 py-10">No resources yet — share the first one!</p>';
    }

    const open = (id) => { const m = $(id); m.classList.remove('hidden'); m.classList.add('flex'); m.querySelector('input,textarea')?.focus(); };
    const close = (m) => { m.classList.add('hidden'); m.classList.remove('flex'); };
    document.querySelectorAll('.lib-modal').forEach((m) => m.addEventListener('click', (e) => { if (e.target === m || e.target.closest('[data-close]')) close(m); }));
    $('shareStoryBtn').addEventListener('click', async () => {
        const mine = await window.makeApiCall('success_stories.php?action=list&mine=1');
        const s = mine?.data?.[0];
        const f = $('storyForm'); f.reset();
        if (s) { f.title.value = s.title; f.content.value = s.content; f.featured_image.value = s.featured_image_url || ''; }
        open('storyModal');
    });
    $('addResBtn').addEventListener('click', () => { $('resForm').reset(); open('resModal'); });
    $('storyForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const r = await window.makeApiCall('success_stories.php?action=create', 'POST', Object.fromEntries(new FormData(e.target)));
        notify(r?.message || 'Failed.', r?.success ? 'success' : 'error');
        if (r?.success) { close($('storyModal')); loadStories(); }
    });
    $('resForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const r = await window.makeApiCall('resources.php?action=create', 'POST', Object.fromEntries(new FormData(e.target)));
        notify(r?.message || 'Failed.', r?.success ? 'success' : 'error');
        if (r?.success) { close($('resModal')); loadResources(); }
    });
    $('resSearch').addEventListener('submit', (e) => { e.preventDefault(); loadResources(); });

    document.addEventListener('click', async (e) => {
        const tab = e.target.closest('.lib-tab');
        if (tab) {
            document.querySelectorAll('.lib-tab').forEach((t) => t.setAttribute('aria-selected', String(t === tab)));
            document.querySelectorAll('[data-panel]').forEach((p) => p.classList.toggle('hidden', p.dataset.panel !== tab.dataset.tab));
            ({ stories: loadStories, resources: loadResources, review: loadReview })[tab.dataset.tab]();
            return;
        }
        const more = e.target.closest('.story-more');
        if (more) {
            const body = document.querySelector(`[data-story="${more.dataset.id}"]`);
            const expanded = body.classList.toggle('line-clamp-5') === false;
            more.textContent = expanded ? 'Show less' : 'Read more';
            if (expanded) window.makeApiCall('success_stories.php?action=view', 'POST', { story_id: +more.dataset.id });
            return;
        }
        const act = e.target.closest('.story-act');
        if (act) {
            const r = await window.makeApiCall(`success_stories.php?action=${act.dataset.act}`, 'POST', { story_id: +act.dataset.id });
            notify(r?.message || 'Failed.', r?.success ? 'success' : 'error');
            loadReview(); loadStories();
            return;
        }
        const ro = e.target.closest('.res-open');
        if (ro) {
            const r = await window.makeApiCall('resources.php?action=download', 'POST', { resource_id: +ro.dataset.id });
            if (r?.success && /^https:\/\//i.test(r.url)) window.open(r.url, '_blank', 'noopener');
            return;
        }
        const rd = e.target.closest('.res-del');
        if (rd && await window.appConfirm('Remove this resource?', { title: 'Remove resource', confirmText: 'Remove' })) {
            const r = await window.makeApiCall('resources.php?action=delete', 'POST', { resource_id: +rd.dataset.id });
            notify(r?.message || 'Failed.', r?.success ? 'success' : 'error');
            loadResources();
        }
    });
    if (location.hash === '#resources') document.querySelector('[data-tab=resources]').click(); else loadStories();
    if (role === 'admin') loadReview();
})();
</script>
<?php include 'includes/footer.php'; ?>
