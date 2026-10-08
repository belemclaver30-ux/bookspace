<?php

use Carbon\CarbonInterface;

/*
 * Aides de formatage utilisées par les vues Blade (montants, dates, libellés).
 */

if (! function_exists('fcfa')) {
    function fcfa(float|int|string|null $montant): string
    {
        return number_format(round((float) $montant), 0, ',', ' ') . ' FCFA';
    }
}

if (! function_exists('nombre')) {
    function nombre(float|int|string|null $valeur): string
    {
        return number_format(round((float) $valeur), 0, ',', ' ');
    }
}

if (! function_exists('date_longue')) {
    /** « Lundi 5 oct. 2026 » */
    function date_longue(?CarbonInterface $date): string
    {
        if (! $date) {
            return '—';
        }

        $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        $mois = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

        return "{$jours[$date->dayOfWeek]} {$date->day} {$mois[$date->month - 1]} {$date->year}";
    }
}

if (! function_exists('plage_horaire')) {
    /** « 14:00 – 16:00 » */
    function plage_horaire(?CarbonInterface $debut, ?CarbonInterface $fin): string
    {
        if (! $debut || ! $fin) {
            return '—';
        }

        return $debut->format('H:i') . ' – ' . $fin->format('H:i');
    }
}

if (! function_exists('distance_lisible')) {
    function distance_lisible(float $km): string
    {
        if ($km < 1) {
            return round($km * 1000) . ' m';
        }

        return number_format($km, $km < 10 ? 1 : 0, ',', ' ') . ' km';
    }
}

if (! function_exists('duree_lisible')) {
    function duree_lisible(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes} min";
        }

        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return $m ? "{$h} h {$m}" : "{$h} h";
    }
}

if (! function_exists('libelle_type')) {
    function libelle_type(?string $type): string
    {
        return [
            'salle_repetition' => 'Salle de répétition',
            'terrain_foot' => 'Terrain de football',
        ][$type] ?? '—';
    }
}

if (! function_exists('heures_lisibles')) {
    /** 2.00 -> « 2 », 1.50 -> « 1,5 » */
    function heures_lisibles(float|int|string|null $heures): string
    {
        return rtrim(rtrim(number_format((float) $heures, 2, ',', ''), '0'), ',');
    }
}
