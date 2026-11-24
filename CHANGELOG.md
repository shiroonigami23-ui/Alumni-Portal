# Changelog

All notable changes to the RJIT Alumni Portal are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [2.3.1] — 2026-11-24 — "One front door"

Every API endpoint now goes through a shared kernel, so security doesn't depend on each of 121 files remembering the same checks.

### 🏗️ Architecture
- **`api/_kernel.php`**: endpoints start with `api_endpoint(['access' => ...])` — `public`, `member`, `staff`, `admin`, `moderator`, or a role list, plus optional stricter levels per `action`. The kernel authenticates, enforces the level *before* the endpoint runs, parses input (query/form/JSON) with validated getters, and gives one response shape (`api_ok` / `api_fail`).
- All 121 endpoints migrated; retired ones use `api_retired()`.
- Internal errors are never shown to people: `api_error_message($e)` keeps our own messages and replaces database/PHP errors with a calm message and a reference code (`ERR-3f9a2c1b`) that's explained only in the server log. 42 endpoints used to echo raw exception text.
- `Auth::authenticate()` (non-exiting, memoised per request) underneath the old `validateRequest()`.

### 🔐 Security
- `get_event_attendees.php` was readable **without signing in** — names, photos and branches of everyone going to any event. Now members only, and private accounts you don't follow and blocked people are counted but not named.
- Sign-in tokens weren't found on nginx/PHP-FPM servers (the header fallback looked in the wrong place).

### 🧪 Tests (CI)
- **API contract** (`tests/static/api_contract.php`): fails the build if an endpoint has no access declaration, reads input or touches the database before declaring, or sends raw exception text. Writes `tests/results/api-manifest.json`, the access map of the whole API.
- **Front-end contract** (`tests/static/frontend_contract.py`): fails on unescaped user values in page templates, links without `safeUrl()`, and user text inside `onclick` strings.
- **Authorization sweep** now reads the declarations (no hand list to forget) and adds a faculty attacker: 64 → **185** attempts. Demo data gains a fictional faculty account.

## [2.3.0] — 2026-11-24 — "Private means private"

A full audit of profiles, privacy, uploads, permissions and injection, driven by new automated attack suites that now run in CI.

### 🔐 Security fixes
- **Any signed-in user could ban any account (including the admin)** through the unused `api/admin_moderation.php`, which had no role check. Retired (410).
- **Stored XSS** on the feed, dashboard, profile, messages, mentorship, the public landing page and the notification bell: post text, names, comments, file names, job/event fields and notifications were inserted as raw HTML. All user text is now escaped (`assets/js/safe.js`), links are restricted to safe schemes (also against `java\tscript:`), and a browser sweep plants payloads in every field to prove it.
- **Reflected XSS** via `profile.php?id=…` (the value was echoed into a script).
- JS-string injection in `onclick` handlers (names with apostrophes on Discovery, message editing, attachment links).
- `upload_verification.php` switched **any active account** to `pending_verification` (locking them out) and wrote files to a world-writable folder.
- Students could file entries in a hidden legacy announcements table; endpoint retired.
- GIF URLs were stored unchecked (`javascript:` and tracking pixels); now https from known GIF hosts only.
- The feed returned every author's email to everyone; message search matched and returned everyone's email.
- Roll numbers were visible to all members; now only the owner, faculty and admins see them.

### 🔒 Privacy (it didn't work before)
- Private accounts' posts appeared in everyone's feed, on their timeline, in post search and in replies; even approved followers got "This profile is private". Now one rule set (`api/_privacy.php`) covers feeds, search, profiles, comments, likes, reposts, shares, views and polls.
- **Follow requests** for private accounts, with approve/decline in Settings → Privacy; going public auto-approves pending requests.
- Private profiles show a **locked card** (name, photo, role, branch, counts) with *Request to follow*, and are findable by name but never by company/city/bio.
- **Show email / show phone** toggles (the columns existed but nothing could set them).
- Sending `"false"` as text made a profile private.

