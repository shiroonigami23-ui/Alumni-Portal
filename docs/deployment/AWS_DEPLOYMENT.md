# Full AWS Deployment Guide

This guide is intentionally explicit and beginner-friendly.

## 1. What gets deployed

- VPC + subnets + security groups
- RDS PostgreSQL
- ECS Fargate service
- ECR image repository
- Application Load Balancer
- S3 bucket for uploads
- CloudWatch logs

Terraform files are in `terraform/`.

## 2. Prerequisites checklist

- AWS account with permission to create VPC, ECS, RDS, ALB, ECR, IAM, S3.
- AWS CLI installed and configured.
- Docker installed and running.
- Terraform installed.
- PostgreSQL client tools installed.

Check commands:

```bash
aws --version
docker --version
terraform version
psql --version
```

## 3. Configure AWS CLI

```bash
aws configure
```

Provide:

- Access key
- Secret key
- Region (example: `us-east-1`)
- Output format (`json`)

## 4. Prepare Terraform config

```bash
cd terraform
cp terraform.tfvars.example terraform.tfvars
```

Open `terraform.tfvars` and set values:

- `aws_region`
- `environment`
- `db_password`
- instance sizes
- desired ECS task count

## 5. Deploy infrastructure

```bash
terraform init
terraform plan
terraform apply
```

Save output values:

```bash
terraform output
terraform output -raw alb_dns_name
terraform output -raw rds_address
terraform output -raw ecr_repository_url
```

## 6. Build and push app image

From project root:

```bash
cd ..
./deployment/deploy-aws.sh
```

If you prefer manual steps:

```bash
ECR_REPO=$(terraform -chdir=terraform output -raw ecr_repository_url)
AWS_REGION=us-east-1

aws ecr get-login-password --region $AWS_REGION | docker login --username AWS --password-stdin $ECR_REPO
docker build -t alumni-portal:latest .
docker tag alumni-portal:latest $ECR_REPO:latest
docker push $ECR_REPO:latest
```

## 7. Database migration (local -> RDS)

Use script:

```bash
./deployment/migrate-to-rds.sh
```

This script:

- Creates local dump
- Connects to RDS
- Restores dump
- Prints migration summary

## 8. Run required SQL migrations on RDS

At minimum run:

```bash
psql -h <RDS_ENDPOINT> -U admin -d alumni_portal -f deployment/sql/2026_02_20_create_mentorship_requests.sql
```

## 9. ECS runtime environment variables

Make sure ECS task definition has:

- `DB_HOST=<rds endpoint>`
- `DB_PORT=5432`
- `DB_NAME=alumni_portal`
- `DB_USER=<db user>`
- `DB_PASSWORD=<db password>`
- `AWS_REGION=<region>`
- `AWS_BUCKET=<uploads bucket name>`
- `APP_ENV=production`
- `APP_DEBUG=false`

## 10. Validate deployment

Get app URL:

```bash
terraform -chdir=terraform output -raw alb_dns_name
```

Test:

```bash
curl http://<alb_dns_name>/live.php
curl http://<alb_dns_name>/
```

## 11. Monitoring and logs

CloudWatch log tail:

```bash
aws logs tail /aws/alumni-portal --follow
```

ECS service status:

```bash
aws ecs describe-services --cluster <cluster_name> --services <service_name>
```

## 12. Common failure cases and fixes

### App cannot connect to DB

- Wrong `DB_HOST`/`DB_USER`/`DB_PASSWORD`
- RDS SG does not allow inbound `5432` from ECS SG

### ALB health check failing

- Wrong health path or app fatal in container
- Check ECS logs in CloudWatch

### Files not uploading

- `AWS_BUCKET` missing
- ECS IAM role missing S3 permissions

### SQL relation missing

- Migration not run on RDS

## 13. Production hardening checklist

- Enable HTTPS (ACM cert + ALB listener 443)
- Use Secrets Manager for DB credentials
- Restrict security groups to least privilege
- Enable automated RDS backups
- Add CloudWatch alarms for ECS and RDS

## 14. Destroy resources (only if needed)

```bash
cd terraform
terraform destroy
```

Warning: this removes infrastructure and can delete data if not backed up.
