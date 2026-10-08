<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class EspacePhoto extends Model
{
    protected $fillable = ['espace_id', 'chemin', 'ordre'];

    protected $appends = ['url'];

    public function espace(): BelongsTo
    {
        return $this->belongsTo(Espace::class);
    }

    /** URL publique de la photo (disque local en dev, cloud en prod). */
    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->chemin, 'http')) {
            return $this->chemin;
        }

        return Storage::disk(config('bookspace.photos_disk', 'public'))->url($this->chemin);
    }
}
