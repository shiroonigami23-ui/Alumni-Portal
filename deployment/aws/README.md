# deployment/aws

This folder contains AWS-specific Docker helpers for this project.

Use root-level docs for full deployment instructions:

- `README.md`
- `QUICKSTART_AWS.md`
- `AWS_DEPLOYMENT.md`
- `POSTGRES_SETUP.md`

## Local container test

From this folder:

```bash
docker compose up --build
```

Then open:

- `http://localhost:8080` (or mapped port from compose file)

Stop:

```bash
docker compose down
```

## Notes

- This folder is not the complete deployment flow by itself.
- Production flow uses Terraform in `terraform/` plus scripts in `deployment/`.
