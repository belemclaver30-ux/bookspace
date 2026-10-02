<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fournisseur SMS générique via API HTTP (agrégateur type LeTexto, Airtel/Orange
 * bulk SMS…). Configurer SMS_DRIVER=http + SMS_HTTP_* dans .env.
 */
class HttpSms implements PasserelleSms
{
    /** @param array{url?:string,token?:string,sender?:string} $config */
    public function __construct(private readonly array $config) {}

    public function envoyer(string $telephone, string $message): bool
    {
        if (empty($this->config['url'])) {
            Log::warning('[SMS] URL de la passerelle non configurée.');

            return false;
        }

        $reponse = Http::withToken($this->config['token'] ?? '')
            ->acceptJson()
            ->post($this->config['url'], [
                'to' => $telephone,
                'from' => $this->config['sender'] ?? 'BookSpace',
                'message' => $message,
            ]);

        if ($reponse->failed()) {
            Log::error("[SMS] Échec envoi à {$telephone} : {$reponse->status()} {$reponse->body()}");

            return false;
        }

        return true;
    }
}
