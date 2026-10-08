<?php

namespace App\Livewire\Client;

use App\Models\Reservation;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Écran de confirmation après paiement de l'acompte. */
#[Layout('components.layouts.ecran', ['fond' => 'bg-white'])]
#[Title('Réservation confirmée')]
class Confirmation extends Component
{
    public Reservation $reservation;

    public function mount(Reservation $reservation)
    {
        abort_unless($reservation->client_id === auth()->id(), 403);

        $this->reservation = $reservation;

        // Pas (encore) confirmée : retour au paiement ou au détail.
        if (! in_array($reservation->statut, ['confirmee', 'terminee'], true)) {
            return $this->redirect(
                "/reservations/{$reservation->id}" . ($reservation->statut === 'en_attente' ? '/paiement' : ''),
            );
        }
    }

    public function render()
    {
        return view('livewire.client.confirmation', ['r' => $this->reservation->loadMissing('espace')]);
    }
}
