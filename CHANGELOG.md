# Changelog

All notable changes to the RJIT Alumni Portal are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [Semantic Versioning](https://semver.org/).

## [Unreleased]

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

[Unreleased]: https://github.com/shiroonigami23-ui/Alumni-Portal/compare/v2.0.0...HEAD
[2.0.0]: https://github.com/shiroonigami23-ui/Alumni-Portal/releases/tag/v2.0.0
