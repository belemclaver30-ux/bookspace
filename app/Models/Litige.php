<?php

namespace App\Models;

use App\Support\EnregistreAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Litige client / gérant tranché par l'administrateur (TDR §5.3). */
class Litige extends Model
{
    use EnregistreAudit;

    protected $fillable = [
        'reservation_id', 'ouvert_par', 'sujet', 'description',
        'statut', 'resolution', 'resolu_par', 'resolu_at',
    ];

    protected function casts(): array
    {
        return ['resolu_at' => 'datetime'];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ouvert_par');
    }

    public function resolveur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolu_par');
    }
}
