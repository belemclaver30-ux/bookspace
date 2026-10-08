<?php

namespace App\Livewire\Gerant;

use App\Livewire\Concerns\AvecGerant;
use App\Models\Espace;
use App\Services\GeoService;
use App\Services\PhotoEspaceService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Gestion d'un espace par son gérant (TDR §5.2) : fiche (nom, tarif,
 * équipements…), photos, localisation, horaires d'ouverture et plages
 * bloquées manuellement. Les modifications de la fiche s'appliquent tout de
 * suite (elles sont historisées par `EnregistreAudit`) ; le type d'espace,
 * lui, ne change pas après la création.
 */
#[Layout('components.layouts.dashboard')]
class EspaceGestion extends Component
{
    use AvecGerant, WithFileUploads;

    private const PHOTOS_MAX = 10;

    public Espace $espace;

    // ─── Fiche ───────────────────────────────────────────────────────────────
    public string $nom = '';

    public string $ville = '';

    public string $tarif_horaire = '';

    public string $equipements = '';

    public string $description = '';

    public string $capacite = '';

    public bool $isolation_phonique = false;

    public string $type_pelouse = '';

    public bool $eclairage = false;

    public string $dimensions = '';

    // ─── Photos ──────────────────────────────────────────────────────────────
    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $nouvellesPhotos = [];

    // ─── Localisation ────────────────────────────────────────────────────────
    public ?float $latitude = null;

    public ?float $longitude = null;

    public string $adresse = '';

    public string $recherche = '';

    /** @var list<array{label: string, latitude: float, longitude: float}> */
    public array $resultats = [];

    // ─── Horaires (index = jour de semaine, 0 = dimanche) ────────────────────
    /** @var array<int, array{actif: bool, o: string, f: string}> */
    public array $horaires = [];

    // ─── Blocage ─────────────────────────────────────────────────────────────
    public string $blocageDebut = '';

    public string $blocageFin = '';

    public string $blocageMotif = '';

    public function mount(Espace $espace): void
    {
        abort_unless($espace->gerant_id === $this->gerant()->id, 403);

        $this->espace = $espace;
        $this->chargerFiche();
        $this->latitude = $espace->latitude;
        $this->longitude = $espace->longitude;
        $this->adresse = $espace->adresse;
        $this->chargerHoraires();
    }

    // ─── Fiche ───────────────────────────────────────────────────────────────

    public function enregistrerFiche(): void
    {
        $salle = $this->espace->type === 'salle_repetition';

        $data = $this->validate([
            'nom' => ['required', 'string', 'max:255'],
            'ville' => ['required', 'string', 'max:255'],
            'tarif_horaire' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'equipements' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'capacite' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'isolation_phonique' => ['boolean'],
            'type_pelouse' => ['nullable', 'string', 'max:100'],
            'eclairage' => ['boolean'],
            'dimensions' => ['nullable', 'string', 'max:50'],
        ], [], [
            'tarif_horaire' => 'tarif horaire', 'capacite' => 'capacité',
            'type_pelouse' => 'type de pelouse',
        ]);

        $this->espace->update([
            'nom' => trim($data['nom']),
            'ville' => trim($data['ville']),
            'tarif_horaire' => $data['tarif_horaire'],
            'equipements' => collect(explode(',', (string) $data['equipements']))
                ->map(fn ($e) => mb_substr(trim($e), 0, 100))->filter()->unique()->values()->all(),
            'description' => trim((string) $data['description']) ?: null,
            // Caractéristiques propres au type d'espace (TDR §7) ; celles de l'autre type restent vides.
            'capacite' => $salle && $data['capacite'] !== '' ? (int) $data['capacite'] : null,
            'isolation_phonique' => $salle && $data['isolation_phonique'],
            'type_pelouse' => ! $salle ? (trim((string) $data['type_pelouse']) ?: null) : null,
            'eclairage' => ! $salle && $data['eclairage'],
            'dimensions' => ! $salle ? (trim((string) $data['dimensions']) ?: null) : null,
        ]);

        $this->chargerFiche();
        session()->flash('fiche', 'Fiche enregistrée. Les réservations déjà faites gardent leur montant.');
    }

