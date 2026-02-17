#!/bin/bash

# AWS Deployment Script for Alumni Portal
# This script helps deploy the application to AWS

set -e

echo "🚀 Alumni Portal AWS Deployment Script"
echo "========================================"

# Check if AWS CLI is installed
if ! command -v aws &> /dev/null; then
    echo "❌ AWS CLI not found. Please install it first."
    exit 1
fi

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker not found. Please install it first."
    exit 1
fi

# Check if Terraform is installed
if ! command -v terraform &> /dev/null; then
    echo "❌ Terraform not found. Please install it first."
    exit 1
fi

# Get AWS account ID
AWS_ACCOUNT_ID=$(aws sts get-caller-identity --query Account --output text)
AWS_REGION=${AWS_REGION:-us-east-1}
PROJECT_NAME="alumni-portal"

echo "📋 Configuration:"
echo "  AWS Account: $AWS_ACCOUNT_ID"
echo "  AWS Region: $AWS_REGION"
echo "  Project: $PROJECT_NAME"
echo ""

# Function to deploy infrastructure
deploy_infrastructure() {
    echo "🏗️  Deploying infrastructure with Terraform..."
    cd terraform
    
    # Initialize Terraform
    terraform init
    
    # Plan
    terraform plan -out=tfplan
    
    # Apply
    read -p "Apply Terraform plan? (yes/no): " confirm
    if [ "$confirm" == "yes" ]; then
        terraform apply tfplan
        echo "✅ Infrastructure deployed successfully!"
    else
        echo "❌ Deployment cancelled."
        exit 1
    fi
    
    cd ..
}

# Function to build and push Docker image
build_and_push() {
    echo "🐳 Building and pushing Docker image..."
    
    # Get ECR repository URL
    ECR_REPO=$(terraform -chdir=terraform output -raw ecr_repository_url)
    
    # Login to ECR
    aws ecr get-login-password --region $AWS_REGION | docker login --username AWS --password-stdin $ECR_REPO
    
    # Build image
    docker build -t $PROJECT_NAME:latest .
    
    # Tag image
    docker tag $PROJECT_NAME:latest $ECR_REPO:latest
    docker tag $PROJECT_NAME:latest $ECR_REPO:$(git rev-parse --short HEAD)
    
    # Push image
    docker push $ECR_REPO:latest
    docker push $ECR_REPO:$(git rev-parse --short HEAD)
    
    echo "✅ Docker image pushed successfully!"
}

# Function to update ECS service
update_service() {
    echo "🔄 Updating ECS service..."
    
    CLUSTER_NAME=$(terraform -chdir=terraform output -raw ecs_cluster_name)
    SERVICE_NAME=$(terraform -chdir=terraform output -raw ecs_service_name)
    
    aws ecs update-service \
        --cluster $CLUSTER_NAME \
        --service $SERVICE_NAME \
        --force-new-deployment \
        --region $AWS_REGION
    
    echo "✅ ECS service updated successfully!"
}

# Function to run database migrations
run_migrations() {
    echo "🗄️  Running database migrations..."
    
    # Get RDS endpoint
    RDS_ENDPOINT=$(terraform -chdir=terraform output -raw rds_address)
    
    echo "  RDS Endpoint: $RDS_ENDPOINT"
    echo "  Note: Run migrations manually or through ECS task"
    
    # TODO: Implement migration task
}

# Main menu
echo "Select deployment option:"
echo "1) Full deployment (Infrastructure + Application)"
echo "2) Deploy infrastructure only"
echo "3) Build and deploy application only"
echo "4) Update ECS service"
echo "5) Run database migrations"
read -p "Enter choice [1-5]: " choice

case $choice in
    1)
        deploy_infrastructure
        build_and_push
        update_service
        run_migrations
        ;;
    2)
        deploy_infrastructure
        ;;
    3)
        build_and_push
        update_service
        ;;
    4)
        update_service
        ;;
    5)
        run_migrations
        ;;
    *)
        echo "❌ Invalid choice"
        exit 1
        ;;
esac

echo ""
echo "🎉 Deployment completed!"
echo ""
echo "📊 Access your application:"
ALB_DNS=$(terraform -chdir=terraform output -raw alb_dns_name)
echo "  Load Balancer: http://$ALB_DNS"
echo ""
echo "📝 Next steps:"
echo "  1. Configure your domain DNS to point to the load balancer"
echo "  2. Set up SSL certificate in ACM"
echo "  3. Update ALB listener to use HTTPS"
echo "  4. Configure CloudWatch alarms"
