@echo off
setlocal
echo ===================================================
echo   Update Server 20 (192.168.0.20) from Share
echo ===================================================
echo.

set "SHARE=\\192.168.0.24\it-software\IT DEV DAVID\PMS"
set "SERVER_ROOT=C:\xampp\htdocs\PMS"
set "SERVER_FRONTEND=C:\xampp\htdocs\PMS\pms_frontend"
set "SERVER_BACKEND=C:\xampp\htdocs\PMS\pms_backend"

:: 1. Update Frontend
echo [1/4] Updating Frontend files...
robocopy "%SHARE%\pms_frontend" "%SERVER_FRONTEND%" /MIR /NP /R:2 /W:3
if exist "%SERVER_ROOT%\PMS_FRONTEND_VUE" (
    robocopy "%SHARE%\PMS_FRONTEND_VUE" "%SERVER_ROOT%\PMS_FRONTEND_VUE" /MIR /NP /R:2 /W:3
)

:: 2. Update Backend (preserve .env and storage)
echo [2/4] Updating Backend files...
robocopy "%SHARE%\pms_backend" "%SERVER_BACKEND%" /MIR /NP /R:2 /W:3 /XD vendor node_modules .git storage public\storage /XF .env .env.local .env.development
if exist "%SERVER_ROOT%\pms_backend_laravel" (
    robocopy "%SHARE%\pms_backend_laravel" "%SERVER_ROOT%\pms_backend_laravel" /MIR /NP /R:2 /W:3 /XD vendor node_modules .git storage public\storage /XF .env .env.local .env.development
)

:: 3. Copy Payroll Excel to Server 20 root
echo [3/4] Copying August 2026 Payroll Excel file...
if exist "%SHARE%\AUGUST 2026 PAYROLL DATA.xlsx" (
    copy /Y "%SHARE%\AUGUST 2026 PAYROLL DATA.xlsx" "%SERVER_ROOT%\AUGUST 2026 PAYROLL DATA.xlsx"
)

:: 4. Run Migrations & Clear Laravel Cache
echo [4/4] Running migrations and clearing Laravel caches on Server 20...
cd /d "%SERVER_BACKEND%"
if exist "artisan" (
    call php artisan migrate --force
    call php artisan optimize:clear
)

echo.
echo ===================================================
echo   SUCCESS: Server 20 PMS updated successfully!
echo   Frontend: http://192.168.0.20:5050/pms_frontend/
echo   Backend:  http://192.168.0.20:5050/pms_backend/
echo.
echo   To ingest all 5,920 employees into Monthly_Employees:
echo   - Click 'One-Click Ingest from Server Excel' in Manage Employees
echo   - OR open: http://localhost:5050/pms_backend/api/pms/sync-local-excel
echo ===================================================
pause
