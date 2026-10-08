<?php

namespace App\Livewire\Gerant;

use App\Livewire\Concerns\AvecGerant;
use App\Models\DemandeModification;
use App\Services\DemandeService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

/** Demandes client (annulation / report) à valider par le gérant (TDR §5.2). */
#[Layout('components.layouts.dashboard')]
class Demandes extends Component
{
    use AvecGerant;

    public const FILTRES = [
        'en_attente' => 'À traiter',
        'acceptee' => 'Acceptées',
        'refusee' => 'Refusées',
    ];

    public string $statut = 'en_attente';

    public function filtrer(string $statut): void
    {
        $this->statut = array_key_exists($statut, self::FILTRES) ? $statut : 'en_attente';
    }

    public function accepter(int $id, DemandeService $demandes): void
    {
        $demande = $this->demande($id);

        try {
            $demandes->accepter($demande, auth()->user());
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['demande' => $e->getMessage()]);
        }
    }

    public function refuser(int $id, ?string $reponse, DemandeService $demandes): void
    {
        $reponse = trim((string) $reponse);

        $demandes->refuser($this->demande($id), auth()->user(), $reponse !== '' ? mb_substr($reponse, 0, 500) : null);
    }

    public function render()
    {
        return view('livewire.gerant.demandes', [
            'demandes' => $this->requete()
                ->where('statut', $this->statut)
                ->with(['reservation.espace', 'demandeur'])
                ->latest()
                ->limit(50)
                ->get(),
            'filtres' => self::FILTRES,
        ]);
    }

    /** Demandes portant sur les réservations du gérant connecté. */
    private function requete()
    {
        $gerantId = $this->gerant()->id;

        return DemandeModification::query()->whereHas('reservation', fn ($q) => $q->where('gerant_id', $gerantId));
    }

    private function demande(int $id): DemandeModification
    {
        return $this->requete()->findOrFail($id);
    }
}
