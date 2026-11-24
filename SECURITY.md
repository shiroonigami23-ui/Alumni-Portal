# Security Policy

The RJIT Alumni Portal holds personal information about students, alumni and faculty, so we take security reports seriously and respond quickly.

## Supported versions

| Version | Supported |
|---|---|
| `main` (2.x) | ✅ Security fixes |
| 1.x (before the router/security rework) | ❌ Please upgrade |

## Reporting a vulnerability

**Please do not open a public GitHub issue for security problems.**

1. Use GitHub's **[private vulnerability reporting](https://github.com/shiroonigami23-ui/Alumni-Portal/security/advisories/new)** (Security tab → *Report a vulnerability*), or
2. Email **shiroonigami23@gmail.com** with the subject `SECURITY: Alumni Portal`.

Please include:

- What you found and where (URL / endpoint / file)
- Steps to reproduce, with a minimal proof of concept
- The impact you believe it has
- Whether any real user data was accessed (please stop and tell us if so)

### What to expect

| Step | Target time |
|---|---|
| Acknowledgement | within **48 hours** |
| Initial assessment & severity | within **5 days** |
| Fix for critical / high issues | within **14 days** |
| Public credit (if you want it) | in the release notes / CHANGELOG |

## Safe-harbour

We will not pursue action against good-faith research that:

- stays within your own test accounts or a local copy of the project,
- does not access, modify or delete other people's data,
- does not degrade the service (no load / DoS testing on the live site),
- does not use social engineering or physical attacks,
- gives us reasonable time to fix before disclosure.

## In scope

- This repository's code (PHP pages, `api/`, `admin/`, `router.php`, `bootstrap/`, `middleware/`)
- The production deployment of the portal
- Infrastructure-as-code in `terraform/` and `deployment/`

## Out of scope

- Findings that need a rooted device, a compromised browser, or physical access
- Missing headers on third-party CDNs we don't control
- Rate-limit thresholds that are merely "too generous" without a concrete impact
- Self-XSS that requires the victim to paste code into their own console
- Reports generated only by automated scanners without a working proof

## How the platform protects users

A summary of the controls is in the [README Security section](README.md#-security). The core lives in:

- `router.php` — only routed pages, `api/*.php` and `admin/*.php` can execute
- `bootstrap/security.php` — headers & CSP, CORS allowlist, rate limiting, login lockout, upload inspection, error scrubbing
- `bootstrap/static_policy.php` + `.htaccess` + `storage/.htaccess` — what may be served, and that uploads can never execute

## For maintainers

- Never commit `.env`, credentials, database dumps or anything from `storage/` (see `.gitignore`).
- Keep `APP_DEBUG=false` in production.
- Put the ALB behind HTTPS (ACM) — HSTS and `Secure` cookies turn on automatically.
- Rotate the DB password and AWS keys if they were ever exposed in history.
- Review `composer` dependencies regularly (`composer audit`).
