@echo off
setlocal

set "SHARE_PATH=\\192.168.0.24\it-software\IT DEV DAVID\PMS"
set "FRONTEND_SRC=c:\Users\USER\Workspaces\htdocs\PMS\HR Onboarding\hrproject Vue\hrproject"
set "BACKEND_SRC=c:\Users\USER\Workspaces\htdocs\PMS\HR Onboarding\hr\hr"

set "FRONTEND_DEST=%SHARE_PATH%\PMS_FRONTEND_VUE"
set "BACKEND_DEST=%SHARE_PATH%\pms_backend_laravel"

echo ===========================================
echo   PMS Project Replacement Deployment 
echo ===========================================
echo.

:: 1. Build Frontend
echo [1/3] Building Vue Frontend (Production)...
cd /d "%FRONTEND_SRC%"
call npm run build
if %errorlevel% neq 0 (
    echo [ERROR] Frontend build failed!
    pause
    exit /b %errorlevel%
)

:: 2. Clean and Transfer Frontend (Mirror to both pms_frontend and PMS_FRONTEND_VUE)
echo [2/3] Cleaning and Mirroring Frontend to Share...
robocopy "%FRONTEND_SRC%\dist" "%SHARE_PATH%\pms_frontend" /MIR /MT /R:2 /W:5 /NP
robocopy "%FRONTEND_SRC%\dist" "%SHARE_PATH%\PMS_FRONTEND_VUE" /MIR /MT /R:2 /W:5 /NP

:: 3. Clean and Transfer Backend (Mirror to both pms_backend and pms_backend_laravel)
echo [3/3] Cleaning and Mirroring Backend to Share...
echo Transferring Laravel files (Mirroring source, excluding vendor/nodes/storage)...
robocopy "%BACKEND_SRC%" "%SHARE_PATH%\pms_backend" /MIR /MT /R:2 /W:5 /NP /XD vendor node_modules .git storage public\storage /XF .env.local .env.development
robocopy "%BACKEND_SRC%" "%SHARE_PATH%\pms_backend_laravel" /MIR /MT /R:2 /W:5 /NP /XD vendor node_modules .git storage public\storage /XF .env.local .env.development

echo.
echo ===========================================
echo   SUCCESS! PMS project replaced by HR Onboarding.
echo   Files are ready at: %SHARE_PATH%
echo ===========================================
pause
