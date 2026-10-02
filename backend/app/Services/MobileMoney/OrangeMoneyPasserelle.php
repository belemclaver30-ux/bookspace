<?php

namespace App\Services\MobileMoney;

use App\Models\Paiement;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Passerelle Orange Money (Burkina Faso) — API Web Payment.
 * À activer avec MOMO_DRIVER=orange et les identifiants marchands dans .env.
 *
 * Le flux réel : obtention d'un token OAuth, création d'une transaction de
 * paiement, redirection/USSD du client, puis notification asynchrone sur
 * /api/paiements/callback (à sécuriser par vérification de signature).
 * Les appels HTTP ci-dessous sont la trame d'intégration — endpoints exacts à
 * confirmer avec la documentation marchand Orange.
 */
class OrangeMoneyPasserelle implements PasserelleMobileMoney
{
    /** @param array{base_url?:string,client_id?:string,client_secret?:string,merchant_key?:string} $config */
    public function __construct(private readonly array $config) {}

    public function demanderPaiement(Paiement $paiement): array
    {
        $this->assertConfig();

        $token = $this->token();

        $reponse = Http::withToken($token)
            ->acceptJson()
            ->post("{$this->config['base_url']}/webpayment", [
                'merchant_key' => $this->config['merchant_key'] ?? null,
                'currency' => 'XOF',
                'order_id' => $paiement->reservation->reference,
                'amount' => (int) $paiement->montant,
                'return_url' => url("/paiement/retour/{$paiement->id}"),
                'cancel_url' => url("/paiement/retour/{$paiement->id}"),
                'notif_url' => url('/api/paiements/callback'),
                'reference' => $paiement->reservation->reference,
            ]);

        if ($reponse->failed()) {
            throw new RuntimeException("Orange Money a refusé la demande : {$reponse->body()}");
        }

        return [
            'reference' => (string) $reponse->json('pay_token', $reponse->json('order_id')),
            'instructions' => $reponse->json('payment_url'),
            'statut' => 'en_cours',
        ];
    }

    public function verifierStatut(string $referenceOperateur): string
    {
        $reponse = Http::withToken($this->token())
            ->acceptJson()
            ->get("{$this->config['base_url']}/webpayment/{$referenceOperateur}");

        return match ($reponse->json('status')) {
            'SUCCESS', 'SUCCESSFUL' => 'valide',
            'FAILED', 'EXPIRED', 'CANCELLED' => 'echoue',
            default => 'en_cours',
        };
    }

    public function mode(): string
    {
        return 'orange_money';
    }

    private function token(): string
    {
        $reponse = Http::asForm()
            ->withBasicAuth($this->config['client_id'], $this->config['client_secret'])
            ->post("{$this->config['base_url']}/oauth/v3/token", ['grant_type' => 'client_credentials']);

        return (string) $reponse->json('access_token');
    }

    private function assertConfig(): void
    {
        foreach (['base_url', 'client_id', 'client_secret'] as $cle) {
            if (empty($this->config[$cle])) {
                throw new RuntimeException("Configuration Orange Money incomplète : {$cle} manquant.");
            }
        }
    }
}
