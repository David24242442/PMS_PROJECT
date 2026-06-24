@echo off
echo ===========================================
echo   HR Onboarding Deployment Script (192.168.0.20:9090)
echo ===========================================
echo.

:: 1. Navigate to Frontend Directory
cd /d "c:\Users\USER\Workspaces\htdocs\HR Onboarding\hrproject Vue\hrproject"

:: 2. Build for Production
echo [1/3] Building Vue Application...
call npm run build
if %errorlevel% neq 0 (
    echo Error: Build failed!
    pause
    exit /b %errorlevel%
)

:: 3. Deploy Frontend (PowerShell Copy-Item)
echo.
echo [2/3] Deploying Frontend to Test Server (192.168.0.20)
powershell -Command "Copy-Item -Path 'dist\*' -Destination '\\192.168.0.20\c$\xampp\htdocs\HR_PROJECT\Frontend\' -Recurse -Force"
IF %ERRORLEVEL% NEQ 0 (
    echo Warning: Deployment encountered an error.
)

:: 4. Deploy Backend
echo.
echo [3/3] Deploying Backend to Test Server
cd /d "c:\Users\USER\Workspaces\htdocs\HR Onboarding\hr\hr"
powershell -Command "Copy-Item -Path '*.*' -Destination '\\192.168.0.20\c$\xampp\htdocs\HR_PROJECT\Backend\' -Recurse -Force"
IF %ERRORLEVEL% NEQ 0 (
    echo Warning: Deployment encountered an error.
)

echo.
echo ===========================================
echo   SUCCESS! Files transferred.
echo   Now apply the Apache config changes if you haven't yet.
echo   URL: http://192.168.0.20:9090/
echo ===========================================
pause
