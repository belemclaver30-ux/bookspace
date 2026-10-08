<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Message d'une conversation client <-> gérant. */
class Message extends Model
{
    protected $fillable = ['conversation_id', 'auteur_id', 'reservation_id', 'contenu', 'lu_at'];

    protected function casts(): array
    {
        return ['lu_at' => 'datetime'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
