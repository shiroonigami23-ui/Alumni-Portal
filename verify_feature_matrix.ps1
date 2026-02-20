$ErrorActionPreference = "Stop"

$baseUrl = "http://localhost/alumni_portal"
$apiUrl = "$baseUrl/api"
$psql = "C:\xampp\pgsql\pgsql\bin\psql.exe"
$php = "C:\xampp\php\php.exe"
$curl = "curl.exe"

$results = @()

function Add-Result {
    param([string]$Feature, [bool]$Passed, [string]$Detail)
    $script:results += [pscustomobject]@{
        feature = $Feature
        status  = if ($Passed) { "PASS" } else { "FAIL" }
        detail  = $Detail
    }
}

function Run-Step {
    param([string]$Feature, [scriptblock]$Action)
    try {
        $detail = & $Action
        Add-Result $Feature $true ([string]$detail)
    } catch {
        Add-Result $Feature $false $_.Exception.Message
    }
}

function Assert-True {
    param([bool]$Condition, [string]$Message)
    if (-not $Condition) { throw $Message }
}

function Invoke-PsqlScalar {
    param([string]$Sql)
    $out = & $psql -h 127.0.0.1 -U postgres -d alumni_portal -At -c $Sql 2>$null
    if ($LASTEXITCODE -ne 0) { throw "psql failed" }
    if ($out -is [array]) { return ($out | Select-Object -First 1).Trim() }
    return ([string]$out).Trim()
}

function Invoke-PsqlNonQuery {
    param([string]$Sql)
    & $psql -h 127.0.0.1 -U postgres -d alumni_portal -v ON_ERROR_STOP=1 -c $Sql 1>$null
    if ($LASTEXITCODE -ne 0) { throw "psql non-query failed" }
}

function Login-User {
    param([string]$Email, [string]$Password)
    $body = @{ email = $Email; password = $Password } | ConvertTo-Json
    $resp = Invoke-RestMethod -Uri "$apiUrl/login.php" -Method POST -Body $body -ContentType "application/json" -SessionVariable ws
    Assert-True ($resp.token -ne $null) "No token in login response for $Email"
    return [pscustomobject]@{
        token   = [string]$resp.token
        csrf    = [string]$resp.csrf_token
        user_id = [int64]$resp.user_id
        role    = [string]$resp.role
        web     = $ws
    }
}

function Auth-Headers {
    param([string]$Token, [string]$Csrf = $null)
    $h = @{ Authorization = "Bearer $Token" }
    if ($Csrf) { $h["X-CSRF-TOKEN"] = $Csrf }
    return $h
}

Write-Host "=== Full Feature Matrix Verification ===" -ForegroundColor Cyan

$ctx = [ordered]@{}

Run-Step "DB Connection" {
    $dbMsg = & $php ".\db_test.php"
    Assert-True ($dbMsg -match "Connected") $dbMsg
    return $dbMsg
}

