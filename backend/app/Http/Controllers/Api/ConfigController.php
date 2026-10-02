<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Parametre;

/**
 * Paramètres publics de la plateforme, nécessaires au client avant même de
 * créer une réservation (taux d'acompte affiché sur l'écran « Choisir un
 * créneau », délai d'annulation sans frais…). Le taux réellement appliqué
 * reste figé côté serveur au moment de la réservation.
 */
class ConfigController extends Controller
{
    public function __invoke(): array
    {
        return [
            'deposit_rate' => (float) Parametre::valeur('deposit_rate', config('bookspace.deposit_rate')),
            'commission_rate' => (float) Parametre::valeur('commission_rate', config('bookspace.commission_rate')),
            'annulation_delai_heures' => (int) Parametre::valeur('annulation_delai_heures', config('bookspace.annulation_delai_heures')),
        ];
    }
}
