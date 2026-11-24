<div align="center">

<img src="assets/readme/banner.gif" alt="RJIT Alumni Portal — Connect, Mentor, Hire, Grow" width="100%">

<h3>The campus hub where RJIT students, alumni and faculty meet, mentor, hire and grow together.</h3>

[![CI](https://github.com/shiroonigami23-ui/Alumni-Portal/actions/workflows/ci.yml/badge.svg)](https://github.com/shiroonigami23-ui/Alumni-Portal/actions/workflows/ci.yml)
![Tests](https://img.shields.io/badge/tests-185%20API%20%7C%2068%20UI%20%7C%2063%20routes-22c55e)
[![License: MIT](https://img.shields.io/badge/License-MIT-22c55e.svg)](LICENSE)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-13%2B-4169E1?logo=postgresql&logoColor=white)
![Redis](https://img.shields.io/badge/Redis-cache-DC382D?logo=redis&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-ready-2496ED?logo=docker&logoColor=white)
![AWS](https://img.shields.io/badge/AWS-ECS%20%7C%20RDS%20%7C%20S3-FF9900?logo=amazonaws&logoColor=white)
![PWA](https://img.shields.io/badge/PWA-installable-5A0FC8?logo=pwa&logoColor=white)
[![Security policy](https://img.shields.io/badge/security-policy-ef4444?logo=shield&logoColor=white)](SECURITY.md)
[![PRs welcome](https://img.shields.io/badge/PRs-welcome-0ea5e9.svg)](CONTRIBUTING.md)

[**Live demo**](http://alumni-portal-alb-616743364.us-east-1.elb.amazonaws.com/) ·
[Features](#-features) ·
[What's new](#-whats-new-in-21) ·
[Screenshots](#-screenshots) ·
[Quick start](#-quick-start) ·
[Routing](#-routing--urls) ·
[Security](#-security) ·
[Deploy](#-deployment) ·
[Contributing](CONTRIBUTING.md)

</div>

---

<div align="center">
<img src="assets/readme/tour.gif" alt="Animated tour of the portal: landing, login, register, help centre, friendly 404 and privacy policy" width="92%">
<br><sub>A quick tour — landing page, sign-in, registration, help centre, the friendly 404 and legal pages.</sub>
</div>

## 🆕 What's new in 2.1 & 2.2

| | |
|:---:|:---:|
| <img src="assets/readme/admin-insights.gif" alt="Admin Insights and Control Centre animating in: counters count up, sign-up chart bars grow" width="100%"> | <img src="assets/readme/command-palette.gif" alt="Pressing Ctrl+K opens a quick-search palette; typing 'refer' and pressing Enter opens the referral board" width="100%"> |
| **Admin Insights & Control Centre** — growth chart, live KPIs, security events, audit log, health, CSV export, site banner, broadcasts | **Ctrl + K quick search** — jump to any page, person, job, event or referral from anywhere |
| <img src="assets/readme/alumni-atlas.gif" alt="Alumni Atlas: map of cities, ranked city list, people in Bengaluru, companies and batches tabs" width="100%"> | <img src="assets/readme/referral-board.gif" alt="Referral board with an animated gradient header, counters and referral offer cards" width="100%"> |
| **Alumni Atlas** — where RJITians live and work, by city, company, batch and branch | **Referral board** — alumni offer referrals, students request them, everyone gets notified |

- ⚡ **3–17× faster APIs.** The schema is checked once per deploy instead of on every request, plus a Redis/APCu/file cache, single-round-trip queries and pre-built CSS ([numbers](#-performance)).
- 🗄️ **One-command PostgreSQL install.** `php scripts/install_db.php` builds all 59 tables. A complete `schema_pg.sql` now exists, so fresh installs work.
- 🧪 **Real test suites with committed results:** 120 end-to-end API checks, 54 real-browser page checks and 63 route checks ([results](tests/results/)).
- 🛠️ **Fixed broken features:** new students wrongly promoted to alumni, a jobs XSS, poster emails leaking, dead job filters and links, invite codes that never worked, a landing-page crash, and more ([CHANGELOG](CHANGELOG.md)).
- 📡 **Live sessions** (Jitsi rooms or YouTube Live), **📚 Library** (success stories + resources), **📊 polls**, **🗓️ scheduled posts**, **🔥 Trending / ✨ For-you feeds** — all new or rebuilt in 2.2.
- ⏱️ **Real scheduler**: `cron/run.php` + a Docker `scheduler` service; scheduled posts also publish without cron.
- ✨ **Motion:** count-ups, staggered cards, growing charts, ripples, animated gradients and confetti — all off for "reduce motion" users.

## 📚 Table of contents

- [What is this?](#-what-is-this)
- [Features](#-features)
- [Screenshots](#-screenshots)
- [Tech stack](#-tech-stack)
- [Project structure](#-project-structure)
- [Quick start](#-quick-start)
  - [Docker (fastest)](#option-a--docker-fastest)
  - [XAMPP + PostgreSQL](#option-b--xampp--postgresql-windows)
  - [XAMPP + MySQL (legacy)](#option-c--xampp--mysql-legacy-not-recommended)
  - [PHP built-in server](#option-d--php-built-in-server-any-os)
- [Database](#-database)
- [Configuration](#-configuration)
- [Routing & URLs](#-routing--urls)
- [Security](#-security)
- [Performance](#-performance)
- [Testing](#-testing)
- [Deployment](#-deployment)
- [API overview](#-api-overview)
- [Roadmap](#-roadmap)
- [Contributing, conduct & license](#-contributing-conduct--license)

---

## 🎓 What is this?

**RJIT Alumni Portal** is a full-stack community platform for **Rustamji Institute of Technology (RJIT), Gwalior**. It brings students, alumni, faculty and admins into one place to:

- 🤝 **Connect** — a searchable alumni directory with batch, branch, company and city filters
- 🧭 **Mentor** — structured mentor ↔ mentee matching with private mentor-group chats and live audio/video spaces
- 💼 **Hire** — a job board where alumni post roles and students apply with their résumé
- 📣 **Share** — a social feed with posts, polls, GIFs, attachments, reposts and reactions
- 📅 **Meet** — events with RSVPs, attendee lists and live streams
- 🛡️ **Stay safe** — reporting, moderation queues, shadow-bans, device bans and an admin console

It runs on plain **PHP 8 + PostgreSQL** (with an optional Redis cache), works on **XAMPP** for college servers, ships as a **Docker** image, and deploys to **AWS (ECS + RDS + S3)** with Terraform.

---

## ✨ Features

<table>
<tr>
<td width="50%" valign="top">

### 🗺️ Alumni Atlas *(new)*
- Interactive map of where alumni live, sized by count
- Ranked cities with top companies; click a city to see who's there
- Companies, batches ("Batch of 2019 · 42 alumni") and branches views
- Filters by company, branch and batch range; respects private profiles

### 🤝 Referral board *(new)*
- Alumni/faculty publish "I can refer you at …" offers with slots and expiry
- Students and alumni send a short pitch + résumé link
- Owners accept, mark **referred 🎉** or decline — requester is notified
- Duplicate, spam and self-requests blocked; admins can remove offers

### 👥 Community & profiles
- Role-aware accounts: **student, alumni, faculty, admin, moderator**
- Rich profiles: avatar, cover, bio, company, role, skills, socials
- Profile privacy controls and blocking
- Activity timeline with posts **and** reposts
- Automatic **student → alumni** promotion at graduation (cron)
- Verified-alumni document flow with admin approval
- GDPR-style **data export** (streamed privately to the owner)

### 📰 Feed
- **✨ For you** (personalised: people you follow, your branch, company and batch) and **🔥 Trending** (time-decayed engagement) sorts, plus Newest / Popular / Following
- **📊 Polls** in the composer — 2–6 options, optional end date, one vote each, live results
- **🗓️ Schedule posts** (text, GIF, poll) up to 90 days ahead; reschedule or cancel from the feed
- Posts with images, files, **GIF search** (Wikimedia Commons)
- Reactions, threaded comments & replies, reposts, shares
- Pinned posts, scheduled posts, drafts, editing
- Infinite scroll, upload progress, view counts
- Report content → moderation queue

</td>
<td width="50%" valign="top">

### 🧭 Mentorship
- Mentor applications (alumni) with faculty/admin approval
- One active mentor per person, enforced server-side
- Auto-created **mentor groups** with chat, ban/kick/unban
- Shared live **audio/video mentor spaces** (Jitsi)

### 💬 Messaging
- Direct messages and mentor-group conversations
- Edit / delete messages and unread counts
- 1:1 **audio/video calls** with in-app call notifications

### 📡 Live & 📚 Library *(new)*
- Alumni/faculty go live in one click — a fresh **Jitsi** video room or a **YouTube Live** embed; a site-wide “LIVE” banner appears for everyone
- **Success stories** (admin-approved) and shared **resources** (notes, sheets, links) with categories and search

### 💼 Careers & events
- Job board with search and filters, “Applied ✓” state, poster tools (applicants, edit, close/reopen, delete) *(rebuilt)*
- Résumés stored privately in the database behind unguessable links
- Events with RSVP, attendee lists, approval workflow
- Live-stream sessions and success stories

### 🛠️ Admin & moderation
- **Insights & Control Centre** *(new)*: 12-week growth chart, KPIs, 7-day engagement, security-event feed, audit log, system health
- **Site-wide banner** and **broadcast notifications** to everyone / students / alumni / faculty *(new)*
- **CSV export** of members (formula-injection safe) *(new)*
- **Alumni invite codes** that verify alumni instantly at sign-up *(now working)*
- Admin dashboard with live stats
- User management: ban, suspend, shadow-ban, device ban, mute DMs
- Reports, announcements approval, alumni token generation
- Bulk user actions and activity logs

</td>
</tr>
</table>

**Everywhere:** **Ctrl+K quick search** · toasts · motion with reduced-motion support · installable **PWA** with offline page and push notifications · **dark / light / ocean** themes · mobile bottom-nav · IST-consistent timestamps · in-app dialogs instead of browser pop-ups · accessible focus rings, skip-link and reduced-motion support.

---

## 🖼️ Screenshots

<div align="center">

| Landing (dark) | Landing (light) |
|:---:|:---:|
| <img src="assets/readme/landing.jpg" alt="Landing page, dark theme" width="100%"> | <img src="assets/readme/landing_light.jpg" alt="Landing page, light theme" width="100%"> |
| **Sign in** | **Register** |
| <img src="assets/readme/login.jpg" alt="Login page" width="100%"> | <img src="assets/readme/register.jpg" alt="Registration page" width="100%"> |
| **Referral board** | **Alumni Atlas** |
| <img src="assets/readme/referrals.jpg" alt="Referral board" width="100%"> | <img src="assets/readme/atlas.jpg" alt="Alumni Atlas" width="100%"> |
| **Live sessions** | **Library — stories & resources** |
| <img src="assets/readme/live.jpg" alt="Live sessions page with an alumni AMA in progress" width="100%"> | <img src="assets/readme/library.jpg" alt="Library page with success stories" width="100%"> |
| **Jobs (poster view)** | **Admin Insights** |
| <img src="assets/readme/jobs.jpg" alt="Jobs page with poster tools" width="100%"> | <img src="assets/readme/insights.jpg" alt="Admin Insights and Control Centre" width="100%"> |
| **Help centre** | **Friendly 404 with quick links** |
| <img src="assets/readme/help.jpg" alt="Help centre" width="100%"> | <img src="assets/readme/notfound.jpg" alt="Friendly 404 page" width="100%"> |

<img src="assets/readme/poll.png" alt="A poll inside a feed post showing results after voting" width="62%">
<br><sub>Polls live inside posts — vote once, see results instantly.</sub>
<br><br>
<img src="assets/readme/theme-switch.gif" alt="Switching between dark and light themes on the sign-in page" width="70%">
<br><sub>Theme switching — every page follows the active theme (dark is the default).</sub>
<br><br>
<img src="assets/readme/mobile.png" alt="Portal on a phone: landing and sign-in" width="460">
<br><sub>Mobile-first: bottom navigation, compact layouts and an installable app.</sub>

</div>

---

## 🧰 Tech stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2+, PDO, Composer (AWS SDK) |
| Database | **PostgreSQL 13+** (supported engine) — `deployment/schema_pg.sql` + runtime feature tables |
| Cache | Redis → APCu → file (auto-selected, `helpers/Cache.php`) |
| Frontend | Server-rendered PHP pages, pre-built Tailwind CSS, Lucide icons, Leaflet maps, vanilla JS |
| Realtime-ish | Polling APIs, Web Push, Jitsi rooms for calls |
| Storage | DB-backed asset & content store; S3 in production |
| Infra | Docker, Apache (`mod_rewrite`), Terraform → AWS VPC, ALB, ECS Fargate, RDS, S3, CloudWatch, SES |
| CI | GitHub Actions — PHP lint (8.2/8.3), route smoke test, PostgreSQL end-to-end tests, lockout test, repo hygiene, ECS deploy |

---

## 🗂️ Project structure

The root holds only entry points. Pages, APIs and infrastructure each live in their own folder.

```text
Alumni-Portal/
├── index.php               # Landing page
├── router.php              # ★ Front controller — every page & API request goes through here
├── .htaccess               # Apache rules: send requests to the router, hide internals
├── sw.js · manifest.webmanifest · offline.html   # PWA (must stay at the root for scope)
│
├── pages/                  # All user-facing pages (served at their old URLs, e.g. /feed.php)
│   ├── dashboard.php  feed.php  profile.php  settings.php  messages.php
│   ├── discovery.php  mentorship.php  jobs.php  events.php  moderation.php
│   ├── help.php  live.php
│   ├── atlas.php  referrals.php  live.php  library.php   # 2.1–2.2
│   ├── auth/               # login, register, forgot-password, reset
│   ├── legal/              # terms, policy (privacy), conduct
│   └── errors/error.php    # Friendly 404 / 429 / 415 … page
│
├── admin/                  # Admin console (insights.php = control centre)
├── api/                    # JSON endpoints (files starting with _ are internal helpers)
├── bootstrap/
│   ├── routes.php          # ★ Route table: pages, aliases/redirects, executable dirs
│   ├── security.php        # ★ Security layer (headers, CSP, CORS, rate limits, upload checks)
│   └── static_policy.php   # Which static files may be served
├── config/                 # Database + AWS config
├── middleware/             # Auth (bearer sessions), CSRF/security helpers
├── models/  helpers/       # Domain models and services (Cache, email, S3, logging…)
├── includes/               # Shared header, sidebar, footer, auth-check.js
├── assets/                 # CSS (tailwind.css, variety-ui.css), JS (variety-ui, motion), data, media
├── cron/                   # run.php = scheduler (publish posts, promote, purge, opt-in archive)
├── storage/                # Runtime uploads (never committed, never executable)
├── fallback_content/       # Demo content shown when the DB is empty/offline
├── deployment/             # schema_pg.sql, migrations, Apache vhost, deploy scripts
├── terraform/              # AWS infrastructure as code
├── scripts/                # install_db.php (one-command DB setup), demo data, importers
├── tools/tailwind/         # Builds assets/css/tailwind.css
├── tests/                  # smoke/ (routes), integration/ (API, PostgreSQL), ui/ (browser), results/
└── docs/                   # Deployment guides, technical write-up, dev notes
    └── deployment/         # AWS_DEPLOYMENT, QUICKSTART_AWS, POSTGRES_SETUP, DEPLOYMENT
```

---

## 🚀 Quick start

### Option A — Docker (fastest)

```bash
git clone https://github.com/shiroonigami23-ui/Alumni-Portal.git
cd Alumni-Portal
cp .env.example .env
docker compose up --build -d
# build the database once (add --demo for sample alumni, posts, jobs and referrals):
docker compose exec app php scripts/install_db.php --demo --admin-password='Choose@Strong1'
```

Open **http://localhost/** and sign in as `admin@rjit.ac.in` with the password you chose. Health check: **http://localhost/healthz**.

### Option B — XAMPP + PostgreSQL (Windows)

```bat
scripts\start_local_xampp.bat
```

This starts XAMPP PostgreSQL on `127.0.0.1:5433` and Apache. Then build the database once:

```bat
set DB_PORT=5433
C:\xampp\php\php.exe scripts\install_db.php --demo --admin-password=Choose@Strong1
```

Open `http://127.0.0.1:8088/` (or `http://localhost/<folder-name>/`).

> Make sure Apache has `mod_rewrite` enabled and `AllowOverride All` for the project folder (XAMPP's default for `htdocs`). The router works both at the domain root and inside a sub-folder.

### Option C — XAMPP + MySQL *(legacy, not recommended)*

The portal is PostgreSQL-native; `deployment/db.sql` is a **partial** legacy MySQL schema kept for reference and many modules (messages, jobs, events, mentorship…) will not work on it. XAMPP ships PostgreSQL — use Option B.

### Option D — PHP built-in server (any OS)

```bash
createdb alumni_portal                                   # PostgreSQL
php scripts/install_db.php --demo --admin-password='Choose@Strong1'
php -S localhost:8088 router.php
```

`router.php` doubles as the built-in server's router script, applying exactly the same routes and security rules as Apache.

---

## 🗄️ Database

PostgreSQL is the single supported engine. Everything is installed by one idempotent command:

```bash
php scripts/install_db.php                      # schema + feature tables + migrations + indexes
php scripts/install_db.php --demo               # …plus fictional demo alumni, posts, jobs, events, referrals
php scripts/install_db.php --admin-password=…   # set the seeded admin password
```

| Layer | Where |
|---|---|
| Base schema (users, profiles, posts, messages, jobs, events, polls, reports, notifications, …) | `deployment/schema_pg.sql` |
| Feature tables created/upgraded by the code (mentorship, calls, content & asset store, referrals, settings, …) | `api/_*schema*.php`, `_content_store.php`, `_asset_store.php` |
| Historical upgrades for existing databases (all idempotent) | `deployment/sql/*.sql` |
| Indexes for hot queries | `scripts/ensure_perf_indexes.sql` |

Re-running the installer on a live database is safe — it only adds what is missing. Demo accounts use `@demo.rjit.local` emails and the password `Demo@12345`; never run `--demo` on production.

## ⚙️ Configuration

Copy `.env.example` → `.env` (Docker) or set the same keys as Apache `SetEnv` / ECS task variables.

| Variable | Purpose | Default |
|---|---|---|
| `DB_DRIVER` | `pgsql` or `mysql` | `pgsql` |
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` | Database connection | — |
| `APP_ENV`, `APP_DEBUG`, `APP_URL` | Environment; `APP_DEBUG=true` shows raw errors | `production`, `false` |
| `AWS_REGION`, `AWS_BUCKET`, `AWS_SES_*`, `AWS_CLOUDWATCH_*` | S3 uploads, SES email, CloudWatch logs | — |
| `ALLOWED_ORIGINS` | Extra origins allowed to call the API from browsers | *(same-origin only)* |
| `TRUSTED_PROXIES` | CIDRs allowed to set `X-Forwarded-*` | loopback + private ranges |
| `RATE_LIMIT_API_PER_MIN` | API requests per IP per minute | `300` |
| `RATE_LIMIT_LOGIN` | Login attempts per IP per 10 min | `20` |
| `LOGIN_MAX_FAILURES`, `LOGIN_LOCK_SECONDS` | Per-account lockout after wrong passwords | `8`, `900` |
| `MAX_REQUEST_MB` | Largest accepted request body | `25` |
| `CSP_CONNECT_EXTRA` | Extra `connect-src` hosts (e.g. a streaming server) | — |
| `CACHE_DRIVER` | `auto` (Redis → APCu → file), `redis`, `apcu`, `file`, `none` | `auto` |
| `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD` | Redis for shared cache across servers | — |
| `DB_PERSISTENT` | Reuse DB connections between requests | `true` |

---

## ⏱️ Scheduled jobs

One entry point, `cron/run.php`, runs every due job. Run it **every minute**:

| Where | How |
|---|---|
| Docker | already done — the `scheduler` service in `docker-compose.yml` loops `cron/run.php` |
| Linux server | `* * * * * php /var/www/html/cron/run.php >> /var/log/rjit-cron.log 2>&1` |
| Windows / XAMPP | `schtasks /Create /SC MINUTE /MO 1 /TN "RJIT Portal Scheduler" /TR "C:\path\to\scripts\run_scheduler.bat"` |
| AWS ECS | an EventBridge schedule running the image with `php cron/run.php` |

| Job | When | What |
|---|---|---|
| `publish` | every run | publishes scheduled posts whose time has come (re-checking posting rules) |
| `promote` | daily 02:00 IST | students → alumni after their graduation date |
| `purge` | daily 03:00 IST | deletes stored content of posts archived 30+ days ago |
| `archive` | weekly, **only if `ARCHIVE_ENABLED=true`** | moves posts older than `ARCHIVE_AFTER_DAYS` (default 365) with no recent activity out of the feed; pinned posts and announcements are never archived |

Run one job by hand: `php cron/run.php --job=promote`. Archive and purge scripts are **dry runs** unless you add `--apply`.
Even without any cron, scheduled posts still go live: the feed runs the publisher at most once a minute (`SCHEDULER_LAZY=true`).

## 🧭 Routing & URLs

<div align="center">
<img src="assets/readme/router-demo.gif" alt="Terminal demo: old URLs resolve, aliases redirect, internals return 404, malicious uploads and cross-origin calls are blocked" width="88%">
</div>

Every `.php` request and every URL that isn't a real static file goes through **`router.php`**, driven by the table in **`bootstrap/routes.php`**:

```mermaid
flowchart LR
    B[Browser] -->|/feed.php · /feed · /signup · /api/get_feed.php| A[Apache .htaccess<br/>or php -S]
    A --> R[router.php]
    R --> S[bootstrap/security.php<br/>headers · CORS · rate limits · upload checks]
    S --> T{bootstrap/routes.php}
    T -->|page| P[pages/…]
    T -->|alias| D[301 → canonical URL]
    T -->|api/ admin/| X[api/*.php · admin/*.php]
    T -->|unknown or internal| E[pages/errors/error.php<br/>friendly 404]
```

**Nothing that worked before breaks.** Browser URLs never change, so every relative link, asset path and API call in the pages keeps resolving:

| You visit | You get |
|---|---|
| `/feed.php` *(old URL)* | `pages/feed.php` — 200 |
| `/feed` *(clean URL)* | the same page — 200 |
| `/edit-profile.php` | 301 → `/settings.php#profile` |
| `/signup`, `/join`, `/sign-up` | 301 → `/register.php` |
| `/privacy`, `/directory`, `/careers`, `/inbox`, `/admin` … | 301 → the right page |
| `/map`, `/batches`, `/refer`, `/insights` | 301 → Atlas, Referral board, admin Insights |
| `/stream`, `/resources`, `/stories` | 301 → Live, Library |
| `/pages/feed.php` *(internal path)* | 301 → `/feed.php` |
| `/feed/` *(trailing slash)* | 301 → `/feed` |
| `/config/Database.php`, `/api/_feed_schema.php`, `/storage/verification/…` | 404 — never served |
| anything unknown | friendly 404 with quick links |

**Add a page:** drop the file in `pages/` and add one line to `'pages'` in `bootstrap/routes.php`.
**Rename a page:** change its target and add the old slug to `'redirects'` — old links keep working forever.

---

## 🔐 Security

Security is centralised in `bootstrap/security.php`, which runs **before every page and API script**, so new endpoints are protected automatically. It aims to be strict without getting in the way: every block returns a clear, human message.

| Layer | What it does |
|---|---|
| **Front controller** | Only routed pages, `api/*.php` and `admin/*.php` can execute. Internal helpers (`api/_*.php`), `config/`, `models/`, `helpers/`, `middleware/`, `cron/`, `scripts/`, `bootstrap/` are unreachable. Path traversal is rejected. |
| **Headers** | Content-Security-Policy (no plugins, no foreign framing, locked `base-uri`/`form-action`), `X-Frame-Options`, `nosniff`, strict Referrer-Policy, Permissions-Policy (camera/mic only for Jitsi), COOP, HSTS on HTTPS. APIs get a sandboxed `default-src 'none'` CSP and `no-store`. |
| **CORS** | The legacy `Access-Control-Allow-Origin: *` on ~100 endpoints is rewritten to **same-origin + `ALLOWED_ORIGINS`** only; hostile pre-flights are refused. |
| **Brute-force protection** | Sliding-window rate limits per IP for all APIs, stricter limits for login, registration and password-reset, plus a **per-account lockout** after repeated wrong passwords ("try again in 12 minutes, or use Forgot password"). |
| **Real client IP** | `X-Forwarded-For` is trusted **only** from configured proxies (e.g. the ALB), so bans and rate limits can't be bypassed by spoofing a header. |
| **Private files** | Résumés and identity documents live in the database or private storage behind unguessable tokens — never at guessable public paths. |
| **Uploads** | Every file is checked platform-wide: dangerous and double extensions (`shell.php.jpg`) refused, real MIME sniffed with `finfo` and matched to the extension, images must decode, and files with embedded `<?php` / `<script>` (polyglots) are blocked. |
| **Storage** | `storage/` can never execute PHP, serves media/documents only, and private areas (verification documents, exports, logs, caches) are never web-visible. Uploaded files are served with a sandbox CSP. |
| **Data exports** | Personal data archives are streamed to the authenticated owner and deleted immediately — never left at a guessable public URL. |
| **Sessions** | `HttpOnly`, `SameSite=Lax`, `Secure` on HTTPS, strict mode, longer IDs, custom cookie name. Bearer tokens are 256-bit random and expire after 7 days. CSRF tokens are checked for cookie-based requests. |
| **Error hygiene** | In production, API responses that would leak SQL errors, stack traces or server paths are replaced with a friendly message; the original is logged server-side. |
| **Server** | `expose_php` off, `ServerTokens Prod`, `TRACE` disabled, no directory listings, non-standard HTTP methods rejected, request-size cap. |

Found a vulnerability? Please follow **[SECURITY.md](SECURITY.md)** — don't open a public issue.

---

## ⚡ Performance

Measured locally: PHP 8.3 + PostgreSQL 16, average of 30 requests per endpoint, before and after 2.1.

| Endpoint | Before | After | |
|---|---:|---:|---:|
| `api/me.php` (every page) | 29.1 ms | **1.7 ms** | 17× |
| `api/get_feed.php` | 37.7 ms | **5.3 ms** | 7× |
| `api/get_conversations.php` | 9.1 ms | **2.6 ms** | 3.5× |
| `api/search_directory.php` | 7.5 ms | **2.9 ms** | 2.6× |
| `api/get_jobs.php` | 6.3 ms | **1.9 ms** | 3.3× |
| `api/alumni_atlas.php` | — | **1.5 ms** | cached |
| `api/admin_insights.php` | — | **3.9 ms** | |

What changed:
- **Schema checks once per deploy, not per request.** The `ensure_*` helpers used to run ~40 `information_schema` queries on every API call. They're now remembered in the cache and re-run automatically when the helper file changes.
- **One DB connection per request**, persistent across requests, using PostgreSQL's single-round-trip parameterised execution (no PREPARE/DEALLOCATE per query).
- **Cache layer** (`helpers/Cache.php`): Redis when `REDIS_HOST` is set (docker-compose wires it up), otherwise APCu, otherwise files. Hot endpoints (Atlas, referral stats, public feed, site banner) are cached with version-based invalidation on writes.
- **Pre-built Tailwind** (50 KB CSS) replaces the ~400 KB in-browser compiler that ran on every page load. Rebuild with `npm --prefix tools/tailwind install && npm --prefix tools/tailwind run build`.
- **Docker image** ships OPcache + JIT, APCu, phpredis, realpath cache and long-lived static asset caching.

## 🧪 Testing

Three automated suites; the latest results are committed in [`tests/results/`](tests/results/).

| Suite | What it checks | Run |
|---|---|---|
| **Routes & security** | 63 checks: every old and clean URL, redirects, hidden internals, assets, CORS, CSP | `bash tests/smoke/routes.sh` (no DB needed) |
| **End-to-end API** — [results](tests/results/integration-latest.md) | 185 checks as admin, alumnus and student: sign-up and invite codes, profile, settings, password change, uploads, feed create/edit/delete, comments, reactions, reposts, pins, messages send/edit/delete, connections, jobs, events, mentorship, notifications, referrals, atlas, admin tools, upload/permission security, likes/comment reactions, For-you/Trending/Following feeds, reports, 1:1 calls, mentor approval → mentor group → group chat → group voice space → leave, polls, scheduled posts (incl. a real cron publish), live sessions, success stories, resources, every cron job | `php tests/integration/run.php http://127.0.0.1:8099` |
| **Real browser** — [results](tests/results/ui-latest.md) | 68 checks: every page for every role in Chrome (fails on any JS error, failed request or blank page) **plus click-through flows** — drafts survive reload, create a poll from the composer, vote, schedule a post, Ctrl+K navigation | `python3 tests/ui/browser_smoke.py --base http://127.0.0.1:8099` |
| **Brute-force lockout** | Wrong passwords lock the account with a friendly message | `php tests/integration/security_limits.php` |

CI runs lint, routes, the full PostgreSQL end-to-end suite and the lockout test on every push. For a local run against a test database:

```bash
createdb alumni_test && DB_NAME=alumni_test php scripts/install_db.php --demo --admin-password='Admin@12345'
DB_NAME=alumni_test RATE_LIMIT_DISABLED=true php -S 127.0.0.1:8099 router.php &
php tests/integration/run.php
```

The older Windows PowerShell feature matrix (`tests/local/verify_feature_matrix.ps1`) still works against a local XAMPP install.

## ☁️ Deployment

### AWS (Terraform + ECS + RDS + S3)

1. **Tools:** AWS CLI, Terraform, Docker, Git, `psql`.
2. **Credentials:** `aws configure` (region e.g. `us-east-1`).
3. **Variables:** `cd terraform && cp terraform.tfvars.example terraform.tfvars` — set `aws_region`, `environment`, `db_password`, sizing.
4. **Infrastructure:** `terraform init && terraform plan && terraform apply`, then `terraform output`.
5. **Image:** `./deployment/deploy-aws.sh` (Git Bash on Windows).
6. **Database:** `./deployment/migrate-to-rds.sh`, then run the SQL migrations in `deployment/sql/` on RDS.
7. **Runtime env:** set `DB_*`, `AWS_REGION`, `AWS_BUCKET`, `APP_ENV=production`, `APP_DEBUG=false` on the ECS task.
8. **Verify:** `curl http://<alb_dns_name>/healthz` and open `/`.

Pushing to `main` runs the **Deploy to AWS** workflow automatically (build → ECR → ECS).

Detailed guides: [Quick start on AWS](docs/deployment/QUICKSTART_AWS.md) · [Full AWS guide](docs/deployment/AWS_DEPLOYMENT.md) · [PostgreSQL setup](docs/deployment/POSTGRES_SETUP.md) · [General deployment](docs/deployment/DEPLOYMENT.md) · [Android APK build](docs/APK_BUILD.md)

> **Production checklist:** never commit `.env`; keep secrets in AWS Secrets Manager / ECS env vars; use RDS + S3; run migrations before traffic; attach an ACM certificate so the ALB serves HTTPS (HSTS and secure cookies switch on automatically).

---

## 🔌 API overview

All endpoints live in `api/` and return JSON. Authenticate with `Authorization: Bearer <token>` from `POST /api/login.php`.

| Area | Endpoints (selection) |
|---|---|
| Auth | `login`, `register_student`, `register_alumni`, `register_faculty`, `request_reset`, `reset_password`, `change_password`, `me`, `csrf_token` |
| Feed | `get_feed`, `create_post`, `edit_post`, `delete_post`, `react_to_post`, `create_comment`, `add_reply`, `toggle_repost`, `share_post`, `create_poll`, `vote_poll`, `schedule_post`, `pin_post`, `search_gifs` |
| People | `search_directory`, `search_alumni`, `get_user_profile`, `update_profile`, `upload_avatar`, `upload_cover`, `connect_user`, `block_user`, `update_privacy`, `export_data` |
| Mentorship | `mentorship?action=…` (list mentors, request, accept, groups, moderation) |
| Messages | `get_conversations`, `get_messages`, `send_message`, `edit_message`, `delete_message`, `start_call` |
| Careers & events | `get_jobs`, `post_job`, `apply_job`, `get_applicants`, `get_events`, `create_event`, `rsvp_event`, `get_event_attendees` |
| Polls & scheduling | `create_poll`, `vote_poll`, `end_poll`; `schedule_post`, `scheduled_posts` (list, `cancel`, `reschedule`) |
| Live & Library | `toggle_stream` (`start` jitsi/youtube, `stop`), `get_active_streams`; `success_stories?action=list\|create\|approve\|reject\|view`; `resources?action=list\|create\|download\|delete` |
| Referrals & Atlas | `referrals?action=list\|stats\|my_requests\|offer_requests`, POST `create_offer\|update_offer\|request\|respond\|withdraw`; `alumni_atlas` |
| Jobs (poster) | `manage_job` (`update`, `close`, `reopen`, `delete`) |
| Search | `master_search` (people, jobs, events, referral offers — used by Ctrl+K) |
| Admin | `admin_insights` (GET + `set_banner`, `clear_banner`, `broadcast`), `admin_export_users` (CSV), `admin_stats`, `admin_users`, `admin_user_actions`, `admin_report_action`, `moderation_queue`, `approve_user`, `generate_token` |
| Public | `site_banner`, `public_feed`, `healthz` |

Clean forms like `/api/get_feed` work too.

---

## 🗺️ Roadmap

- [ ] HTTPS on the production ALB (ACM certificate)
- [x] Self-hosted Tailwind build *(2.1)*
- [ ] Server-synced post drafts across devices
- [ ] Two-factor sign-in for admins and faculty
- [x] Alumni map by city and company, batches view *(2.1 — Alumni Atlas)*
- [x] Referral board *(2.1)*
- [x] Live sessions, polls, scheduled posts, Library, Trending/For-you *(2.2)*
- [ ] Batch reunion pages with RSVP + photo wall
- [ ] Give-back: scholarships and donation pledges
- [ ] Real-time chat over WebSockets (currently polling)
- [ ] Self-hosted Lucide/Leaflet to drop the last CDNs from the CSP

See the full history in **[CHANGELOG.md](CHANGELOG.md)** and the running deploy log in **[docs/DEV_NOTES.md](docs/DEV_NOTES.md)**.

---

## 🤝 Contributing, conduct & license

- **Contributing:** read [CONTRIBUTING.md](CONTRIBUTING.md) — setup, branch/commit style, how to add pages & routes, PR checklist.
- **Community standards:** [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md). The portal's own user-facing rules are at `/conduct.php`, `/terms.php` and `/policy.php`.
- **Security:** [SECURITY.md](SECURITY.md).
- **License:** [MIT](LICENSE) © 2026 Aryan Singh Chandel. Faculty photos and RJIT imagery belong to their owners and are used for the institute's portal only.

<div align="center">
<br>
Made with ❤️ for the RJIT community · <a href="https://github.com/shiroonigami23-ui">@shiroonigami23-ui</a>
</div>
