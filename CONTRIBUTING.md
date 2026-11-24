# Contributing to RJIT Alumni Portal

Thanks for helping build the RJIT community hub! Students, alumni and faculty are all welcome — whether that's fixing a typo, improving a page, or shipping a whole feature.

## 🧭 Ground rules

- Be kind and constructive — see [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).
- **Never** commit real user data, `.env` files, database dumps or anything from `storage/`.
- Security issues go to [SECURITY.md](SECURITY.md), not public issues.

## 🛠️ Local setup

```bash
git clone https://github.com/<you>/Alumni-Portal.git
cd Alumni-Portal
cp .env.example .env

# Easiest: Docker
docker compose up --build

# Or, no database needed for UI work:
php -S localhost:8088 router.php
```

Full options (XAMPP + PostgreSQL / MySQL, AWS) are in the [README](README.md#-quick-start).

## 🌿 Workflow

1. **Open or pick an issue** so work isn't duplicated. Use the bug/feature templates.
2. **Fork** and create a branch: `feat/alumni-map`, `fix/feed-gif-preview`, `docs/setup-mysql`.
3. Make focused changes — one feature or fix per pull request.
4. Run the checks below.
5. Open a **pull request** using the template and link the issue (`Closes #12`).

### Commit messages

We use [Conventional Commits](https://www.conventionalcommits.org/):

```
feat(mentorship): let mentors pause new requests
fix(feed): stop double-submit on slow networks
docs(readme): add MySQL troubleshooting
security(api): require auth on export endpoint
refactor(router): extract static policy
```

## ✅ Before you open a PR

```bash
# 1. Lint every PHP file + the contracts (seconds, no database)
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
php tests/static/api_contract.php          # every endpoint declares who may call it
python3 tests/static/frontend_contract.py  # every user value in page templates is escaped

# 2. Route + security smoke test (no DB required)
bash tests/smoke/routes.sh

# 3. End-to-end API tests against a throwaway PostgreSQL database
createdb alumni_test && DB_NAME=alumni_test php scripts/install_db.php --demo --admin-password='Admin@12345'
DB_NAME=alumni_test RATE_LIMIT_DISABLED=true php -S 127.0.0.1:8099 router.php &
php tests/integration/run.php
DB_NAME=alumni_test php tests/integration/authz.php            # staff endpoints refuse members
SERVER_LOG=/tmp/srv.log php tests/integration/sqli_fuzz.php    # start the server with 2>/tmp/srv.log
python3 tests/ui/browser_smoke.py && DB_NAME=alumni_test python3 tests/ui/xss_sweep.py   # needs: pip install playwright

# 4. If you added Tailwind classes, rebuild the CSS
npm --prefix tools/tailwind install && npm --prefix tools/tailwind run build
```

Then:

- [ ] UI changes checked in **dark and light** themes and on a **phone-width** screen
- [ ] No new console errors
- [ ] New strings shown to users are friendly and clear
- [ ] `CHANGELOG.md` updated under **[Unreleased]**
- [ ] `README.md` updated if behaviour, setup or URLs changed
- [ ] Screenshots / GIF attached to the PR for visual changes

## 🧩 How the code is organised

| Want to… | Go to |
|---|---|
| Add or change a **page** | `pages/` (auth pages in `pages/auth/`, legal in `pages/legal/`) |
| Give it a **URL** | one line in `bootstrap/routes.php` → `'pages'` |
| **Rename / move** a page safely | update its target, add the old slug to `'redirects'` |
| Add an **API endpoint** | `api/your_endpoint.php`, starting with `api_endpoint([...])` — see below (prefix helpers with `_` so they're never web-reachable) |
| Change **security** behaviour | `bootstrap/security.php` (and mirror static rules in `.htaccess` ↔ `bootstrap/static_policy.php`) |
| Shared **layout** | `includes/header.php`, `includes/sidebar.php`, `includes/footer.php` |
| **Theme / styles** | `assets/css/variety-ui.css`, `assets/js/variety-ui.js` |
| **Database** change | update `deployment/schema_pg.sql` (fresh installs) **and** add an idempotent dated migration in `deployment/sql/` (existing databases) |
| Cache something | `Cache::remember(Cache::key('group', $suffix), $ttl, fn () => …)` and `Cache::bump('group')` on writes |
| New styles | Tailwind classes work after `npm --prefix tools/tailwind run build`; shared theme in `assets/css/variety-ui.css` |

### Writing an API endpoint

**Every endpoint goes through the API kernel (`api/_kernel.php`).** The first thing a file does is declare who may call it; the kernel enforces that *before* any of your code runs, so a forgotten role check can't happen. CI fails the build if an endpoint doesn't declare one (`tests/static/api_contract.php`).

```php
<?php
/** Pin a post to the top of your profile. POST { post_id } */
require_once __DIR__ . '/_kernel.php';
require_once __DIR__ . '/_privacy.php';
$api = api_endpoint([
    'access'  => 'member',               // public | member | staff | admin | moderator | ['alumni', 'faculty', 'admin']
    'methods' => ['POST'],               // anything else → 405
    'actions' => ['force' => 'admin'],   // optional: stricter level for ?action=force
]);

$postId = $api->int('post_id', required: true);          // 400 "post_id is required." / "must be a whole number."
privacy_require_post($api->db, $api->uid, $postId);       // 404 unless this person may see it

$own = $api->db->prepare('SELECT 1 FROM posts WHERE post_id = :p AND user_id = :u');   // always prepared statements
$own->execute([':p' => $postId, ':u' => $api->uid]);
if (!$own->fetchColumn() && !$api->isAdmin()) {
    api_fail(403, 'You can only pin your own posts.');       // ownership is still your job
}
// ...
$api->log('POST_PIN', "Pinned post #$postId");               // audit log; never breaks the request
api_ok(['post_id' => $postId], 'Pinned.');
```

| You get | From |
|---|---|
| `$api->db`, `$api->uid`, `$api->role`, `$api->isModerator`, `$api->action` | the kernel, already authenticated (bans, suspensions, shadow bans, device bans checked) |
| `$api->int()`, `->str($key, $max)`, `->bool()`, `->oneOf($key, [...])`, `->has()` | validated input from the query string, form fields or JSON body; bad input → a clear 400/422 |
| `api_ok($data, $message)`, `api_fail($code, $message)` | one response shape everywhere: `{ success, status, message, data }` |
| `api_error_message($e)` | in a `catch`: your own messages pass through, database/PHP errors become "Something went wrong … (ref ERR-3f9a2c1b)" with details only in the server log |
| `api_retired('new_endpoint.php')` | when you replace an endpoint: old URL answers 410 with a pointer instead of a 404 |

The access level is the *floor*. Ownership ("is this **your** post?") and finer rules still belong in your code. The router adds rate limiting, CORS, upload inspection, numeric-id checks and security headers on top.

Staff-level endpoints (`staff`, `admin`, `moderator`, role lists, and stricter `actions`) are **attacked automatically** by `tests/integration/authz.php` on every push — it reads your declaration, so there's no list to remember.

### Writing front-end code

Pages build HTML with template strings. Every value that came from a person goes through the shared helpers in `assets/js/safe.js` (loaded on every page):

```js
el.innerHTML = `
  <a href="${escapeHtml(safeUrl(user.website))}">${escapeHtml(user.name)}</a>   <!-- text + links -->
  <p>${linkifyText(escapeHtml(post.content))}</p>                              <!-- @mentions + URLs -->
  <span>${Number(post.likes_count)}</span>                                    <!-- numbers -->
  <button data-name="${escapeHtml(user.name)}" onclick="openChat(${Number(user.id)}, this.dataset.name)">Chat</button>`;
```

Never put a person's text inside `onclick="fn('${…}')"`. HTML-escaping can't protect a JavaScript string; pass it through a `data-` attribute. `tests/static/frontend_contract.py` flags all three mistakes in CI. If a value is genuinely safe (a pre-built HTML snippet, a value escaped earlier), mark it inside the expression: `${chipsHtml /* safe-html: built from escaped parts above */}` — the reason is the review.

### Security checklist for contributors

- Use **prepared statements** for every query — never concatenate user input into SQL.
- Escape output in HTML with `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` (or `Security::e()`). In JS, use `textContent`, or wrap every user value in `escapeHtml()` (and `safeUrl()` for `href`/`src`) inside template strings. Never put user text inside an `onclick="…('${…}')"` string; use a `data-` attribute instead.
- Anything that shows a person or a post goes through `api/_privacy.php` (`privacy_post_visible_sql()`, `privacy_can_view()`, `privacy_require_post()`), so private accounts and blocks are respected.
- New endpoint? Declare its access with `api_endpoint()` — the contract check and the authorization sweep pick it up automatically.
- Check **authorisation**, not just authentication: "is this user allowed to edit *this* post?"
- Don't echo exception messages to users; use `api_error_message($e)` (CI rejects raw `$e->getMessage()` in responses).
- Store uploads through the asset store / S3 helpers, never under a user-chosen filename.

## 🐛 Reporting bugs & 💡 ideas

Use **Issues → New issue** and pick a template. Include steps, expected vs actual behaviour, browser/device, and screenshots.

## 👥 Maintainers & collaborators

- **Aryan Singh Chandel** ([@shiroonigami23-ui](https://github.com/shiroonigami23-ui)) — creator & maintainer

Want to become a regular collaborator? Land a couple of good PRs and ask in an issue — we'd love the help.

Thank you for making the RJIT network stronger! 🎓
