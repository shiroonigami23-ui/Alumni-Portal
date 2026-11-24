#!/usr/bin/env python3
"""
Stored-XSS sweep in a real browser.

1. Signs in as an alumnus ("attacker") through the API and plants an XSS payload in every field
   a user can write: name, bio, company, skills, posts, comments, replies, messages, jobs, events,
   referral offers/requests, success stories, resources, live-session titles, file names, search text.
2. Opens every page as the admin and as a student, waits, and checks whether any payload ran.

A payload that runs pushes its tag into window.__xss, so a failure names the exact field.

    python3 tests/ui/xss_sweep.py [--base URL] [--cdn-dir DIR] [--chrome PATH] [--out tests/results/xss-latest.json]
"""
import argparse, asyncio, json, os, sys, time, urllib.request
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from playwright.async_api import async_playwright

ap = argparse.ArgumentParser()
ap.add_argument('--base', default=os.environ.get('BASE_URL', 'http://127.0.0.1:8099/'))
ap.add_argument('--cdn-dir')
ap.add_argument('--chrome')
ap.add_argument('--out', default='tests/results/xss-latest.json')
args = ap.parse_args()
BASE = args.base.rstrip('/') + '/'

ATTACKER = ('meera.rajput.7@demo.rjit.local', 'Demo@12345')
VIEWERS = {
    'admin': ('admin@rjit.ac.in', os.environ.get('ADMIN_PASSWORD', 'Admin@12345')),
    'student': ('ayaan.tomar@demo.rjit.local', 'Demo@12345'),
}

def P(tag):
    # short enough for 120-char fields; window.__x is defined in every page by an init script
    return f'<img src=x onerror="__x(\'{tag}\')">"><svg onload="__x(\'{tag}-s\')">'

def api(method, path, token=None, body=None, files=None):
    url = BASE + 'api/' + path
    headers = {'Accept': 'application/json'}
    if token:
        headers['Authorization'] = 'Bearer ' + token
    data = None
    if files:
        boundary = '----xss' + str(time.time()).replace('.', '')
        parts = []
        for k, v in (body or {}).items():
            parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode())
        for k, (name, content, mime) in files.items():
            parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"; filename="{name}"\r\nContent-Type: {mime}\r\n\r\n'.encode() + content + b'\r\n')
        parts.append(f'--{boundary}--\r\n'.encode())
        data = b''.join(parts)
        headers['Content-Type'] = 'multipart/form-data; boundary=' + boundary
    elif body is not None:
        data = json.dumps(body).encode()
        headers['Content-Type'] = 'application/json'
    req = urllib.request.Request(url, data=data, headers=headers, method=method)
    try:
        with urllib.request.urlopen(req, timeout=20) as r:
            return r.status, json.loads(r.read() or b'null')
    except urllib.error.HTTPError as e:
        try:
            return e.code, json.loads(e.read() or b'null')
        except Exception:
            return e.code, None

def token_for(email, pw):
    code, j = api('POST', 'login.php', body={'email': email, 'password': pw})
    j = j or {}
    return j.get('token') or (j.get('data') or {}).get('token')

def ids(j, *keys):
    if isinstance(j, dict):
        for k in keys:
            if k in j and j[k]:
                return j[k]
        for v in j.values():
            r = ids(v, *keys)
            if r:
                return r
    return None