    // ─── Photos ──────────────────────────────────────────────────────────────

    /** Envoi dès la sélection des fichiers (galerie ou appareil photo). */
    public function updatedNouvellesPhotos(): void
    {
        try {
            $this->validate([
                'nouvellesPhotos' => ['array', 'max:' . self::PHOTOS_MAX],
                'nouvellesPhotos.*' => ['image', 'max:8192'], // 8 Mo / fichier
            ], [
                'nouvellesPhotos.max' => self::PHOTOS_MAX . ' photos maximum par envoi.',
                'nouvellesPhotos.*.image' => 'Seules les images sont acceptées.',
                'nouvellesPhotos.*.max' => 'Chaque photo doit faire 8 Mo au maximum.',
            ]);

            if ($this->espace->photos()->count() + count($this->nouvellesPhotos) > self::PHOTOS_MAX) {
                throw ValidationException::withMessages([
                    'nouvellesPhotos' => self::PHOTOS_MAX . ' photos maximum par espace.',
                ]);
            }

            app(PhotoEspaceService::class)->ajouter($this->espace, $this->nouvellesPhotos);
        } finally {
            $this->nouvellesPhotos = [];
        }
    }

    public function supprimerPhoto(int $id, PhotoEspaceService $photos): void
    {
        $photos->supprimer($this->espace->photos()->findOrFail($id));
    }

    public function photoPrincipale(int $id, PhotoEspaceService $photos): void
    {
        $photos->definirPrincipale($this->espace, $this->espace->photos()->findOrFail($id));
    }

    // ─── Localisation ────────────────────────────────────────────────────────

    public function chercherAdresse(GeoService $geo): void
    {
        $this->resetErrorBag('localisation');
        $requete = trim($this->recherche);

        if (mb_strlen($requete) < 3) {
            return;
        }

        $this->resultats = $geo->rechercher(mb_substr($requete, 0, 160));

        if ($this->resultats === []) {
            $this->addError('localisation', 'Aucune adresse trouvée. Placez le marqueur manuellement sur la carte.');
        }
    }

    public function choisirResultat(int $index): void
    {
        $resultat = $this->resultats[$index] ?? null;

        if (! $resultat) {
            return;
        }

        $this->latitude = round($resultat['latitude'], 6);
        $this->longitude = round($resultat['longitude'], 6);
        $this->adresse = $this->adresseCourte($resultat['label']);
        $this->resultats = [];
        $this->recherche = '';

        $this->dispatch('point-deplace', lat: $this->latitude, lng: $this->longitude);
    }

    /** Position GPS du gérant (envoyée par la carte) : on en déduit l'adresse. */
    public function utiliserPosition(float $lat, float $lng, GeoService $geo): void
    {
        abort_unless(abs($lat) <= 90 && abs($lng) <= 180, 422);

        $this->latitude = round($lat, 6);
        $this->longitude = round($lng, 6);

        if ($adresse = $geo->adresseDe($this->latitude, $this->longitude)) {
            $this->adresse = $this->adresseCourte($adresse);
        }
    }

