<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** Photo de la vue immersive d'un espace : un des 4 angles, ou le panorama 360°. */
class EspaceVue extends Model
{
    /** Positions des angles, dans l'ordre où l'on tourne (sens des aiguilles d'une montre). */
    public const ANGLES = ['Face', 'Droite', 'Arrière', 'Gauche'];

    protected $table = 'espace_vues';

    protected $fillable = ['espace_id', 'type', 'position', 'chemin', 'largeur', 'hauteur'];

    public function espace(): BelongsTo
    {
        return $this->belongsTo(Espace::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk(config('bookspace.photos_disk', 'public'))->url($this->chemin);
    }
}
