<?php

namespace App\Livewire\Client;

use App\Models\Evaluation;
use App\Models\Reservation;
use App\Services\DemandeService;
use App\Services\ReservationService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

/**
 * Détail d'une réservation du client, avec ses actions :
 * - `en_attente` (non payée) : annulation directe ;
 * - `confirmee` : demande d'annulation ou de report, à valider par le gérant ;
 * - `terminee` : évaluation de l'espace (TDR §5.1).
 */
#[Layout('components.layouts.ecran')]
class ReservationDetail extends Component
{
    public Reservation $reservation;

    /** Formulaire de demande ouvert : '' | 'annulation' | 'report'. */
    public string $mode = '';

    public string $motif = '';

    public string $date = '';

    public string $duree = '1';

    public int $note = 5;

    public string $commentaire = '';

    public bool $avisEnvoye = false;

    public function mount(Reservation $reservation): void
    {
        abort_unless($reservation->client_id === auth()->id(), 403);

        $this->reservation = $reservation;
        $this->date = $reservation->date_debut->format('Y-m-d\TH:i');
        $this->duree = (string) (float) $reservation->duree_heures;
    }

    public function ouvrir(string $mode): void
    {
        $this->mode = in_array($mode, ['annulation', 'report'], true) ? $mode : '';
        $this->resetErrorBag();
    }

    /** Réservation pas encore payée : le client l'annule lui-même. */
    public function annuler(ReservationService $reservations): void
    {
        if ($this->reservation->statut === 'confirmee') {
            throw ValidationException::withMessages([
                'reservation' => "Réservation confirmée : envoyez une demande d'annulation, le gérant doit la valider.",
            ]);
        }

        try {
            $reservations->annuler($this->reservation, auth()->user(), 'Annulée par le client');
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['reservation' => $e->getMessage()]);
        }
    }

    public function envoyerDemande(DemandeService $demandes): void
    {
        $data = $this->validate([
            'mode' => ['required', Rule::in(['annulation', 'report'])],
            'motif' => ['nullable', 'string', 'max:500'],
            'date' => ['required_if:mode,report', 'nullable', 'date', 'after:now'],
            'duree' => ['nullable', 'numeric', 'min:1', 'max:12'],
        ], [
            'date.after' => 'Le nouveau créneau doit être dans le futur.',
        ]);

        $report = $data['mode'] === 'report';
        $dureeChangee = (float) $data['duree'] !== (float) $this->reservation->duree_heures;

        $demandes->creer(
            $this->reservation,
            auth()->user(),
            $data['mode'],
            trim($data['motif']) ?: null,
            $report ? CarbonImmutable::parse($data['date']) : null,
            $report && $dureeChangee ? (float) $data['duree'] : null,
        );

        $this->reset('mode', 'motif');
    }

    public function retirerDemande(DemandeService $demandes): void
    {
        $demande = $this->reservation->demandeEnCours()->first();

        if ($demande) {
            $demandes->retirer($demande);
        }
    }

    public function evaluer(): void
    {
        $data = $this->validate([
            'note' => ['required', 'integer', 'min:1', 'max:5'],
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($this->reservation->statut !== 'terminee') {
            throw ValidationException::withMessages([
                'reservation' => 'Vous pourrez évaluer cet espace une fois la réservation terminée.',
            ]);
        }

        if ($this->reservation->evaluation()->exists()) {
            throw ValidationException::withMessages(['reservation' => 'Cette réservation a déjà été évaluée.']);
        }

        Evaluation::create([
            'reservation_id' => $this->reservation->id,
            'client_id' => auth()->id(),
            'espace_id' => $this->reservation->espace_id,
            'note' => $data['note'],
            'commentaire' => trim($data['commentaire']) ?: null,
        ]);

        $this->avisEnvoye = true;
    }

    public function render()
    {
        // Relit l'état courant : le gérant ou une tâche planifiée a pu le changer.
        $this->reservation->refresh()->load(['espace', 'paiement', 'evaluation', 'derniereDemande']);

        return view('livewire.client.reservation-detail', [
            'r' => $this->reservation,
            'demande' => $this->reservation->derniereDemande,
        ])->title($this->reservation->reference);
    }
}