    public function enregistrerLocalisation(): void
    {
        $this->resetErrorBag('localisation');

        if ($this->latitude === null || $this->longitude === null) {
            $this->addError('localisation', "Placez d'abord le marqueur sur la carte.");

            return;
        }

        $data = $this->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'adresse' => ['nullable', 'string', 'max:255'],
        ]);

        $this->espace->update([
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'adresse' => trim($data['adresse']) ?: $this->espace->adresse,
        ]);
        $this->adresse = $this->espace->adresse;

        session()->flash('localisation', 'Localisation enregistrée');
    }

    // ─── Horaires ────────────────────────────────────────────────────────────

    public function enregistrerHoraires(): void
    {
        $this->validate([
            'horaires' => ['required', 'array', 'size:7'],
            'horaires.*.actif' => ['boolean'],
            'horaires.*.o' => ['required', 'date_format:H:i'],
            'horaires.*.f' => ['required', 'date_format:H:i'],
        ], [
            'horaires.*.o.date_format' => "Heure d'ouverture invalide.",
            'horaires.*.f.date_format' => 'Heure de fermeture invalide.',
        ]);

        $actifs = collect($this->horaires)
            ->filter(fn ($h) => $h['actif'])
            ->map(fn ($h, $jour) => ['jour_semaine' => (int) $jour, 'heure_ouverture' => $h['o'], 'heure_fermeture' => $h['f']]);

        if ($actifs->contains(fn ($h) => $h['heure_fermeture'] <= $h['heure_ouverture'])) {
            throw ValidationException::withMessages([
                'horaires' => "L'heure de fermeture doit être après l'heure d'ouverture.",
            ]);
        }

        $this->espace->disponibilites()->delete();
        $this->espace->disponibilites()->createMany($actifs->values()->all());

        session()->flash('horaires', 'Horaires enregistrés.');
    }

    // ─── Plages bloquées ─────────────────────────────────────────────────────

    public function bloquer(): void
    {
        $data = $this->validate([
            'blocageDebut' => ['required', 'date'],
            'blocageFin' => ['required', 'date', 'after:blocageDebut'],
            'blocageMotif' => ['nullable', 'string', 'max:255'],
        ], [
            'blocageFin.after' => 'La fin de la plage doit être après son début.',
        ], [
            'blocageDebut' => 'début', 'blocageFin' => 'fin', 'blocageMotif' => 'motif',
        ]);

        $blocage = [
            'date_debut' => $data['blocageDebut'],
            'date_fin' => $data['blocageFin'],
            'motif' => trim($data['blocageMotif']) ?: null,
        ];

        $this->espace->blocages()->create($blocage);
        $this->espace->journaliser('plage_bloquee', $blocage);

        $this->reset('blocageDebut', 'blocageFin', 'blocageMotif');
    }

    public function debloquer(int $id): void
    {
        $this->espace->blocages()->whereKey($id)->delete();
    }

    public function render()
    {
        return view('livewire.gerant.espace-gestion', [
            'photos' => $this->espace->photos()->get(),
            'blocages' => $this->espace->blocages()->orderBy('date_debut')->get(),
            'jours' => ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
            'photosMax' => self::PHOTOS_MAX,
        ])->title($this->espace->nom);
    }

    private function chargerFiche(): void
    {
        $e = $this->espace;

        $this->nom = $e->nom;
        $this->ville = (string) $e->ville;
        $this->tarif_horaire = (string) (float) $e->tarif_horaire;
        $this->equipements = implode(', ', $e->equipements ?? []);
        $this->description = (string) $e->description;
        $this->capacite = $e->capacite ? (string) $e->capacite : '';
        $this->isolation_phonique = (bool) $e->isolation_phonique;
        $this->type_pelouse = (string) $e->type_pelouse;
        $this->eclairage = (bool) $e->eclairage;
        $this->dimensions = (string) $e->dimensions;
    }

    private function chargerHoraires(): void
    {
        $this->horaires = array_fill(0, 7, ['actif' => false, 'o' => '08:00', 'f' => '20:00']);

        foreach ($this->espace->disponibilites as $d) {
            $this->horaires[$d->jour_semaine] = [
                'actif' => true,
                'o' => substr($d->heure_ouverture, 0, 5),
                'f' => substr($d->heure_fermeture, 0, 5),
            ];
        }
    }

    /** « Rue X, Quartier, Ville, Région, Pays » -> les trois premiers éléments. */
    private function adresseCourte(string $adresse): string
    {
        return mb_substr(implode(', ', array_slice(array_map('trim', explode(',', $adresse)), 0, 3)), 0, 255);
    }
}
