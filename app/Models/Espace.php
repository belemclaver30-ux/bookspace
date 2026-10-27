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

    /** Visuel affiché tant que le gérant n'a pas ajouté de photo. */
    public const PHOTO_PAR_DEFAUT = 'https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?w=800&h=480&fit=crop&auto=format';

    protected $fillable = [
        'gerant_id', 'type', 'nom', 'description', 'adresse', 'ville',
        'tarif_horaire', 'statut', 'motif_rejet', 'equipements',
        'latitude', 'longitude',
        'capacite', 'isolation_phonique',
        'type_pelouse', 'eclairage', 'dimensions',
        'note_moyenne', 'nb_avis', 'mis_en_avant', 'abonnement_actif',
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
            'abonnement_actif' => 'boolean',
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

    /** Vue immersive : 4 angles et/ou panorama 360° (voir EspaceVue). */
    public function vues(): HasMany
    {
        return $this->hasMany(EspaceVue::class)->orderBy('type')->orderBy('position');
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

    public function abonnements(): HasMany
    {
        return $this->hasMany(Abonnement::class);
    }

    /** Formules d'abonnement proposées par le gérant, du plus court au plus long pack. */
    public function abonnementFormules(): HasMany
    {
        return $this->hasMany(EspaceAbonnementFormule::class)->orderBy('nombre_seances');
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
