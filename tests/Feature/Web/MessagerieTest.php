<?php

namespace Tests\Feature\Web;

use App\Livewire\Messages\Boite;
use App\Livewire\Messages\Fil;
use App\Livewire\Notifications;
use App\Models\Conversation;
use App\Models\Espace;
use App\Models\Gerant;
use App\Models\Message;
use App\Models\Reservation;
use App\Models\User;
use App\Services\MessagerieService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MessagerieTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private Gerant $gerant;

    private Reservation $reservation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::factory()->client()->create(['name' => 'Konan Marcel']);
        $this->gerant = Gerant::factory()->valide()->create(['raison_sociale' => 'Studio Harmonie']);
        $espace = Espace::factory()->create(['gerant_id' => $this->gerant->id]);
        $this->reservation = Reservation::factory()->create([
            'client_id' => $this->client->id, 'espace_id' => $espace->id, 'statut' => 'confirmee',
        ]);
    }

    public function test_le_gerant_ecrit_au_client_qui_repond(): void
    {
        // Le gérant ouvre la conversation depuis la réservation.
        $this->actingAs($this->gerant->user)
            ->get("/messages/reservation/{$this->reservation->id}")
            ->assertRedirect();
        $conversation = Conversation::firstOrFail();

        Livewire::actingAs($this->gerant->user)
            ->withQueryParams(['reservation' => $this->reservation->id])
            ->test(Fil::class, ['conversation' => $conversation])
            ->assertSee($this->reservation->reference)
            ->set('contenu', "Bonjour, pensez à apporter vos câbles jack.")
            ->call('envoyer')
            ->assertHasNoErrors()
            ->assertSet('contenu', '')
            ->assertSet('reservationId', null)
            ->assertSee('pensez à apporter vos câbles');

        $premier = Message::firstOrFail();
        $this->assertSame($this->reservation->id, $premier->reservation_id);

        // Le client voit le message non lu dans sa navigation et sa boîte.
        $this->actingAs($this->client);
        $this->assertSame(1, app(MessagerieService::class)->nonLus($this->client));
        $this->get('/messages')->assertOk()->assertSee('Studio Harmonie')->assertSee('pensez à apporter');
        $this->get('/app')->assertSee('1 non lu');

        // Il ouvre le fil (= lu) et répond.
        Livewire::actingAs($this->client)->test(Fil::class, ['conversation' => $conversation])
            ->assertSee('pensez à apporter vos câbles')
            ->set('contenu', 'Merci, ce sera fait !')
            ->call('envoyer')
            ->assertHasNoErrors();

        $this->assertNotNull($premier->fresh()->lu_at);
        $this->assertSame(0, app(MessagerieService::class)->nonLus($this->client));
        $this->assertSame(1, app(MessagerieService::class)->nonLus($this->gerant->user));
        $this->assertSame(2, $conversation->messages()->count());

        Livewire::actingAs($this->gerant->user)->test(Boite::class)
            ->assertSee('Konan Marcel')->assertSee('Merci, ce sera fait');
    }

    public function test_le_client_contacte_le_gerant_depuis_sa_reservation(): void
    {
        $this->actingAs($this->client)->get("/reservations/{$this->reservation->id}")
            ->assertSee("/messages/reservation/{$this->reservation->id}", false)
            ->assertSee('Contacter le gérant');

        $this->get("/messages/reservation/{$this->reservation->id}")
            ->assertRedirect('/messages/' . Conversation::firstOrFail()->id . "?reservation={$this->reservation->id}");

        // Une seule conversation par couple client / gérant.
        $this->get("/messages/reservation/{$this->reservation->id}");
        $this->assertDatabaseCount('conversations', 1);
    }

    public function test_message_vide_refuse(): void
    {
        $conversation = app(MessagerieService::class)->pourReservation($this->reservation, $this->client);

        Livewire::actingAs($this->client)->test(Fil::class, ['conversation' => $conversation])
            ->set('contenu', '   ')
            ->call('envoyer')
            ->assertHasErrors('contenu');

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_pas_de_messagerie_sans_reservation_commune(): void
    {
        $inconnu = User::factory()->client()->create();
        $autreGerant = Gerant::factory()->valide()->create();

        // Ni un autre client, ni un autre gérant ne peuvent ouvrir la conversation.
        $this->actingAs($inconnu)->get("/messages/reservation/{$this->reservation->id}")->assertForbidden();
        $this->actingAs($autreGerant->user)->get("/messages/reservation/{$this->reservation->id}")->assertForbidden();

        $conversation = app(MessagerieService::class)->pourReservation($this->reservation, $this->client);
        $this->actingAs($inconnu)->get("/messages/{$conversation->id}")->assertForbidden();
        $this->actingAs($autreGerant->user)->get("/messages/{$conversation->id}")->assertForbidden();

        // Réservation saisie au comptoir : pas de compte client à qui écrire.
        $manuelle = Reservation::factory()->create([
            'espace_id' => $this->reservation->espace_id, 'client_id' => null, 'origine' => 'gerant',
            'client_nom' => 'Comptoir', 'client_telephone' => '+22670000000', 'statut' => 'confirmee',
            'date_debut' => $this->reservation->date_debut->addDay(), 'date_fin' => $this->reservation->date_fin->addDay(),
        ]);
        $this->actingAs($this->gerant->user)->get("/messages/reservation/{$manuelle->id}")->assertStatus(422);
    }

    public function test_l_administrateur_n_a_pas_de_messagerie(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/messages')->assertRedirect('/admin');
    }

    public function test_une_reservation_d_un_autre_couple_ne_peut_pas_etre_citee(): void
    {
        $conversation = app(MessagerieService::class)->pourReservation($this->reservation, $this->client);
        $etrangere = Reservation::factory()->create();

        app(MessagerieService::class)->envoyer($conversation, $this->client, 'Bonjour', $etrangere->id);

        $this->assertNull(Message::firstOrFail()->reservation_id);
    }

    // ─── Notifications dépliables ────────────────────────────────────────────

    public function test_une_notification_se_deplie_au_clic_et_devient_lue(): void
    {
        $notification = $this->client->notifications()->create([
            'canal' => 'sms', 'type' => 'reservation_confirmee', 'titre' => 'Réservation confirmée',
            'contenu' => 'Votre créneau du 05/10/2026 à 10:00 pour « Studio Harmonie » est confirmé.',
            'data' => ['reservation_id' => $this->reservation->id],
        ]);

        $this->actingAs($this->client)->get('/app')->assertSee('1 non lu');

        Livewire::actingAs($this->client)->test(Notifications::class)
            ->assertSee('1 non lue')
            ->assertDontSee('Voir la réservation')
            ->call('basculer', $notification->id)
            ->assertSet('ouverte', $notification->id)
            ->assertSee('Voir la réservation')
            ->assertSee("/reservations/{$this->reservation->id}", false)
            ->assertSee('également envoyée par SMS')
            ->assertSee('Tout est lu')
            ->call('basculer', $notification->id)
            ->assertSet('ouverte', null);

        $this->assertNotNull($notification->fresh()->lu_at);
    }

    public function test_un_utilisateur_ne_peut_pas_lire_la_notification_d_un_autre(): void
    {
        $autre = User::factory()->client()->create();
        $notification = $autre->notifications()->create(['canal' => 'push', 'type' => 'test', 'titre' => 'Privé', 'contenu' => 'Secret']);

        Livewire::actingAs($this->client)->test(Notifications::class)
            ->assertDontSee('Secret')
            ->call('basculer', $notification->id)
            ->assertDontSee('Secret');

        $this->assertNull($notification->fresh()->lu_at);
    }
}
