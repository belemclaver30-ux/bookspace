<?php

namespace Tests\Feature;

use App\Models\Espace;
use App\Models\Reservation;
use App\Services\MobileMoney\YengaPasserelle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class YengaPasserelleTest extends TestCase
{
    use RefreshDatabase;

    private function paiement(): \App\Models\Paiement
    {
        $espace = Espace::factory()->create();
        $resa = Reservation::factory()->for($espace)->create(['gerant_id' => $espace->gerant_id]);

        return $resa->paiements()->create([
            'montant' => $resa->montant_acompte,
            'mode' => 'yenga',
            'telephone' => '+22670000001',
            'statut' => 'initie',
        ]);
    }

    public function test_demande_de_paiement_renvoie_le_lien_de_checkout(): void
    {
        Http::fake([
            'api.yengapay.com/*' => Http::response([
                'checkoutPageUrlWithPaymentToken' => 'https://checkout.yengapay.com/pay/abc123',
            ]),
        ]);

        $paiement = $this->paiement();
        $passerelle = new YengaPasserelle([
            'group_id' => 'grp-1', 'project_id' => 'proj-1', 'api_key' => 'test-key',
        ]);

        $demande = $passerelle->demanderPaiement($paiement);

        $this->assertSame($paiement->reservation->reference, $demande['reference']);
        $this->assertSame('https://checkout.yengapay.com/pay/abc123', $demande['instructions']);
        $this->assertSame('en_cours', $demande['statut']);

        Http::assertSent(function ($request) use ($paiement) {
            return $request->url() === "https://api.yengapay.com/api/v1/groups/grp-1/payment-intent/proj-1"
                && $request->hasHeader('x-api-key', 'test-key')
                && $request['paymentAmount'] === (int) $paiement->montant
                && $request['reference'] === $paiement->reservation->reference;
        });
    }

    public function test_echoue_si_configuration_incomplete(): void
    {
        $this->expectException(RuntimeException::class);

        (new YengaPasserelle([]))->demanderPaiement($this->paiement());
    }

    public function test_echoue_si_yenga_refuse_la_demande(): void
    {
        Http::fake(['api.yengapay.com/*' => Http::response(['message' => 'invalid'], 422)]);

        $this->expectException(RuntimeException::class);

        (new YengaPasserelle(['group_id' => 'g', 'project_id' => 'p', 'api_key' => 'k']))
            ->demanderPaiement($this->paiement());
    }

    public function test_verifier_statut_est_un_no_op_faute_de_polling_yenga(): void
    {
        $passerelle = new YengaPasserelle(['group_id' => 'g', 'project_id' => 'p', 'api_key' => 'k']);

        $this->assertSame('en_cours', $passerelle->verifierStatut('peu-importe'));
    }
}
