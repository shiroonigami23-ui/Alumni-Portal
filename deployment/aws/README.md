# AWS Deployment Guide for Alumni Portal

## Prerequisites
- AWS Account
- Docker installed locally (for building images)
- AWS CLI configured

## Option 1: AWS App Runner (Recommended for simple container deployment)
1. Build the Docker image:
   ```bash
   docker build -t alumni-portal .
   ```
2. Push to Amazon ECR.
3. Create an App Runner service linked to your ECR image.
4. Set Environment Variables in App Runner:
   - `DB_HOST`: Your RDS endpoint
   - `DB_PORT`: 5432
   - `DB_NAME`: alumni_portal
   - `DB_USER`: Your DB user
   - `DB_PASSWORD`: Your DB password

## Option 2: Elastic Beanstalk
1. Zip the project files (excluding `.git`, `node_modules`).
2. Create a new Elastic Beanstalk Application (PHP platform).
3. Upload the zip file.
4. Configure environment variables in Software Configuration.

## Database Setup (RDS)
1. Create a PostgreSQL instance in RDS.
2. Ensure Security Group allows traffic from your App Runner/Beanstalk service.
3. Run the schema migration scripts (found in `database/` or export your local schema).

## Local Testing with Docker Compose
Run `docker-compose up --build` inside `deployment/aws/` to test the container locally.
