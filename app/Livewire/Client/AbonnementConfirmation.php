<?php

namespace App\Livewire\Client;

use App\Models\Abonnement;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Écran de confirmation après paiement de l'acompte unique d'un abonnement. */
#[Layout('components.layouts.ecran', ['fond' => 'bg-white'])]
#[Title('Abonnement confirmé')]
class AbonnementConfirmation extends Component
{
    public Abonnement $abonnement;

    public function mount(Abonnement $abonnement)
    {
        abort_unless($abonnement->client_id === auth()->id(), 403);

        $this->abonnement = $abonnement;

        // Pas (encore) confirmé : retour au paiement ou à la liste.
        if (! in_array($abonnement->statut, ['confirmee', 'terminee'], true)) {
            return $this->redirect(
                $abonnement->statut === 'en_attente' ? "/abonnements/{$abonnement->id}/paiement" : '/abonnements',
            );
        }
    }

    public function render()
    {
        return view('livewire.client.abonnement-confirmation', [
            'a' => $this->abonnement->loadMissing('espace', 'reservations'),
        ]);
    }
}
