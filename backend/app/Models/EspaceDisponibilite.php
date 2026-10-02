<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Horaire d'ouverture hebdomadaire d'un espace (jour_semaine : 0 = dimanche). */
class EspaceDisponibilite extends Model
{
    protected $table = 'espace_disponibilites';

    protected $fillable = ['espace_id', 'jour_semaine', 'heure_ouverture', 'heure_fermeture'];

    protected function casts(): array
    {
        return ['jour_semaine' => 'integer'];
    }

    public function espace(): BelongsTo
    {
        return $this->belongsTo(Espace::class);
    }
}
