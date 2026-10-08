<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Classe Evaluation (TDR §7) : avis client sur un espace après réservation. */
class Evaluation extends Model
{
    protected $fillable = ['reservation_id', 'client_id', 'espace_id', 'note', 'commentaire'];

    protected function casts(): array
    {
        return ['note' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saved(fn (Evaluation $e) => $e->espace?->rafraichirNoteMoyenne());
        static::deleted(fn (Evaluation $e) => $e->espace?->rafraichirNoteMoyenne());
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function espace(): BelongsTo
    {
        return $this->belongsTo(Espace::class);
    }
}
