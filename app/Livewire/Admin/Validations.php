<?php

namespace App\Livewire\Admin;

use App\Models\Espace;
use App\Models\Gerant;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Validation des gérants et des espaces par l'administrateur (TDR §5.3, §15 :
 * « un gérant peut créer un espace et le voir validé par l'administrateur »).
 */
#[Layout('components.layouts.dashboard')]
class Validations extends Component
{
    #[Url]
    public string $onglet = 'gerants';

    public function afficher(string $onglet): void
    {
        $this->onglet = $onglet === 'espaces' ? 'espaces' : 'gerants';
    }

    public function validerGerant(int $id): void
    {
        $gerant = Gerant::findOrFail($id);

        $gerant->update([
            'statut_validation' => 'valide',
            'valide_par' => auth()->id(),
            'valide_at' => now(),
            'motif_rejet' => null,
        ]);
        $gerant->journaliser('gerant_valide');
    }

    public function rejeterGerant(int $id, ?string $motif): void
    {
        if (! ($motif = $this->motif($motif))) {
            return;
        }

        $gerant = Gerant::findOrFail($id);

        $gerant->update([
            'statut_validation' => 'rejete',
            'valide_par' => auth()->id(),
            'motif_rejet' => $motif,
        ]);
        $gerant->journaliser('gerant_rejete', ['motif' => $motif]);
    }

    public function validerEspace(int $id): void
    {
        $espace = Espace::findOrFail($id);

        $espace->update(['statut' => 'valide', 'motif_rejet' => null]);
        $espace->journaliser('espace_valide');
    }

    public function rejeterEspace(int $id, ?string $motif): void
    {
        $this->cloturerEspace($id, 'rejete', $motif);
    }

    /** Retire de la publication un espace déjà validé (TDR §5.3). */
    public function suspendreEspace(int $id, ?string $motif): void
    {
        $this->cloturerEspace($id, 'suspendu', $motif);
    }

    public function render()
    {
        return view('livewire.admin.validations', [
            'gerants' => $this->onglet === 'gerants'
                ? Gerant::with('user')->where('statut_validation', 'en_attente')->latest()->limit(50)->get()
                : collect(),
            'espaces' => $this->onglet === 'espaces'
                ? Espace::with('gerant')->where('statut', 'en_attente')->latest()->limit(50)->get()
                : collect(),
            'publies' => $this->onglet === 'espaces'
                ? Espace::with('gerant')->where('statut', 'valide')->orderBy('nom')->limit(100)->get()
                : collect(),
            'compteurs' => [
                'gerants' => Gerant::where('statut_validation', 'en_attente')->count(),
                'espaces' => Espace::where('statut', 'en_attente')->count(),
            ],
        ]);
    }

    private function cloturerEspace(int $id, string $statut, ?string $motif): void
    {
        if (! ($motif = $this->motif($motif))) {
            return;
        }

        $espace = Espace::findOrFail($id);

        $espace->update(['statut' => $statut, 'motif_rejet' => $motif]);
        $espace->journaliser("espace_{$statut}", ['motif' => $motif]);
    }

    private function motif(?string $motif): ?string
    {
        $motif = mb_substr(trim((string) $motif), 0, 500);

        return $motif !== '' ? $motif : null;
    }
}
