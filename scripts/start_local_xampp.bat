@echo off
setlocal

set "XAMPP_ROOT=C:\xampp"
set "APACHE_BIN=%XAMPP_ROOT%\apache\bin"
set "PG_BIN=%XAMPP_ROOT%\pgsql\pgsql\bin"
set "PG_DATA=%XAMPP_ROOT%\pgsql\data"
set "PG_LOG=%XAMPP_ROOT%\pgsql\logfile"

echo [1/3] Starting XAMPP PostgreSQL (expected on 127.0.0.1:5433)...
"%PG_BIN%\pg_ctl.exe" status -D "%PG_DATA%" >nul 2>&1
if errorlevel 1 (
  "%PG_BIN%\pg_ctl.exe" start -D "%PG_DATA%" -l "%PG_LOG%"
) else (
  echo PostgreSQL is already running.
)

echo [2/3] Checking PostgreSQL readiness...
"%PG_BIN%\pg_isready.exe" -h 127.0.0.1 -p 5433
if errorlevel 1 (
  echo WARNING: PostgreSQL is not responding on 5433 yet.
)

echo [3/3] Starting Apache...
C:\Windows\System32\tasklist.exe | C:\Windows\System32\findstr.exe /I "httpd.exe" >nul
if errorlevel 1 (
  start "xampp-apache" /MIN "%APACHE_BIN%\httpd.exe"
  echo Apache start command sent.
) else (
  echo Apache is already running.
)

echo.
echo Local app URL: http://127.0.0.1:8088/
echo DB in use: PostgreSQL on 127.0.0.1:5433

endlocal
