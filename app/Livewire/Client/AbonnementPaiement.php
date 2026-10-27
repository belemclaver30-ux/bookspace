<?php

namespace App\Livewire\Client;

use App\Models\Abonnement;
use App\Services\PaiementService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

/**
 * Paiement de l'acompte unique d'un abonnement (TDR §8 — amélioration). Même
 * principe que `Paiement` pour une réservation ponctuelle : un seul versement
 * Mobile Money confirme toutes les séances du pack d'un coup.
 */
#[Layout('components.layouts.ecran')]
#[Title("Paiement de l'acompte")]
class AbonnementPaiement extends Component
{
    /** Nombre maximal de sondages (toutes les 3 s) avant d'abandonner. */
    private const SONDAGES_MAX = 20;

    public Abonnement $abonnement;

    public bool $verification = false;

    public int $sondages = 0;

    public function mount(Abonnement $abonnement)
    {
        abort_unless($abonnement->client_id === auth()->id(), 403);

        $this->abonnement = $abonnement;

        // Déjà réglé (ou annulé) : rien à payer ici.
        if ($abonnement->statut !== 'en_attente') {
            return $this->redirect($this->suite($abonnement));
        }
    }

    public function payer(PaiementService $paiements)
    {
        $this->resetErrorBag();

        try {
            $paiements->initierAbonnement($this->abonnement, 'yenga', auth()->user()->telephone);
        } catch (RuntimeException $e) {
            report($e);

            throw ValidationException::withMessages([
                'paiement' => 'Le service de paiement est indisponible pour le moment. Réessayez dans un instant.',
            ]);
        }

        $this->abonnement->refresh();

        if ($this->abonnement->statut === 'confirmee') {
            return $this->redirect("/abonnements/{$this->abonnement->id}/confirmation");
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

        $paiement = $this->abonnement->paiement();

        if ($paiement) {
            $paiement = $paiements->synchroniser($paiement);
        }

        $this->abonnement->refresh();

        if ($this->abonnement->statut === 'confirmee') {
            return $this->redirect("/abonnements/{$this->abonnement->id}/confirmation");
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
        $this->abonnement->loadMissing('espace');
        $paiement = $this->abonnement->paiement();

        return view('livewire.client.abonnement-paiement', [
            'a' => $this->abonnement,
            'paiementEnCours' => $paiement?->statut === 'en_cours',
        ]);
    }

    /** Lien de la page de paiement hébergée, tant que le paiement est en cours. */
    private function lienPaiement(): ?string
    {
        $paiement = $this->abonnement->paiement();
        $lien = $paiement?->statut === 'en_cours' ? ($paiement->payload['demande']['instructions'] ?? null) : null;

        return is_string($lien) && str_starts_with($lien, 'https://') ? $lien : null;
    }

    private function suite(Abonnement $abonnement): string
    {
        return in_array($abonnement->statut, ['confirmee', 'terminee'], true)
            ? "/abonnements/{$abonnement->id}/confirmation"
            : '/abonnements';
    }
}
