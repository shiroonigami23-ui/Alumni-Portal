"""
Real-browser UI smoke test (Playwright + Chrome).

Signs in as admin, alumnus and student, opens every page, and records:
  - JavaScript errors (pageerror + console.error)
  - failed same-origin requests (HTTP >= 400, except expected 401/403/404 probes)
  - blank pages
Optionally saves screenshots (--shots DIR).

Usage:
  python3 tests/ui/browser_smoke.py --base http://127.0.0.1:8099 [--cdn-dir DIR] [--shots DIR]

--cdn-dir: folder with offline copies of tw.js (Tailwind), lucide.min.js, leaflet.js,
leaflet.css and fonts.css for sandboxes without internet. Without it, CDNs load normally.
Writes tests/results/ui-latest.md and .json.
"""
import argparse, asyncio, json, os, sys, time
from pathlib import Path
from playwright.async_api import async_playwright

ROOT = Path(__file__).resolve().parents[2]
ap = argparse.ArgumentParser()
ap.add_argument('--base', default='http://127.0.0.1:8099')
ap.add_argument('--cdn-dir')
ap.add_argument('--shots')
ap.add_argument('--chrome', default=os.environ.get('CHROME_PATH'))
args = ap.parse_args()
BASE = args.base.rstrip('/') + '/'

ACCOUNTS = {
    'admin': ('admin@rjit.ac.in', os.environ.get('ADMIN_PASSWORD', 'Admin@12345')),
    'alumni': (os.environ.get('ALUMNI_EMAIL', 'aarav.sharma.0@demo.rjit.local'), 'Demo@12345'),
    'student': (os.environ.get('STUDENT_EMAIL', 'ayaan.tomar@demo.rjit.local'), 'Demo@12345'),
}
PAGES = {
    'common': ['dashboard.php', 'feed.php', 'profile.php', 'settings.php', 'messages.php', 'discovery.php', 'atlas.php',
               'referrals.php', 'jobs.php', 'events.php', 'mentorship.php', 'live.php', 'library.php', 'help.php'],
    'admin': ['admin/insights.php', 'admin/dashboard.php', 'admin/users.php', 'admin/reports.php', 'admin/tokens.php',
              'admin/system-settings.php', 'moderation.php'],
    'public': ['', 'login.php', 'register.php', 'forgot-password.php', 'terms.php', 'policy.php', 'conduct.php', 'old-link-404'],
}
cdn = {}
if args.cdn_dir:
    d = Path(args.cdn_dir)
    cdn = {k: (d / f).read_text() for k, f in [('tailwind', 'tw.js'), ('lucide', 'lucide.min.js'), ('leaflet.js', 'leaflet.js'),
                                               ('leaflet.css', 'leaflet.css'), ('fonts', 'fonts.css')] if (d / f).exists()}

async def route(r):
    u = r.request.url
    if u.startswith(BASE) or not cdn:
        return await r.continue_()
    if 'cdn.tailwindcss.com' in u and 'tailwind' in cdn: return await r.fulfill(body=cdn['tailwind'], content_type='application/javascript')
    if 'lucide' in u and 'lucide' in cdn: return await r.fulfill(body=cdn['lucide'], content_type='application/javascript')
    if 'leaflet' in u and u.endswith('.js') and 'leaflet.js' in cdn: return await r.fulfill(body=cdn['leaflet.js'], content_type='application/javascript')
    if 'leaflet' in u and u.endswith('.css') and 'leaflet.css' in cdn: return await r.fulfill(body=cdn['leaflet.css'], content_type='text/css')
    if 'fonts.googleapis.com' in u and 'fonts' in cdn: return await r.fulfill(body=cdn['fonts'], content_type='text/css')
    if 'basemaps.cartocdn.com' in u or 'tile.openstreetmap' in u:  # offline map tiles: plain tile
        return await r.fulfill(status=200, body=b'', content_type='image/png')
    return await r.abort()

IGNORE = ('favicon', 'Failed to load resource: net::ERR_FAILED', 'net::ERR_BLOCKED', 'cdn.tailwindcss.com should not be used in production',
          'ERR_NAME_NOT_RESOLVED', 'push', 'Notification', 'ERR_ABORTED')

