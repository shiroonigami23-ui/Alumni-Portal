# Security Policy

The RJIT Alumni Portal holds personal information about students, alumni and faculty, so we take security reports seriously and respond quickly.

## Supported versions

| Version | Supported |
|---|---|
| `main` (2.3+) | ✅ Security fixes |
| 2.0 – 2.2 | ⚠️ Please upgrade: 2.3 fixes an unguarded ban endpoint and stored XSS |
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
- `bootstrap/security.php` also: numeric-id check on every API parameter, oversized-upload messages, image-bomb refusal, lossless EXIF/GPS stripping
- `api/_kernel.php` — the one front door: every endpoint declares its access level (`public`, `member`, `staff`, `admin`, `moderator` or a role list) and the kernel enforces it before the endpoint runs; safe error messages with reference codes
- `api/_privacy.php` — who may see which profile, post, comment and contact detail (private accounts, follow requests, blocks)
- `api/_usernames.php` — case-insensitive unique @handles and e-mails, enforced by the database
- `assets/js/safe.js` — `escapeHtml`, `safeUrl`, `linkifyText`: the only way user text reaches the page

### Automated attack tests (run in CI on every push)

| Suite | What it attacks |
|---|---|
| `tests/static/api_contract.php` | Every endpoint declares its access level before touching input or the database; no raw exception text in responses |
| `tests/static/frontend_contract.py` | Every user value in page templates is escaped; no user text in `onclick` strings |
| `tests/integration/authz.php` | A student, an alumnus and a faculty member attack every endpoint above their level (targets read from the declarations); refused **and** database unchanged |
| `tests/integration/sqli_fuzz.php` | 2,744 SQL-injection requests across 98 endpoints, including time-based blind payloads |
| `tests/ui/xss_sweep.py` | Payloads in every writable field (plus raw legacy rows), 37 pages opened in Chrome |
| `tests/integration/security_limits.php` | Brute-force login lockout |

A new endpoint is covered automatically: the contract makes it declare its access level, the authorization sweep attacks it if that level is above `member`, and the fuzz sends it injection payloads.

## For maintainers

- Never commit `.env`, credentials, database dumps or anything from `storage/` (see `.gitignore`).
- Keep `APP_DEBUG=false` in production.
- Put the ALB behind HTTPS (ACM) — HSTS and `Secure` cookies turn on automatically.
- Rotate the DB password and AWS keys if they were ever exposed in history.
- Review `composer` dependencies regularly (`composer audit`).
