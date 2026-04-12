$ErrorActionPreference = "Stop"

$baseUrl = "http://localhost/alumni_portal"
$apiUrl = "$baseUrl/api"

Write-Host "=== Alumni Portal Local Verification ===" -ForegroundColor Cyan

# 1) DB connectivity
Write-Host "`n[1/4] Checking DB connectivity..." -ForegroundColor Yellow
try {
    $dbResult = & "C:\xampp\php\php.exe" ".\db_test.php"
    Write-Host $dbResult -ForegroundColor Green
} catch {
    Write-Host "DB check failed: $($_.Exception.Message)" -ForegroundColor Red
}

# 2) Public endpoints
Write-Host "`n[2/4] Checking public endpoints..." -ForegroundColor Yellow
$publicEndpoints = @(
    "get_jobs.php",
    "get_active_streams.php",
    "get_events.php"
)

foreach ($ep in $publicEndpoints) {
    try {
        $resp = Invoke-WebRequest -Uri "$apiUrl/$ep" -UseBasicParsing -TimeoutSec 8
        if ($resp.Content -match "Fatal error|Uncaught|Parse error") {
            Write-Host "$ep => runtime fatal detected" -ForegroundColor Red
        } else {
            Write-Host "$ep => $($resp.StatusCode)" -ForegroundColor Green
        }
    } catch {
        Write-Host "$ep => FAILED ($($_.Exception.Message))" -ForegroundColor Red
    }
}

# 3) Full API fatal sweep (no token)
Write-Host "`n[3/4] Sweeping all API endpoints for runtime fatals..." -ForegroundColor Yellow
$fatalHits = @()
$statusCount = @{}

Get-ChildItem ".\api" -Filter "*.php" | ForEach-Object {
    $fileName = $_.Name
    $url = "$apiUrl/$fileName"
    try {
        $resp = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 8
        $status = [int]$resp.StatusCode
        if ($resp.Content -match "Fatal error|Uncaught|Parse error") {
            $fatalHits += $fileName
        }
    } catch {
        if ($_.Exception.Response) {
            $status = [int]$_.Exception.Response.StatusCode.value__
        } else {
            $status = -1
        }
    }
    if (-not $statusCount.ContainsKey($status)) { $statusCount[$status] = 0 }
    $statusCount[$status]++
}

if ($fatalHits.Count -eq 0) {
    Write-Host "No runtime fatals detected in GET sweep." -ForegroundColor Green
} else {
    Write-Host "Runtime fatal endpoints: $($fatalHits -join ', ')" -ForegroundColor Red
}

Write-Host "Status distribution:" -ForegroundColor Gray
$statusCount.Keys | Sort-Object | ForEach-Object {
    Write-Host "  $_ => $($statusCount[$_])" -ForegroundColor Gray
}

# 4) Optional auth check
Write-Host "`n[4/4] Optional auth flow check..." -ForegroundColor Yellow
$testEmail = $env:TEST_EMAIL
$testPassword = $env:TEST_PASSWORD

if ([string]::IsNullOrWhiteSpace($testEmail) -or [string]::IsNullOrWhiteSpace($testPassword)) {
    Write-Host "Skipped. Set TEST_EMAIL and TEST_PASSWORD to run auth checks." -ForegroundColor DarkYellow
} else {
    try {
        $loginPayload = @{
            email = $testEmail
            password = $testPassword
        } | ConvertTo-Json

        $loginResp = Invoke-RestMethod -Uri "$apiUrl/login.php" -Method POST -Body $loginPayload -ContentType "application/json"
        if (-not $loginResp.token) {
            throw "Login returned no token."
        }

        $headers = @{ Authorization = "Bearer $($loginResp.token)" }
        $meResp = Invoke-RestMethod -Uri "$apiUrl/me.php" -Method GET -Headers $headers
        Write-Host "Auth success for user ID: $($meResp.user.user_id)" -ForegroundColor Green
    } catch {
        Write-Host "Auth check failed: $($_.Exception.Message)" -ForegroundColor Red
    }
}

Write-Host "`nVerification completed." -ForegroundColor Cyan
