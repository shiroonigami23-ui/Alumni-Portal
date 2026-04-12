# Alumni Portal (XAMPP + PostgreSQL/MySQL + AWS)

This README is written as a beginner-friendly setup guide.

Use one of these:

1. Local XAMPP + PostgreSQL (`5433`) (recommended for this repo).
2. Local XAMPP + MySQL (`3306`) (for college server compatibility).
3. AWS deploy (Terraform + ECS + RDS + S3).

---

## 1. Local Setup On Windows (XAMPP)

### 1A. Fast start (PostgreSQL in XAMPP)

From project root:

```bat
scripts\start_local_xampp.bat
```

This starts:

- XAMPP PostgreSQL at `127.0.0.1:5433`
- Apache web server

Then open:

- `http://127.0.0.1:8088/`

### 1B. MySQL setup on XAMPP (step-by-step)

Use this if you want local behavior close to a MySQL-based server.

### Step 1: Start XAMPP services

In XAMPP Control Panel, start:

- `Apache`
- `MySQL`

### Step 2: Create database

```bat
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS alumni_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

If MySQL root has a password, use `-p`:

```bat
C:\xampp\mysql\bin\mysql.exe -u root -p -e "CREATE DATABASE IF NOT EXISTS alumni_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Step 3: Import schema + required MySQL migration

```bat
C:\xampp\mysql\bin\mysql.exe -u root alumni_portal < deployment\db.sql
C:\xampp\mysql\bin\mysql.exe -u root alumni_portal < deployment\sql\2026_04_12_moderator_post_workflow_mysql.sql
```

If using password:

```bat
C:\xampp\mysql\bin\mysql.exe -u root -p alumni_portal < deployment\db.sql
C:\xampp\mysql\bin\mysql.exe -u root -p alumni_portal < deployment\sql\2026_04_12_moderator_post_workflow_mysql.sql
```

### Step 4: Set app DB env vars for Apache runtime

Important: this app reads DB settings from process environment (`getenv`).  
Set vars inside Apache config so browser requests use MySQL too.

Edit file:

- `C:\xampp\apache\conf\extra\httpd-vhosts.conf`  
  (or your active Apache vhost file)

Add inside your `<VirtualHost ...>` block:

```apache
SetEnv DB_DRIVER mysql
SetEnv DB_HOST 127.0.0.1
SetEnv DB_PORT 3306
SetEnv DB_NAME alumni_portal
SetEnv DB_USER root
SetEnv DB_PASSWORD
```

If root has password:

```apache
SetEnv DB_PASSWORD your_mysql_password
```

Restart Apache after saving config.

### Step 5: Verify DB connection

```bat
C:\xampp\php\php.exe db_test.php
```

Expected output: successful connection.

### Step 6: Run app

Open:

- `http://localhost/alumni_portal`

---

## 2. Local Verification Script

Run this to test major features end-to-end with placeholder data:

