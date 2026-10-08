<?php

namespace App\Services\MobileMoney;

use App\Models\Paiement;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Passerelle Yenga (agrégateur burkinabè Kreezus) — checkout hébergé
 * multi-opérateurs (Orange Money, Moov Money, Telecel Money…).
 * `MOMO_DRIVER=yenga` + identifiants dans .env.
 *
 * Contrat vérifié via la documentation publique Kreezus (readme du plugin
 * WooCommerce officiel) :
 * - POST https://api.yengapay.com/api/v1/groups/{group_id}/payment-intent/{project_id}
 *   headers `x-api-key`, body { paymentAmount, reference, articles[] },
 *   réponse { checkoutPageUrlWithPaymentToken }.
 * - Webhook (IPN) : header `X-Webhook-Hash` = HMAC-SHA256(corps brut, secret),
 *   payload { reference, paymentStatus: DONE|FAILED|PENDING|CANCELLED, id }.
 * Aucun endpoint de consultation de statut n'est documenté : le statut ne
 * progresse que via le webhook (cf. verifierStatut ci-dessous).
 */
class YengaPasserelle implements PasserelleMobileMoney
{
    private const BASE_URL = 'https://api.yengapay.com/api/v1';

    /** @param array{group_id?:string,project_id?:string,api_key?:string,webhook_secret?:string} $config */
    public function __construct(private readonly array $config) {}

    public function demanderPaiement(Paiement $paiement): array
    {
        $this->assertConfig();

        $reservation = $paiement->reservation;

        $reponse = Http::withHeaders([
            'x-api-key' => $this->config['api_key'],
            'Accept' => 'application/json',
        ])->post(
            self::BASE_URL . "/groups/{$this->config['group_id']}/payment-intent/{$this->config['project_id']}",
            [
                'paymentAmount' => (int) $paiement->montant,
                'reference' => $reservation->reference,
                'articles' => [[
                    'title' => "Acompte — {$reservation->espace->nom}",
                    'description' => "Réservation {$reservation->reference}",
                    'price' => (int) $paiement->montant,
                ]],
            ],
        );

        if ($reponse->failed()) {
            throw new RuntimeException("Yenga a refusé la demande : {$reponse->body()}");
        }

        $lien = $reponse->json('checkoutPageUrlWithPaymentToken');

        if (! $lien) {
            throw new RuntimeException('Yenga n\'a pas renvoyé de lien de paiement.');
        }

        return [
            // Notre propre référence : c'est elle que le webhook Yenga nous
            // renverra dans `reference`, pas un identifiant généré par Yenga.
            'reference' => $reservation->reference,
            'instructions' => $lien,
            'statut' => 'en_cours',
        ];
    }

    /**
     * Yenga ne documente aucun endpoint de consultation de statut : la seule
     * source de vérité est le webhook (voir PaiementController::callbackYenga).
     * On renvoie donc toujours `en_cours` — un no-op sûr pour le sondage
     * périodique du front, qui n'avancera que quand le webhook arrive.
     */
    public function verifierStatut(string $referenceOperateur): string
    {
        return 'en_cours';
    }

    public function mode(): string
    {
        return 'yenga';
    }

    private function assertConfig(): void
    {
        foreach (['group_id', 'project_id', 'api_key'] as $cle) {
            if (empty($this->config[$cle])) {
                throw new RuntimeException("Configuration Yenga incomplète : {$cle} manquant.");
            }
        }
    }
}
