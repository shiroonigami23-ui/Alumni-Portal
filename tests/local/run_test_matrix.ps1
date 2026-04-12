param(
    [switch]$SkipBulk = $false
)

$ErrorActionPreference = "Continue"

$root = Split-Path -Parent $PSScriptRoot
$stamp = Get-Date -Format "yyyyMMdd_HHmmss"
$reportRoot = Join-Path $root "storage\test_reports"
$runDir = Join-Path $reportRoot $stamp
New-Item -ItemType Directory -Path $runDir -Force | Out-Null

$tests = @(
    @{ name = "smoke"; script = "test_smoke.ps1"; type = "smoke" },
    @{ name = "integration"; script = "test_new_endpoints.ps1"; type = "integration" },
    @{ name = "system"; script = "test_all_features.ps1"; type = "system" },
    @{ name = "user"; script = "test_fixes.ps1"; type = "user" },
    @{ name = "bulk"; script = "verify_feature_matrix.ps1"; type = "bulk" },
    @{ name = "local"; script = "verify_local.ps1"; type = "system" }
)

if ($SkipBulk) {
    $tests = $tests | Where-Object { $_.name -ne "bulk" }
}

$results = @()

foreach ($t in $tests) {
    $scriptPath = Join-Path $root $t.script
    $outFile = Join-Path $runDir ($t.name + ".log")

    if (!(Test-Path $scriptPath)) {
        $results += [pscustomobject]@{
            name = $t.name
            type = $t.type
            script = $t.script
            status = "missing"
            exit_code = -1
            log = (Split-Path $outFile -Leaf)
            started_at = (Get-Date).ToString("o")
            ended_at = (Get-Date).ToString("o")
        }
        continue
    }

    $start = Get-Date
    & powershell -ExecutionPolicy Bypass -File $scriptPath *>&1 | Tee-Object -FilePath $outFile
    $exitCode = $LASTEXITCODE
    $end = Get-Date

    $results += [pscustomobject]@{
        name = $t.name
        type = $t.type
        script = $t.script
        status = ($(if ($exitCode -eq 0) { "pass" } else { "fail" }))
        exit_code = $exitCode
        log = (Split-Path $outFile -Leaf)
        started_at = $start.ToString("o")
        ended_at = $end.ToString("o")
    }
}

$summary = [pscustomobject]@{
    generated_at = (Get-Date).ToString("o")
    run_id = $stamp
    counts = [pscustomobject]@{
        total = ($results | Measure-Object).Count
        pass = ($results | Where-Object { $_.status -eq "pass" } | Measure-Object).Count
        fail = ($results | Where-Object { $_.status -eq "fail" } | Measure-Object).Count
        missing = ($results | Where-Object { $_.status -eq "missing" } | Measure-Object).Count
    }
    tests = $results
}

$summaryFile = Join-Path $runDir "summary.json"
$summary | ConvertTo-Json -Depth 6 | Out-File -FilePath $summaryFile -Encoding utf8

$latestFile = Join-Path $reportRoot "latest.json"
$summary | ConvertTo-Json -Depth 6 | Out-File -FilePath $latestFile -Encoding utf8

Write-Host "TEST_MATRIX_DONE: $summaryFile"