```powershell
$env:PGPASSWORD=""
powershell -ExecutionPolicy Bypass -File .\tests\local\verify_feature_matrix.ps1
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

---

## 3. AWS Deployment (Beginner Checklist)

This is the safest sequence for first deployment.

### Step 1: Install required tools

- AWS CLI
- Terraform
- Docker Desktop
- Git
- PostgreSQL client tools (`psql`)

Quick check:

```bash
aws --version
terraform version
docker --version
psql --version
```

### Step 2: Configure AWS credentials

```bash
aws configure
```

Set:

- Access key
- Secret key
- Region (example: `us-east-1`)
- Output: `json`

### Step 3: Configure Terraform variables

```bash
cd terraform
cp terraform.tfvars.example terraform.tfvars
```

Edit `terraform.tfvars` and set at least:

- `aws_region`
- `environment`
- `db_password`
- sizing/count variables as needed

### Step 4: Create AWS infrastructure

```bash
terraform init
terraform plan
terraform apply
```

Check outputs:

```bash
terraform output
terraform output -raw alb_dns_name
terraform output -raw rds_address
terraform output -raw ecr_repository_url
```

### Step 5: Build and push app image

From repo root, run:

```bash
./deployment/deploy-aws.sh
```

On Windows, run from Git Bash.

### Step 6: Migrate database to RDS

```bash
./deployment/migrate-to-rds.sh
```

### Step 7: Run required SQL migration on RDS

```bash
psql -h <RDS_ENDPOINT> -U admin -d alumni_portal -f deployment/sql/2026_02_20_create_mentorship_requests.sql
```

### Step 8: Ensure ECS runtime env vars are set

Required:

- `DB_HOST=<rds endpoint>`
- `DB_PORT=5432`
- `DB_NAME=alumni_portal`
- `DB_USER=<db user>`
- `DB_PASSWORD=<db password>`
- `AWS_REGION=<region>`
- `AWS_BUCKET=<bucket name>`
- `APP_ENV=production`
- `APP_DEBUG=false`

### Step 9: Validate live service

```bash
curl http://<alb_dns_name>/live.php
curl http://<alb_dns_name>/
```

---

## 4. PostgreSQL Setup Guide

See detailed DB guide:

- `POSTGRES_SETUP.md`

This includes:

- Local DB creation
- Backup and restore commands
- RDS migration commands

## 5. AWS Deployment Reference

If you want copy-paste production deployment, follow:

1. `QUICKSTART_AWS.md` (fast path)
2. `AWS_DEPLOYMENT.md` (complete details)

## 6. Important Files

- `deployment/deploy-aws.sh` - build/push image and redeploy ECS
- `deployment/migrate-to-rds.sh` - local PostgreSQL to AWS RDS migration
- `deployment/sql/2026_02_20_create_mentorship_requests.sql` - required migration for mentorship
- `tests/local/verify_local.ps1` - lightweight local health checks
- `tests/local/verify_feature_matrix.ps1` - full feature matrix

## 7. Notes for Production

- Never commit `.env` files.
- Keep DB credentials in AWS Secrets Manager / ECS env vars.
- Use RDS for DB and S3 for uploads in production.
- Run SQL migrations on RDS before first production traffic.

## 8. Current Status

Latest local verification result:

- API/model/cron lint: pass
- API runtime fatal sweep: pass
- Full feature matrix: pass

## 9. Working Memory

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
  - profile identity now resolves current-user state from `api/me.php` before falling back to `localStorage`, reducing the “sometimes blank profile” behavior when local cache is missing or stale
  - avatar / cover uploads and generic file uploads now use DB-backed asset URLs; old local avatar/cover references are migrated or cleared on read
  - bare legacy avatar filenames like `avatar_46_...jpg` are treated as stale local media and no longer reused blindly by header/sidebar/feed/profile
  - `get_conversations.php` now includes the mentor owner id for mentor groups, preventing warning-leak / malformed JSON risk in group conversation payloads
  - admin user management now loads moderation state per account so action buttons only show the valid next action (`Ban` vs `Unban`, `Shadow` vs `Lift Shadow`, `Mute DM` vs `Unmute DM`)
  - admins can open any profile, including private faculty/alumni profiles, through the same profile API
  - clicking the bell icon now bulk-marks all notifications as read instead of requiring notification-by-notification clearing
  - admins can delete any post or comment/reply directly from the live feed UI; comment/reply delete now respects admin role too
  - admin delete now also works cleanly from profile and admin report flows, and post/comment deletion removes DB-backed payloads for cascaded comment/reply trees instead of leaving orphaned content rows
  - post/comment moderation no longer relies on enum-unsafe role SQL such as `LOWER(role)` against PostgreSQL enum columns
  - mentor-group admin transfer is now restricted to non-student members, and a group can be disbanded explicitly; if an admin leaves without an eligible successor, the mentor group is disbanded automatically and its GC/messages are removed via cascade
  - mentorship now separates one normal mentor-group slot from one admin-led GC slot:
    - students cannot see or join admin-led mentor groups
    - faculty can only join admin-led mentor groups, not another normal mentor group
    - alumni/faculty can keep one normal mentor-group relationship plus one admin-led GC, but no extra regular groups
    - accepted-request fallback no longer recreates a mentorship after the user left or the group was disbanded
    - site admins can disband any mentor group / GC from the mentorship or messages flows
  - mobile navigation now uses a bottom-bar-first pattern on small screens with a `More` sheet for secondary destinations instead of a persistent left drawer
  - mobile polish pass:
    - bottom-nav icons are larger with tighter labels and touch-feedback states
    - the mobile `More` sheet opens with a smoother slide-up animation
    - the mobile header uses a shorter brand treatment and hides the theme button to avoid crowding notification/profile actions
    - dashboard hides lower-priority blocks by default on phones (`Jump Back In`, `Recent Activity`, `Recent Notifications`) and suppresses the least important stats card to reduce scroll
    - discovery now defaults to compact list mode on phones, collapses filters by default, trims low-priority card details, and hides the top-companies block on mobile
  - direct messages call flow is now real instead of placeholder UI:
    - starting an audio/video call opens a live Jitsi room immediately for the caller
    - the receiver gets a clickable notification that opens the same room
    - mentor-group chats now support one live shared mentor space at a time:
      - members can start or join an audio/video mentor space from the group header
      - the active space is reused for all members until it is ended or expires
      - group admins or the person who started the space can end it from Messages
  - message content payloads are no longer written to task-local `storage/messages/...` files for new sends/edits:
    - direct and mentor-group messages now store content in the DB-backed content store
    - legacy message payload pointers are auto-migrated to DB-backed storage on successful read
    - this fixes the live AWS `[Content Missing]` flicker caused by different app tasks disagreeing about local files
  - the shared header theme toggle is now visible on small screens across pages instead of disappearing outside Discovery
  - browser `alert` / `confirm` / `prompt` dialogs are being replaced with the shared in-app portal dialog layer:
    - Messages uses custom dialogs for edit/delete/disband/call flows
    - mentorship, feed, profile, settings, dashboard, discovery, jobs, events, and admin moderation pages now use the same app-styled modal system instead of native browser popups
  - portal timestamp rendering is now being standardized to Indian Standard Time (`Asia/Kolkata`) in the shared frontend date parser/formatter:
    - raw DB timestamps without offsets are treated as UTC-source server values before display
    - relative labels such as `m ago` / `h ago` and exact date-time labels now resolve through one shared IST formatter
    - messages, feed/profile posts and replies, mentorship membership timestamps, notifications, and admin time tables should no longer drift between pages
  - dashboard now routes its cards/feed/notification requests through the shared API helper instead of a stale local `res.json()` wrapper, avoiding repeated console parse errors when an endpoint returns HTML or a PHP warning page instead of JSON
  - profile timeline no longer shows `Edit` / `Delete` controls on repost activity cards for users who only reposted the original post
  - settings now treats the account email as a managed login identifier instead of an editable profile field:
    - the email shown in settings is read-only
    - student login email is explicitly locked by institute policy
    - alumni/faculty/admin should use bio/contact/social fields for public-facing contact info, without affecting their actual login id
  - forgot-password is now a real email-reset flow:
    - new `forgot-password.php` and `reset.php` pages exist
    - reset tokens are issued securely, expire after 30 minutes, and are invalidated after use
    - reset emails are sent through the shared `EmailService`
    - the old localhost/token-in-response simulation flow is gone
    - SES delivery should use the ECS runtime/SDK path first; if delivery fails, the API now returns an error instead of silently pretending success
  - profile timeline now treats reposts as first-class activity:
    - the profile page fetches both authored posts and reposts
    - student profiles relabel the first timeline tab to `Activity`
    - students still cannot create top-level posts, but their repost activity can populate the profile timeline
    - the dedicated `Reposts` tab now uses the profile owner's repost activity instead of the viewer's repost state
  - discovery directory relies on `api/search_directory.php`; a mapper bug around profile avatar resolution can break the whole alumni response, so verify discovery after avatar/media changes

### Infra note

- The live ALB currently serves HTTP on port `80`.
- HTTPS on port `443` is not configured yet in `us-east-1` because there is no issued ACM certificate attached to the ALB listener.
- If phones/browsers auto-upgrade to HTTPS, they can show `ERR_CONNECTION_REFUSED` even while desktop HTTP works.

### Stable behavior we now expect

- Feed loads on AWS without fatal JS crashes
- Mobile screens should no longer be dominated by the left navigation; small screens should use the bottom nav plus `More` sheet
- Mobile header should no longer crowd the brand with theme/notification/profile controls
- Dashboard should open in a lighter mobile state with fewer sections expanded by default
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
- Admin should be able to delete any post/comment/reply from feed, profile, and admin review flows without PostgreSQL enum errors
- Students should not be able to create top-level posts from feed or profile
- Forgot-password should send a real reset email instead of returning a token/simulation link
- Reset links should expire after 30 minutes and work through `reset.php`
- A student/alumni should never have more than one active mentor relationship at once
- Students should not see or join admin-led mentor groups; faculty/alumni can have at most one normal mentor group plus one admin-led GC
- Disbanding a mentor group should remove all members, end active matches, and delete the GC/messages by cascade
- Discovery should open in a compact mobile layout with collapsed filters and list view on phones
- Direct 1:1 message audio/video calls should open a working Jitsi room for both the caller and the callee
- Mentor-group chats should support one active shared mentor space that members can join safely from the chat header
- The theme toggle should remain visible in the shared mobile header, not just on Discovery
- Custom in-app dialogs should appear across the portal instead of native browser `alert` / `confirm` / `prompt` popups
- New and edited messages should survive AWS task changes without briefly rendering `[Content Missing]`
- Portal timestamps should render consistently in Indian Standard Time across posts, replies, messages, notifications, mentorship, and admin views
- Dashboard should not spam JSON parse errors in console just because an API response body is HTML/malformed
- Repost activity cards on profile should not show misleading edit/delete controls
- Settings should not imply that login email is editable when it is actually managed separately from profile content

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
   - admin can delete a post from feed, profile, and admin reports without `lower(user_role)` / enum SQL failures
   - admin can delete comments and replies from the live feed
   - alumni mentor applications require faculty/admin approval
   - students/alumni cannot join a second mentor without leaving the current one
   - students cannot see or join admin-led mentor groups
   - faculty/alumni cannot exceed one normal mentor group plus one admin-led GC
   - mentor group moderation actions work: accept, reject, ban, unban, kick, leave
   - disbanding a mentor group removes members, ends active matches, and removes the GC conversation
   - mentor-group conversation list shows unread counts and header member chips
   - starting an audio/video call from a direct message opens a live Jitsi room without popup-blocker regressions
   - the receiving user gets a call notification that opens the same room from the header notification tray
   - mentor-group audio/video buttons open or join one active mentor space for the whole group
   - group admins or the space starter can end the current mentor space
   - mobile pages keep the theme toggle visible in the shared header
   - Messages edit/delete/disband flows use the custom app dialog rather than browser popup chrome
   - discovery on mobile starts in list mode with filters collapsed and the top-companies block hidden
4. Verify any new API route directly from the live ALB URL.

### Notes for future work

- Keep this section updated after every meaningful deploy.
- Add commit ID + what changed + any remaining known issue.
- Prefer recording live behavior here instead of relying on chat memory.
## Project Memory

### Admin Moderation And Access Control
- Admin moderation is being expanded through `api/admin_user_actions.php` and `api/admin_report_action.php`.
- Admin can now ban/unban users, collect and ban their known devices, restrict posting, restrict messaging, shadow-ban, and reset passwords through the admin flow.
- Non-admins cannot report admin-authored posts/comments anymore; the backend blocks those attempts in `api/report_content.php`.
- Non-admins cannot directly message admins. Private alumni/faculty also cannot start direct conversations while their profile is private.

### Password Reset Delivery
- App-side forgot-password flow exists through `forgot-password.php`, `api/request_reset.php`, and `api/reset_password.php`.
- Current AWS SES status still blocks real delivery:
  - SES account is still in sandbox
  - no SES email identities are verified
  - no production sender identity is available yet
- Because of that, password reset requests can generate tokens but cannot reliably send email until SES is configured.
