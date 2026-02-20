# AWS Quickstart (Copy-Paste Friendly)

Use this when you want the fastest working deploy.

## 0. Tools required

- AWS CLI configured (`aws configure`)
- Terraform
- Docker
- Git
- PostgreSQL client (`psql`, `pg_dump`, `pg_restore`)

## 1. Clone repo and go to project

```bash
git clone https://github.com/shiroonigami23-ui/Alumni-Portal.git
cd Alumni-Portal
```

## 2. Configure Terraform variables

```bash
cd terraform
cp terraform.tfvars.example terraform.tfvars
```

Edit `terraform.tfvars` and set at least:

- `db_password`
- `aws_region`
- desired sizes/counts for dev or prod

## 3. Deploy infrastructure

```bash
terraform init
terraform plan
terraform apply
```

Save outputs:

```bash
terraform output
```

## 4. Build and push Docker image

```bash
cd ..
./deployment/deploy-aws.sh
```

If script prompts options, choose full deploy or app-only as needed.

## 5. Migrate database to RDS

```bash
./deployment/migrate-to-rds.sh
```

## 6. Run required SQL migrations on RDS

```bash
psql -h <RDS_ENDPOINT> -U admin -d alumni_portal -f deployment/sql/2026_02_20_create_mentorship_requests.sql
```

## 7. Set runtime environment variables in ECS task/service

Required:

- `DB_HOST`
- `DB_PORT=5432`
- `DB_NAME=alumni_portal`
- `DB_USER`
- `DB_PASSWORD`
- `AWS_REGION`
- `AWS_BUCKET`

Optional app env:

- `APP_ENV=production`
- `APP_DEBUG=false`

## 8. Get application URL

```bash
cd terraform
terraform output -raw alb_dns_name
```

Open:

- `http://<alb_dns_name>/`

## 9. Verify health

```bash
curl http://<alb_dns_name>/live.php
```

Expected: healthy response.

## 10. If deployment fails

- Check ECS task logs (CloudWatch)
- Check DB security group (5432)
- Check ALB health check path
- Check DB env vars in ECS

For full details: `AWS_DEPLOYMENT.md`
