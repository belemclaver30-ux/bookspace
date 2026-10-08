<?php

namespace App\Models;

use App\Support\EnregistreAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Profil Gérant (TDR §7). Soumis à validation par l'administrateur avant de
 * pouvoir publier des espaces.
 */
class Gerant extends Model
{
    use EnregistreAudit, HasFactory;

    protected $fillable = [
        'user_id',
        'raison_sociale',
        'telephone_pro',
        'ville',
        'momo_operateur',
        'momo_numero',
        'momo_titulaire',
        'statut_validation',
        'motif_rejet',
        'valide_par',
        'valide_at',
        'abonnement_premium',
        'abonnement_expire_at',
    ];

    protected function casts(): array
    {
        return [
            'valide_at' => 'datetime',
            'abonnement_premium' => 'boolean',
            'abonnement_expire_at' => 'datetime',
        ];
    }

    /** Opérateurs Mobile Money proposés pour le versement des revenus. */
    public const OPERATEURS = [
        'orange_money' => 'Orange Money',
        'moov_money' => 'Moov Money',
        'telecel_money' => 'Telecel Money',
    ];

    public function estValide(): bool
    {
        return $this->statut_validation === 'valide';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function espaces(): HasMany
    {
        return $this->hasMany(Espace::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
