<?php

namespace App\Models;

use App\Support\EnregistreAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Classe Abonnement (TDR §8 — amélioration « réservations récurrentes ») :
 * un groupe réserve le même créneau hebdomadaire plusieurs semaines de suite,
 * à un prix forfaitaire fixé par le gérant (formule choisie à la création).
 * Chaque séance est une Reservation normale rattachée via `abonnement_id` :
 * paiement, report ou annulation d'une séance passent par ReservationService
 * sans rien dupliquer ici. Ne porte que le paiement unique de l'acompte et
 * l'annulation globale des séances à venir.
 */
class Abonnement extends Model
{
    use EnregistreAudit;

    protected $fillable = [
        'reference', 'client_id', 'espace_id', 'gerant_id', 'formule_id',
        'nombre_seances', 'duree_heures',
        'prix_total', 'taux_acompte', 'montant_acompte',
        'taux_commission', 'montant_commission', 'montant_reverse_gerant',
        'statut', 'annulee_par', 'motif_annulation',
        'confirmee_at', 'annulee_at',
    ];

    protected function casts(): array
    {
        return [
            'duree_heures' => 'decimal:2',
            'prix_total' => 'decimal:2',
            'taux_acompte' => 'decimal:2',
            'montant_acompte' => 'decimal:2',
            'taux_commission' => 'decimal:2',
            'montant_commission' => 'decimal:2',
            'montant_reverse_gerant' => 'decimal:2',
            'confirmee_at' => 'datetime',
            'annulee_at' => 'datetime',
        ];
    }

    public function estAnnulable(): bool
    {
        return in_array($this->statut, ['en_attente', 'confirmee'], true);
    }

    // ─── Relations ───────────────────────────────────────────────────────────
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function espace(): BelongsTo
    {
        return $this->belongsTo(Espace::class);
    }

    public function gerant(): BelongsTo
    {
        return $this->belongsTo(Gerant::class);
    }

    public function formule(): BelongsTo
    {
        return $this->belongsTo(EspaceAbonnementFormule::class, 'formule_id');
    }

    /** Les séances de l'abonnement, une Reservation par occurrence. */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class)->orderBy('date_debut');
    }

    public function premiereSeance(): ?Reservation
    {
        return $this->reservations->first() ?? $this->reservations()->first();
    }

    /** Paiement unique de l'acompte, rattaché à la première séance. */
    public function paiement(): ?Paiement
    {
        return $this->premiereSeance()?->paiement;
    }
}
