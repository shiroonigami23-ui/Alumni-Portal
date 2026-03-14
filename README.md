# Alumni Portal (XAMPP + PostgreSQL + AWS Ready)

This project runs in two modes:

1. Local mode (XAMPP + PostgreSQL) for fast development/testing.
2. AWS mode (Terraform + ECS + RDS + S3) for production hosting.

The repo already includes:

- 77 API endpoints in `api/`
- Models in `models/`
- Cron jobs in `cron/`
- AWS infra in `terraform/`
- Deployment scripts in `deployment/`
- Full local verification script: `verify_feature_matrix.ps1`

## 1. Local Setup (Windows + XAMPP + PostgreSQL)

### Step 1: Start services

- Start Apache (XAMPP)
- Start PostgreSQL (port `5432`)

### Step 2: Set DB environment variables (PowerShell)

```powershell
$env:DB_HOST="127.0.0.1"
$env:DB_PORT="5432"
$env:DB_NAME="alumni_portal"
$env:DB_USER="postgres"
$env:DB_PASSWORD="postgres"
```

If your postgres password is different, set that value.

### Step 3: Verify DB connection

```powershell
C:\xampp\php\php.exe .\db_test.php
```

Expected: connection success message.

### Step 4: Open app

- `http://localhost/alumni_portal`

## 2. Full Local Verification

Run this to test major features end-to-end with placeholder data:

```powershell
$env:PGPASSWORD="postgres"
powershell -ExecutionPolicy Bypass -File .\verify_feature_matrix.ps1
```

It validates:

- Auth and profile update
- Feed/posts/comments/reactions
- Messaging and inbox
- Events and RSVP
- Jobs and applications
- Mentorship flow
- Resources and success stories
- Notifications read flow
- Upload endpoints
- Search endpoints
- Live stream lifecycle

## 3. PostgreSQL Setup Guide

See detailed DB guide:

- `POSTGRES_SETUP.md`

This includes:

- Local DB creation
- Backup and restore commands
- RDS migration commands

## 4. AWS Deployment

If you want copy-paste production deployment, follow:

1. `QUICKSTART_AWS.md` (fast path)
2. `AWS_DEPLOYMENT.md` (complete details)

## 5. Important Files

- `deployment/deploy-aws.sh` - build/push image and redeploy ECS
- `deployment/migrate-to-rds.sh` - local PostgreSQL to AWS RDS migration
- `deployment/sql/2026_02_20_create_mentorship_requests.sql` - required migration for mentorship
- `verify_local.ps1` - lightweight local health checks
- `verify_feature_matrix.ps1` - full feature matrix

## 6. Notes for Production

- Never commit `.env` files.
- Keep DB credentials in AWS Secrets Manager / ECS env vars.
- Use RDS for DB and S3 for uploads in production.
- Run SQL migrations on RDS before first production traffic.

## 7. Current Status

Latest local verification result:

- API/model/cron lint: pass
- API runtime fatal sweep: pass
- Full feature matrix: pass

## 8. Working Memory

Use this section as the quick project memory for both local work and live AWS checks.

### Live production URL

- Base URL: `http://alumni-portal-alb-616743364.us-east-1.elb.amazonaws.com/`
- Feed: `http://alumni-portal-alb-616743364.us-east-1.elb.amazonaws.com/feed.php`
- Dashboard: `http://alumni-portal-alb-616743364.us-east-1.elb.amazonaws.com/dashboard.php`

When verifying a fresh deploy, append `?refresh=<commit>` to bypass stale browser caches.

### Recent live fixes

- `259264a`:
  - fixed feed like/share/reply UI crashes
  - fixed shared footer `classList` null errors affecting jobs/events/mentorship
- `a7ff122`:
  - moved post/comment attachments into DB-backed asset storage
- `c2d6d1e`:
  - moved post/comment payload content off ECS task-local files into DB-backed content payloads
- `e09ff7a`:
  - fixed feed crash regression
  - added first GIF picker UI
  - made dashboard sections collapsible
- `2c0b9e5`:
  - added dashboard stats count endpoints
  - replaced dead Giphy calls with live GIF search against Wikimedia Commons
  - added feed draft support
  - added feed upload progress / post-button locking
  - polished dashboard quick links and collapse defaults

### Stable behavior we now expect

- Feed loads on AWS without fatal JS crashes
- Profile posting no longer double-submits
- Reporting is limited to one report per account per post
- Share action exists and should either open native sharing or copy the link
- Image/file attachments for new posts/comments should survive ECS task changes
- GIF picker should show real GIF search results instead of a dead URL field
- Dashboard count cards should hit real API endpoints instead of 404 routes

### Known follow-up items

- Drafts are currently browser-local, not server-synced across devices
- Tailwind CDN warning still appears in console; this is a production warning, not a runtime blocker
- Old posts created before the DB-backed attachment/content migrations may still show broken legacy media
- Messages still need a cleanup pass for placeholder avatar/media fallbacks

### Verification checklist before calling a deploy good

1. Open:
   - `feed.php?refresh=<commit>`
   - `profile.php?refresh=<commit>`
   - `dashboard.php?refresh=<commit>`
   - `jobs.php?refresh=<commit>`
   - `events.php?refresh=<commit>`
   - `mentorship.php?refresh=<commit>`
2. Confirm there are no new console errors except the Tailwind CDN warning.
3. Confirm:
   - post create works
   - comments/replies work
   - like/share work
   - GIF picker shows real results
   - attachment upload shows progress and renders after posting
   - dashboard cards load counts without 404/API parse errors
4. Verify any new API route directly from the live ALB URL.

### Notes for future work

- Keep this section updated after every meaningful deploy.
- Add commit ID + what changed + any remaining known issue.
- Prefer recording live behavior here instead of relying on chat memory.
