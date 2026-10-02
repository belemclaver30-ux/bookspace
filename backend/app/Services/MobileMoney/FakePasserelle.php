<?php

namespace App\Services\MobileMoney;

use App\Models\Paiement;
use Illuminate\Support\Str;

/**
 * Passerelle simulée pour le développement : toute demande de paiement est
 * immédiatement acceptée. `MOMO_DRIVER=fake` dans .env.
 */
class FakePasserelle implements PasserelleMobileMoney
{
    public function __construct(private readonly string $mode = 'fake') {}

    public function demanderPaiement(Paiement $paiement): array
    {
        return [
            'reference' => 'FAKE-' . strtoupper(Str::random(10)),
            'instructions' => 'Paiement simulé — validé automatiquement.',
            'statut' => 'valide',
        ];
    }

    public function verifierStatut(string $referenceOperateur): string
    {
        return 'valide';
    }

    public function mode(): string
    {
        return $this->mode;
    }
}
