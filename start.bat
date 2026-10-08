@echo off
REM Lance BookSpace en local (application Laravel + Livewire, un seul serveur).
REM Prerequis : XAMPP MySQL demarre (base "bookspace").

cd /d %~dp0

if not exist public\build\manifest.json (
    echo Compilation des fichiers CSS/JS...
    call npm run build
)

echo Demarrage de BookSpace sur http://127.0.0.1:8000 ...
start "BookSpace" cmd /k "cd /d %~dp0 && php artisan serve --host=127.0.0.1 --port=8000"

echo.
echo Une fenetre s'est ouverte. Ouvrez ensuite http://127.0.0.1:8000
echo Laissez-la ouverte tant que vous utilisez l'application.
