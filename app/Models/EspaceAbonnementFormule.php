<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Formule d'abonnement (TDR §8 — amélioration « réservations récurrentes ») :
 * nombre de séances hebdomadaires + prix forfaitaire, fixés librement par le
 * gérant sur un espace donné.
 */
class EspaceAbonnementFormule extends Model
{
    protected $table = 'espace_abonnement_formules';

    protected $fillable = ['espace_id', 'nombre_seances', 'prix', 'actif'];

    protected function casts(): array
    {
        return [
            'nombre_seances' => 'integer',
            'prix' => 'decimal:2',
            'actif' => 'boolean',
        ];
    }

    public function espace(): BelongsTo
    {
        return $this->belongsTo(Espace::class);
    }
}
