@echo off
echo ===========================================
echo   PMS Frontend Deployment Script
echo ===========================================
echo.

:: 1. Navigate to Project Directory
cd /d "c:\Users\USER\Workspaces\htdocs\PMS\PMS_FRONTEND_VUE"

:: 2. Build for Production
echo [1/2] Building Vue Application...
call npm run build
if %errorlevel% neq 0 (
    echo Error: Build failed!
    pause
    exit /b %errorlevel%
)

:: 3. Deploy to Test Server
echo.
echo [2/2] Deploying to Test Server (192.168.0.20)...
xcopy "dist\*" "\\192.168.0.20\c$\xampp\htdocs\PMS\pms_frontend\" /E /H /Y /I
if %errorlevel% neq 0 (
    echo Error: Deployment failed!
    pause
    exit /b %errorlevel%
)

echo.
echo ===========================================
echo   SUCCESS! Deployment Complete.
echo   URL: http://192.168.0.20:5050/
echo ===========================================
pause
