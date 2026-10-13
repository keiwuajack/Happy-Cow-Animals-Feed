@echo off
rem One-click installer for a CLIENT PC. Run after copying this folder into XAMPP\htdocs
rem and starting Apache + MySQL in the XAMPP Control Panel.
setlocal
set "APPDIR=%~dp0"
if "%APPDIR:~-1%"=="\" set "APPDIR=%APPDIR:~0,-1%"
for %%I in ("%APPDIR%") do set "APPNAME=%%~nxI"
for %%I in ("%APPDIR%\..\..") do set "XAMPP=%%~fI"
set "MYSQL=%XAMPP%\mysql\bin\mysql.exe"
set "PHP=%XAMPP%\php\php.exe"

if not exist "%PHP%" ( echo XAMPP not found. Copy this folder into XAMPP\htdocs first. & pause & exit /b 1 )

"%MYSQL%" -u root -e "SELECT 1" >nul 2>&1
if errorlevel 1 ( echo Cannot reach MySQL. Start MySQL in the XAMPP Control Panel and run again. & pause & exit /b 1 )

"%MYSQL%" -u root -e "USE agrovet_pos" >nul 2>&1
if not errorlevel 1 ( echo Database agrovet_pos already exists. Installation stopped to protect existing data. & pause & exit /b 1 )

echo Creating database...
"%MYSQL%" -u root -e "CREATE DATABASE agrovet_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if errorlevel 1 ( echo Failed to create database. & pause & exit /b 1 )
"%MYSQL%" -u root agrovet_pos < "%APPDIR%\config\schema.sql"
if errorlevel 1 ( echo Failed to import tables. & pause & exit /b 1 )

echo Creating administrator account...
"%PHP%" "%APPDIR%\config\install.php"

echo.
echo Scheduling daily backup...
call "%APPDIR%\config\setup_auto_backup.bat"

echo.
echo ==========================================================
echo  WRITE DOWN THE ADMIN PASSWORD SHOWN ABOVE, then open:
echo  http://localhost/%APPNAME%/login.php
echo  Username: admin   - change the password after signing in.
echo ==========================================================
start "" "http://localhost/%APPNAME%/login.php"
pause