### ✨ Added
- **@handles** (Twitter-style, separate from display names): unique regardless of case (database index), auto-generated for every account by a DB trigger (from the name, never the email), live availability check with suggestions, reserved and staff-lookalike names refused, 14-day change cool-down, old handles redirect and stay reserved for 30 days, `/@handle` and `/u/handle` links, `@mentions` and URLs become links in posts and comments, search by handle.
- **Blocking** (it returned "Blocking is disabled"): block/unblock/list; mutual and total (profiles, feeds, search, chats, calls, follows); staff can't be blocked.
- **Profile strength** meter, **profile views** (last 30 days), pronouns, *open to mentoring / referrals / opportunities* badges.
- **Download my data**: one JSON file of everything you created (the button said "coming soon").
- Students can edit their photo, cover, headline, skills, links and city; only identity fields stay locked.

### 🐛 Fixed
- **Message search failed with a 500 for every non-admin**, so nobody could start a new chat.
- **Feed post search never showed results** (wrong response shape); it now searches post text, titles and authors with all visibility rules.
- "Following" feed also showed posts from people who follow *you*.
- Profile saves crashed with 500 on long values; now 422 with per-field messages, `linkedin.com/in/x` gets `https://`, HTML is stripped from names.
- Non-numeric IDs crashed 15 endpoints with database errors; a central check returns a clear 400.
- Creating a referral offer without an experience level returned 500.
- Liking, reposting or sharing a deleted post returned a 500 (foreign key), now 404.
- Emails differing only by case could create duplicate accounts; sign-in with capital letters failed. Emails are now normalised by trigger with a unique `lower(email)` index.
- Oversized uploads said "No avatar file received" / "Post content is required"; now "too large (max N MB)". Animated GIF avatars allowed.
- Broken placeholder images (`via.placeholder.com`).

### 📷 Uploads
- Camera/GPS metadata stripped losslessly from JPEG, PNG and WebP (JPEG orientation preserved).
- "Image bombs" (>12,000 px a side or >50 MP) refused.
- Stored-file display names are cleaned (no paths or control characters); Unicode names download correctly (RFC 5987).

### 🧪 Tests (all run in CI)
- API suite 185 → **230** checks; browser suite 68 → **78** (handle checker, `/@handle`, locked card, request → approve).
- New: **authorization sweep** (64), **SQL-injection fuzz** (2,744 requests, 98 endpoints, 0 injections), **stored-XSS sweep** (37 pages, including raw legacy rows), and a new **Chrome CI job**.

### 🗑️ Retired (410 with the replacement)
`add_comment`, `add_reply`, `manage-post`, `join_event`, `search_alumni`, `upload`, `admin_moderation`, `create_announcement`, `approve_announcement`.

## [2.2.0] — 2026-11-24 — "Everything works"

Every feature was exercised end-to-end (185 API checks, 68 browser checks); everything that was broken or never wired up now works.

### ✨ Added
- **Live sessions** (`/live.php`) — alumni/faculty go live with a fresh Jitsi room or a YouTube Live embed; viewers watch in-page; site-wide LIVE banner; sessions auto-close after 8 h.
- **Library** (`/library.php`) — success stories with an admin review queue, and shared resources with categories, search, open counts and delete.
- **Polls in the feed** — create from the composer (2–6 options, optional end date), vote inline, live results; `api/_polls.php` batches polls into the feed in one query.
- **Scheduled posts in the feed** — schedule text/GIF/poll up to 90 days ahead, list, reschedule, cancel (`api/schedule_post.php`, `api/scheduled_posts.php`).
- **Feed ranking**: ✨ *For you* (hype × affinity — follows, branch, company, batch) and 🔥 *Trending* (time-decayed engagement); *For you* is the default.
- **Scheduler**: `cron/run.php` (publish every minute, promote daily, purge daily, archive weekly opt-in), `scripts/run_scheduler.bat`, Docker `scheduler` service; scheduled posts also publish lazily from the feed when no cron is set up.

