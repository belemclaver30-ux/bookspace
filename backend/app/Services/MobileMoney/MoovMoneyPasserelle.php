<?php

namespace App\Services\MobileMoney;

use App\Models\Paiement;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Passerelle Moov Money (Burkina Faso). À activer avec MOMO_DRIVER=moov.
 * Trame d'intégration — endpoints/champs exacts à confirmer avec Moov Africa.
 */
class MoovMoneyPasserelle implements PasserelleMobileMoney
{
    /** @param array{base_url?:string,token?:string,merchant_id?:string} $config */
    public function __construct(private readonly array $config) {}

    public function demanderPaiement(Paiement $paiement): array
    {
        if (empty($this->config['base_url']) || empty($this->config['token'])) {
            throw new RuntimeException('Configuration Moov Money incomplète.');
        }

        $reponse = Http::withToken($this->config['token'])
            ->acceptJson()
            ->post("{$this->config['base_url']}/transactions/requesttopay", [
                'merchant_id' => $this->config['merchant_id'] ?? null,
                'amount' => (int) $paiement->montant,
                'currency' => 'XOF',
                'external_id' => $paiement->reservation->reference,
                'payer_msisdn' => $paiement->telephone,
                'callback_url' => url('/api/paiements/callback'),
            ]);

        if ($reponse->failed()) {
            throw new RuntimeException("Moov Money a refusé la demande : {$reponse->body()}");
        }

        return [
            'reference' => (string) $reponse->json('transaction_id', $paiement->reservation->reference),
            'instructions' => 'Validez le paiement en composant le code reçu par USSD.',
            'statut' => 'en_cours',
        ];
    }

    public function verifierStatut(string $referenceOperateur): string
    {
        $reponse = Http::withToken($this->config['token'])
            ->acceptJson()
            ->get("{$this->config['base_url']}/transactions/{$referenceOperateur}");

        return match (strtoupper((string) $reponse->json('status'))) {
            'SUCCESSFUL', 'SUCCESS' => 'valide',
            'FAILED', 'REJECTED', 'TIMEOUT' => 'echoue',
            default => 'en_cours',
        };
    }

    public function mode(): string
    {
        return 'moov_money';
    }
}
