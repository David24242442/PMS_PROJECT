@echo off
setlocal

:: Set backup destination with timestamp
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set "dt=%%I"
set "TIMESTAMP=%dt:~0,4%-%dt:~4,2%-%dt:~6,2%_%dt:~8,2%%dt:~10,2%"
set "BACKUP_ROOT=C:\Backups\PMS_Server20_%TIMESTAMP%"

set "SERVER=\\192.168.0.20\c$\xampp\htdocs"

echo ===========================================
echo   PMS Server 20 Backup - %TIMESTAMP%
echo ===========================================
echo.
echo Backup destination: %BACKUP_ROOT%
echo.

:: 1. PMS Frontend
echo [1/4] Backing up PMS Frontend...
robocopy "%SERVER%\PMS\pms_frontend" "%BACKUP_ROOT%\PMS\pms_frontend" /MIR /MT /R:2 /W:5 /NP /NFL /NDL
if %errorlevel% geq 8 echo [ERROR] PMS Frontend backup failed!

:: 2. PMS Backend (exclude vendor to save space, include everything else)
echo [2/4] Backing up PMS Backend...
robocopy "%SERVER%\PMS\pms_backend" "%BACKUP_ROOT%\PMS\pms_backend" /MIR /MT /R:2 /W:5 /NP /NFL /NDL /XD vendor node_modules
if %errorlevel% geq 8 echo [ERROR] PMS Backend backup failed!

:: 3. HR Onboarding Frontend
echo [3/4] Backing up HR Onboarding Frontend...
robocopy "%SERVER%\HR_PROJECT\Frontend" "%BACKUP_ROOT%\HR_PROJECT\Frontend" /MIR /MT /R:2 /W:5 /NP /NFL /NDL
if %errorlevel% geq 8 echo [ERROR] HR Frontend backup failed!

:: 4. HR Onboarding Backend
echo [4/4] Backing up HR Onboarding Backend...
robocopy "%SERVER%\HR_PROJECT\Backend" "%BACKUP_ROOT%\HR_PROJECT\Backend" /MIR /MT /R:2 /W:5 /NP /NFL /NDL /XD vendor node_modules
if %errorlevel% geq 8 echo [ERROR] HR Backend backup failed!

echo.
echo ===========================================
echo   BACKUP COMPLETE!
echo   Location: %BACKUP_ROOT%
echo ===========================================
pause
