# Alumni Portal - Complete Setup Guide

## Prerequisites
- PHP 8.1+
- PostgreSQL 12+
- Composer (for AWS SDK)
- AWS Account (optional, for cloud features)

## Local Development Setup

### 1. Database Setup
```bash
# Create database
createdb alumni_portal

# Import schema (ensure you have the schema file)
psql alumni_portal < database/schema.sql
```

### 2. Environment Configuration
```bash
# Copy environment template
cp .env.example .env

# Edit .env with your credentials
nano .env
```

### 3. Install Dependencies (Optional - for AWS features)
```bash
composer install
```

### 4. Configure Web Server
Point your web server document root to the project directory.

**XAMPP/Apache:**
- Place project in `htdocs/alumni_portal`
- Access via `http://localhost/alumni_portal`

## AWS Deployment

### 1. Prepare for Deployment
```bash
# Build Docker image
cd deployment/aws
docker build -t alumni-portal .

# Test locally
docker-compose up
```

### 2. Deploy to AWS

#### Option A: AWS App Runner
```bash
# Push to ECR
aws ecr create-repository --repository-name alumni-portal
docker tag alumni-portal:latest <account-id>.dkr.ecr.<region>.amazonaws.com/alumni-portal:latest
docker push <account-id>.dkr.ecr.<region>.amazonaws.com/alumni-portal:latest

# Create App Runner service via AWS Console
# Set environment variables in App Runner configuration
```

#### Option B: AWS Elastic Beanstalk
```bash
# Initialize EB
eb init -p docker alumni-portal

# Create environment
eb create alumni-portal-prod

# Set environment variables
eb setenv DB_HOST=<rds-endpoint> DB_NAME=alumni_portal ...
```

### 3. Configure AWS Services

#### RDS (PostgreSQL)
1. Create RDS PostgreSQL instance
2. Configure security group to allow app access
3. Import schema to RDS database
4. Update `DB_HOST` environment variable

#### S3 (File Storage)
1. Create S3 bucket: `alumni-portal-uploads`
2. Configure CORS policy
3. Set bucket policy for public read (if needed)
4. Update `AWS_BUCKET` environment variable

#### SES (Email Service)
1. Verify sender email in SES
2. Request production access (if needed)
3. Update `AWS_SES_FROM_EMAIL` environment variable

#### CloudWatch (Logging)
1. Create log group: `/aws/alumni-portal`
2. Create log stream: `application-logs`
3. Ensure IAM role has CloudWatch permissions

### 4. Environment Variables
Set these in your deployment environment:

```bash
# Database
DB_HOST=<rds-endpoint>
DB_PORT=5432
DB_NAME=alumni_portal
DB_USER=<db-user>
DB_PASSWORD=<db-password>

# AWS
AWS_ACCESS_KEY_ID=<your-key>
AWS_SECRET_ACCESS_KEY=<your-secret>
AWS_REGION=us-east-1
AWS_BUCKET=alumni-portal-uploads
AWS_SES_FROM_EMAIL=noreply@alumni.rjit.ac.in
AWS_CLOUDWATCH_LOG_GROUP=/aws/alumni-portal
```

## Security Checklist

- [x] CSRF protection on all state-changing endpoints
- [x] XSS prevention via output sanitization
- [x] Secure session configuration (HttpOnly, SameSite)
- [x] Environment variables for sensitive data
- [x] Input validation on all user inputs
- [ ] Enable HTTPS in production
- [ ] Configure rate limiting (consider AWS WAF)
- [ ] Regular security audits

## API Endpoints

### Authentication
- `POST /api/login.php` - User login (returns JWT + CSRF token)
- `POST /api/register.php` - User registration

### Mentorship
- `POST /api/mentorship.php?action=request` - Request mentorship
- `POST /api/mentorship.php?action=respond` - Accept/reject request
- `GET /api/mentorship.php?action=list_requests` - List pending requests

### Events
- `POST /api/events.php?action=create` - Create event (CSRF required)
- `GET /api/events.php?action=list&filter=upcoming` - List events
- `POST /api/events.php?action=rsvp` - RSVP to event (CSRF required)

### Success Stories
- `POST /api/success_stories.php?action=create` - Submit story (CSRF required)
- `GET /api/success_stories.php?action=list` - List approved stories
- `POST /api/success_stories.php?action=approve` - Approve story (Admin only, CSRF required)

### Resources
- `POST /api/resources.php?action=create` - Upload resource (CSRF required)
- `GET /api/resources.php?action=list&category=career` - List resources
- `POST /api/resources.php?action=download` - Track download

### File Upload
- `POST /api/upload.php` - Upload file to S3 (CSRF required)

## Troubleshooting

### Database Connection Issues
- Verify `DB_HOST` is correct (use `127.0.0.1` instead of `localhost` on Windows)
- Check PostgreSQL is running
- Verify credentials in `.env`

### AWS Integration Not Working
- Ensure `composer install` was run
- Verify AWS credentials are set
- Check IAM permissions for S3, SES, CloudWatch

### CSRF Token Errors
- Ensure frontend sends `X-CSRF-TOKEN` header or `csrf_token` in POST body
- Token is returned in login response

## Monitoring

### CloudWatch Logs
```bash
# View logs
aws logs tail /aws/alumni-portal --follow
```

### Application Logs (Local)
```bash
tail -f storage/logs/app.log
```