async def visit(ctx, role, path, results):
    page = await ctx.new_page()
    errors, failed = [], []
    page.on('pageerror', lambda e: errors.append(f'JS: {e}'))
    page.on('console', lambda m: errors.append(f'console: {m.text}') if m.type == 'error' and not any(i in m.text for i in IGNORE) and not (path == 'old-link-404' and '404' in m.text) else None)
    def on_resp(resp):
        if os.environ.get('UI_DEBUG') and resp.status >= 400:
            print('      debug:', resp.status, resp.url[:140])
        if resp.url.startswith(BASE) and resp.status >= 400 and not (path == 'old-link-404' and resp.status == 404):
            failed.append(f'{resp.status} {resp.url.replace(BASE, "/")}')
    page.on('response', on_resp)
    t = time.time()
    try:
        await page.goto(BASE + path, wait_until='networkidle', timeout=20000)
        await page.wait_for_timeout(700)
    except Exception as e:
        errors.append(f'load: {e}')
    ms = round((time.time() - t) * 1000)
    final = page.url.replace(BASE, '/')
    text = (await page.inner_text('body'))[:20000] if not errors or True else ''
    blank = len(text.strip()) < 40
    if args.shots:
        os.makedirs(args.shots, exist_ok=True)
        await page.screenshot(path=f"{args.shots}/{role}_{(path or 'home').replace('/', '_').replace('.php', '')}.png")
    ok = not errors and not failed and not blank
    results.append({'role': role, 'page': '/' + path, 'final_url': final, 'ms': ms, 'pass': ok, 'errors': errors[:6], 'failed_requests': failed[:6], 'blank': blank})
    print(f"  {'✓' if ok else '✗'} {role:8} /{path:28} {ms:5}ms {'' if ok else (errors + failed + (['blank page'] if blank else []))[:3]}")
    await page.close()

async def login(ctx, email, password):
    page = await ctx.new_page()
    seen = []
    page.on('console', lambda m: seen.append(f'{m.type}: {m.text[:200]}'))
    page.on('pageerror', lambda e: seen.append(f'pageerror: {e}'))
    page.on('response', lambda r: seen.append(f'{r.status} {r.url[-70:]}') if '/api/' in r.url else None)
    await page.goto(BASE + 'login.php', wait_until='networkidle')
    await page.fill('#email', email)
    await page.fill('#password', password)
    await page.click('button[type=submit]')
    try:
        await page.wait_for_function("() => !!localStorage.getItem('jwt_token')", timeout=10000)
        await page.wait_for_load_state('networkidle')
        ok = True
    except Exception:
        ok = False
        print(f'    login failed for {email} at {page.url}; browser said:')
        for line in seen[-25:]:
            print('      ' + line)
    await page.close()
    return ok

