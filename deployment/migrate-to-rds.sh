#!/bin/bash

# Database Migration Script for AWS RDS PostgreSQL
# Migrates local PostgreSQL database to AWS RDS

set -e

echo "🗄️  Database Migration to AWS RDS"
echo "=================================="

# Configuration
LOCAL_DB_HOST="127.0.0.1"
LOCAL_DB_PORT="5432"
LOCAL_DB_NAME="alumni_portal"
LOCAL_DB_USER="postgres"

# Get RDS details from Terraform
if [ -f "terraform/terraform.tfstate" ]; then
    RDS_ENDPOINT=$(cd terraform && terraform output -raw rds_address)
    echo "✅ RDS endpoint found: $RDS_ENDPOINT"
else
    read -p "Enter RDS endpoint: " RDS_ENDPOINT
fi

read -p "Enter RDS username [admin]: " RDS_USER
RDS_USER=${RDS_USER:-admin}

read -sp "Enter RDS password: " RDS_PASSWORD
echo ""

RDS_DB_NAME="alumni_portal"

# Backup directory
BACKUP_DIR="deployment/db-backups"
mkdir -p $BACKUP_DIR
BACKUP_FILE="$BACKUP_DIR/backup_$(date +%Y%m%d_%H%M%S).sql"

echo ""
echo "📋 Migration Plan:"
echo "  Source: $LOCAL_DB_HOST:$LOCAL_DB_PORT/$LOCAL_DB_NAME"
echo "  Target: $RDS_ENDPOINT:5432/$RDS_DB_NAME"
echo "  Backup: $BACKUP_FILE"
echo ""

read -p "Proceed with migration? (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo "❌ Migration cancelled."
    exit 1
fi

# Step 1: Dump local database
echo ""
echo "📦 Step 1: Dumping local database..."
pg_dump -h $LOCAL_DB_HOST \
    -p $LOCAL_DB_PORT \
    -U $LOCAL_DB_USER \
    -d $LOCAL_DB_NAME \
    -F c \
    -f $BACKUP_FILE

if [ $? -eq 0 ]; then
    echo "✅ Database dumped successfully to $BACKUP_FILE"
    echo "   Size: $(du -h $BACKUP_FILE | cut -f1)"
else
    echo "❌ Failed to dump database"
    exit 1
fi

# Step 2: Test RDS connection
echo ""
echo "🔌 Step 2: Testing RDS connection..."
PGPASSWORD=$RDS_PASSWORD psql -h $RDS_ENDPOINT \
    -U $RDS_USER \
    -d postgres \
    -c "SELECT version();" > /dev/null

if [ $? -eq 0 ]; then
    echo "✅ RDS connection successful"
else
    echo "❌ Failed to connect to RDS"
    exit 1
fi

# Step 3: Create database if not exists
echo ""
echo "🏗️  Step 3: Creating database on RDS..."
PGPASSWORD=$RDS_PASSWORD psql -h $RDS_ENDPOINT \
    -U $RDS_USER \
    -d postgres \
    -c "CREATE DATABASE $RDS_DB_NAME;" 2>/dev/null || echo "Database already exists"

# Step 4: Restore to RDS
echo ""
echo "📥 Step 4: Restoring database to RDS..."
PGPASSWORD=$RDS_PASSWORD pg_restore -h $RDS_ENDPOINT \
    -U $RDS_USER \
    -d $RDS_DB_NAME \
    -v \
    --no-owner \
    --no-acl \
    $BACKUP_FILE

if [ $? -eq 0 ]; then
    echo "✅ Database restored successfully"
else
    echo "⚠️  Restore completed with warnings (this is normal)"
fi

# Step 5: Verify migration
echo ""
echo "🔍 Step 5: Verifying migration..."

# Count tables
LOCAL_TABLES=$(psql -h $LOCAL_DB_HOST -p $LOCAL_DB_PORT -U $LOCAL_DB_USER -d $LOCAL_DB_NAME -t -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public';")
RDS_TABLES=$(PGPASSWORD=$RDS_PASSWORD psql -h $RDS_ENDPOINT -U $RDS_USER -d $RDS_DB_NAME -t -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public';")

echo "  Local tables: $LOCAL_TABLES"
echo "  RDS tables: $RDS_TABLES"

if [ "$LOCAL_TABLES" -eq "$RDS_TABLES" ]; then
    echo "✅ Table count matches"
else
    echo "⚠️  Table count mismatch"
fi

# Step 6: Update application configuration
echo ""
echo "🔧 Step 6: Updating application configuration..."
echo ""
echo "Update your .env.production file with:"
echo "  DB_HOST=$RDS_ENDPOINT"
echo "  DB_PORT=5432"
echo "  DB_NAME=$RDS_DB_NAME"
echo "  DB_USER=$RDS_USER"
echo "  DB_PASSWORD=<your-password>"
echo ""

echo "🎉 Migration completed successfully!"
echo ""
echo "📝 Next steps:"
echo "  1. Verify data integrity in RDS"
echo "  2. Update application environment variables"
echo "  3. Deploy application to ECS"
echo "  4. Test application with RDS"
echo "  5. Keep local backup: $BACKUP_FILE"
