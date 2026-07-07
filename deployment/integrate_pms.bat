@echo off
echo ===========================================
echo   PMS to HR Onboarding Integration Setup
echo ===========================================
echo.
echo [1/1] Installing dependencies for HR Onboarding...
cd /d "C:\Users\USER\Workspaces\htdocs\PMS\HR Onboarding\hrproject Vue\hrproject"
call npm install sweetalert2
if %errorlevel% neq 0 (
    echo Error: Failed to install sweetalert2.
    pause
    exit /b %errorlevel%
)
echo.
echo Dependencies installed successfully.
echo.
pause
