@echo off
echo ========================================================
echo   Updating Server 20 PMS & HR Onboarding from Share
echo ========================================================
echo.

set "SHARE=\\192.168.0.24\it-software\IT DEV DAVID\PMS"

echo [1/4] Updating PMS Frontend...
robocopy "%SHARE%\pms_frontend" "C:\xampp\htdocs\PMS\pms_frontend" /MIR /NP /R:2 /W:3

echo [2/4] Updating PMS Backend...
robocopy "%SHARE%\pms_backend" "C:\xampp\htdocs\PMS\pms_backend" /MIR /NP /R:2 /W:3 /XD vendor node_modules .git storage public\storage /XF .env .env.local .env.development

echo [3/4] Clearing Laravel Route and Config Cache...
if exist "C:\xampp\htdocs\PMS\pms_backend" (
    cd /d "C:\xampp\htdocs\PMS\pms_backend"
    php artisan optimize:clear
)

echo [4/4] Setting up Database Tables (Online Onboardings & Sessions Logs & Created By)...
curl -s "http://127.0.0.1:5050/pms_backend/api/setup-online-sessions-tables"
echo.

echo ========================================================
echo   SUCCESS! Server 20 updated and tables initialized.
echo ========================================================
pause