def seed():
    t = token_for(*ATTACKER)
    adm = token_for(*VIEWERS['admin'])
    stu = token_for(*VIEWERS['student'])
    assert t and adm and stu, 'login failed'
    _, me = api('GET', 'me.php', t)
    uid = ids(me, 'user_id', 'id')
    _, sme = api('GET', 'me.php', stu)
    sid = ids(sme, 'user_id', 'id')
    log = []
    def do(name, *a, **k):
        code, j = api(*a, **k)
        log.append((name, code))
        return j
    do('profile', 'POST', 'update_profile.php', t, {'full_name': 'Meera ' + P('name'), 'bio': P('bio'), 'current_company': P('company'),
        'job_role': P('jobrole'), 'location_city': P('city'), 'skills': 'PHP, ' + P('skill'), })
    do('profile-links', 'POST', 'update_profile.php', t, {'personal_website': 'javascript:alert(1)'})
    j = do('post', 'POST', 'create_post.php', t, {'content': 'Hello ' + P('post'), 'post_type': 'text'})
    pid = ids(j, 'post_id', 'id')
    # approve so every viewer sees it
    do('approve', 'POST', 'moderation_queue.php', adm, {'post_id': pid, 'action': 'approve'})
    do('comment', 'POST', 'create_comment.php', t, {'post_id': pid, 'content': P('comment')})
    jf = do('post-file', 'POST', 'create_post.php', t, {'content': 'file post', 'post_type': 'text'},
            files={'attachment': (P('filename').replace('"', '').replace('/', '') + '.pdf', b'%PDF-1.4\n%%EOF\n', 'application/pdf')})
    do('approve2', 'POST', 'moderation_queue.php', adm, {'post_id': ids(jf, 'post_id', 'id'), 'action': 'approve'})
    _, cj = api('POST', 'create_conversation.php', t, {'other_user_id': sid})
    do('message', 'POST', 'send_message.php', t, {'receiver_id': sid, 'conversation_id': ids(cj, 'conversation_id', 'id') or sid, 'message': P('message')})
    do('message-admin', 'POST', 'send_message.php', t, {'receiver_id': 1, 'message': P('message-admin')})
    do('job', 'POST', 'post_job.php', t, {'job_title': 'SDE ' + P('jobtitle'), 'company_name': 'Acme ' + P('jobco'), 'location': P('jobloc'),
        'job_type': 'full-time', 'description': 'Great role, apply now please. ' + P('jobdesc'), 'requirements': P('jobreq')})
    d = time.strftime('%Y-%m-%d', time.localtime(time.time() + 86400 * 5))
    do('event', 'POST', 'create_event.php', adm, {'title': 'Meet ' + P('event'), 'description': P('eventdesc'), 'event_date': d, 'event_time': '18:00',
        'end_date': d, 'end_time': '20:00', 'location': P('eventloc'), 'visibility': 'public'})
    do('referral', 'POST', 'referrals.php', t, {'action': 'create_offer', 'company': 'Acme ' + P('refco'), 'roles': P('refroles'), 'locations': P('refloc'), 'slots': 2})
    do('story', 'POST', 'success_stories.php?action=create', t, {'title': 'Story ' + P('storytitle'), 'content': ('My long journey from campus to company. ' * 3) + P('story')})
    do('resource', 'POST', 'resources.php?action=create', t, {'title': 'Notes ' + P('restitle'), 'description': P('resdesc'), 'file_url': 'https://example.com/n.pdf', 'category': P('rescat')})
    do('live', 'POST', 'toggle_stream.php', t, {'action': 'start', 'title': 'Live ' + P('livetitle'), 'description': P('livedesc'), 'provider': 'jitsi'})
    do('report', 'POST', 'report_content.php', t, {'post_id': pid, 'reason': 'other', 'details': P('report')})
    do('follow-notif', 'POST', 'connect_user.php', t, {'user_id': sid, 'action': 'follow'})
    do('follow-notif-admin', 'POST', 'connect_user.php', t, {'user_id': 1, 'action': 'follow'})
    do('mentor', 'POST', 'mentorship.php?action=become_mentor', t, {'headline': 'Mentor ' + P('mentorhead'), 'expertise': P('mentorexp'), 'message': P('mentormsg')})
    # Legacy data: rows written before input cleaning existed. Plant raw HTML directly so the
    # browser-side escaping is tested too (needs DB access; skipped otherwise).
    if os.environ.get('DB_NAME') and uid:
        import subprocess
        sql = ("UPDATE profiles SET full_name = $x$Meera " + P('legacy-name') + "$x$, bio = $x$" + P('legacy-bio') + "$x$, "
               "current_company = $x$" + P('legacy-co') + "$x$, job_role = $x$" + P('legacy-role') + "$x$, "
               "linkedin_url = 'javascript:__x(1)' WHERE user_id = " + str(int(uid)) + "; "
               "INSERT INTO notifications (user_id, notification_type, content, related_user_id) "
               "SELECT user_id, 'follow', $x$" + P('notif') + " started following you$x$, " + str(int(uid)) + " FROM users "
               "WHERE email IN ('admin@rjit.ac.in', 'ayaan.tomar@demo.rjit.local')")
        env = dict(os.environ, PGPASSWORD=os.environ.get('DB_PASSWORD', ''))
        r = subprocess.run(['psql', '-h', os.environ.get('DB_HOST', '127.0.0.1'), '-U', os.environ.get('DB_USER', 'postgres'),
                            '-d', os.environ['DB_NAME'], '-qAtc', sql], env=env, capture_output=True, text=True)
        log.append(('legacy-profile-rows', 200 if r.returncode == 0 else 500))
    return uid, pid, log, t

