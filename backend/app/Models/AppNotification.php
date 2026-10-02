<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Classe Notification (TDR §7-8) : message client <-> gérant, multi-canal. */
class AppNotification extends Model
{
    protected $table = 'app_notifications';

    protected $fillable = [
        'user_id', 'canal', 'type', 'titre', 'contenu', 'data', 'envoye_at', 'lu_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'envoye_at' => 'datetime',
            'lu_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
