<?php

namespace App\Http\Controllers\Api\Gerant;

use App\Http\Controllers\Controller;
use App\Http\Resources\EspaceResource;
use App\Models\Espace;
use App\Models\EspacePhoto;
use App\Services\PhotoEspaceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Gestion des espaces par le gérant (TDR §5.2 : créer / modifier la fiche,
 * soumettre à validation). Un espace créé est `en_attente` jusqu'à validation
 * par l'administrateur.
 */
class EspaceController extends Controller
{
    public function __construct(private readonly PhotoEspaceService $photos) {}

    public function index(Request $request)
    {
        return EspaceResource::collection(
            $this->gerant($request)->espaces()->with('photos')->latest()->get()
        );
    }

    public function store(Request $request): EspaceResource
    {
        $gerant = $this->gerant($request);
        abort_unless($gerant->estValide(), 403, 'Votre compte gérant doit être validé avant de publier un espace.');

        $data = $this->valider($request);

        $espace = $gerant->espaces()->create($data + ['statut' => 'en_attente']);
        $this->enregistrerPhotos($request, $espace);

        return new EspaceResource($espace->load('photos'));
    }

    public function show(Request $request, Espace $espace): EspaceResource
    {
        $this->autoriser($request, $espace);

        return new EspaceResource($espace->load(['photos', 'disponibilites', 'blocages']));
    }

    public function update(Request $request, Espace $espace): EspaceResource
    {
        $this->autoriser($request, $espace);

        $data = $this->valider($request, $espace);
        $espace->update($data);
        $this->enregistrerPhotos($request, $espace);

        return new EspaceResource($espace->fresh()->load('photos'));
    }

    /** Re-soumettre un espace rejeté / modifié à la validation admin. */
    public function soumettre(Request $request, Espace $espace): EspaceResource
    {
        $this->autoriser($request, $espace);

        $espace->update(['statut' => 'en_attente', 'motif_rejet' => null]);
        $espace->journaliser('soumise_validation');

        return new EspaceResource($espace);
    }

    public function destroy(Request $request, Espace $espace)
    {
        $this->autoriser($request, $espace);
        $espace->delete();

        return response()->json(['message' => 'Espace supprimé.']);
    }

    /** Ajoute une ou plusieurs photos réelles à l'espace (TDR §5.1 « photos »). */
    public function ajouterPhotos(Request $request, Espace $espace): EspaceResource
    {
        $this->autoriser($request, $espace);

        $request->validate([
            'photos' => ['required', 'array', 'max:10'],
            'photos.*' => ['image', 'max:8192'], // 8 Mo / fichier
        ]);

        $this->enregistrerPhotos($request, $espace);

        return new EspaceResource($espace->fresh()->load('photos'));
    }

    public function supprimerPhoto(Request $request, Espace $espace, EspacePhoto $photo): EspaceResource
    {
        $this->autoriser($request, $espace);
        abort_unless($photo->espace_id === $espace->id, 404);

        $this->photos->supprimer($photo);

        return new EspaceResource($espace->fresh()->load('photos'));
    }

    /** Fait d'une photo la photo principale (ordre 0). */
    public function photoPrincipale(Request $request, Espace $espace, EspacePhoto $photo): EspaceResource
    {
        $this->autoriser($request, $espace);
        abort_unless($photo->espace_id === $espace->id, 404);

        $this->photos->definirPrincipale($espace, $photo);

        return new EspaceResource($espace->fresh()->load('photos'));
    }

    private function valider(Request $request, ?Espace $espace = null): array
    {
        return $request->validate([
            'type' => [$espace ? 'sometimes' : 'required', Rule::in(['salle_repetition', 'terrain_foot'])],
            'nom' => [$espace ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'adresse' => [$espace ? 'sometimes' : 'required', 'string', 'max:255'],
            'ville' => [$espace ? 'sometimes' : 'required', 'string', 'max:255'],
            'tarif_horaire' => [$espace ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'equipements' => ['nullable', 'array'],
            'equipements.*' => ['string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'capacite' => ['nullable', 'integer', 'min:1'],
            'isolation_phonique' => ['boolean'],
            'type_pelouse' => ['nullable', 'string', 'max:100'],
            'eclairage' => ['boolean'],
            'dimensions' => ['nullable', 'string', 'max:50'],
        ]);
    }

    private function enregistrerPhotos(Request $request, Espace $espace): void
    {
        if (! $request->hasFile('photos')) {
            return;
        }

        $request->validate(['photos.*' => ['image', 'max:8192']]);
        $this->photos->ajouter($espace, $request->file('photos'));
    }

    private function gerant(Request $request)
    {
        return $request->user()->gerant()->firstOrFail();
    }

    private function autoriser(Request $request, Espace $espace): void
    {
        abort_unless($espace->gerant_id === $this->gerant($request)->id, 403);
    }
}