Run-Step "Seed Placeholder Users" {
    $ctx.password = "QaPass@123"
    $hash = (& $php -r "echo password_hash('QaPass@123', PASSWORD_BCRYPT);").Trim().Replace("'", "''")
    $ctx.studentEmail = "qa_student@rjit.ac.in"
    $ctx.alumniEmail = "qa_alumni@rjit.ac.in"
    $ctx.adminEmail = "qa_admin@rjit.ac.in"

    $ctx.studentId = [int64](Invoke-PsqlScalar "INSERT INTO users (email,password_hash,role,status,can_post,email_verified) VALUES ('$($ctx.studentEmail)','$hash','student','active',true,true) ON CONFLICT (email) DO UPDATE SET password_hash=EXCLUDED.password_hash, role='student', status='active', can_post=true, email_verified=true RETURNING user_id;")
    $ctx.alumniId = [int64](Invoke-PsqlScalar "INSERT INTO users (email,password_hash,role,status,can_post,email_verified) VALUES ('$($ctx.alumniEmail)','$hash','alumni','active',true,true) ON CONFLICT (email) DO UPDATE SET password_hash=EXCLUDED.password_hash, role='alumni', status='active', can_post=true, email_verified=true RETURNING user_id;")
    $ctx.adminId = [int64](Invoke-PsqlScalar "INSERT INTO users (email,password_hash,role,status,can_post,email_verified) VALUES ('$($ctx.adminEmail)','$hash','admin','active',true,true) ON CONFLICT (email) DO UPDATE SET password_hash=EXCLUDED.password_hash, role='admin', status='active', can_post=true, email_verified=true RETURNING user_id;")

    Invoke-PsqlNonQuery "INSERT INTO profiles (user_id, full_name, graduation_year, branch, department, is_private) VALUES ($($ctx.studentId), 'QA Student', 2026, 'CSE', 'Computer Science', false) ON CONFLICT (user_id) DO UPDATE SET full_name=EXCLUDED.full_name, graduation_year=EXCLUDED.graduation_year, branch=EXCLUDED.branch, department=EXCLUDED.department, is_private=false;"
    Invoke-PsqlNonQuery "INSERT INTO profiles (user_id, full_name, graduation_year, branch, department, is_private) VALUES ($($ctx.alumniId), 'QA Alumni', 2020, 'CSE', 'Engineering', false) ON CONFLICT (user_id) DO UPDATE SET full_name=EXCLUDED.full_name, graduation_year=EXCLUDED.graduation_year, branch=EXCLUDED.branch, department=EXCLUDED.department, is_private=false;"
    Invoke-PsqlNonQuery "INSERT INTO profiles (user_id, full_name, graduation_year, branch, department, is_private) VALUES ($($ctx.adminId), 'QA Admin', 2018, 'CSE', 'Administration', false) ON CONFLICT (user_id) DO UPDATE SET full_name=EXCLUDED.full_name, graduation_year=EXCLUDED.graduation_year, branch=EXCLUDED.branch, department=EXCLUDED.department, is_private=false;"
    return "student=$($ctx.studentId) alumni=$($ctx.alumniId) admin=$($ctx.adminId)"
}

Run-Step "Ensure Mentorship Schema" {
    Invoke-PsqlNonQuery @"
CREATE TABLE IF NOT EXISTS mentorship_requests (
    request_id BIGSERIAL PRIMARY KEY,
    mentee_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    mentor_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'accepted', 'rejected')),
    created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (mentee_id, mentor_id)
);
"@

    Invoke-PsqlNonQuery "CREATE INDEX IF NOT EXISTS idx_mentorship_requests_mentor_status ON mentorship_requests(mentor_id, status);"
    Invoke-PsqlNonQuery "CREATE INDEX IF NOT EXISTS idx_mentorship_requests_mentee ON mentorship_requests(mentee_id);"
    return "mentorship_requests ready"
}

Run-Step "Login (student/alumni/admin)" {
    $ctx.student = Login-User -Email $ctx.studentEmail -Password $ctx.password
    $ctx.alumni = Login-User -Email $ctx.alumniEmail -Password $ctx.password
    $ctx.admin = Login-User -Email $ctx.adminEmail -Password $ctx.password
    return "roles=$($ctx.student.role),$($ctx.alumni.role),$($ctx.admin.role)"
}

Run-Step "me.php (student)" {
    $resp = Invoke-RestMethod -Uri "$apiUrl/me.php" -Method GET -Headers (Auth-Headers -Token $ctx.student.token) -WebSession $ctx.student.web
    Assert-True ($resp.success -eq $true) "me.php did not return success"
    return "user_id=$($resp.data.user_id)"
}

Run-Step "update_profile.php" {
    $body = @{ full_name = "QA Student Updated"; bio = "Matrix test"; skills = "php,sql"; tech_stack = "php,postgres" } | ConvertTo-Json
    $resp = Invoke-RestMethod -Uri "$apiUrl/update_profile.php" -Method POST -Body $body -ContentType "application/json" -Headers (Auth-Headers -Token $ctx.student.token) -WebSession $ctx.student.web
    Assert-True ($resp.message -match "updated") "Profile update failed"
    return $resp.message
}

Run-Step "get_user_profile.php" {
    $resp = Invoke-RestMethod -Uri "$apiUrl/get_user_profile.php?user_id=$($ctx.student.user_id)" -Method GET -Headers (Auth-Headers -Token $ctx.alumni.token) -WebSession $ctx.alumni.web
    Assert-True ($resp.user_id -eq $ctx.student.user_id) "Profile user mismatch"
    return "profile fetched"
}

