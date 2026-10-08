<?php

namespace App\Models;

use App\Support\EnregistreAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Classe Reservation (TDR §7-8). Cycle de vie :
 * en_attente → (paiement acompte validé) → confirmee → terminee
 *            → annulee (par client ou gérant, selon conditions).
 */
class Reservation extends Model
{
    use EnregistreAudit, HasFactory;

    protected $fillable = [
        'reference', 'client_id', 'client_nom', 'client_telephone', 'origine', 'espace_id', 'gerant_id',
        'date_debut', 'date_fin', 'duree_heures',
        'prix_total', 'taux_acompte', 'montant_acompte',
        'taux_commission', 'montant_commission', 'montant_reverse_gerant', 'reversement_id',
        'statut', 'annulee_par', 'motif_annulation',
        'confirmee_at', 'annulee_at', 'rappel_envoye_at',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'datetime',
            'date_fin' => 'datetime',
            'duree_heures' => 'decimal:2',
            'prix_total' => 'decimal:2',
            'taux_acompte' => 'decimal:2',
            'montant_acompte' => 'decimal:2',
            'taux_commission' => 'decimal:2',
            'montant_commission' => 'decimal:2',
            'montant_reverse_gerant' => 'decimal:2',
            'confirmee_at' => 'datetime',
            'annulee_at' => 'datetime',
            'rappel_envoye_at' => 'datetime',
        ];
    }

    public function estAnnulable(): bool
    {
        return in_array($this->statut, ['en_attente', 'confirmee'], true)
            && $this->date_debut->isFuture();
    }

    /** Une réservation encore à venir et active peut être déplacée. */
    public function estReportable(): bool
    {
        return $this->estAnnulable();
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

    public function reversement(): BelongsTo
    {
        return $this->belongsTo(Reversement::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    /** Paiement d'acompte le plus récent. */
    public function paiement(): HasOne
    {
        return $this->hasOne(Paiement::class)->latestOfMany();
    }

    public function evaluation(): HasOne
    {
        return $this->hasOne(Evaluation::class);
    }

    public function litiges(): HasMany
    {
        return $this->hasMany(Litige::class);
    }

    public function demandes(): HasMany
    {
        return $this->hasMany(DemandeModification::class);
    }

    /** Dernière demande de modification, quel que soit son statut. */
    public function derniereDemande(): HasOne
    {
        return $this->hasOne(DemandeModification::class)->latestOfMany();
    }

    public function demandeEnCours(): HasOne
    {
        return $this->hasOne(DemandeModification::class)->where('statut', 'en_attente')->latestOfMany();
    }
}
