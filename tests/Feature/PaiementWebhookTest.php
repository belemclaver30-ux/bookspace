<?php

namespace Tests\Feature;

use App\Models\Espace;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaiementWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_callback_refuse_sans_signature_quand_un_secret_est_configure(): void
    {
        config(['bookspace.mobile_money.webhook_secret' => 'topsecret']);

        $this->postJson('/api/paiements/callback', [
            'reference_operateur' => 'OP-1',
            'statut' => 'valide',
        ])->assertStatus(401);
    }

    public function test_callback_confirme_la_reservation_avec_une_signature_valide(): void
    {
        config(['bookspace.mobile_money.webhook_secret' => 'topsecret']);

        $espace = Espace::factory()->create();
        $resa = Reservation::factory()->for($espace)->create([
            'gerant_id' => $espace->gerant_id,
            'statut' => 'en_attente',
        ]);
        $paiement = $resa->paiements()->create([
            'montant' => $resa->montant_acompte,
            'mode' => 'orange_money',
            'telephone' => '+22670000000',
            'reference_operateur' => 'OP-XYZ',
            'statut' => 'en_cours',
        ]);

        $payload = ['reference_operateur' => 'OP-XYZ', 'statut' => 'valide'];
        $signature = hash_hmac('sha256', json_encode($payload), 'topsecret');

        $this->withHeaders(['X-Signature' => $signature])
            ->postJson('/api/paiements/callback', $payload)
            ->assertOk();

        $this->assertSame('confirmee', $resa->fresh()->statut);
        $this->assertSame('valide', $paiement->fresh()->statut);
    }

    public function test_callback_reste_ouvert_sans_secret_en_dev(): void
    {
        config(['bookspace.mobile_money.webhook_secret' => null]);

        $this->postJson('/api/paiements/callback', [
            'reference_operateur' => 'inconnu',
            'statut' => 'valide',
        ])->assertOk();
    }

    public function test_callback_yenga_refuse_sans_signature_quand_un_secret_est_configure(): void
    {
        config(['bookspace.mobile_money.yenga.webhook_secret' => 'topsecret']);

        $this->postJson('/api/paiements/callback/yenga', [
            'reference' => 'RES-1',
            'paymentStatus' => 'DONE',
        ])->assertStatus(401);
    }

    public function test_callback_yenga_confirme_la_reservation_quand_done(): void
    {
        config(['bookspace.mobile_money.yenga.webhook_secret' => 'topsecret']);

        $espace = Espace::factory()->create();
        $resa = Reservation::factory()->for($espace)->create([
            'gerant_id' => $espace->gerant_id,
            'statut' => 'en_attente',
        ]);
        $paiement = $resa->paiements()->create([
            'montant' => $resa->montant_acompte,
            'mode' => 'yenga',
            'telephone' => '+22670000000',
            'reference_operateur' => $resa->reference,
            'statut' => 'en_cours',
        ]);

        $payload = ['reference' => $resa->reference, 'paymentStatus' => 'DONE', 'id' => 'yenga-tx-1'];
        $signature = hash_hmac('sha256', json_encode($payload), 'topsecret');

        $this->withHeaders(['X-Webhook-Hash' => $signature])
            ->postJson('/api/paiements/callback/yenga', $payload)
            ->assertOk();

        $this->assertSame('confirmee', $resa->fresh()->statut);
        $this->assertSame('valide', $paiement->fresh()->statut);
    }

    public function test_callback_yenga_echoue_la_reservation_quand_failed(): void
    {
        $espace = Espace::factory()->create();
        $resa = Reservation::factory()->for($espace)->create([
            'gerant_id' => $espace->gerant_id,
            'statut' => 'en_attente',
        ]);
        $paiement = $resa->paiements()->create([
            'montant' => $resa->montant_acompte,
            'mode' => 'yenga',
            'telephone' => '+22670000000',
            'reference_operateur' => $resa->reference,
            'statut' => 'en_cours',
        ]);

        $this->postJson('/api/paiements/callback/yenga', [
            'reference' => $resa->reference,
            'paymentStatus' => 'FAILED',
        ])->assertOk();

        $this->assertSame('en_attente', $resa->fresh()->statut);
        $this->assertSame('echoue', $paiement->fresh()->statut);
    }

    public function test_callback_yenga_ignore_les_statuts_non_definitifs(): void
    {
        $espace = Espace::factory()->create();
        $resa = Reservation::factory()->for($espace)->create([
            'gerant_id' => $espace->gerant_id,
            'statut' => 'en_attente',
        ]);
        $paiement = $resa->paiements()->create([
            'montant' => $resa->montant_acompte,
            'mode' => 'yenga',
            'telephone' => '+22670000000',
            'reference_operateur' => $resa->reference,
            'statut' => 'en_cours',
        ]);

        $this->postJson('/api/paiements/callback/yenga', [
            'reference' => $resa->reference,
            'paymentStatus' => 'PENDING',
        ])->assertOk();

        $this->assertSame('en_cours', $paiement->fresh()->statut);
    }
}
