<?php

namespace App\Livewire\Gerant;

use App\Livewire\Concerns\AvecGerant;
use App\Models\Reservation;
use App\Services\ReservationService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

/**
 * Réservations reçues par le gérant (TDR §5.2) : calendrier mensuel, liste
 * filtrable, report / annulation, et ajout manuel d'une réservation prise au
 * comptoir ou par téléphone.
 */
#[Layout('components.layouts.dashboard')]
class Reservations extends Component
{
    use AvecGerant;

    public const FILTRES = [
        '' => 'Toutes',
        'confirmee' => 'Confirmées',
        'terminee' => 'Terminées',
        'en_attente' => 'En attente',
        'annulee' => 'Annulées',
    ];

    /** Mois affiché, au format `Y-m`. */
    public string $mois = '';

    /** Jour sélectionné dans le calendrier (`Y-m-d`), ou null pour tout le mois. */
    public ?string $jour = null;

    public string $statut = '';

    // ─── Réservation manuelle ────────────────────────────────────────────────
    public bool $ajout = false;

    public string $espaceId = '';

    public string $date = '';

    public string $duree = '1';

    public string $clientNom = '';

    public string $clientTelephone = '';

    // ─── Report ──────────────────────────────────────────────────────────────
    public ?int $reportId = null;

    public string $reportDate = '';

    public string $reportDuree = '1';

    public function mount(): void
    {
        $this->mois = now()->format('Y-m');
    }

    public function changerMois(int $delta): void
    {
        $this->mois = $this->debutDuMois()->addMonths($delta <=> 0)->format('Y-m');
        $this->jour = null;
    }

    public function choisirJour(?string $jour): void
    {
        $this->jour = $jour && $jour !== $this->jour && preg_match('/^\d{4}-\d{2}-\d{2}$/', $jour) ? $jour : null;
    }

    public function filtrer(string $statut): void
    {
        $this->statut = array_key_exists($statut, self::FILTRES) ? $statut : '';
    }

    // ─── Réservation manuelle ────────────────────────────────────────────────

    public function basculerAjout(): void
    {
        $this->ajout = ! $this->ajout;
        $this->resetErrorBag();

        if ($this->ajout) {
            // Prochaine heure ronde, pour préremplir le créneau.
            $this->date = now()->addHour()->startOfHour()->format('Y-m-d\TH:i');
        }
    }

    public function creerManuelle(ReservationService $reservations): void
    {
        $data = $this->validate([
            'espaceId' => ['required', 'integer'],
            'date' => ['required', 'date', 'after:now'],
            'duree' => ['required', 'numeric', 'min:1', 'max:12'],
            'clientNom' => ['required', 'string', 'max:255'],
            'clientTelephone' => ['required', 'string', 'max:30'],
        ], [
            'espaceId.required' => 'Choisissez un espace.',
            'date.after' => 'Le créneau doit être dans le futur.',
        ], [
            'clientNom' => 'nom du client', 'clientTelephone' => 'téléphone du client', 'date' => 'créneau', 'duree' => 'durée',
        ]);

        $espace = $this->gerant()->espaces()->where('statut', 'valide')->find($data['espaceId']);

        if (! $espace) {
            throw ValidationException::withMessages(['espaceId' => 'Espace introuvable ou non validé.']);
        }

        $debut = CarbonImmutable::parse($data['date']);

        $reservations->creerManuelle(
            $espace, $debut, (float) $data['duree'], trim($data['clientNom']), trim($data['clientTelephone']), auth()->user(),
        );

        $this->reset('ajout', 'espaceId', 'duree', 'clientNom', 'clientTelephone');
        $this->mois = $debut->format('Y-m');
        $this->jour = $debut->toDateString();
    }

    // ─── Report / annulation ─────────────────────────────────────────────────

    public function ouvrirReport(?int $id): void
    {
        $this->resetErrorBag();
        $this->reportId = null;

        if ($id && ($reservation = $this->reservation($id))) {
            $this->reportId = $reservation->id;
            $this->reportDate = $reservation->date_debut->format('Y-m-d\TH:i');
            $this->reportDuree = (string) (float) $reservation->duree_heures;
        }
    }

    public function reporter(ReservationService $reservations): void
    {
        $reservation = $this->reservation((int) $this->reportId);

        $data = $this->validate([
            'reportDate' => ['required', 'date', 'after:now'],
            'reportDuree' => ['required', 'numeric', 'min:1', 'max:12'],
        ], [
            'reportDate.after' => 'Le nouveau créneau doit être dans le futur.',
        ], [
            'reportDate' => 'nouveau créneau', 'reportDuree' => 'durée',
        ]);

        $dureeChangee = (float) $data['reportDuree'] !== (float) $reservation->duree_heures;

        $reservations->reporter(
            $reservation,
            auth()->user(),
            CarbonImmutable::parse($data['reportDate']),
            $dureeChangee ? (float) $data['reportDuree'] : null,
        );

        $this->reportId = null;
    }

    /** Annulation par le gérant, motif obligatoire (communiqué au client). */
    public function annuler(int $id, ?string $motif, ReservationService $reservations): void
    {
        $motif = trim((string) $motif);

        if ($motif === '') {
            return;
        }

        $reservation = $this->reservation($id);

        try {
            $reservations->annuler($reservation, auth()->user(), mb_substr($motif, 0, 500));
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['annulation' => $e->getMessage()]);
        }
    }

    public function render()
    {
        $gerant = $this->gerant();
        $debut = $this->debutDuMois();

        $duMois = Reservation::where('gerant_id', $gerant->id)
            ->whereBetween('date_debut', [$debut, $debut->endOfMonth()])
            ->with(['espace', 'client'])
            ->orderBy('date_debut')
            ->get();

        $parJour = $duMois->groupBy(fn (Reservation $r) => $r->date_debut->toDateString());

        // 6 semaines à partir du lundi de la semaine contenant le 1er.
        $premierLundi = $debut->startOfWeek(CarbonImmutable::MONDAY);
        $cellules = collect(range(0, 41))->map(fn (int $i) => $premierLundi->addDays($i));

        return view('livewire.gerant.reservations', [
            'debut' => $debut,
            'cellules' => $cellules,
            'parJour' => $parJour,
            'totalMois' => $duMois->count(),
            'liste' => $duMois
                ->when($this->jour, fn ($c) => $c->filter(fn ($r) => $r->date_debut->toDateString() === $this->jour))
                ->when($this->statut !== '', fn ($c) => $c->where('statut', $this->statut))
                ->values(),
            'filtres' => self::FILTRES,
            'espacesValides' => $this->ajout ? $gerant->espaces()->where('statut', 'valide')->orderBy('nom')->get(['id', 'nom']) : collect(),
        ]);
    }

    private function debutDuMois(): CarbonImmutable
    {
        $mois = preg_match('/^\d{4}-\d{2}$/', $this->mois) ? $this->mois : now()->format('Y-m');

        return CarbonImmutable::parse("{$mois}-01")->startOfDay();
    }

    /** Réservation du gérant connecté (404 sinon). */
    private function reservation(int $id): Reservation
    {
        return Reservation::where('gerant_id', $this->gerant()->id)->findOrFail($id);
    }
}
