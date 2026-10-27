<?php

namespace App\Livewire\Client;

use App\Models\Espace;
use App\Models\EspaceAbonnementFormule;
use App\Models\Parametre;
use App\Services\AbonnementService;
use App\Services\DisponibiliteService;
use App\Services\ReservationService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Écran « Choisir un créneau » puis création de la réservation (TDR §8). */
#[Layout('components.layouts.ecran')]
#[Title('Choisir un créneau')]
class Reserver extends Component
{
    /** Nombre de jours proposés à la réservation, à partir de demain. */
    private const JOURS_PROPOSES = 14;

    private const DUREES = [1, 2, 3];

    public Espace $espace;

    public string $date = '';

    public int $duree = 1;

    public ?string $creneau = null;

    /** Réservation ponctuelle ou pack de séances récurrentes (TDR §8 — amélioration). */
    public string $mode = 'ponctuelle';

    public ?int $formuleId = null;

    public function mount(Espace $espace): void
    {
        abort_unless($espace->statut === 'valide', 404);

        $this->espace = $espace;
        $this->date = CarbonImmutable::tomorrow()->toDateString();
    }

    public function choisirMode(string $mode): void
    {
        if (in_array($mode, ['ponctuelle', 'abonnement'], true)) {
            $this->mode = $mode;
            $this->creneau = null;
        }
    }

    public function choisirFormule(int $id): void
    {
        $formule = $this->formules()->firstWhere('id', $id);

        if ($formule) {
            $this->formuleId = $id;
            $this->creneau = null;
        }
    }

    public function choisirDate(string $date): void
    {
        if ($this->jours()->contains(fn (CarbonImmutable $j) => $j->toDateString() === $date)) {
            $this->date = $date;
            $this->creneau = null;
        }
    }

    public function choisirDuree(int $heures): void
    {
        if (in_array($heures, self::DUREES, true)) {
            $this->duree = $heures;
            $this->creneau = null;
        }
    }

    public function choisirCreneau(string $heure): void
    {
        $libre = $this->creneaux()->first(fn ($c) => $c['heure'] === $heure && $c['disponible']);

        $this->creneau = $libre ? $heure : null;
    }

    public function reserver(ReservationService $reservations)
    {
        if (! $this->creneau) {
            throw ValidationException::withMessages(['creneau' => 'Choisissez un créneau.']);
        }

        try {
            $reservation = $reservations->creer(
                auth()->user(),
                $this->espace,
                CarbonImmutable::parse("{$this->date} {$this->creneau}"),
                (float) $this->duree,
            );
        } catch (ValidationException $e) {
            // Créneau pris entre-temps : on rafraîchit la grille.
            $this->creneau = null;

            throw $e;
        }

        return $this->redirect("/reservations/{$reservation->id}/paiement");
    }

    public function sabonner(AbonnementService $abonnements)
    {
        if (! $this->creneau) {
            throw ValidationException::withMessages(['creneau' => 'Choisissez un créneau.']);
        }

        $formule = $this->formuleId ? $this->formules()->firstWhere('id', $this->formuleId) : null;

        if (! $formule) {
            throw ValidationException::withMessages(['formule' => 'Choisissez une formule.']);
        }

        try {
            $abonnement = $abonnements->creer(
                auth()->user(),
                $this->espace,
                $formule,
                CarbonImmutable::parse("{$this->date} {$this->creneau}"),
                (float) $this->duree,
            );
        } catch (ValidationException $e) {
            // Un créneau d'une des séances est pris entre-temps : on rafraîchit la grille.
            $this->creneau = null;

            throw $e;
        }

        return $this->redirect("/abonnements/{$abonnement->id}/paiement");
    }

    public function render()
    {
        $tauxAcompte = (float) Parametre::valeur('deposit_rate', config('bookspace.deposit_rate'));
        $prixTotal = (float) $this->espace->tarif_horaire * $this->duree;
        $formule = $this->formuleId ? $this->formules()->firstWhere('id', $this->formuleId) : null;
        $prixFormule = $formule ? (float) $formule->prix : 0.0;

        return view('livewire.client.reserver', [
            'jours' => $this->jours(),
            'durees' => self::DUREES,
            'creneaux' => $this->creneaux(),
            'jour' => CarbonImmutable::parse($this->date),
            'tauxAcompte' => $tauxAcompte,
            'prixTotal' => $prixTotal,
            'acompte' => round($prixTotal * $tauxAcompte / 100),
            'abonnementDisponible' => $this->espace->abonnement_actif && $this->formules()->isNotEmpty(),
            'formules' => $this->formules(),
            'formule' => $formule,
            'acompteFormule' => round($prixFormule * $tauxAcompte / 100),
        ]);
    }

    /** @return \Illuminate\Support\Collection<int, EspaceAbonnementFormule> */
    private function formules()
    {
        return $this->espace->abonnementFormules()->where('actif', true)->get();
    }

    private function jours()
    {
        $demain = CarbonImmutable::tomorrow();

        return collect(range(0, self::JOURS_PROPOSES - 1))->map(fn (int $i) => $demain->addDays($i));
    }

    private function creneaux()
    {
        return app(DisponibiliteService::class)
            ->creneauxDuJour($this->espace, CarbonImmutable::parse($this->date)->startOfDay(), (float) $this->duree);
    }
}
