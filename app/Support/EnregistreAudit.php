<?php

namespace App\Support;

use App\Models\AuditEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Historisation des événements (TDR §10.2). À utiliser sur les modèles métier
 * dont on veut tracer le cycle de vie (Reservation, Espace, Paiement, Gerant…).
 *
 * - `boot*` enregistre automatiquement les créations et modifications.
 * - `journaliser()` permet de tracer une transition métier nommée
 *   (confirmee, annulee, paiement_valide, validee_par_admin…).
 */
trait EnregistreAudit
{
    public static function bootEnregistreAudit(): void
    {
        static::created(function ($model) {
            $model->ecrireAudit('creee', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $model->ecrireAudit(
                'modifiee',
                array_intersect_key($model->getOriginal(), $model->getChanges()),
                $model->getChanges(),
            );
        });

        static::deleted(function ($model) {
            $model->ecrireAudit('supprimee', $model->getOriginal(), null);
        });
    }

    public function journaliser(string $action, array $contexte = []): AuditEvent
    {
        return $this->ecrireAudit($action, null, $contexte ?: $this->getChanges());
    }

    protected function ecrireAudit(string $action, ?array $avant, ?array $apres): AuditEvent
    {
        return AuditEvent::create([
            'auteur_id' => Auth::id(),
            'sujet_type' => static::class,
            'sujet_id' => $this->getKey(),
            'action' => $action,
            'avant' => $this->nettoyer($avant),
            'apres' => $this->nettoyer($apres),
            'ip' => Request::ip(),
            'created_at' => now(),
        ]);
    }

    private function nettoyer(?array $donnees): ?array
    {
        if ($donnees === null) {
            return null;
        }

        unset($donnees['password'], $donnees['remember_token'], $donnees['updated_at']);

        return $donnees;
    }
}
