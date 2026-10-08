<?php

namespace App\Livewire\Client;

use App\Models\Reservation;
use App\Services\PaiementService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

/**
 * Paiement de l'acompte via Mobile Money (TDR §8). Le client est redirigé vers
 * la page de paiement hébergée (Yenga) ; à son retour, l'écran sonde le statut
 * jusqu'à réception du webhook de confirmation.
 */
#[Layout('components.layouts.ecran')]
#[Title("Paiement de l'acompte")]
class Paiement extends Component
{
    /** Nombre maximal de sondages (toutes les 3 s) avant d'abandonner. */
    private const SONDAGES_MAX = 20;

    public Reservation $reservation;

    public bool $verification = false;

    public int $sondages = 0;

    public function mount(Reservation $reservation)
    {
        abort_unless($reservation->client_id === auth()->id(), 403);

        $this->reservation = $reservation;

        // Déjà réglée (ou annulée) : rien à payer ici.
        if ($reservation->statut !== 'en_attente') {
            return $this->redirect($this->suite($reservation));
        }
    }

    public function payer(PaiementService $paiements)
    {
        $this->resetErrorBag();

        try {
            $paiements->initier($this->reservation, 'yenga', auth()->user()->telephone);
        } catch (RuntimeException $e) {
            report($e);

            throw ValidationException::withMessages([
                'paiement' => 'Le service de paiement est indisponible pour le moment. Réessayez dans un instant.',
            ]);
        }

        $this->reservation->refresh();

        if ($this->reservation->statut === 'confirmee') {
            return $this->redirect("/reservations/{$this->reservation->id}/confirmation");
        }

        $lien = $this->lienPaiement();

        if (! $lien) {
            throw ValidationException::withMessages([
                'paiement' => 'Lien de paiement indisponible pour le moment. Réessayez.',
            ]);
        }

        return $this->redirect($lien);
    }

    /** « J'ai terminé mon paiement » : démarre le sondage du statut. */
    public function verifier(): void
    {
        $this->resetErrorBag();
        $this->verification = true;
        $this->sondages = 0;
    }

    /** Appelé toutes les 3 s (wire:poll) tant que la vérification est en cours. */
    public function sonder(PaiementService $paiements)
    {
        if (! $this->verification) {
            return;
        }

        $paiement = $this->reservation->paiement()->first();

        if ($paiement) {
            $paiement = $paiements->synchroniser($paiement);
        }

        $this->reservation->refresh();

        if ($this->reservation->statut === 'confirmee') {
            return $this->redirect("/reservations/{$this->reservation->id}/confirmation");
        }

        if ($paiement?->statut === 'echoue') {
            $this->verification = false;
            $this->addError('paiement', 'Le paiement a échoué. Réessayez.');

            return;
        }

        if (++$this->sondages >= self::SONDAGES_MAX) {
            $this->verification = false;
            $this->addError('paiement', 'Toujours en attente de confirmation. Réessayez dans un instant.');
        }
    }

    public function render()
    {
        $this->reservation->loadMissing('espace');
        $paiement = $this->reservation->paiement()->first();

        return view('livewire.client.paiement', [
            'r' => $this->reservation,
            'paiementEnCours' => $paiement?->statut === 'en_cours',
        ]);
    }

    /** Lien de la page de paiement hébergée, tant que le paiement est en cours. */
    private function lienPaiement(): ?string
    {
        $paiement = $this->reservation->paiement()->first();
        $lien = $paiement?->statut === 'en_cours' ? ($paiement->payload['demande']['instructions'] ?? null) : null;

        return is_string($lien) && str_starts_with($lien, 'https://') ? $lien : null;
    }

    private function suite(Reservation $reservation): string
    {
        return in_array($reservation->statut, ['confirmee', 'terminee'], true)
            ? "/reservations/{$reservation->id}/confirmation"
            : "/reservations/{$reservation->id}";
    }
}