### 🐛 Fixed
- **Scheduled posts never published** (no cron configured, and the publisher used the wrong column); scheduling also let **students bypass the no-post rule** and skipped alumni moderation.
- **Archive cron would have deleted every post older than 7 days** (with all comments/likes). Now opt-in, 365-day default, skips pinned/announcements/active threads, dry-run by default.
- Purge cron ignored database-stored content.
- **Live streaming never worked** — “start” updated a row that never existed, the player pointed at `localhost`, the LIVE badge checked a field the API never returned, and `get_active_streams` exposed **secret stream keys**.
- **Polls**: no-expiry polls couldn't be voted on; anyone could attach a poll to any post; votes weren't tied to their poll; closed polls weren't enforced; no UI existed.
- **Mentor applications could never be approved on PostgreSQL** (ambiguous parameter type) — so alumni mentors, mentor groups, group chat and group calls were unreachable.
- Success stories were listed before approval; resources accepted `javascript:` links; story text was written to local disk.
- Feed showed posts from blocked users; database sessions now always use UTC (servers in local time shifted every timestamp).
- Poll placement, `comment_reactions` / `scheduled_posts` column names (auto-renamed on existing databases).

### 🗑️ Removed
- Unused models `Post`, `Profile`, `Mentorship`, `Resource`, `SuccessStory` (logic lives in the APIs).

## [2.1.0] — 2026-11-24 — "Supreme hub"

### ✨ Added
- **Alumni Atlas** (`/atlas.php`, `api/alumni_atlas.php`) — interactive map of alumni by city, ranked cities with top companies, people per city, companies / batches / branches views, filters. Respects private profiles.
- **Referral board** (`/referrals.php`, `api/referrals.php`) — offers with slots and expiry, requests with pitch + résumé link, accept / referred / decline with notifications, withdraw, admin removal, abuse limits.
- **Admin Insights & Control Centre** (`/admin/insights.php`, `api/admin_insights.php`) — 12-week sign-up chart, KPIs, 7-day engagement, roles, security events (from the new `storage/logs/security.log`), audit log, system health, top companies, auto-refresh.
- **Site-wide banner** (admin-set, shown on every page, dismissible) and **broadcast notifications** by audience.
- **CSV export** of members (`api/admin_export_users.php`, formula-injection safe, audited).
- **Ctrl+K command palette** with live global search (`api/master_search.php` rebuilt), toasts, quick-search chip.
- **Job management** — `api/manage_job.php` (edit, close/reopen, delete), poster tools on the jobs page, “Applied ✓” state, working search and filters.
- **Alumni invite codes** now work: an admin-issued code verifies an alumnus instantly at sign-up and is marked used.
- **Motion layer** (`assets/js/motion.js`) — count-ups, staggered entrances, growing charts, ripples, animated gradients, confetti; disabled for reduced-motion users.
- **One-command database install** — `scripts/install_db.php` (+ `--demo`, `--admin-password`) and a complete PostgreSQL schema `deployment/schema_pg.sql` (59 tables).
- **Cache layer** `helpers/Cache.php` (Redis → APCu → file) with group invalidation and `schema_once`.
- **Test suites with committed results**: `tests/integration/run.php` (120 API checks), `tests/ui/browser_smoke.py` (54 real-browser checks), `tests/integration/security_limits.php` (lockout). CI now runs the PostgreSQL suite.
- `/favicon.ico`, `/map`, `/batches`, `/refer`, `/insights` routes.

### ⚡ Performance
- Schema `ensure_*` checks (~40 queries) now run once per deploy instead of every request — `me.php` 29 ms → 1.7 ms, feed 38 ms → 5 ms, most APIs 2–3 ms.
- One DB connection per request, persistent connections, single-round-trip parameterised queries on PostgreSQL.
- Pre-built Tailwind CSS (50 KB) replaces the in-browser Tailwind compiler on every page; Lucide pinned instead of `@latest`.
- Docker image: OPcache + JIT, APCu, phpredis, realpath cache; docker-compose wires the app to Redis.

