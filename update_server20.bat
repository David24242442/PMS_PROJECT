@echo off
setlocal
echo ===================================================
echo   Update Server 20 (192.168.0.20) from Share
echo ===================================================
echo.

set "SHARE=\\192.168.0.24\it-software\IT DEV DAVID\PMS"
set "SERVER_FRONTEND=C:\xampp\htdocs\PMS\pms_frontend"
set "SERVER_BACKEND=C:\xampp\htdocs\PMS\pms_backend"

:: 1. Update Frontend
echo [1/3] Updating Frontend files...
robocopy "%SHARE%\pms_frontend" "%SERVER_FRONTEND%" /MIR /NP /R:2 /W:3

:: 2. Update Backend (preserve .env and storage)
echo [2/3] Updating Backend files...
robocopy "%SHARE%\pms_backend" "%SERVER_BACKEND%" /MIR /NP /R:2 /W:3 /XD vendor node_modules .git storage public\storage /XF .env .env.local .env.development

:: 3. Clear Laravel Cache
echo [3/3] Clearing Laravel caches on Server 20...
cd /d "%SERVER_BACKEND%"
if exist "artisan" (
    call php artisan optimize:clear
)

echo.
echo ===================================================
echo   SUCCESS: Server 20 PMS updated successfully!
echo   Frontend: http://192.168.0.20:5050/pms_frontend/
echo   Backend:  http://192.168.0.20:5050/pms_backend/
echo ===================================================
pause
