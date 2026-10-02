<?php

namespace App\Models;

use App\Support\EnregistreAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Classe Paiement (TDR §7-8) : acompte réglé via Mobile Money.
 */
class Paiement extends Model
{
    use EnregistreAudit;

    protected $fillable = [
        'reservation_id', 'montant', 'mode', 'telephone',
        'reference_operateur', 'statut', 'payload', 'paye_at',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'payload' => 'array',
            'paye_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