### 🐛 Fixed
- **New students were promoted to alumni on their first login** when their email didn't match the roll-number pattern. Promotion now uses the email pattern or profile graduation year and never guesses.
- **Stored XSS on the jobs page** (titles, companies, descriptions and links rendered as HTML).
- **Poster emails leaked** publicly by `get_jobs.php`; the public landing feed fell back to emails and returned restricted (alumni/student-only) posts with no limit.
- Jobs: “Open application link” never showed (API/UI field mismatch); search and filters did nothing; `post_job` returned no ID and had no validation; closed jobs still accepted applications; posters could apply to their own jobs.
- `master_search.php` queried a non-existent `campus_events` table.
- Messages could fail with “column admin_user_id does not exist” depending on which feature created `mentorship_groups` first.
- Admin invite-token list crashed (schema mismatch); invite codes were never consumed by registration.
- Landing page crashed while formatting dates (infinite recursion) so fallback posts never rendered.
- Header showed the user's email instead of their name; gradient badges lost their colour in dark mode.
- PHP warning in `public_feed.php`.

### 🔒 Security
- Résumés moved from guessable `storage/resumes/` paths to the private database asset store; `storage/resumes` is no longer served.
- Security events (rate limits, blocked uploads, failed logins, forbidden requests) are logged for admins.
- CSP no longer allows the Tailwind CDN.

### 🗄️ Database
- PostgreSQL is the single supported engine. `deployment/db.sql` (MySQL) is marked legacy/partial.

## [2.0.0] — 2026-11-24 — "Organised & Hardened"

### ✨ Added
- **Front controller `router.php`** and route table **`bootstrap/routes.php`** — every page and API request now flows through one place.
- **Clean URLs** for every page (`/feed`, `/login`, `/terms` …) alongside the original `.php` URLs.
- **Friendly redirects** for old and guessable links: `/edit-profile.php` → `settings.php#profile`, `/signup`, `/join`, `/privacy`, `/directory`, `/careers`, `/inbox`, `/admin`, `/faq` and more.
- **Friendly error page** (`pages/errors/error.php`) for 404/405/413/415/429 with quick links, dark-mode aware.
- **`/healthz`** endpoint (no DB, no session) used by Docker, ECS and the ALB.
- **Security layer `bootstrap/security.php`** that runs before every script:
  - Content-Security-Policy, Permissions-Policy, COOP, HSTS (on HTTPS) and other hardening headers
  - CORS allowlist (same-origin + `ALLOWED_ORIGINS`) replacing `*` on ~100 endpoints
  - Sliding-window **rate limits** for all APIs and strict limits for login/registration/password reset
  - **Per-account login lockout** after repeated wrong passwords, with a clear "try again in N minutes" message
  - Trusted-proxy-aware **client IP** (spoofed `X-Forwarded-For` no longer bypasses bans/limits)
  - Platform-wide **upload inspection**: MIME sniffing, dangerous/double extension blocking, image decoding, embedded-code (polyglot) detection
  - Production **error scrubbing** so SQL errors / stack traces / paths never reach clients
  - Hardened session cookies (`HttpOnly`, `SameSite=Lax`, `Secure` on HTTPS, strict mode)
