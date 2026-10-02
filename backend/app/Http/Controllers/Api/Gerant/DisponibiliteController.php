<?php

namespace App\Http\Controllers\Api\Gerant;

use App\Http\Controllers\Controller;
use App\Models\Espace;
use Illuminate\Http\Request;

/**
 * Calendrier du gérant (TDR §5.2 : « définir les créneaux disponibles et
 * bloquer manuellement certaines plages »).
 */
class DisponibiliteController extends Controller
{
    public function horaires(Request $request, Espace $espace)
    {
        $this->autoriser($request, $espace);

        $data = $request->validate([
            'horaires' => ['required', 'array'],
            'horaires.*.jour_semaine' => ['required', 'integer', 'between:0,6'],
            'horaires.*.heure_ouverture' => ['required', 'date_format:H:i'],
            'horaires.*.heure_fermeture' => ['required', 'date_format:H:i', 'after:horaires.*.heure_ouverture'],
        ]);

        $espace->disponibilites()->delete();
        $espace->disponibilites()->createMany($data['horaires']);

        return $espace->disponibilites()->get();
    }

    public function bloquer(Request $request, Espace $espace)
    {
        $this->autoriser($request, $espace);

        $data = $request->validate([
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
            'motif' => ['nullable', 'string', 'max:255'],
        ]);

        $blocage = $espace->blocages()->create($data);
        $espace->journaliser('plage_bloquee', $data);

        return response()->json(['data' => $blocage], 201);
    }

    public function debloquer(Request $request, Espace $espace, int $blocage)
    {
        $this->autoriser($request, $espace);

        $espace->blocages()->whereKey($blocage)->delete();

        return response()->json(['message' => 'Plage débloquée.']);
    }

    private function autoriser(Request $request, Espace $espace): void
    {
        abort_unless($espace->gerant_id === $request->user()->gerant?->id, 403);
    }
}
