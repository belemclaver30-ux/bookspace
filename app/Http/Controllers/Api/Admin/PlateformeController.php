<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Litige;
use App\Models\Parametre;
use App\Services\StatistiquesService;
use Illuminate\Http\Request;

/**
 * Supervision de la plateforme + paramètres (commission) + litiges (TDR §5.3).
 */
class PlateformeController extends Controller
{
    public function tableauDeBord(StatistiquesService $statistiques)
    {
        return $statistiques->plateforme();
    }

    public function parametres()
    {
        return Parametre::all();
    }

    public function majParametre(Request $request, string $cle)
    {
        $data = $request->validate(['valeur' => ['required', 'string']]);

        abort_unless(Parametre::query()->whereKey($cle)->exists(), 404);
        Parametre::definir($cle, $data['valeur']);

        return response()->json(['data' => Parametre::find($cle)]);
    }

    public function litiges(Request $request)
    {
        return Litige::with(['reservation', 'auteur'])
            ->when($request->query('statut'), fn ($q, $s) => $q->where('statut', $s))
            ->latest()
            ->paginate(20);
    }

    public function resoudreLitige(Request $request, Litige $litige)
    {
        $data = $request->validate([
            'statut' => ['required', 'in:en_cours,resolu,ferme'],
            'resolution' => ['nullable', 'string', 'max:2000'],
        ]);

        $litige->update($data + [
            'resolu_par' => $request->user()->id,
            'resolu_at' => in_array($data['statut'], ['resolu', 'ferme'], true) ? now() : null,
        ]);

        return response()->json(['data' => $litige->fresh(['reservation', 'auteur', 'resolveur'])]);
    }
}
