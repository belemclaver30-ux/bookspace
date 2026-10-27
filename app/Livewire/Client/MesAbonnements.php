<?php

namespace App\Livewire\Client;

use App\Services\AbonnementService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

/**
 * « Mes abonnements » du client (TDR §8 — amélioration « réservations récurrentes »).
 * Comme pour une réservation ponctuelle, le client n'annule lui-même qu'avant
 * paiement de l'acompte ; une fois confirmé, une demande par séance passe par
 * le fil existant (`DemandeService`, chaque séance restant une Reservation).
 */
#[Layout('components.layouts.client')]
#[Title('Mes abonnements')]
class MesAbonnements extends Component
{
    public function annuler(int $id, AbonnementService $abonnements): void
    {
        $abonnement = auth()->user()->abonnements()->findOrFail($id);

        if ($abonnement->statut !== 'en_attente') {
            throw ValidationException::withMessages([
                'abonnement' => "Abonnement confirmé : contactez le gérant pour annuler une séance.",
            ]);
        }

        try {
            $abonnements->annuler($abonnement, auth()->user(), 'Annulé par le client');
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['abonnement' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.client.mes-abonnements', [
            'abonnements' => auth()->user()->abonnements()->with('espace')->latest()->get(),
        ]);
    }
}
