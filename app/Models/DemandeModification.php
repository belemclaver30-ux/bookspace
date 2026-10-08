<?php

namespace App\Models;

use App\Support\EnregistreAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Demande d'annulation ou de report faite par le client sur une réservation
 * confirmée. Le gérant l'accepte (l'action est appliquée) ou la refuse.
 */
class DemandeModification extends Model
{
    use EnregistreAudit;

    protected $table = 'demandes_modification';

    protected $fillable = [
        'reservation_id', 'demandeur_id', 'type', 'statut', 'motif',
        'nouvelle_date_debut', 'nouvelle_duree_heures',
        'reponse_gerant', 'traite_par', 'traitee_at',
    ];

    protected function casts(): array
    {
        return [
            'nouvelle_date_debut' => 'datetime',
            'nouvelle_duree_heures' => 'decimal:2',
            'traitee_at' => 'datetime',
        ];
    }

    public function estEnAttente(): bool
    {
        return $this->statut === 'en_attente';
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'demandeur_id');
    }

    public function traiteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par');
    }
}
