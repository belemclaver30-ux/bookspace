<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
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
        'notif_sms',
        'notif_rappels',
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
            'notif_sms' => 'boolean',
            'notif_rappels' => 'boolean',
            'visite_terminee_at' => 'datetime',
        ];
    }

    // ─── Rôles ───────────────────────────────────────────────────────────────
    public function estClient(): bool { return $this->role === 'client'; }
    public function estGerant(): bool { return $this->role === 'gerant'; }
    public function estAdmin(): bool { return $this->role === 'admin'; }

    /** « +226 70 00 00 01 » -> « +22670000001 » (identifiant de connexion). */
    public static function normaliserTelephone(string $telephone): string
    {
        return preg_replace('/[\s.\-]+/', '', trim($telephone));
    }

    // ─── Préférences de notification (actives par défaut) ────────────────────
    public function accepteSms(): bool { return $this->notif_sms !== false; }
    public function accepteRappels(): bool { return $this->notif_rappels !== false; }

    /** Écran d'accueil par défaut selon le rôle. */
    public function accueil(): string
    {
        return match ($this->role) {
            'admin' => '/admin',
            'gerant' => '/gerant',
            default => '/app',
        };
    }

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

    /** Espaces mis en favori par le client. */
    public function favoris(): BelongsToMany
    {
        return $this->belongsToMany(Espace::class, 'favoris')->withTimestamps();
    }

    /** URL publique de la photo de profil, ou null (initiale affichée à la place). */
    public function avatarUrl(): ?string
    {
        return $this->avatar ? Storage::disk(config('bookspace.photos_disk', 'public'))->url($this->avatar) : null;
    }
}
