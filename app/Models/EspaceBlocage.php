<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Plage bloquée manuellement par le gérant (TDR §5.2). */
class EspaceBlocage extends Model
{
    protected $table = 'espace_blocages';

    protected $fillable = ['espace_id', 'date_debut', 'date_fin', 'motif'];

    protected function casts(): array
    {
        return [
            'date_debut' => 'datetime',
            'date_fin' => 'datetime',
        ];
    }

    public function espace(): BelongsTo
    {
        return $this->belongsTo(Espace::class);
    }
}
