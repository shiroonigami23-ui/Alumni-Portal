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
- current worktree:
  - students are now blocked from creating top-level posts on both feed and profile; they can still comment/reply/repost/report
  - profile now falls back cleanly when legacy local avatar/cover paths are missing instead of rendering a broken image in the UI
  - aligned dashboard upcoming-event APIs with the real `events` schema (`start_datetime` / `event_status`)
  - changed mentorship so:
    - students and alumni can each be under only one active mentor at a time
    - they must leave the current mentor before joining another
    - faculty/admin can become mentors immediately
    - alumni submit mentor applications for faculty/admin approval
    - students never see `Become a Mentor`
    - accepted mentor requests create a mentor group
    - mentor-group admin ownership transfers if the current admin leaves and another member is available
    - mentors/admins can ban, unban, or kick group members
    - bans are messaging bans for the mentor group chat
  - surfaced mentor groups inside the existing Messages screen using `group:<id>` conversations
  - added clearer mentor-group badges in Messages plus member list/header metadata
  - added unread tracking for mentor-group conversations
  - changed feed so older posts load progressively with a bottom sentinel instead of trying to render everything at once
  - added post draft controls plus upload-progress / upload-lock UI in the feed composer
  - mentorship page now uses accepted-request state as a fallback source of truth for the current mentor and refreshes requests before rendering mentor actions, so accepted mentees should no longer see `Current Mentor: none` plus extra `Request to Join` buttons
  - the remaining feed/comment/discovery avatar APIs now scrub dead local `storage/profiles/...` paths, and header/sidebar stop reusing stale cached local avatar URLs

### Stable behavior we now expect

- Feed loads on AWS without fatal JS crashes
- Profile posting no longer double-submits
- Reporting is limited to one report per account per post
- Share action exists and should either open native sharing or copy the link
- Image/file attachments for new posts/comments should survive ECS task changes
- GIF picker should show real GIF search results instead of a dead URL field
- Feed should progressively load older posts while scrolling instead of dumping the whole feed at once
- Feed drafts should stay on the current browser until posted or discarded
- Feed uploads should lock the composer and show progress/overlay feedback while files are being sent
- Dashboard count cards should hit real API endpoints instead of 404 routes
- Mentorship should no longer show `Become a Mentor` to students
- Alumni should move through a pending mentor-application flow instead of becoming mentors instantly
- Accepted mentor/mentee matches should have a mentor group conversation available in Messages
- Mentor-group conversations should show a mentor-group badge, member list, and unread counts
- Students should not be able to create top-level posts from feed or profile
- A student/alumni should never have more than one active mentor relationship at once

### Known follow-up items

- Drafts are currently browser-local, not server-synced across devices
- Tailwind CDN warning still appears in console; this is a production warning, not a runtime blocker
- Old posts created before the DB-backed attachment/content migrations may still show broken legacy media
- Messages still need a cleanup pass for placeholder avatar/media fallbacks
- Mentor-group admin transfer currently falls back to the next available member; there is no separate admin-picker UI yet

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
   - GIF selection renders an actual animated GIF on the post, not a dead preview card
   - attachment upload shows progress and renders after posting
   - feed infinite-scroll loads older posts as you reach the bottom
   - draft save / discard works on the current browser
   - dashboard cards load counts without 404/API parse errors
   - dashboard upcoming-events panel loads without HTML/PHP warning leakage
   - students do not see `Become a Mentor`
   - students do not see a top-level post composer on feed/profile
   - alumni mentor applications require faculty/admin approval
   - students/alumni cannot join a second mentor without leaving the current one
   - mentor group moderation actions work: accept, reject, ban, unban, kick, leave
   - mentor-group conversation list shows unread counts and header member chips
4. Verify any new API route directly from the live ALB URL.

### Notes for future work

- Keep this section updated after every meaningful deploy.
- Add commit ID + what changed + any remaining known issue.
- Prefer recording live behavior here instead of relying on chat memory.
