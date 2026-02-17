# 🎓 Alumni Portal - AWS Deployment Template

[![AWS](https://img.shields.io/badge/AWS-Ready-orange?logo=amazon-aws)](https://aws.amazon.com)
[![Docker](https://img.shields.io/badge/Docker-Enabled-blue?logo=docker)](https://www.docker.com)
[![Terraform](https://img.shields.io/badge/Terraform-IaC-purple?logo=terraform)](https://www.terraform.io)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php)](https://www.php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15-336791?logo=postgresql)](https://www.postgresql.org)

A production-ready, AWS-deployable alumni portal template with complete infrastructure as code, containerization, and CI/CD pipeline.

## ✨ Features

- 🚀 **Full AWS Deployment** - Complete Terraform infrastructure
- 🐳 **Dockerized** - Containerized PHP application
- 🗄️ **PostgreSQL** - RDS Multi-AZ database
- 📦 **S3 Storage** - File uploads with CloudFront CDN
- 🔒 **Secure** - VPC, Secrets Manager, encryption at rest
- 📊 **Monitored** - CloudWatch logs and metrics
- 🔄 **CI/CD** - GitHub Actions automated deployment
- 📈 **Scalable** - ECS Fargate with auto-scaling
- 💰 **Cost-Optimized** - ~$35/month dev, ~$215/month production

## 🏗️ Architecture

```
Internet → Route 53 → CloudFront (CDN)
                   ↓
         Application Load Balancer
                   ↓
         ECS Fargate (2+ containers)
                   ↓
         ┌─────────┴─────────┐
         ↓                   ↓
    RDS PostgreSQL      S3 Bucket
    (Multi-AZ)          (Uploads)
```

## 📋 Prerequisites

- AWS Account with appropriate permissions
- [AWS CLI](https://aws.amazon.com/cli/) configured
- [Docker](https://www.docker.com/) installed
- [Terraform](https://www.terraform.io/) >= 1.0
- [Git](https://git-scm.com/)
- [Composer](https://getcomposer.org/) (for PHP dependencies)
- PostgreSQL client (for migrations)

## 🚀 Quick Start

### 1. Clone and Configure

```bash
# Clone this repository
git clone https://github.com/YOUR_USERNAME/alumni-portal-aws.git
cd alumni-portal-aws

# Install dependencies
composer install --no-dev

# Copy environment template
cp .env.example .env
# Edit .env with your local settings
```

### 2. Local Development

```bash
# Start with Docker Compose
docker-compose up -d

# Access at http://localhost:8080
```

### 3. Deploy to AWS

```bash
# Configure Terraform
cd terraform
cp terraform.tfvars.example terraform.tfvars
# Edit terraform.tfvars and set db_password

# Deploy infrastructure
terraform init
terraform apply

# Build and push Docker image
cd ..
./deployment/deploy-aws.sh
```

See [QUICKSTART_AWS.md](QUICKSTART_AWS.md) for detailed instructions.

## 📁 Project Structure

```
alumni_portal/
├── api/                    # API endpoints (77+ endpoints)
├── config/                 # Configuration files
│   ├── AWS.php            # AWS SDK setup
│   └── Database.php       # PostgreSQL connection
├── deployment/            # Deployment scripts
│   ├── apache/           # Apache configuration
│   ├── deploy-aws.sh     # Automated deployment
│   └── migrate-to-rds.sh # Database migration
├── helpers/              # Helper classes
│   └── S3Helper.php     # S3 file management
├── includes/            # Shared includes
├── models/              # Data models
├── terraform/           # Infrastructure as Code
│   ├── main.tf         # Terraform configuration
│   ├── vpc.tf          # VPC setup
│   ├── rds.tf          # PostgreSQL database
│   ├── s3.tf           # S3 + CloudFront
│   ├── ecs.tf          # ECS Fargate
│   └── alb.tf          # Load balancer
├── .github/
│   └── workflows/
│       └── deploy.yml  # CI/CD pipeline
├── Dockerfile          # Container definition
├── docker-compose.yml  # Local development
└── README.md          # This file
```

## 🔧 Configuration

### Environment Variables

Create `.env` file (never commit this):

```bash
# Database
DB_HOST=localhost
DB_PORT=5432
DB_NAME=alumni_portal
DB_USER=postgres
DB_PASSWORD=your_password

# AWS (for production)
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_REGION=us-east-1
AWS_BUCKET=alumni-portal-uploads
```

### Terraform Variables

Edit `terraform/terraform.tfvars`:

```hcl
environment     = "production"
aws_region      = "us-east-1"
db_password     = "CHANGE_THIS_SECURE_PASSWORD"
db_instance_class = "db.t3.medium"
ecs_desired_count = 2
```

## 🐳 Docker

### Local Development

```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f

# Stop services
docker-compose down
```

### Build Production Image

```bash
docker build -t alumni-portal:latest .
```

## ☁️ AWS Deployment

### Infrastructure Components

- **VPC**: Isolated network with public/private subnets
- **RDS**: PostgreSQL 15.4 (Multi-AZ, encrypted)
- **ECS**: Fargate serverless containers
- **ALB**: Application Load Balancer
- **S3**: File storage with versioning
- **CloudFront**: Global CDN
- **Secrets Manager**: Secure credential storage
- **CloudWatch**: Logging and monitoring

### Deployment Steps

1. **Deploy Infrastructure**
   ```bash
   cd terraform
   terraform init
   terraform apply
   ```

2. **Build & Push Image**
   ```bash
   ./deployment/deploy-aws.sh
   ```

3. **Migrate Database**
   ```bash
   ./deployment/migrate-to-rds.sh
   ```

4. **Access Application**
   ```bash
   # Get ALB DNS from Terraform output
   terraform output alb_dns_name
   ```

See [AWS_DEPLOYMENT.md](AWS_DEPLOYMENT.md) for comprehensive guide.

## 🔄 CI/CD

### GitHub Actions

Automated deployment on push to `main` branch.

**Setup:**
1. Add GitHub secrets:
   - `AWS_ACCESS_KEY_ID`
   - `AWS_SECRET_ACCESS_KEY`

2. Push to main:
   ```bash
   git push origin main
   ```

3. Deployment runs automatically!

## 💰 Cost Estimation

### Development Environment
- RDS (db.t3.micro): ~$15/month
- ECS Fargate (1 task): ~$15/month
- S3 + CloudFront: ~$2/month
- **Total: ~$35/month**

### Production Environment
- RDS (db.t3.medium, Multi-AZ): ~$120/month
- ECS Fargate (2 tasks): ~$60/month
- ALB: ~$20/month
- S3 + CloudFront: ~$15/month
- **Total: ~$215/month**

## 📊 Monitoring

### CloudWatch Logs

```bash
# View application logs
aws logs tail /aws/alumni-portal --follow
```

### Metrics

Access CloudWatch dashboard for:
- ECS service metrics
- ALB request metrics
- RDS database metrics
- Custom application metrics

## 🔒 Security

- ✅ Database in private subnets
- ✅ Encryption at rest (RDS, S3)
- ✅ Secrets Manager for credentials
- ✅ Security groups with least privilege
- ✅ VPC isolation
- ✅ HTTPS ready (SSL certificate setup)
- ✅ CloudWatch audit logs

## 📈 Scaling

### Horizontal Scaling
```bash
# Scale to 4 containers
aws ecs update-service \
  --cluster alumni-portal-cluster \
  --service alumni-portal-service \
  --desired-count 4
```

### Vertical Scaling
Edit `terraform/terraform.tfvars`:
```hcl
ecs_task_cpu    = "1024"  # 1 vCPU
ecs_task_memory = "2048"  # 2 GB
```

## 🛠️ Development

### Local Setup

1. Install dependencies:
   ```bash
   composer install
   ```

2. Set up database:
   ```bash
   createdb alumni_portal
   psql alumni_portal < database/schema.sql
   ```

3. Start development server:
   ```bash
   docker-compose up
   ```

### Running Tests

```bash
# Run PHP tests
./vendor/bin/phpunit

# Test API endpoints
./test_all_features.ps1
```

## 🤝 Contributing

This is a template repository. To use it:

1. Click "Use this template" on GitHub
2. Clone your new repository
3. Customize for your institution
4. Deploy to AWS!

## 📝 Customization

### Branding
- Update `includes/header.php` with your logo
- Modify color scheme in Tailwind classes
- Change institution name throughout

### Features
- Add/remove API endpoints in `api/`
- Customize pages in root directory
- Modify database schema in `database/`

## 🐛 Troubleshooting

### Database Connection Issues
```bash
# Test RDS connectivity
nc -zv <rds-endpoint> 5432
```

### ECS Tasks Not Starting
```bash
# Check task logs
aws ecs describe-tasks --cluster alumni-portal-cluster --tasks <task-id>
```

### ALB Health Checks Failing
```bash
# Verify health endpoint
curl http://<alb-dns>/live.php
```

See [AWS_DEPLOYMENT.md](AWS_DEPLOYMENT.md) for more troubleshooting.

## 📚 Documentation

- [Quick Start Guide](QUICKSTART_AWS.md)
- [AWS Deployment Guide](AWS_DEPLOYMENT.md)
- [Original Deployment Guide](DEPLOYMENT.md)

## 🔗 Useful Links

- [AWS Documentation](https://docs.aws.amazon.com/)
- [Terraform AWS Provider](https://registry.terraform.io/providers/hashicorp/aws/latest/docs)
- [Docker Documentation](https://docs.docker.com/)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)

## 📄 License

This is a template project. Feel free to use it for your institution's alumni portal.

## 🙏 Acknowledgments

Built for RJIT (Rajiv Gandhi Institute of Technology) Alumni Portal.

## 📧 Support

For issues or questions:
- Open an issue on GitHub
- Check documentation in `AWS_DEPLOYMENT.md`
- Review CloudWatch logs for errors

---

**Made with ❤️ for educational institutions worldwide**

🌟 **Star this repo if you find it useful!**
