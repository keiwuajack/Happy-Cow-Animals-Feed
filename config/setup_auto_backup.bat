@echo off
rem Creates a Windows scheduled task that backs up the database every day at 18:00
rem (and at next start if the PC was off). Run once by double-clicking.
setlocal
set "APPDIR=%~dp0.."
for %%I in ("%APPDIR%") do set "APPDIR=%%~fI"
for %%I in ("%APPDIR%\..\..") do set "XAMPP=%%~fI"

if not exist "%XAMPP%\php\php.exe" (
    echo Could not find XAMPP PHP at "%XAMPP%\php\php.exe".
    echo Keep this project inside XAMPP\htdocs and try again.
    pause
    exit /b 1
)

powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "$a = New-ScheduledTaskAction -Execute '%XAMPP%\php\php.exe' -Argument '\"%APPDIR%\config\auto_backup.php\"';" ^
  "$t = New-ScheduledTaskTrigger -Daily -At 18:00;" ^
  "$s = New-ScheduledTaskSettingsSet -StartWhenAvailable -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries;" ^
  "Register-ScheduledTask -TaskName 'HappyCowPOS Daily Backup' -Action $a -Trigger $t -Settings $s -Description 'Daily database backup for Happy Cow POS' -Force | Out-Null"

if errorlevel 1 (
    echo Failed to create the task. Right-click this file and choose Run as administrator.
    pause
    exit /b 1
)

echo Daily backup scheduled for 18:00. MySQL must be running at that time.
echo Backups are saved in "%XAMPP%\pos-backups" and kept for 30 days.
echo Running a test backup now...
"%XAMPP%\php\php.exe" "%APPDIR%\config\auto_backup.php"
pause