- `bootstrap/static_policy.php` + root `.htaccess` + `storage/.htaccess` so internals and private storage are never served and uploads can never execute.
- **Route & security smoke test** `tests/smoke/routes.sh` and a **CI workflow** (lint + smoke test).
- Accessibility & polish: visible keyboard focus rings, **skip-to-content** link, `prefers-reduced-motion` support, consistent radii, smoother interactions.
- Docs: new README with animated banner, page tour GIF, theme GIF, router demo GIF and screenshots; `LICENSE` (MIT), `SECURITY.md`, `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, issue & PR templates, this changelog; `docs/DEV_NOTES.md` keeps the full deploy log from the old README.
- New optional environment variables documented in `.env.example`.

### 🔄 Changed
- **Repository reorganised** — the root now holds only entry points:
  - app pages → `pages/`, auth pages → `pages/auth/`, legal pages → `pages/legal/`
  - deployment guides → `docs/deployment/`
- All URLs stay the same in the browser, so existing links, bookmarks, relative assets and API calls keep working.
- Docker/ECS/ALB health checks now use `/healthz` instead of the demo `live.php` page.
- Docker image ships hardened `php.ini` and Apache settings (`expose_php=Off`, `ServerTokens Prod`, `TraceEnable Off`, no directory listings).
- Service worker cache bumped to `v2` and now only caches successful same-origin responses.
- Personal **data export** is streamed to the authenticated owner (`api/export_data.php?download=1`) and deleted right after, instead of being left at a public URL.
- `api/asset.php` never serves uploaded HTML/SVG/XML as active content and sandboxes every served file.
- Activity log moved from the web root (`storage/log.txt`) to `storage/logs/activity.log`.

### 🐛 Fixed
- Login, register and other auth cards were unreadable in the default dark theme (light text on a light card).
- "Alumni" pill and other `bg-blue-50` surfaces had poor contrast in dark mode.
- Offline page "Retry" link pointed at a hard-coded `/alumni_portal/feed.php` path.
- `edit-profile.php` was a stray redirect file at the root — now a route alias.

### 🔒 Security
- Removed debug endpoint `api/test.php` (echoed request headers).
- Login had no brute-force protection (`RateLimiter` existed but was unused) — now rate-limited and locked per account.
- Removed committed runtime data from `storage/` (a personal-data export archive, private messages, comments, posts, a résumé and an IP activity log). These paths were already in `.gitignore`. **They still exist in git history — consider purging history if that data was real.**
- Identity verification documents, exports, logs and caches are no longer web-accessible.

### 🗑️ Removed
- `api/test.php`, root `edit-profile.php`, committed `storage/` runtime files.

---

## [1.4.0] — 2026-04-12
### Added
- Moderator workflow, alumni post review queue, post edit revisions and moderation.
- **MySQL support** alongside PostgreSQL; DB bootstrap and XAMPP 5433 fallback.
- Beginner MySQL/XAMPP and AWS setup guide.
### Fixed
- Landing stats and theme; alumni/student profile rules; admin header avatar resolution; mentorship UI start-up.

## [1.3.1] — 2026-03-25
- Alumni data update.

## [1.3.0] — 2026-03-15
### Added
- Mentor spaces (shared live audio/video rooms) and actionable 1:1 call notifications.
- In-app portal dialogs replacing browser `alert/confirm/prompt`.
### Changed
- All portal timestamps standardised to IST.
- Message payloads moved to the DB-backed content store (fixes `[Content Missing]` on AWS).
### Fixed
- Admin moderation and mentor-group rules; dashboard JSON handling; profile settings UI.

## [1.2.0] — 2026-03-14
### Added
- Password-reset email flow (`forgot-password.php`, `reset.php`) with 30-minute tokens.
- Admin moderation controls, delete controls and mentor-group disband flow.
- Profile repost activity; GIF picker; feed drafts; upload progress; infinite scroll.
- Redesigned mobile navigation (bottom bar + "More" sheet).
### Changed
- Post/comment attachments and content stored in the database (survive ECS task changes).
- Students restricted from top-level posts; mentorship rules tightened.
### Fixed
- Many feed, auth-script, discovery, avatar and dashboard crashes.

## [1.1.0] — 2026-03-09 → 2026-03-13
### Added
- Admin modules, feed interactions, PWA, alumni import tooling, lifecycle automation, abuse workflow.
- Subject-choice student pre-registration importer.
### Fixed
- CSRF flow for bearer-authenticated APIs; API URL resolution across deployments; BOM before `session_start`.
### Changed
- Terraform adjusted for ECS secrets and free-tier RDS.

## [1.0.0] — 2026-02-17 → 2026-02-20
### Added
- Initial AWS-deployable alumni portal with Terraform, Docker and CI/CD.
- Premium UI theme; PostgreSQL/AWS setup docs; local verification scripts.

[Unreleased]: https://github.com/shiroonigami23-ui/Alumni-Portal/compare/v2.3.1...HEAD
[2.3.1]: https://github.com/shiroonigami23-ui/Alumni-Portal/releases/tag/v2.3.1
[2.3.0]: https://github.com/shiroonigami23-ui/Alumni-Portal/releases/tag/v2.3.0
[2.2.0]: https://github.com/shiroonigami23-ui/Alumni-Portal/releases/tag/v2.2.0
[2.1.0]: https://github.com/shiroonigami23-ui/Alumni-Portal/releases/tag/v2.1.0
[2.0.0]: https://github.com/shiroonigami23-ui/Alumni-Portal/releases/tag/v2.0.0
