<?php

namespace App\Models;

use App\Support\EnregistreAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Classe abstraite Espace (TDR §7) — héritage single-table.
 * `type` = salle_repetition | terrain_foot.
 */
class Espace extends Model
{
    use EnregistreAudit, HasFactory, SoftDeletes;

    protected $fillable = [
        'gerant_id', 'type', 'nom', 'description', 'adresse', 'ville',
        'tarif_horaire', 'statut', 'motif_rejet', 'equipements',
        'latitude', 'longitude',
        'capacite', 'isolation_phonique',
        'type_pelouse', 'eclairage', 'dimensions',
        'note_moyenne', 'nb_avis', 'mis_en_avant',
    ];

    protected function casts(): array
    {
        return [
            'equipements' => 'array',
            'tarif_horaire' => 'decimal:2',
            'note_moyenne' => 'decimal:2',
            'isolation_phonique' => 'boolean',
            'eclairage' => 'boolean',
            'mis_en_avant' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────
    public function scopePublies(Builder $q): Builder
    {
        return $q->where('statut', 'valide');
    }

    public function scopeRecherche(Builder $q, ?string $ville, ?string $type, ?string $texte): Builder
    {
        return $q
            ->when($ville, fn ($q) => $q->where('ville', 'like', "%{$ville}%"))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($texte, fn ($q) => $q->where(fn ($q) => $q
                ->where('nom', 'like', "%{$texte}%")
                ->orWhere('description', 'like', "%{$texte}%")));
    }

    // ─── Relations ───────────────────────────────────────────────────────────
    public function gerant(): BelongsTo
    {
        return $this->belongsTo(Gerant::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(EspacePhoto::class)->orderBy('ordre');
    }

    public function disponibilites(): HasMany
    {
        return $this->hasMany(EspaceDisponibilite::class);
    }

    public function blocages(): HasMany
    {
        return $this->hasMany(EspaceBlocage::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    /** Recalcule les agrégats d'avis après ajout/suppression d'une évaluation. */
    public function rafraichirNoteMoyenne(): void
    {
        $this->forceFill([
            'note_moyenne' => round((float) $this->evaluations()->avg('note'), 2),
            'nb_avis' => $this->evaluations()->count(),
        ])->saveQuietly();
    }
}
