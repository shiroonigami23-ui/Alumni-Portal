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
