<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Journal d'historisation append-only (TDR §10.2). */
class AuditEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'auteur_id', 'sujet_type', 'sujet_id', 'action', 'avant', 'apres', 'ip', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'avant' => 'array',
            'apres' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    public function sujet(): MorphTo
    {
        return $this->morphTo('sujet', 'sujet_type', 'sujet_id');
    }
}
