<?php

namespace App\Http\Controllers\Api\Gerant;

use App\Http\Controllers\Controller;
use App\Services\StatistiquesService;
use Illuminate\Http\Request;

/**
 * Statistiques du gérant (TDR §5.2 : « taux d'occupation, revenus par période »).
 * Fournit aussi les séries nécessaires aux graphes du tableau de bord.
 */
class StatistiqueController extends Controller
{
    public function __invoke(Request $request, StatistiquesService $statistiques)
    {
        $gerant = $request->user()->gerant()->firstOrFail();

        $data = $request->validate([
            'du' => ['nullable', 'date'],
            'au' => ['nullable', 'date', 'after_or_equal:du'],
        ]);

        return $statistiques->gerant($gerant, $data['du'] ?? null, $data['au'] ?? null);
    }
}
