@echo off
set "SHARE=\\192.168.0.24\it-software\IT DEV DAVID\PMS"

echo [1/4] Copying to pms_frontend...
robocopy "PMS_FRONTEND_VUE\dist" "%SHARE%\pms_frontend" /MIR /NP /R:2 /W:2

echo [2/4] Copying to PMS_FRONTEND_VUE\dist...
robocopy "PMS_FRONTEND_VUE\dist" "%SHARE%\PMS_FRONTEND_VUE\dist" /MIR /NP /R:2 /W:2

echo [3/4] Copying to PMS_FRONTEND_VUE\src...
robocopy "PMS_FRONTEND_VUE\src" "%SHARE%\PMS_FRONTEND_VUE\src" /MIR /NP /R:2 /W:2

echo [4/4] Copying backend files...
robocopy "PMS_BACKEND_LARAVEL" "%SHARE%\pms_backend" /MIR /XD vendor node_modules .git storage public\storage /XF .env .env.local .env.development /NP /R:2 /W:2

echo Robocopy sync finished successfully.
