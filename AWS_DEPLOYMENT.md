# Alumni Portal - AWS Deployment Guide

## 🚀 Quick Start

This guide will help you deploy the RJIT Alumni Portal to AWS with full production infrastructure.

## Prerequisites

- AWS Account with appropriate permissions
- AWS CLI configured (`aws configure`)
- Docker installed
- Terraform >= 1.0
- Git
- PostgreSQL client (for migrations)

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                         Internet                             │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
              ┌────────────────┐
              │   Route 53     │ (DNS)
              └────────┬───────┘
                       │
                       ▼
              ┌────────────────┐
              │  CloudFront    │ (CDN for S3)
              └────────────────┘
                       │
                       ▼
         ┌─────────────────────────┐
         │  Application Load       │
         │  Balancer (ALB)         │
         └──────────┬──────────────┘
                    │
        ┌───────────┴───────────┐
        │                       │
        ▼                       ▼
┌───────────────┐       ┌───────────────┐
│  ECS Fargate  │       │  ECS Fargate  │
│  Task 1       │       │  Task 2       │
└───────┬───────┘       └───────┬───────┘
        │                       │
        └───────────┬───────────┘
                    │
        ┌───────────┴───────────┐
        │                       │
        ▼                       ▼
┌───────────────┐       ┌───────────────┐
│  RDS          │       │  S3 Bucket    │
│  PostgreSQL   │       │  (Uploads)    │
│  (Multi-AZ)   │       │               │
└───────────────┘       └───────────────┘
```

## AWS Services Used

- **VPC**: Isolated network with public/private subnets
- **RDS PostgreSQL**: Managed database (Multi-AZ for production)
- **ECS Fargate**: Serverless container orchestration
- **ECR**: Docker container registry
- **ALB**: Application load balancer
- **S3**: File storage for uploads
- **CloudFront**: CDN for static assets
- **Secrets Manager**: Secure credential storage
- **CloudWatch**: Logging and monitoring
- **IAM**: Access management

## Step-by-Step Deployment

### 1. Prepare Environment

```bash
# Clone repository
git clone <your-repo-url>
cd alumni_portal

# Install dependencies
composer install --no-dev
```

### 2. Configure Terraform Variables

Edit `terraform/terraform.tfvars`:

```hcl
# Set your database password
db_password = "YOUR_SECURE_PASSWORD_HERE"

# Adjust resources as needed
db_instance_class = "db.t3.medium"  # or db.t3.micro for dev
ecs_desired_count = 2               # number of containers
```

### 3. Deploy Infrastructure

```bash
cd terraform

# Initialize Terraform
terraform init

# Review plan
terraform plan

# Apply (creates all AWS resources)
terraform apply

# Save outputs
terraform output > ../deployment/terraform-outputs.txt
```

This will create:
- VPC with public/private subnets across 2 AZs
- RDS PostgreSQL database (Multi-AZ in production)
- S3 bucket with versioning and encryption
- CloudFront distribution
- ECS cluster with Fargate
- Application Load Balancer
- All necessary security groups and IAM roles

### 4. Build and Push Docker Image

```bash
# Get ECR repository URL from Terraform output
ECR_REPO=$(terraform output -raw ecr_repository_url)
AWS_REGION=us-east-1

# Login to ECR
aws ecr get-login-password --region $AWS_REGION | \
  docker login --username AWS --password-stdin $ECR_REPO

# Build image
docker build -t alumni-portal:latest .

# Tag and push
docker tag alumni-portal:latest $ECR_REPO:latest
docker push $ECR_REPO:latest
```

### 5. Run Database Migrations

```bash
# Get RDS endpoint
RDS_ENDPOINT=$(cd terraform && terraform output -raw rds_address)

# Connect to database
psql -h $RDS_ENDPOINT -U admin -d alumni_portal

# Run your schema file
psql -h $RDS_ENDPOINT -U admin -d alumni_portal < database/schema.sql
```

### 6. Deploy Application

```bash
# Update ECS service to use new image
CLUSTER_NAME=$(cd terraform && terraform output -raw ecs_cluster_name)
SERVICE_NAME=$(cd terraform && terraform output -raw ecs_service_name)

aws ecs update-service \
  --cluster $CLUSTER_NAME \
  --service $SERVICE_NAME \
  --force-new-deployment \
  --region us-east-1
```

### 7. Verify Deployment

```bash
# Get ALB DNS name
ALB_DNS=$(cd terraform && terraform output -raw alb_dns_name)

# Test application
curl http://$ALB_DNS/live.php

# Should return: {"status":"ok"}
```

## Automated Deployment

Use the provided script for automated deployment:

```bash
chmod +x deployment/deploy-aws.sh
./deployment/deploy-aws.sh
```

## CI/CD with GitHub Actions

The repository includes a GitHub Actions workflow (`.github/workflows/deploy.yml`) for automated deployments.

### Setup GitHub Secrets

Add these secrets to your GitHub repository:

- `AWS_ACCESS_KEY_ID`
- `AWS_SECRET_ACCESS_KEY`

### Trigger Deployment

```bash
# Push to main branch triggers automatic deployment
git push origin main
```

## Environment Variables

The application uses these environment variables (automatically set by ECS):

```bash
# Database (from Secrets Manager)
DB_HOST=<rds-endpoint>
DB_PORT=5432
DB_NAME=alumni_portal
DB_USER=admin
DB_PASSWORD=<from-secrets-manager>

