<?php

namespace App\Livewire\Admin;

use App\Models\Gerant;
use App\Models\Reversement;
use App\Services\ReversementService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use RuntimeException;

/**
 * Gérants validés : coordonnées de versement, soldes et enregistrement des
 * reversements Mobile Money (voir `ReversementService`).
 */
#[Layout('components.layouts.dashboard')]
#[Title('Gérants & reversements')]
class Gerants extends Component
{
    #[Url(as: 'q', except: '')]
    public string $recherche = '';

    #[Url(except: false)]
    public bool $aPayer = false;

    /** Gérant dont le formulaire de versement est ouvert. */
    public ?int $versement = null;

    public string $reference = '';

    public string $note = '';

    public function ouvrirVersement(int $id): void
    {
        $this->versement = $this->versement === $id ? null : $id;
        $this->reset('reference', 'note');
        $this->resetErrorBag();
    }

    public function enregistrerVersement(ReversementService $reversements): void
    {
        $data = $this->validate([
            'reference' => ['required', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'reference.required' => 'Indiquez la référence de la transaction Mobile Money.',
        ]);

        $gerant = Gerant::where('statut_validation', 'valide')->findOrFail($this->versement);

        try {
            $reversement = $reversements->enregistrer(
                $gerant, trim($data['reference']), trim((string) $data['note']) ?: null, auth()->user(),
            );
        } catch (RuntimeException $e) {
            $this->addError('reference', $e->getMessage());

            return;
        }

        $this->reset('versement', 'reference', 'note');
        session()->flash('succes', 'Versement de ' . fcfa($reversement->montant) . " à {$gerant->raison_sociale} enregistré ; le gérant a été prévenu.");
    }

    public function render(ReversementService $reversements)
    {
        $soldes = $reversements->soldes();
        $texte = trim($this->recherche);

        $gerants = Gerant::with('user')->withCount('espaces')
            ->where('statut_validation', 'valide')
            ->when($texte !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('raison_sociale', 'like', "%{$texte}%")
                ->orWhere('momo_numero', 'like', "%{$texte}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$texte}%")->orWhere('telephone', 'like', "%{$texte}%"))))
            ->when($this->aPayer, fn ($q) => $q->whereKey(collect($soldes)->filter(fn ($s) => $s['du'] > 0)->keys()->all()))
            ->orderBy('raison_sociale')
            ->get()
            // Les gérants à payer d'abord.
            ->sortByDesc(fn (Gerant $g) => $soldes[$g->id]['du'] ?? 0)
            ->values();

        return view('livewire.admin.gerants', [
            'gerants' => $gerants,
            'soldes' => $soldes,
            'vide' => ['du' => 0.0, 'nb_du' => 0, 'a_venir' => 0.0, 'reverse' => 0.0],
            'totalDu' => array_sum(array_column($soldes, 'du')),
            'totalAVenir' => array_sum(array_column($soldes, 'a_venir')),
            'historique' => Reversement::with('gerant', 'auteur')->latest()->limit(20)->get(),
        ]);
    }
}
