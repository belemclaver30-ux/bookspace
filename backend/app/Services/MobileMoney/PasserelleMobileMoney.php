<?php

namespace App\Services\MobileMoney;

use App\Models\Paiement;

/**
 * Contrat d'une passerelle Mobile Money (TDR §10.3).
 * Implémentations : FakePasserelle (dev), OrangeMoneyPasserelle, MoovMoneyPasserelle.
 */
interface PasserelleMobileMoney
{
    /**
     * Démarre une demande de paiement. Renvoie la référence opérateur à
     * conserver, et éventuellement un code/URL de confirmation à présenter au
     * client (USSD push, deep link…).
     *
     * @return array{reference: string, instructions?: string, statut: string}
     */
    public function demanderPaiement(Paiement $paiement): array;

    /**
     * Vérifie l'état d'une transaction auprès de l'opérateur.
     *
     * @return 'valide'|'echoue'|'en_cours'
     */
    public function verifierStatut(string $referenceOperateur): string;

    /** Identifiant du mode : orange_money | moov_money. */
    public function mode(): string;
}