Run-Step "create_post.php" {
    $ctx.postTitle = "QA Post $(Get-Date -Format 'yyyyMMddHHmmss')"
    $body = @{ title = $ctx.postTitle; content = "Placeholder post content."; post_type = "text" } | ConvertTo-Json
    $resp = Invoke-RestMethod -Uri "$apiUrl/create_post.php" -Method POST -Body $body -ContentType "application/json" -Headers (Auth-Headers -Token $ctx.student.token -Csrf $ctx.student.csrf) -WebSession $ctx.student.web
    $ctx.postId = [int64]$resp.post_id
    Assert-True ($resp.status -eq "success" -and $ctx.postId -gt 0) "Post create failed"
    return "post_id=$($ctx.postId)"
}

Run-Step "get_feed.php" {
    $feed = Invoke-RestMethod -Uri "$apiUrl/get_feed.php" -Method GET -Headers (Auth-Headers -Token $ctx.student.token) -WebSession $ctx.student.web
    $hit = $false
    foreach ($p in $feed) { if ($p.post_id -eq $ctx.postId) { $hit = $true; break } }
    Assert-True $hit "Post not found in feed"
    return "post visible"
}

Run-Step "react_to_post.php" {
    $body = @{ post_id = $ctx.postId } | ConvertTo-Json
    $resp = Invoke-RestMethod -Uri "$apiUrl/react_to_post.php" -Method POST -Body $body -ContentType "application/json" -Headers (Auth-Headers -Token $ctx.alumni.token) -WebSession $ctx.alumni.web
    Assert-True ($resp.message -match "liked|already liked") "Reaction failed"
    return $resp.message
}

Run-Step "create_comment.php" {
    $ctx.commentText = "QA comment $(Get-Date -Format 'HHmmss')"
    $body = @{ post_id = $ctx.postId; content = $ctx.commentText } | ConvertTo-Json
    $resp = Invoke-RestMethod -Uri "$apiUrl/create_comment.php" -Method POST -Body $body -ContentType "application/json" -Headers (Auth-Headers -Token $ctx.alumni.token) -WebSession $ctx.alumni.web
    Assert-True ($resp.message -match "Comment added") "Comment create failed"
    return $resp.message
}

Run-Step "get_comments.php" {
    $resp = Invoke-RestMethod -Uri "$apiUrl/get_comments.php?post_id=$($ctx.postId)" -Method GET
    $found = $false
    foreach ($c in $resp.comments) { if ($c.text -eq $ctx.commentText) { $found = $true; break } }
    Assert-True $found "Comment text not found"
    return "comment hydrated"
}

Run-Step "pin_post.php + unpin_post.php" {
    $body = @{ post_id = $ctx.postId } | ConvertTo-Json
    $pin = Invoke-RestMethod -Uri "$apiUrl/pin_post.php" -Method POST -Body $body -ContentType "application/json" -Headers (Auth-Headers -Token $ctx.student.token) -WebSession $ctx.student.web
    $unpin = Invoke-RestMethod -Uri "$apiUrl/unpin_post.php" -Method POST -Body $body -ContentType "application/json" -Headers (Auth-Headers -Token $ctx.student.token) -WebSession $ctx.student.web
    Assert-True (($pin.message -match "pinned|already") -and ($unpin.message -match "unpinned|not found")) "Pin/unpin failed"
    return "pin/unpin ok"
}

Run-Step "send_message.php + get_messages.php + get_inbox.php" {
    $ctx.msgText = "QA msg $(Get-Date -Format 'HHmmss')"
    $msgBody = @{ receiver_id = $ctx.alumni.user_id; message = $ctx.msgText } | ConvertTo-Json
    $send = Invoke-RestMethod -Uri "$apiUrl/send_message.php" -Method POST -Body $msgBody -ContentType "application/json" -Headers (Auth-Headers -Token $ctx.student.token) -WebSession $ctx.student.web
    Assert-True ($send.message -match "delivered") "send_message failed"

    $history = Invoke-RestMethod -Uri "$apiUrl/get_messages.php?contact_id=$($ctx.student.user_id)" -Method GET -Headers (Auth-Headers -Token $ctx.alumni.token) -WebSession $ctx.alumni.web
    $found = $false
    foreach ($m in $history) { if ($m.message -eq $ctx.msgText) { $found = $true; break } }
    Assert-True $found "Message not found in history"

    $inbox = Invoke-RestMethod -Uri "$apiUrl/get_inbox.php" -Method GET -Headers (Auth-Headers -Token $ctx.alumni.token) -WebSession $ctx.alumni.web
    Assert-True ($inbox.count -ge 1) "Inbox empty"
    return "history+inbox ok"
}

