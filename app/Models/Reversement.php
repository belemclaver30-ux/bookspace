<?php

namespace App\Models;

use App\Support\EnregistreAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Versement Mobile Money de BookSpace à un gérant (voir `ReversementService`). */
class Reversement extends Model
{
    use EnregistreAudit;

    protected $fillable = [
        'gerant_id', 'montant', 'nb_reservations',
        'momo_operateur', 'momo_numero', 'momo_titulaire',
        'reference_operateur', 'note', 'effectue_par',
    ];

    protected function casts(): array
    {
        return ['montant' => 'decimal:2'];
    }

    public function gerant(): BelongsTo
    {
        return $this->belongsTo(Gerant::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'effectue_par');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
