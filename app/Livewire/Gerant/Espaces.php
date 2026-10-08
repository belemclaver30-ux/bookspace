<?php

namespace App\Livewire\Gerant;

use App\Livewire\Concerns\AvecGerant;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Espaces du gérant (TDR §5.2 : créer la fiche, soumettre à validation). Un
 * espace créé est `en_attente` jusqu'à validation par l'administrateur.
 */
#[Layout('components.layouts.dashboard')]
class Espaces extends Component
{
    use AvecGerant;

    public bool $creation = false;

    public string $type = 'salle_repetition';

    public string $nom = '';

    public string $adresse = '';

    public string $ville = 'Ouagadougou';

    public string $tarif_horaire = '';

    public string $equipements = '';

    public string $description = '';

    public function basculerCreation(): void
    {
        $this->creation = ! $this->creation;
        $this->resetErrorBag();
    }

    public function creer(): void
    {
        $gerant = $this->gerant();
        abort_unless($gerant->estValide(), 403, 'Votre compte gérant doit être validé avant de publier un espace.');

        $data = $this->validate([
            'type' => ['required', Rule::in(['salle_repetition', 'terrain_foot'])],
            'nom' => ['required', 'string', 'max:255'],
            'adresse' => ['required', 'string', 'max:255'],
            'ville' => ['required', 'string', 'max:255'],
            'tarif_horaire' => ['required', 'numeric', 'min:0'],
            'equipements' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $gerant->espaces()->create([
            ...$data,
            'nom' => trim($data['nom']),
            'adresse' => trim($data['adresse']),
            'ville' => trim($data['ville']),
            'description' => trim($data['description']) ?: null,
            'equipements' => collect(explode(',', $data['equipements']))
                ->map(fn ($e) => mb_substr(trim($e), 0, 100))->filter()->values()->all(),
            'statut' => 'en_attente',
        ]);

        $this->reset('creation', 'type', 'nom', 'adresse', 'ville', 'tarif_horaire', 'equipements', 'description');
        session()->flash('succes', 'Espace créé : il sera visible des clients après validation par l’administrateur.');
    }

    /** Re-soumettre un espace rejeté à la validation admin. */
    public function soumettre(int $id): void
    {
        $espace = $this->gerant()->espaces()->findOrFail($id);

        $espace->update(['statut' => 'en_attente', 'motif_rejet' => null]);
        $espace->journaliser('soumise_validation');
    }

    public function render()
    {
        $gerant = $this->gerant();

        return view('livewire.gerant.espaces', [
            'valide' => $gerant->estValide(),
            'espaces' => $gerant->espaces()->latest()->get(),
        ]);
    }
}
