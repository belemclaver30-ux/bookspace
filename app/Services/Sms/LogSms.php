<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

/** Fournisseur SMS par défaut en dev : écrit le message dans les logs. */
class LogSms implements PasserelleSms
{
    public function envoyer(string $telephone, string $message): bool
    {
        Log::channel('stack')->info("[SMS] {$telephone} : {$message}");

        return true;
    }
}
