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
               'referrals.php', 'jobs.php', 'events.php', 'mentorship.php', 'help.php'],
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
    await page.close()
    return ok

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
            for path in PAGES['common'] + (PAGES['admin'] if role == 'admin' else []):
                await visit(ctx, role, path, results)
            await ctx.close()
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