async def main():
    uid, pid, log, atk = seed()
    print('seeded:', ', '.join(f'{n}:{c}' for n, c in log))
    pages = ['dashboard.php', 'feed.php', f'profile.php?id={uid}', 'messages.php', 'discovery.php', 'discovery.php?search=Meera', 'atlas.php',
             'referrals.php', 'jobs.php', 'events.php', 'mentorship.php', 'live.php', 'library.php', 'help.php',
             f'profile.php?id=%27%3B(window.__xss%3Dwindow.__xss||[]).push(%27reflected-id%27)%3B%2F%2F']
    admin_pages = ['admin/insights.php', 'admin/dashboard.php', 'admin/users.php', 'admin/reports.php', 'admin/tokens.php', 'moderation.php',
                   'library.php#review']
    cdn = {}
    if args.cdn_dir:
        d = Path(args.cdn_dir)
        cdn = {k: (d / f).read_text() for k, f in [('tailwind', 'tw.js'), ('lucide', 'lucide.min.js'), ('leaflet.js', 'leaflet.js'),
                                                   ('leaflet.css', 'leaflet.css'), ('fonts', 'fonts.css')] if (d / f).exists()}

    async def route(r):
        u = r.request.url
        if u.startswith(BASE) or not cdn:
            return await r.continue_()
        for key, needle, ctype in [('tailwind', 'cdn.tailwindcss.com', 'application/javascript'), ('lucide', 'lucide', 'application/javascript'),
                                   ('fonts', 'fonts.googleapis.com', 'text/css')]:
            if needle in u and key in cdn:
                return await r.fulfill(body=cdn[key], content_type=ctype)
        if 'leaflet' in u and 'leaflet.js' in cdn:
            return await r.fulfill(body=cdn['leaflet.js' if u.endswith('.js') else 'leaflet.css'], content_type='application/javascript' if u.endswith('.js') else 'text/css')
        if 'basemaps' in u or 'openstreetmap' in u:
            return await r.fulfill(status=200, body=b'', content_type='image/png')
        return await r.abort()

    results = []
    async with async_playwright() as p:
        b = await p.chromium.launch(executable_path=args.chrome) if args.chrome else await p.chromium.launch()
        for role, (email, pw) in VIEWERS.items():
            ctx = await b.new_context(viewport={'width': 1440, 'height': 900})
            await ctx.route('**/*', route)
            await ctx.add_init_script("window.__x = function (t) { (window.__xss = window.__xss || []).push(t); };")
            lp = await ctx.new_page()
            await lp.goto(BASE + 'login.php', wait_until='networkidle')
            await lp.fill('#email', email); await lp.fill('#password', pw)
            await lp.click('button[type=submit]')
            await lp.wait_for_function("() => !!localStorage.getItem('jwt_token')", timeout=10000)
            await lp.wait_for_load_state('networkidle'); await lp.close()
            for path in pages + (admin_pages if role == 'admin' else []):
                pg = await ctx.new_page()
                dialogs = []
                pg.on('dialog', lambda dlg: (dialogs.append(dlg.message), asyncio.ensure_future(dlg.dismiss())))
                try:
                    await pg.goto(BASE + path, wait_until='networkidle', timeout=20000)
                    await pg.wait_for_timeout(900)
                    # open every comment section + the first conversation so lazy content renders
                    for sel in ['#notificationBtn, [data-notification-toggle], #notifBell', '.comment-toggle-btn', '.conversation-item', '[data-conversation-id]']:
                        for el in (await pg.query_selector_all(sel))[:3]:
                            try:
                                await el.click(timeout=1500)
                                await pg.wait_for_timeout(500)
                            except Exception:
                                pass
                    for link in await pg.query_selector_all('a[href^="javascript:"]:not([href^="javascript:void"])'):
                        dialogs.append('javascript: link rendered → ' + (await link.get_attribute('href'))[:40])
                    fired = await pg.evaluate('window.__xss || []')
                except Exception as e:
                    fired = [f'load error: {e}'[:120]]
                fired = sorted(set(fired)) + dialogs
                ok = not fired
                results.append({'role': role, 'page': '/' + path, 'pass': ok, 'fired': fired})
                print(f"  {'✓' if ok else '✗'} {role:8} /{path[:60]:60} {'' if ok else fired}")
                await pg.close()
            await ctx.close()
        await b.close()
    api('POST', 'toggle_stream.php', atk, {'action': 'stop'})
    passed = sum(r['pass'] for r in results)
    Path(args.out).parent.mkdir(parents=True, exist_ok=True)
    Path(args.out).write_text(json.dumps({'generated': time.strftime('%Y-%m-%dT%H:%M:%S%z'), 'passed': passed, 'total': len(results),
                                          'seeded': log, 'results': results}, indent=2))
    print(f'{passed}/{len(results)} pages free of XSS')
    sys.exit(0 if passed == len(results) else 1)

asyncio.run(main())
