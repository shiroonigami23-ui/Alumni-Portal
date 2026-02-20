# PostgreSQL Setup (Local and AWS RDS)

This is a practical guide for this project.

## A. Local PostgreSQL Setup (Windows)

### 1. Start PostgreSQL

If installed via XAMPP/custom package, confirm service is up:

```powershell
C:\xampp\pgsql\pgsql\bin\pg_isready.exe -h 127.0.0.1 -p 5432
```

Expected: `accepting connections`

### 2. Create database (if missing)

```powershell
$env:PGPASSWORD="postgres"
C:\xampp\pgsql\pgsql\bin\psql.exe -h 127.0.0.1 -U postgres -d postgres -c "CREATE DATABASE alumni_portal;"
```

If already exists, PostgreSQL will report that.

### 3. Set app DB env variables

```powershell
$env:DB_HOST="127.0.0.1"
$env:DB_PORT="5432"
$env:DB_NAME="alumni_portal"
$env:DB_USER="postgres"
$env:DB_PASSWORD="postgres"
```

### 4. Verify app connectivity

```powershell
C:\xampp\php\php.exe .\db_test.php
```

## B. Required SQL Migration

Run this once on each environment (local + RDS):

```powershell
$env:PGPASSWORD="postgres"
C:\xampp\pgsql\pgsql\bin\psql.exe -h 127.0.0.1 -U postgres -d alumni_portal -f .\deployment\sql\2026_02_20_create_mentorship_requests.sql
```

## C. Backup Local Database

```powershell
$env:PGPASSWORD="postgres"
C:\xampp\pgsql\pgsql\bin\pg_dump.exe -h 127.0.0.1 -U postgres -d alumni_portal -F c -f .\deployment\db-backups\alumni_portal_latest.dump
```

## D. Restore Backup Locally

```powershell
$env:PGPASSWORD="postgres"
C:\xampp\pgsql\pgsql\bin\pg_restore.exe -h 127.0.0.1 -U postgres -d alumni_portal --clean --if-exists .\deployment\db-backups\alumni_portal_latest.dump
```

## E. Migrate Local DB to AWS RDS

Use the script:

```bash
./deployment/migrate-to-rds.sh
```

The script:

- Dumps local DB
- Connects to RDS
- Restores backup to RDS
- Compares table count

## F. Quick RDS Manual Connectivity Test

```bash
psql -h <RDS_ENDPOINT> -U admin -d alumni_portal -c "SELECT NOW();"
```

If this fails:

- Check RDS security group inbound rule for port `5432`
- Check ECS/EC2 security group outbound
- Check username/password

## G. Common Errors

### `relation "..." does not exist`

- Migration not applied on this environment.
- Run required SQL files from `deployment/sql/`.

### `password authentication failed`

- Wrong `DB_PASSWORD`.
- Update local env vars or ECS task env vars.

### `connection refused`

- PostgreSQL service down or wrong host/port.

## H. Production Best Practice

- Do not use `postgres` superuser for app runtime.
- Create dedicated app user with least privileges.
- Store secrets in AWS Secrets Manager.
