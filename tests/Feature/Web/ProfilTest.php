<?php

namespace Tests\Feature\Web;

use App\Livewire\Admin\Litiges;
use App\Livewire\Profil\Aide;
use App\Livewire\Profil\Preferences;
use App\Models\Espace;
use App\Models\Gerant;
use App\Models\Litige;
use App\Models\Reservation;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\Sms\PasserelleSms;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ProfilTest extends TestCase
{
    use RefreshDatabase;

    // ─── Préférences ─────────────────────────────────────────────────────────

    public function test_le_profil_mene_aux_preferences_et_a_l_aide(): void
    {
        $this->actingAs(User::factory()->client()->create());

        $this->get('/profil')->assertOk()
            ->assertSee('/profil/preferences', false)
            ->assertSee('/profil/aide', false)
            ->assertSee('/conditions', false)
            ->assertSee('/confidentialite', false);

        $this->get('/profil/preferences')->assertOk()->assertSee('Recevoir les SMS');
        $this->get('/profil/aide')->assertOk()->assertSee('Questions fréquentes');
    }

    public function test_les_sous_pages_du_profil_exigent_une_connexion(): void
    {
        $this->get('/profil/preferences')->assertRedirect('/connexion');
        $this->get('/profil/aide')->assertRedirect('/connexion');
    }

    public function test_les_preferences_sont_enregistrees_des_qu_elles_changent(): void
    {
        $user = User::factory()->client()->create();

        Livewire::actingAs($user)->test(Preferences::class)
            ->assertSet('notifSms', true)
            ->assertSet('notifRappels', true)
            ->set('notifSms', false)
            ->assertSet('enregistre', 'sms')
            ->assertSee('SMS désactivés')
            ->set('notifRappels', false)
            ->assertSet('enregistre', 'rappels');

        $user->refresh();
        $this->assertFalse($user->notif_sms);
        $this->assertFalse($user->notif_rappels);

        // Les réglages sont relus à la prochaine visite.
        Livewire::actingAs($user)->test(Preferences::class)->assertSet('notifSms', false)->assertSet('notifRappels', false);
    }

    public function test_sms_desactive_seule_la_notification_in_app_part(): void
    {
        $sms = Mockery::spy(PasserelleSms::class);
        $this->app->instance(PasserelleSms::class, $sms);

        $avecSms = User::factory()->client()->create();
        $sansSms = User::factory()->client()->create(['notif_sms' => false]);
        $service = app(NotificationService::class);

        $service->notifier($avecSms, 'test', 'Titre', 'Contenu', [], 'sms');
        $service->notifier($sansSms, 'test', 'Titre', 'Contenu', [], 'sms');

        $sms->shouldHaveReceived('envoyer')->once()->with($avecSms->telephone, Mockery::any());
        $this->assertSame(1, $sansSms->notifications()->count());
    }

    public function test_rappel_desactive_par_le_client_mais_pas_par_le_gerant(): void
    {
        $client = User::factory()->client()->create(['notif_rappels' => false]);
        $reservation = Reservation::factory()->create(['client_id' => $client->id, 'statut' => 'confirmee']);
        $gerant = $reservation->espace->gerant->user;

        app(NotificationService::class)->reservationRappel($reservation);

        $this->assertSame(0, $client->notifications()->where('type', 'reservation_rappel')->count());
        $this->assertSame(1, $gerant->notifications()->where('type', 'reservation_rappel')->count());
    }

    // ─── Aide & support ──────────────────────────────────────────────────────

    public function test_le_centre_d_aide_s_adapte_au_role(): void
    {
        config(['bookspace.support' => ['email' => 'aide@bookspace.bf', 'telephone' => '+226 25 00 00 00', 'whatsapp' => null, 'horaires' => null]]);

        $this->actingAs(User::factory()->client()->create())->get('/profil/aide')
            ->assertSee('Comment réserver un espace ?')
            ->assertSee('aide@bookspace.bf')->assertSee('+226 25 00 00 00')
            ->assertDontSee('WhatsApp')
            ->assertSee('Signaler un problème');

        $this->actingAs(Gerant::factory()->valide()->create()->user)->get('/profil/aide')
            ->assertSee('Comment publier un espace ?')->assertDontSee('Comment réserver un espace ?');

        $this->actingAs(User::factory()->admin()->create())->get('/profil/aide')
            ->assertSee('Comment traiter un signalement ?')->assertDontSee('Signaler un problème');
    }

    public function test_un_client_signale_un_probleme_sur_sa_reservation(): void
    {
        $client = User::factory()->client()->create();
        $reservation = Reservation::factory()->create(['client_id' => $client->id, 'statut' => 'confirmee']);

        $composant = Livewire::actingAs($client)->test(Aide::class)
            ->assertSee($reservation->reference)
            ->set('reservationId', (string) $reservation->id)
            ->set('sujet', 'Salle fermée à mon arrivée')
            ->set('description', 'Personne sur place à 10 h, le gérant ne répond pas.')
            ->call('signaler')
            ->assertHasNoErrors()
            ->assertSet('envoye', true)
            ->assertSet('sujet', '')
            ->assertSee('Mes signalements')
            ->assertSee('Salle fermée à mon arrivée');

        $this->assertDatabaseHas('litiges', [
            'reservation_id' => $reservation->id, 'ouvert_par' => $client->id, 'statut' => 'ouvert',
        ]);

        // Pas de doublon tant que le premier signalement est en cours.
        $composant->set('reservationId', (string) $reservation->id)
            ->set('sujet', 'Encore le même problème')
            ->set('description', 'Je renvoie le même signalement.')
            ->call('signaler')
            ->assertHasErrors('reservationId');

        $this->assertDatabaseCount('litiges', 1);
    }

    public function test_impossible_de_signaler_la_reservation_d_un_autre(): void
    {
        $etrangere = Reservation::factory()->create();

        Livewire::actingAs(User::factory()->client()->create())->test(Aide::class)
            ->assertDontSee($etrangere->reference)
            ->set('reservationId', (string) $etrangere->id)
            ->set('sujet', 'Tentative sur une autre réservation')
            ->set('description', 'Cette réservation ne m’appartient pas.')
            ->call('signaler')
            ->assertHasErrors('reservationId');

        $this->assertDatabaseCount('litiges', 0);
    }

    public function test_un_gerant_signale_un_probleme_et_est_prevenu_de_la_resolution(): void
    {
        $gerant = Gerant::factory()->valide()->create();
        $espace = Espace::factory()->create(['gerant_id' => $gerant->id]);
        $debut = CarbonImmutable::tomorrow()->setTime(10, 0);
        $reservation = Reservation::factory()->create([
            'espace_id' => $espace->id, 'statut' => 'confirmee', 'date_debut' => $debut, 'date_fin' => $debut->addHours(2),
        ]);

        Livewire::actingAs($gerant->user)->test(Aide::class)
            ->set('reservationId', (string) $reservation->id)
            ->set('sujet', 'Client absent sans prévenir')
            ->set('description', 'Le client ne s’est pas présenté au créneau réservé.')
            ->call('signaler')
            ->assertHasNoErrors();

        $litige = Litige::firstOrFail();
        $this->assertSame($gerant->user_id, $litige->ouvert_par);

        // L'administrateur le résout : l'auteur reçoit une notification.
        Livewire::actingAs(User::factory()->admin()->create())->test(Litiges::class)
            ->assertSee('Client absent sans prévenir')
            ->call('traiter', $litige->id, 'resolu', 'Client averti.');

        $notification = $gerant->user->notifications()->where('type', 'litige_traite')->firstOrFail();
        $this->assertStringContainsString('Client averti.', $notification->contenu);

        Livewire::actingAs($gerant->user)->test(Aide::class)->assertSee('Résolu')->assertSee('Client averti.');
    }

    public function test_l_administrateur_ne_peut_pas_ouvrir_de_signalement(): void
    {
        Livewire::actingAs(User::factory()->admin()->create())->test(Aide::class)
            ->call('signaler')
            ->assertForbidden();
    }

    // ─── Pages légales ───────────────────────────────────────────────────────

    public function test_les_pages_legales_sont_publiques(): void
    {
        $this->get('/conditions')->assertOk()->assertSee("Conditions d'utilisation")->assertSee('Réservation et acompte');
        $this->get('/confidentialite')->assertOk()->assertSee('Politique de confidentialité')->assertSee('Données collectées');

        // L'inscription renvoie vers les deux textes à accepter.
        $this->get('/inscription')->assertSee('/conditions', false)->assertSee('/confidentialite', false);
    }
}