Run-Step "events.php create/list + rsvp_event.php" {
    $tomorrow = (Get-Date).AddDays(1).ToString("yyyy-MM-dd")
    $evBody = @{
        title = "QA Event $(Get-Date -Format 'yyyyMMddHHmmss')"
        description = "Event description"
        event_date = $tomorrow
        event_time = "10:30:00"
        location = "Auditorium"
        visibility = "public"
        rsvp_limit = 100
    } | ConvertTo-Json
    # Use admin creator so event is immediately approved and visible in upcoming list.
    $create = Invoke-RestMethod -Uri "$apiUrl/events.php?action=create" -Method POST -Body $evBody -ContentType "application/json" -Headers (Auth-Headers -Token $ctx.admin.token -Csrf $ctx.admin.csrf) -WebSession $ctx.admin.web
    $ctx.eventId = [int64]$create.event_id
    Assert-True ($ctx.eventId -gt 0) "Event create failed"

    $list = Invoke-RestMethod -Uri "$apiUrl/events.php?action=list&filter=upcoming&limit=20" -Method GET -Headers (Auth-Headers -Token $ctx.student.token) -WebSession $ctx.student.web
    $found = $false
    foreach ($e in $list) { if ($e.event_id -eq $ctx.eventId) { $found = $true; break } }
    Assert-True $found "Event not in list"

    $rsvpBody = @{ event_id = $ctx.eventId; status = "attending" } | ConvertTo-Json
    $rsvp = Invoke-RestMethod -Uri "$apiUrl/rsvp_event.php" -Method POST -Body $rsvpBody -ContentType "application/json" -Headers (Auth-Headers -Token $ctx.student.token) -WebSession $ctx.student.web
    Assert-True ($rsvp.message -match "RSVP updated") "RSVP failed"
    return "event_id=$($ctx.eventId)"
}

Run-Step "post_job.php + get_jobs.php + apply_job.php + get_applicants.php" {
    $ctx.jobTitle = "QA Job $(Get-Date -Format 'yyyyMMddHHmmss')"
    $jobBody = @{
        company_name = "QA Company"
        job_title = $ctx.jobTitle
        description = "Job description placeholder"
        location = "Remote"
        salary_range = "10-12 LPA"
        application_url = "https://example.com/apply"
        job_type = "full-time"
    } | ConvertTo-Json
    $post = Invoke-RestMethod -Uri "$apiUrl/post_job.php" -Method POST -Body $jobBody -ContentType "application/json" -Headers (Auth-Headers -Token $ctx.alumni.token) -WebSession $ctx.alumni.web
    Assert-True ($post.message -match "posted") "post_job failed"

    $jobs = Invoke-RestMethod -Uri "$apiUrl/get_jobs.php" -Method GET
    Assert-True ($jobs.count -ge 1) "get_jobs returned empty"
    $ctx.jobId = [int64](Invoke-PsqlScalar "SELECT job_id FROM jobs WHERE job_title = '$($ctx.jobTitle)' ORDER BY created_at DESC LIMIT 1;")
    Assert-True ($ctx.jobId -gt 0) "job_id lookup failed"

    $resumePath = Join-Path (Get-Location) "test_doc.pdf"
    $applyRaw = & $curl -s -X POST -H "Authorization: Bearer $($ctx.student.token)" -F "job_id=$($ctx.jobId)" -F "cover_letter=QA cover letter" -F "resume=@$resumePath" "$apiUrl/apply_job.php"
    $apply = $applyRaw | ConvertFrom-Json
    Assert-True ($apply.message -match "submitted|already applied") "apply_job failed: $applyRaw"

    $apps = Invoke-RestMethod -Uri "$apiUrl/get_applicants.php?job_id=$($ctx.jobId)" -Method GET -Headers (Auth-Headers -Token $ctx.alumni.token) -WebSession $ctx.alumni.web
    Assert-True ($apps.count -ge 1) "No applicants returned"
    return "job_id=$($ctx.jobId) applicants=$($apps.count)"
}

