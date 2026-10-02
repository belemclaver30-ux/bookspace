<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Classe Utilisateur (TDR §7) : socle commun aux trois acteurs internes.
 * Le rôle discrimine Client / Gérant / Administrateur.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'telephone',
        'email',
        'password',
        'role',
        'avatar',
        'actif',
        'derniere_connexion_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'derniere_connexion_at' => 'datetime',
            'password' => 'hashed',
            'actif' => 'boolean',
        ];
    }

    // ─── Rôles ───────────────────────────────────────────────────────────────
    public function estClient(): bool { return $this->role === 'client'; }
    public function estGerant(): bool { return $this->role === 'gerant'; }
    public function estAdmin(): bool { return $this->role === 'admin'; }

    // ─── Relations ───────────────────────────────────────────────────────────
    public function gerant(): HasOne
    {
        return $this->hasOne(Gerant::class);
    }

    /** Réservations passées en tant que client. */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'client_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'client_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }
}
