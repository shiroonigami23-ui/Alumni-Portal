# Test Records

Use the matrix runner to keep timestamped records of smoke/integration/system/user/bulk checks.

## Run

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\run_test_matrix.ps1
```

Optional (skip bulk):

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\run_test_matrix.ps1 -SkipBulk
```

## Output

- Per-run folder: `storage/test_reports/YYYYMMDD_HHMMSS/`
- Per-test logs: `smoke.log`, `integration.log`, `system.log`, `user.log`, `bulk.log`, `local.log`
- Summary JSON: `storage/test_reports/YYYYMMDD_HHMMSS/summary.json`
- Latest pointer: `storage/test_reports/latest.json`

This keeps an auditable history of test outcomes over time.

