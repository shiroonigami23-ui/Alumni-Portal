@echo off
REM Windows / XAMPP scheduler. Add to Task Scheduler: run every 1 minute.
REM   schtasks /Create /SC MINUTE /MO 1 /TN "RJIT Portal Scheduler" /TR "%~f0"
set "PHP=C:\xampp\php\php.exe"
if not defined DB_PORT set DB_PORT=5433
"%PHP%" "%~dp0..\cron\run.php" >> "%~dp0..\storage\logs\cron.log" 2>&1
