# Quick Start - AWS Deployment

## Prerequisites
- AWS Account
- AWS CLI configured
- Docker installed
- Terraform installed

## Deploy in 5 Steps

### 1. Configure Terraform
```bash
cd terraform
cp terraform.tfvars.example terraform.tfvars
# Edit terraform.tfvars and set db_password
```

### 2. Deploy Infrastructure
```bash
terraform init
terraform apply
```

### 3. Build & Push Docker Image
```bash
# Get ECR URL
ECR_REPO=$(terraform output -raw ecr_repository_url)

# Login to ECR
aws ecr get-login-password --region us-east-1 | \
  docker login --username AWS --password-stdin $ECR_REPO

# Build and push
docker build -t alumni-portal .
docker tag alumni-portal:latest $ECR_REPO:latest
docker push $ECR_REPO:latest
```

### 4. Migrate Database
```bash
cd ..
chmod +x deployment/migrate-to-rds.sh
./deployment/migrate-to-rds.sh
```

### 5. Access Application
```bash
# Get ALB DNS
ALB_DNS=$(cd terraform && terraform output -raw alb_dns_name)
echo "Application: http://$ALB_DNS"
```

## Automated Deployment
```bash
chmod +x deployment/deploy-aws.sh
./deployment/deploy-aws.sh
```

## CI/CD Setup
1. Add GitHub secrets: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`
2. Push to main branch
3. Automatic deployment!

## Cost
- Dev: ~$35/month
- Production: ~$215/month

## Support
See [AWS_DEPLOYMENT.md](AWS_DEPLOYMENT.md) for detailed guide.
