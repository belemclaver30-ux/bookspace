<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\EspaceResource;
use App\Models\Espace;
use App\Models\Gerant;
use Illuminate\Http\Request;

/**
 * Validation des gérants et des espaces par l'administrateur (TDR §5.3, §15 :
 * « un gérant peut créer un espace et le voir validé par l'administrateur »).
 */
class ValidationController extends Controller
{
    public function gerantsEnAttente()
    {
        return Gerant::with('user')->where('statut_validation', 'en_attente')->latest()->paginate(20);
    }

    public function validerGerant(Request $request, Gerant $gerant)
    {
        $gerant->update([
            'statut_validation' => 'valide',
            'valide_par' => $request->user()->id,
            'valide_at' => now(),
            'motif_rejet' => null,
        ]);
        $gerant->journaliser('gerant_valide');

        return response()->json(['data' => $gerant->fresh('user')]);
    }

    public function rejeterGerant(Request $request, Gerant $gerant)
    {
        $data = $request->validate(['motif' => ['required', 'string', 'max:500']]);

        $gerant->update([
            'statut_validation' => 'rejete',
            'valide_par' => $request->user()->id,
            'motif_rejet' => $data['motif'],
        ]);
        $gerant->journaliser('gerant_rejete', $data);

        return response()->json(['data' => $gerant->fresh('user')]);
    }

    public function espacesEnAttente()
    {
        return EspaceResource::collection(
            Espace::with(['photos', 'gerant.user'])->where('statut', 'en_attente')->latest()->paginate(20)
        );
    }

    public function validerEspace(Request $request, Espace $espace): EspaceResource
    {
        $espace->update(['statut' => 'valide', 'motif_rejet' => null]);
        $espace->journaliser('espace_valide');

        return new EspaceResource($espace->fresh('photos'));
    }

    public function rejeterEspace(Request $request, Espace $espace): EspaceResource
    {
        $data = $request->validate(['motif' => ['required', 'string', 'max:500']]);

        $espace->update(['statut' => 'rejete', 'motif_rejet' => $data['motif']]);
        $espace->journaliser('espace_rejete', $data);

        return new EspaceResource($espace->fresh('photos'));
    }

    public function suspendreEspace(Request $request, Espace $espace): EspaceResource
    {
        $data = $request->validate(['motif' => ['required', 'string', 'max:500']]);

        $espace->update(['statut' => 'suspendu', 'motif_rejet' => $data['motif']]);
        $espace->journaliser('espace_suspendu', $data);

        return new EspaceResource($espace->fresh('photos'));
    }
}