# AWS Services
AWS_REGION=us-east-1
AWS_BUCKET=alumni-portal-uploads
AWS_CLOUDFRONT_URL=https://xxxxx.cloudfront.net

# Application
APP_ENV=production
APP_DEBUG=false
```

## Monitoring

### CloudWatch Logs

```bash
# View application logs
aws logs tail /aws/alumni-portal --follow

# View specific log stream
aws logs tail /aws/alumni-portal --follow \
  --log-stream-names ecs/alumni-portal/<task-id>
```

### CloudWatch Metrics

Access CloudWatch dashboard in AWS Console:
- ECS service metrics
- ALB metrics
- RDS metrics
- Custom application metrics

## Scaling

### Horizontal Scaling (More Containers)

```bash
# Update desired count
aws ecs update-service \
  --cluster alumni-portal-cluster \
  --service alumni-portal-service \
  --desired-count 4
```

Or update in Terraform:

```hcl
# terraform/terraform.tfvars
ecs_desired_count = 4
```

### Vertical Scaling (Bigger Containers)

```hcl
# terraform/terraform.tfvars
ecs_task_cpu    = "1024"  # 1 vCPU
ecs_task_memory = "2048"  # 2 GB
```

### Database Scaling

```hcl
# terraform/terraform.tfvars
db_instance_class = "db.t3.large"
```

## SSL/TLS Setup

### 1. Request Certificate in ACM

```bash
aws acm request-certificate \
  --domain-name alumni.rjit.ac.in \
  --validation-method DNS \
  --region us-east-1
```

### 2. Validate Certificate

Add DNS records as instructed by ACM.

### 3. Update ALB Listener

Uncomment HTTPS listener in `terraform/alb.tf` and apply:

```bash
cd terraform
terraform apply
```

## Backup and Disaster Recovery

### Database Backups

- Automated daily snapshots (7-day retention)
- Manual snapshots before major changes
- Point-in-time recovery enabled

### Restore from Backup

```bash
aws rds restore-db-instance-from-db-snapshot \
  --db-instance-identifier alumni-portal-db-restored \
  --db-snapshot-identifier <snapshot-id>
```

### S3 Versioning

S3 bucket has versioning enabled. Restore deleted files:

```bash
aws s3api list-object-versions \
  --bucket alumni-portal-uploads \
  --prefix uploads/

aws s3api get-object \
  --bucket alumni-portal-uploads \
  --key uploads/file.jpg \
  --version-id <version-id> \
  restored-file.jpg
```

## Cost Optimization

### Development Environment

```hcl
# terraform/terraform.tfvars
environment = "development"
db_instance_class = "db.t3.micro"
ecs_desired_count = 1
```

Estimated cost: ~$35/month

### Production Environment

```hcl
environment = "production"
db_instance_class = "db.t3.medium"
ecs_desired_count = 2
```

Estimated cost: ~$215/month

### Cost Reduction Tips

1. Use Reserved Instances for RDS (save up to 60%)
2. Enable S3 Intelligent-Tiering
3. Set up CloudWatch alarms for cost anomalies
4. Use Spot Instances for non-critical workloads

## Troubleshooting

### ECS Tasks Not Starting

```bash
# Check task logs
aws ecs describe-tasks \
  --cluster alumni-portal-cluster \
  --tasks <task-id>

# Check CloudWatch logs
aws logs tail /aws/alumni-portal --follow
```

### Database Connection Issues

```bash
# Test connectivity from ECS task
aws ecs execute-command \
  --cluster alumni-portal-cluster \
  --task <task-id> \
  --container alumni-portal \
  --interactive \
  --command "/bin/bash"

# Inside container
nc -zv <rds-endpoint> 5432
```

### ALB Health Checks Failing

```bash
# Check target health
aws elbv2 describe-target-health \
  --target-group-arn <target-group-arn>

# Verify /live.php endpoint
curl http://<alb-dns>/live.php
```

## Cleanup

To destroy all AWS resources:

```bash
cd terraform
terraform destroy
```

**Warning**: This will delete all data including the database!

## Security Best Practices

- ✅ Database in private subnet
- ✅ Encryption at rest (RDS, S3)
- ✅ Secrets in Secrets Manager
- ✅ Security groups with least privilege
- ✅ VPC with NAT gateways
- ✅ CloudWatch logging enabled
- ✅ Multi-AZ deployment (production)
- ⚠️ Enable WAF for DDoS protection
- ⚠️ Set up CloudTrail for audit logs
- ⚠️ Enable GuardDuty for threat detection

## Support

For issues or questions:
- Check CloudWatch logs
- Review Terraform state
- Contact: devops@rjit.ac.in

## Next Steps

1. ✅ Deploy infrastructure
2. ✅ Push Docker image
3. ✅ Run database migrations
4. ⏳ Configure custom domain
5. ⏳ Set up SSL certificate
6. ⏳ Configure CloudWatch alarms
7. ⏳ Set up backup strategy
8. ⏳ Performance testing
