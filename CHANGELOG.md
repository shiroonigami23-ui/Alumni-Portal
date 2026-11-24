# Changelog

All notable changes to the RJIT Alumni Portal are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [Semantic Versioning](https://semver.org/).

## [Unreleased]

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

[Unreleased]: https://github.com/shiroonigami23-ui/Alumni-Portal/compare/v2.2.0...HEAD
[2.2.0]: https://github.com/shiroonigami23-ui/Alumni-Portal/releases/tag/v2.2.0
[2.1.0]: https://github.com/shiroonigami23-ui/Alumni-Portal/releases/tag/v2.1.0
[2.0.0]: https://github.com/shiroonigami23-ui/Alumni-Portal/releases/tag/v2.0.0