Run-Step "mentorship request/list/respond" {
    Invoke-PsqlNonQuery "DELETE FROM mentorship_requests WHERE mentee_id = $($ctx.student.user_id) AND mentor_id = $($ctx.alumni.user_id);"
    $reqBody = @{ mentor_id = $ctx.alumni.user_id; message = "Need mentorship." } | ConvertTo-Json
    $req = Invoke-RestMethod -Uri "$apiUrl/mentorship.php?action=request" -Method POST -Body $reqBody -ContentType "application/json" -Headers (Auth-Headers -Token $ctx.student.token) -WebSession $ctx.student.web
    Assert-True ($req.status -eq $true -or $req.message -match "already pending") "Mentorship request failed"

    $list = Invoke-RestMethod -Uri "$apiUrl/mentorship.php?action=list_requests" -Method POST -Headers (Auth-Headers -Token $ctx.alumni.token) -WebSession $ctx.alumni.web
    Assert-True ($list.Count -ge 1) "No mentorship requests listed"
    $reqId = [int64]$list[0].request_id
    Assert-True ($reqId -gt 0) "Invalid request id"

    $respBody = @{ request_id = $reqId; status = "accepted" } | ConvertTo-Json
    $resp = Invoke-RestMethod -Uri "$apiUrl/mentorship.php?action=respond" -Method POST -Body $respBody -ContentType "application/json" -Headers (Auth-Headers -Token $ctx.alumni.token) -WebSession $ctx.alumni.web
    Assert-True ($resp.message -match "updated") "Mentorship respond failed"
    return "request_id=$reqId"
}

Run-Step "resources create/list" {
    $body = @{ title = "QA Resource"; description = "Resource"; category = "notes"; file_url = "https://example.com/r.pdf"; resource_type = "document" } | ConvertTo-Json
    $create = Invoke-RestMethod -Uri "$apiUrl/resources.php?action=create" -Method POST -Body $body -ContentType "application/json" -Headers (Auth-Headers -Token $ctx.alumni.token -Csrf $ctx.alumni.csrf) -WebSession $ctx.alumni.web
    Assert-True ([int64]$create.resource_id -gt 0) "Resource create failed"

    $list = Invoke-RestMethod -Uri "$apiUrl/resources.php?action=list&limit=10" -Method GET -Headers (Auth-Headers -Token $ctx.student.token) -WebSession $ctx.student.web
    Assert-True ($list.Count -ge 1) "Resource list empty"
    return "resource_id=$($create.resource_id)"
}

Run-Step "success_stories create/list" {
    Invoke-PsqlNonQuery "DELETE FROM success_stories WHERE alumni_user_id = $($ctx.alumni.user_id);"
    $body = @{ title = "QA Story"; content = "Story content"; category = "career" } | ConvertTo-Json
    $create = Invoke-RestMethod -Uri "$apiUrl/success_stories.php?action=create" -Method POST -Body $body -ContentType "application/json" -Headers (Auth-Headers -Token $ctx.alumni.token -Csrf $ctx.alumni.csrf) -WebSession $ctx.alumni.web
    Assert-True ([int64]$create.story_id -gt 0) "Story create failed"

    $list = Invoke-RestMethod -Uri "$apiUrl/success_stories.php?action=list&limit=10" -Method GET -Headers (Auth-Headers -Token $ctx.student.token) -WebSession $ctx.student.web
    Assert-True ($list.Count -ge 1) "Story list empty"
    return "story_id=$($create.story_id)"
}

Run-Step "report_content + get_reports(admin)" {
    $repBody = @{ post_id = $ctx.postId } | ConvertTo-Json
    $rep = Invoke-RestMethod -Uri "$apiUrl/report_content.php" -Method POST -Body $repBody -ContentType "application/json" -Headers (Auth-Headers -Token $ctx.alumni.token) -WebSession $ctx.alumni.web
    Assert-True ($rep.message -match "Report logged|Threshold reached") "report_content failed"

    $reports = Invoke-RestMethod -Uri "$apiUrl/get_reports.php" -Method GET -Headers (Auth-Headers -Token $ctx.admin.token) -WebSession $ctx.admin.web
    Assert-True ($reports.Count -ge 1) "get_reports returned empty"
    return "reports_count=$($reports.Count)"
}

Run-Step "notifications get + mark_all_read" {
    $before = Invoke-RestMethod -Uri "$apiUrl/get_notifications.php" -Method GET -Headers (Auth-Headers -Token $ctx.student.token) -WebSession $ctx.student.web
    $mark = Invoke-RestMethod -Uri "$apiUrl/mark_notif_read.php" -Method POST -Body "{}" -ContentType "application/json" -Headers (Auth-Headers -Token $ctx.student.token) -WebSession $ctx.student.web
    $after = Invoke-RestMethod -Uri "$apiUrl/get_notifications.php" -Method GET -Headers (Auth-Headers -Token $ctx.student.token) -WebSession $ctx.student.web
    Assert-True ($after.unread_count -eq 0) "Unread notifications still present"
    return "unread_before=$($before.unread_count) unread_after=$($after.unread_count)"
}