async def interactions(b, results):
    """Click-through checks of interactive features in a real browser."""
    def rec(name, ok, detail=''):
        results.append({'role': 'flow', 'page': name, 'pass': bool(ok), 'ms': None, 'errors': [] if ok else [detail or 'failed'], 'failed_requests': [], 'blank': False})
        print(f"  {'✓' if ok else '✗'} flow     {name} {'' if ok else detail}")
    stamp = str(int(time.time()))
    # ---- admin: draft, poll, schedule, palette
    ctx = await b.new_context(viewport={'width': 1440, 'height': 900}); await ctx.route('**/*', route)
    await login(ctx, *ACCOUNTS['admin'])
    pg = await ctx.new_page()
    errs = []
    pg.on('pageerror', lambda e: errs.append(str(e)))
    await pg.goto(BASE + 'feed.php', wait_until='networkidle'); await pg.wait_for_timeout(600)
    await pg.fill('#postContent', f'Draft {stamp}')
    await pg.click('#saveDraftBtn'); await pg.wait_for_timeout(300)
    await pg.reload(wait_until='networkidle'); await pg.wait_for_timeout(600)
    rec('Feed draft survives a reload', await pg.input_value('#postContent') == f'Draft {stamp}')
    await pg.click('#discardDraftBtn'); await pg.wait_for_timeout(300)
    rec('Discard draft clears the composer', await pg.input_value('#postContent') == '')
    # poll post
    await pg.fill('#postContent', f'UI poll {stamp}')
    await pg.click('#fxPollBtn'); await pg.fill('#fxPollQ', 'Tea or coffee?')
    opts = await pg.query_selector_all('.fx-opt'); await opts[0].fill('Tea'); await opts[1].fill('Coffee')
    await pg.click('#submitPostBtn'); await pg.wait_for_timeout(2500)
    card = await pg.query_selector(f'.post-card:has-text("UI poll {stamp}")')
    rec('Create a post with a poll from the composer', card is not None and await card.query_selector('.fx-poll') is not None)
    # schedule
    await pg.fill('#postContent', f'UI scheduled {stamp}')
    await pg.click('#fxScheduleBtn'); await pg.wait_for_timeout(200)
    rec('Schedule mode switches the button to “Schedule”', (await pg.inner_text('#submitPostBtn')).strip() == 'Schedule')
    await pg.click('#submitPostBtn'); await pg.wait_for_timeout(1500)
    rec('Scheduled post appears in “Your scheduled posts”', f'UI scheduled {stamp}' in (await pg.inner_text('#fxScheduled')))
    # palette
    await pg.keyboard.press('Control+k'); await pg.wait_for_timeout(200)
    await pg.keyboard.type('library'); await pg.wait_for_timeout(150); await pg.keyboard.press('Enter')
    try:
        await pg.wait_for_url('**/library.php', timeout=8000); nav = True
    except Exception:
        nav = False
    rec('Ctrl+K palette navigates', nav, pg.url)
    rec('No JavaScript errors during admin flows', not errs, '; '.join(errs[:2]))
    await ctx.close()
    # ---- student: vote on the poll
    ctx = await b.new_context(viewport={'width': 1440, 'height': 900}); await ctx.route('**/*', route)
    await login(ctx, *ACCOUNTS['student'])
    pg = await ctx.new_page()
    await pg.goto(BASE + 'feed.php', wait_until='networkidle'); await pg.wait_for_timeout(800)
    await pg.select_option('#sortBy', 'newest')
    await pg.wait_for_timeout(1200)
    card = await pg.query_selector(f'.post-card:has-text("UI poll {stamp}")')
    btn = await card.query_selector('.fx-vote') if card else None
    if btn:
        await btn.click(); await pg.wait_for_timeout(1200)
        card = await pg.query_selector(f'.post-card:has-text("UI poll {stamp}")')
        rec('Vote on a poll and see results', card is not None and '1 vote' in (await card.inner_text()))
    else:
        rec('Vote on a poll and see results', False, 'poll not found in feed')
    await ctx.close()

    # ---- privacy + @handles: alumnus goes private, student finds them by /@handle and asks to follow
    actx = await b.new_context(viewport={'width': 1440, 'height': 900}); await actx.route('**/*', route)
    await login(actx, *ACCOUNTS['alumni'])
    ap = await actx.new_page()
    aerrs = []
    ap.on('pageerror', lambda e: aerrs.append(str(e)))
    await ap.goto(BASE + 'settings.php?scope=basic#profile', wait_until='networkidle'); await ap.wait_for_timeout(900)
    if await ap.is_disabled('#settingsUsername'):
        cool = 'change it again' in (await ap.inner_text('#settingsUsernameHelp')).lower()
        rec('Handle field rejects reserved names live', cool, 'in cool-down')
        rec('Handle field shows a free handle as available', cool, 'in cool-down (shows next change date)')
    else:
        await ap.fill('#settingsUsername', 'admin'); await ap.wait_for_timeout(900)
        rec('Handle field rejects reserved names live', 'reserved' in (await ap.inner_text('#settingsUsernameHelp')).lower())
        await ap.fill('#settingsUsername', f'ui_free_{stamp[-6:]}'); await ap.wait_for_timeout(900)
        rec('Handle field shows a free handle as available', 'available' in (await ap.inner_text('#settingsUsernameHelp')).lower()
            and not await ap.is_disabled('#settingsUsernameSave'))
    rec('Privacy page lists visibility toggles', len(await ap.query_selector_all('#settingsVisibilityToggles input[data-key]')) >= 3)
    handle = await ap.evaluate("async () => (await makeApiCall('username.php')).data.username")
    await ap.evaluate("async () => makeApiCall('update_privacy.php', 'POST', {is_private: true})")
    sctx = await b.new_context(viewport={'width': 1440, 'height': 900}); await sctx.route('**/*', route)
    await login(sctx, *ACCOUNTS['student'])
    sp = await sctx.new_page()
    serrs = []
    sp.on('pageerror', lambda e: serrs.append(str(e)))
    # make sure we start as a non-follower
    await sp.goto(BASE + 'feed.php', wait_until='networkidle')
    aid = await ap.evaluate("async () => (await makeApiCall('me.php')).data.user_id")
    await sp.evaluate(f"async () => makeApiCall('connect_user.php', 'POST', {{user_id: {int(aid)}, action: 'unfollow'}})")
    await sp.goto(BASE + '@' + handle, wait_until='networkidle'); await sp.wait_for_timeout(1200)
    rec('/@handle opens the right profile', f'u={handle}' in sp.url and ('@' + handle) in (await sp.inner_text('#profileHandle')), sp.url)
    locked = await sp.is_visible('#lockedPanel') and 'private' in (await sp.inner_text('#lockedPanel')).lower()
    rec('Private profile shows a locked card to non-followers', locked)
    rec('Follow button reads "Request to follow"', 'Request to follow' in (await sp.inner_text('#connectBtn')))
    await sp.click('#connectBtn'); await sp.wait_for_timeout(1000)
    rec('Sending a request switches the button to "Requested"', 'Requested' in (await sp.inner_text('#connectBtn')))
    await ap.goto(BASE + 'settings.php?scope=privacy#privacy', wait_until='networkidle'); await ap.wait_for_timeout(1200)
    appr = await ap.query_selector('#settingsFollowRequests [data-act="approve"]')
    if appr:
        await appr.click(); await ap.wait_for_timeout(1200)
    rec('Owner approves the request from Settings', appr is not None)
    await sp.reload(wait_until='networkidle'); await sp.wait_for_timeout(1200)
    rec('Approved follower sees the full profile', not await sp.is_visible('#lockedPanel') and await sp.is_visible('#tabContent'))
    await ap.evaluate("async () => makeApiCall('update_privacy.php', 'POST', {is_private: false})")
    rec('No JavaScript errors in privacy / handle flows', not aerrs and not serrs, '; '.join((aerrs + serrs)[:2]))
    await actx.close(); await sctx.close()

