<?php

namespace App\Services\Sms;

/** Contrat d'un fournisseur de SMS transactionnels (TDR §10.3). */
interface PasserelleSms
{
    /** @return bool succès de la remise à la passerelle */
    public function envoyer(string $telephone, string $message): bool;
}
