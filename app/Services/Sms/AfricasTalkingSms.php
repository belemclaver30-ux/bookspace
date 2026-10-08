<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fournisseur SMS Africa's Talking (https://africastalking.com) — couvre le
 * Burkina Faso. Configurer SMS_DRIVER=africastalking + AT_* dans .env.
 * En sandbox (AT_USERNAME=sandbox), l'API répond normalement mais aucun SMS
 * n'est réellement délivré — pratique pour tester l'intégration sans frais
 * avant de passer sur un compte production.
 */
class AfricasTalkingSms implements PasserelleSms
{
    private const URL_PROD = 'https://api.africastalking.com/version1/messaging';
    private const URL_SANDBOX = 'https://api.sandbox.africastalking.com/version1/messaging';

    /** @param array{username?:string,api_key?:string,sender?:string} $config */
    public function __construct(private readonly array $config) {}

    public function envoyer(string $telephone, string $message): bool
    {
        $username = $this->config['username'] ?? '';
        $apiKey = $this->config['api_key'] ?? '';

        if (empty($username) || empty($apiKey)) {
            Log::warning("[SMS] Africa's Talking non configuré (AT_USERNAME/AT_API_KEY manquant).");

            return false;
        }

        $url = $username === 'sandbox' ? self::URL_SANDBOX : self::URL_PROD;

        $payload = [
            'username' => $username,
            'to' => $telephone,
            'message' => $message,
        ];
        if (! empty($this->config['sender'])) {
            $payload['from'] = $this->config['sender'];
        }

        $reponse = Http::asForm()
            ->withHeaders(['apiKey' => $apiKey, 'Accept' => 'application/json'])
            ->post($url, $payload);

        if ($reponse->failed()) {
            Log::error("[SMS] Africa's Talking : échec envoi à {$telephone} : {$reponse->status()} {$reponse->body()}");

            return false;
        }

        $statut = $reponse->json('SMSMessageData.Recipients.0.status');

        if ($statut !== 'Success') {
            Log::error("[SMS] Africa's Talking : statut inattendu pour {$telephone} : {$reponse->body()}");

            return false;
        }

        return true;
    }
}
