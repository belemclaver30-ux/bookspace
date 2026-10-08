<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Clôture automatique des réservations dont le créneau est passé (TDR §5.1).
Schedule::command('reservations:cloturer')->everyFifteenMinutes()->withoutOverlapping();

// Libération des créneaux réservés mais jamais payés (acompte hors délai).
Schedule::command('reservations:expirer')->everyFiveMinutes()->withoutOverlapping();

// Rappel SMS client + gérant ~30 min avant le créneau (bookspace.rappel_minutes_avant).
Schedule::command('reservations:rappeler')->everyFiveMinutes()->withoutOverlapping();