Run-Step "upload_avatar + upload_file + upload" {
    $avatarPath = Join-Path (Get-Location) "test_avatar.jpg"
    $docPath = Join-Path (Get-Location) "test_doc.pdf"

    $avatarRaw = & $curl -s -X POST -H "Authorization: Bearer $($ctx.student.token)" -F "avatar=@$avatarPath" "$apiUrl/upload_avatar.php"
    $avatar = $avatarRaw | ConvertFrom-Json
    Assert-True ($avatar.message -match "Avatar updated") "Avatar upload failed: $avatarRaw"

    $fileRaw = & $curl -s -X POST -H "Authorization: Bearer $($ctx.student.token)" -F "context=posts" -F "attachment=@$docPath" "$apiUrl/upload_file.php"
    $fileResp = $fileRaw | ConvertFrom-Json
    Assert-True ($fileResp.message -match "uploaded successfully") "upload_file failed: $fileRaw"

    $phpSess = ($ctx.student.web.Cookies.GetCookies($baseUrl) | Where-Object { $_.Name -eq "PHPSESSID" } | Select-Object -First 1).Value
    $upRaw = & $curl -s -X POST -H "Authorization: Bearer $($ctx.student.token)" -H "X-CSRF-TOKEN: $($ctx.student.csrf)" -H "Cookie: PHPSESSID=$phpSess" -F "file=@$docPath" "$apiUrl/upload.php"
    $up = $upRaw | ConvertFrom-Json
    Assert-True ($up.message -match "Upload successful") "upload failed: $upRaw"
    return "uploads ok"
}

Run-Step "search_directory + master_search" {
    $sd = Invoke-RestMethod -Uri "$apiUrl/search_directory.php?role=alumni&search=QA" -Method GET -Headers (Auth-Headers -Token $ctx.student.token) -WebSession $ctx.student.web
    Assert-True ($sd.Count -ge 1) "search_directory returned none"

    $ms = Invoke-RestMethod -Uri "$apiUrl/master_search.php?q=QA" -Method GET -Headers (Auth-Headers -Token $ctx.student.token) -WebSession $ctx.student.web
    Assert-True ($ms -ne $null) "master_search null"
    return "search ok"
}

Run-Step "toggle_stream start/stop + get_active_streams" {
    $startBody = @{ action = "start"; title = "QA Live"; description = "Live test" } | ConvertTo-Json
    $start = Invoke-RestMethod -Uri "$apiUrl/toggle_stream.php" -Method POST -Body $startBody -ContentType "application/json" -Headers (Auth-Headers -Token $ctx.alumni.token) -WebSession $ctx.alumni.web
    Assert-True ($start.status -eq "success") "Stream start failed"

    $active = Invoke-RestMethod -Uri "$apiUrl/get_active_streams.php" -Method GET
    Assert-True ($active.status -eq "success") "get_active_streams failed"

    $stopBody = @{ action = "stop" } | ConvertTo-Json
    $stop = Invoke-RestMethod -Uri "$apiUrl/toggle_stream.php" -Method POST -Body $stopBody -ContentType "application/json" -Headers (Auth-Headers -Token $ctx.alumni.token) -WebSession $ctx.alumni.web
    Assert-True ($stop.status -eq "success") "Stream stop failed"
    return "stream lifecycle ok"
}

[int]$pass = @($results | Where-Object { $_.status -eq "PASS" }).Count
[int]$fail = @($results | Where-Object { $_.status -eq "FAIL" }).Count

$report = [pscustomobject]@{
    summary = [pscustomobject]@{
        timestamp = (Get-Date).ToString("s")
        total     = $results.Count
        passed    = $pass
        failed    = $fail
    }
    results = $results
}

$report | ConvertTo-Json -Depth 8 | Set-Content "docs/feature_matrix_report.json" -Encoding ASCII

Write-Host ""
Write-Host "=== Feature Matrix Complete ===" -ForegroundColor Cyan
Write-Host "Passed: $pass  Failed: $fail  Total: $($results.Count)"
Write-Host "Report: docs/feature_matrix_report.json"
