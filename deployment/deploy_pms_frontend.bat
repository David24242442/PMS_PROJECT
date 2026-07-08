@echo off
setlocal

set "SHARE_PATH=\\192.168.0.24\it-software\IT DEV DAVID\PMS\PMS_FRONTEND_VUE"
set "FRONTEND_SRC=c:\Users\USER\Workspaces\htdocs\PMS\HR Onboarding\hrproject Vue\hrproject"

echo ===========================================
echo   PMS Frontend Deployment (via Share 24)
echo ===========================================
echo.

:: 1. Navigate to Project Directory
cd /d "%FRONTEND_SRC%"

:: 2. Build for Production
echo [1/2] Building Vue Application...
call npm run build
if %errorlevel% neq 0 (
    echo Error: Build failed!
    pause
    exit /b %errorlevel%
)

:: 3. Deploy to Share Folder on 192.168.0.24
echo.
echo [2/2] Deploying to Share Folder (192.168.0.24)...
robocopy "%FRONTEND_SRC%\dist" "%SHARE_PATH%" /MIR /MT /R:2 /W:5 /NP
if %errorlevel% geq 8 (
    echo Error: Deployment failed with error %errorlevel%
    pause
    exit /b %errorlevel%
)

echo.
echo ===========================================
echo   SUCCESS! Deployed to share folder.
echo   Files at: %SHARE_PATH%
echo   URL: http://192.168.0.20:5050/
echo ===========================================
pause
