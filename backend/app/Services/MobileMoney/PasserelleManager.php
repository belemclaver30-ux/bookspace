<?php

namespace App\Services\MobileMoney;

use InvalidArgumentException;

/**
 * Fabrique la passerelle Mobile Money adaptée au mode demandé et au driver
 * configuré (config/bookspace.php → mobile_money.driver).
 */
class PasserelleManager
{
    public function pour(string $mode): PasserelleMobileMoney
    {
        $driver = config('bookspace.mobile_money.driver', 'fake');

        if ($driver === 'fake') {
            return new FakePasserelle($mode);
        }

        return match ($mode) {
            'orange_money' => new OrangeMoneyPasserelle(config('bookspace.mobile_money.orange', [])),
            'moov_money' => new MoovMoneyPasserelle(config('bookspace.mobile_money.moov', [])),
            'yenga' => new YengaPasserelle(config('bookspace.mobile_money.yenga', [])),
            default => throw new InvalidArgumentException("Mode de paiement inconnu : {$mode}"),
        };
    }
}
