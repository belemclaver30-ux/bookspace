<?php

namespace Tests\Feature;

use App\Models\Espace;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationRappelTest extends TestCase
{
    use RefreshDatabase;

    public function test_rappel_envoye_au_client_et_au_gerant_30_min_avant(): void
    {
        config(['bookspace.rappel_minutes_avant' => 30]);

        $espace = Espace::factory()->create();
        $resa = Reservation::factory()->for($espace)->create([
            'gerant_id' => $espace->gerant_id,
            'statut' => 'confirmee',
            'date_debut' => now()->addMinutes(35),
        ]);

        $this->artisan('reservations:rappeler')->assertSuccessful();

        $this->assertNotNull($resa->fresh()->rappel_envoye_at);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $resa->client_id,
            'type' => 'reservation_rappel',
            'canal' => 'sms',
        ]);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $espace->gerant->user_id,
            'type' => 'reservation_rappel',
            'canal' => 'sms',
        ]);
    }

    public function test_pas_de_rappel_hors_fenetre_ou_deja_envoye(): void
    {
        config(['bookspace.rappel_minutes_avant' => 30]);

        $espace = Espace::factory()->create();

        $trop_tot = Reservation::factory()->for($espace)->create([
            'gerant_id' => $espace->gerant_id,
            'statut' => 'confirmee',
            'date_debut' => now()->addHours(3),
        ]);
        $deja_rappelee = Reservation::factory()->for($espace)->create([
            'gerant_id' => $espace->gerant_id,
            'statut' => 'confirmee',
            'date_debut' => now()->addMinutes(35),
            'rappel_envoye_at' => now()->subMinutes(2),
        ]);
        $non_confirmee = Reservation::factory()->for($espace)->create([
            'gerant_id' => $espace->gerant_id,
            'statut' => 'en_attente',
            'date_debut' => now()->addMinutes(35),
        ]);

        $this->artisan('reservations:rappeler')->assertSuccessful();

        $this->assertNull($trop_tot->fresh()->rappel_envoye_at);
        $this->assertNull($non_confirmee->fresh()->rappel_envoye_at);
        $this->assertDatabaseMissing('app_notifications', ['user_id' => $trop_tot->client_id, 'type' => 'reservation_rappel']);
        $this->assertDatabaseMissing('app_notifications', ['user_id' => $non_confirmee->client_id, 'type' => 'reservation_rappel']);

        // La réservation déjà rappelée n'est pas re-traitée (pas de nouvelle notif).
        $this->assertDatabaseCount('app_notifications', 0);
        $this->assertSame(
            $deja_rappelee->rappel_envoye_at->timestamp,
            $deja_rappelee->fresh()->rappel_envoye_at->timestamp,
        );
    }
}
