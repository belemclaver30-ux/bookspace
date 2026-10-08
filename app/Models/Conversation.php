<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Fil de messages entre un client et un gérant (voir MessagerieService). */
class Conversation extends Model
{
    protected $fillable = ['client_id', 'gerant_id', 'dernier_message_at'];

    protected function casts(): array
    {
        return ['dernier_message_at' => 'datetime'];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function gerant(): BelongsTo
    {
        return $this->belongsTo(Gerant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function dernierMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /** Conversations auxquelles l'utilisateur participe (comme client ou comme gérant). */
    public function scopeDe(Builder $q, User $user): Builder
    {
        return $user->estGerant()
            ? $q->where('gerant_id', $user->gerant?->id ?? 0)
            : $q->where('client_id', $user->id);
    }

    public function concerne(User $user): bool
    {
        return $user->estGerant()
            ? $this->gerant_id === $user->gerant?->id
            : $this->client_id === $user->id;
    }

    /** Nom affiché de l'interlocuteur de `$user`. */
    public function interlocuteur(User $user): ?User
    {
        return $user->estGerant() ? $this->client : $this->gerant?->user;
    }
}
