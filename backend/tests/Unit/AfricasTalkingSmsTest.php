<?php

namespace Tests\Unit;

use App\Services\Sms\AfricasTalkingSms;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AfricasTalkingSmsTest extends TestCase
{
    public function test_envoi_reussi_en_sandbox(): void
    {
        Http::fake([
            'api.sandbox.africastalking.com/*' => Http::response([
                'SMSMessageData' => [
                    'Recipients' => [['status' => 'Success', 'number' => '+22670000010']],
                ],
            ]),
        ]);

        $sms = new AfricasTalkingSms(['username' => 'sandbox', 'api_key' => 'test-key']);

        $this->assertTrue($sms->envoyer('+22670000010', 'Test'));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.sandbox.africastalking.com/version1/messaging'
                && $request['username'] === 'sandbox'
                && $request['to'] === '+22670000010'
                && $request->hasHeader('apiKey', 'test-key');
        });
    }

    public function test_utilise_url_production_hors_sandbox(): void
    {
        Http::fake([
            'api.africastalking.com/*' => Http::response([
                'SMSMessageData' => ['Recipients' => [['status' => 'Success']]],
            ]),
        ]);

        $sms = new AfricasTalkingSms(['username' => 'monentreprise', 'api_key' => 'test-key']);
        $sms->envoyer('+22670000010', 'Test');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.africastalking.com/version1/messaging');
    }

    public function test_echec_si_non_configure(): void
    {
        $sms = new AfricasTalkingSms([]);

        $this->assertFalse($sms->envoyer('+22670000010', 'Test'));
    }

    public function test_echec_si_statut_operateur_negatif(): void
    {
        Http::fake([
            '*' => Http::response([
                'SMSMessageData' => ['Recipients' => [['status' => 'InvalidPhoneNumber']]],
            ]),
        ]);

        $sms = new AfricasTalkingSms(['username' => 'sandbox', 'api_key' => 'test-key']);

        $this->assertFalse($sms->envoyer('+22670000010', 'Test'));
    }
}
