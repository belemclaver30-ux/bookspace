#!/usr/bin/env bash
# Démarrage Railway : migrations, caches, planificateur en arrière-plan, serveur web.
set -e

php artisan storage:link --force || true
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Tâches planifiées (reservations:expirer, cloturer, rappeler) : une fois par minute.
( while true; do php artisan schedule:run --no-interaction >/dev/null 2>&1; sleep 60; done ) &

exec php -S 0.0.0.0:"${PORT:-8080}" -t public vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
