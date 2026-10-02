@echo off
REM Lance BookSpace en local : API Laravel (port 8000) + front Vite (port 8443).
REM Prerequis : XAMPP MySQL demarre (base "bookspace").

echo Demarrage de l'API Laravel sur http://127.0.0.1:8000 ...
start "BookSpace API" cmd /k "cd /d %~dp0backend && php artisan serve --host=127.0.0.1 --port=8000"

echo Demarrage du front Vite sur http://127.0.0.1:8443 ...
start "BookSpace Web" cmd /k "cd /d %~dp0 && set PORT=8443&& npm run dev"

echo.
echo Deux fenetres se sont ouvertes. Ouvrez ensuite http://127.0.0.1:8443
echo Laissez-les ouvertes tant que vous utilisez l'application.