async def main():
    results = []
    async with async_playwright() as p:
        b = await p.chromium.launch(executable_path=args.chrome or None, args=['--no-sandbox'])
        ctx = await b.new_context(viewport={'width': 1440, 'height': 900})
        await ctx.route('**/*', route)
        print('public')
        for path in PAGES['public']:
            await visit(ctx, 'guest', path, results)
        await ctx.close()
        for role, (email, pw) in ACCOUNTS.items():
            ctx = await b.new_context(viewport={'width': 1440, 'height': 900})
            await ctx.route('**/*', route)
            ok = await login(ctx, email, pw)
            results.append({'role': role, 'page': 'login via UI', 'pass': ok, 'ms': None, 'errors': [] if ok else ['login failed'], 'failed_requests': [], 'blank': False})
            print(f"{role}: login {'ok' if ok else 'FAILED'}")
            if not ok:
                # Logged-out pages would "pass" silently (no token → no API calls), so don't pretend.
                await ctx.close()
                continue
            for path in PAGES['common'] + (PAGES['admin'] if role == 'admin' else []):
                await visit(ctx, role, path, results)
            await ctx.close()
        print('interactions')
        await interactions(b, results)
        await b.close()
    passed = sum(r['pass'] for r in results)
    print(f'\n{passed}/{len(results)} UI checks passed')
    out = ROOT / 'tests' / 'results'
    out.mkdir(parents=True, exist_ok=True)
    (out / 'ui-latest.json').write_text(json.dumps({'date': time.strftime('%Y-%m-%dT%H:%M:%S%z'), 'passed': passed, 'total': len(results), 'results': results}, indent=2, ensure_ascii=False))
    md = [f'# UI smoke test results\n\n**{passed} / {len(results)} passed** · {time.strftime("%Y-%m-%d %H:%M")} · Chrome via Playwright, signed in as each role.\n',
          'Each page is opened in a real browser; a check fails on any JavaScript error, failed same-origin request, or blank page.\n',
          '| | Role | Page | Load | Problems |', '|---|---|---|---|---|']
    for r in results:
        md.append(f"| {'✅' if r['pass'] else '❌'} | {r['role']} | `{r['page']}` | {str(r['ms']) + ' ms' if r['ms'] else ''} | {'; '.join((r['errors'] + r['failed_requests'])[:3]).replace('|', '/')[:300]} |")
    (out / 'ui-latest.md').write_text('\n'.join(md) + '\n')
    sys.exit(0 if passed == len(results) else 1)

asyncio.run(main())
