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
# 1. Lint every PHP file
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l

# 2. Route + security smoke test (no DB required)
bash tests/smoke/routes.sh
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
| Add an **API endpoint** | `api/your_endpoint.php` (prefix helpers with `_` so they're never web-reachable) |
| Change **security** behaviour | `bootstrap/security.php` (and mirror static rules in `.htaccess` ↔ `bootstrap/static_policy.php`) |
| Shared **layout** | `includes/header.php`, `includes/sidebar.php`, `includes/footer.php` |
| **Theme / styles** | `assets/css/variety-ui.css`, `assets/js/variety-ui.js` |
| **Database** change | add a dated migration in `deployment/sql/` (PostgreSQL **and** MySQL variants if needed) |

### Writing an API endpoint

```php
<?php
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$db = (new Database())->getConnection();
$userId = (new Auth($db))->validateRequest();   // 401/403 handled for you

$stmt = $db->prepare('SELECT ... WHERE user_id = :uid'); // always prepared statements
$stmt->execute(['uid' => $userId]);
echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
```

You get rate limiting, CORS rules, upload inspection and security headers automatically from the router — no need to set `Access-Control-Allow-Origin` yourself.

### Security checklist for contributors

- Use **prepared statements** for every query — never concatenate user input into SQL.
- Escape output in HTML with `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` (or `Security::e()`), and use `textContent` instead of `innerHTML` in JS for user content.
- Check **authorisation**, not just authentication: "is this user allowed to edit *this* post?"
- Don't echo exception messages to users; log them with `error_log()`.
- Store uploads through the asset store / S3 helpers, never under a user-chosen filename.

## 🐛 Reporting bugs & 💡 ideas

Use **Issues → New issue** and pick a template. Include steps, expected vs actual behaviour, browser/device, and screenshots.

## 👥 Maintainers & collaborators

- **Aryan Singh Chandel** ([@shiroonigami23-ui](https://github.com/shiroonigami23-ui)) — creator & maintainer

Want to become a regular collaborator? Land a couple of good PRs and ask in an issue — we'd love the help.

Thank you for making the RJIT network stronger! 🎓
