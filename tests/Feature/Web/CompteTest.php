<?php

namespace Tests\Feature\Web;

use App\Livewire\Auth\Connexion;
use App\Livewire\Auth\MotDePasseOublie;
use App\Livewire\Client\EspaceFiche;
use App\Livewire\Profil\Compte;
use App\Livewire\Profil\Favoris;
use App\Livewire\Profil\Securite;
use App\Models\Espace;
use App\Models\Gerant;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Sms\PasserelleSms;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CompteTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{0: string, 1: string}> SMS « envoyés » pendant le test */
    private array $sms = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(PasserelleSms::class, new class($this->sms) implements PasserelleSms {
            public function __construct(private array &$boite) {}

            public function envoyer(string $telephone, string $message): bool
            {
                $this->boite[] = [$telephone, $message];

                return true;
            }
        });
    }

    private function dernierCode(): string
    {
        preg_match('/\b(\d{6})\b/', end($this->sms)[1], $m);

        return $m[1];
    }

    // ─── Mot de passe oublié ─────────────────────────────────────────────────

    public function test_mot_de_passe_oublie_par_code_sms(): void
    {
        $user = User::factory()->client()->create(['telephone' => '+22670000001']);
        $user->createToken('mobile');

        $this->get('/connexion')->assertSee('/mot-de-passe-oublie', false);
        $this->get('/mot-de-passe-oublie')->assertOk();

        $composant = Livewire::test(MotDePasseOublie::class)
            ->set('telephone', '+226 70 00 00 01')
            ->call('envoyerCode')
            ->assertSet('etape', 'code')
            ->assertSee('Renvoyer le code');

        $this->assertCount(1, $this->sms);
        $this->assertSame('+22670000001', $this->sms[0][0]);

        $composant->set('code', $this->dernierCode())
            ->call('verifierCode')
            ->assertHasNoErrors()
            ->assertSet('etape', 'nouveau')
            ->assertSee('Code vérifié')
            ->set('password', 'nouveaumdp')
            ->call('reinitialiser')
            ->assertHasNoErrors()
            ->assertRedirect('/app');

        $this->assertTrue(Hash::check('nouveaumdp', $user->fresh()->password));
        $this->assertAuthenticatedAs($user);
        $this->assertSame(0, $user->tokens()->count());
        $this->assertSame('Mot de passe modifié. Bienvenue à nouveau !', session('toast'));

        // Alerte de sécurité : SMS (même si l'utilisateur a coupé les SMS) + notification.
        $this->assertStringContainsString('mot de passe de votre compte a été modifié', end($this->sms)[1]);
        $this->assertDatabaseHas('app_notifications', ['user_id' => $user->id, 'type' => 'mot_de_passe_modifie']);
    }

    public function test_mot_de_passe_oublie_ne_revele_pas_si_le_numero_existe(): void
    {
        Livewire::test(MotDePasseOublie::class)
            ->set('telephone', '+22679999999')
            ->call('envoyerCode')
            ->assertHasNoErrors()
            ->assertSet('etape', 'code')
            ->assertSee('Si un compte existe')
            ->set('code', '123456')
            ->call('verifierCode')
            ->assertHasErrors('code')
            ->assertSet('etape', 'code');

        $this->assertCount(0, $this->sms);
    }

    /** L'étape « nouveau » ne s'atteint qu'avec un code vérifié côté serveur. */
    public function test_impossible_de_sauter_l_etape_du_code(): void
    {
        $user = User::factory()->client()->create(['telephone' => '+22670000001']);

        Livewire::test(MotDePasseOublie::class)
            ->set('telephone', '+22670000001')->call('envoyerCode')
            ->set('etape', 'nouveau')
            ->set('password', 'piratage1')
            ->call('reinitialiser')
            ->assertHasErrors('code')
            ->assertSet('etape', 'code');

        $this->assertFalse(Hash::check('piratage1', $user->fresh()->password));
        $this->assertGuest();
    }

    public function test_code_faux_puis_trop_d_essais(): void
    {
        $user = User::factory()->client()->create(['telephone' => '+22670000001']);

        $composant = Livewire::test(MotDePasseOublie::class)->set('telephone', '+22670000001')->call('envoyerCode');
        $bon = $this->dernierCode();
        $faux = $bon === '111111' ? '222222' : '111111';

        for ($i = 0; $i < 5; $i++) {
            $composant->set('code', $faux)->call('verifierCode')->assertHasErrors('code')->assertSet('etape', 'code');
        }

        // Même le bon code est refusé après 5 essais : il faut en redemander un.
        $composant->set('code', $bon)->call('verifierCode')->assertHasErrors('code')->assertSet('etape', 'code');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_code_expire(): void
    {
        User::factory()->client()->create(['telephone' => '+22670000001']);

        $composant = Livewire::test(MotDePasseOublie::class)->set('telephone', '+22670000001')->call('envoyerCode');
        $code = $this->dernierCode();

        $this->travel(11)->minutes();

        $composant->set('code', $code)->call('verifierCode')->assertHasErrors('code');
    }

    public function test_trop_lent_apres_le_code(): void
    {
        $user = User::factory()->client()->create(['telephone' => '+22670000001']);

        $composant = Livewire::test(MotDePasseOublie::class)->set('telephone', '+22670000001')->call('envoyerCode');
        $composant->set('code', $this->dernierCode())->call('verifierCode')->assertSet('etape', 'nouveau');

        $this->travel(11)->minutes();

        $composant->set('password', 'nouveaumdp')->call('reinitialiser')
            ->assertHasErrors('code')->assertSet('etape', 'code');
        $this->assertFalse(Hash::check('nouveaumdp', $user->fresh()->password));
    }

    public function test_renvoyer_le_code_apres_le_delai(): void
    {
        User::factory()->client()->create(['telephone' => '+22670000001']);

        $composant = Livewire::test(MotDePasseOublie::class)->set('telephone', '+22670000001')->call('envoyerCode');
        $premier = $this->dernierCode();

        // Trop tôt : refusé, aucun SMS de plus.
        $composant->call('renvoyerCode')->assertHasErrors('code');
        $this->assertCount(1, $this->sms);

        $this->travel(MotDePasseOublie::DELAI_RENVOI + 1)->seconds();
        $composant->call('renvoyerCode')->assertHasNoErrors()->assertSee('Un nouveau code vient');
        $this->assertCount(2, $this->sms);

        // L'ancien code ne vaut plus rien, le nouveau fonctionne.
        if ($premier !== $this->dernierCode()) {
            $composant->set('code', $premier)->call('verifierCode')->assertHasErrors('code');
        }
        $composant->set('code', $this->dernierCode())->call('verifierCode')->assertSet('etape', 'nouveau');
    }

    public function test_envois_de_codes_limites(): void
    {
        User::factory()->client()->create(['telephone' => '+22670000001']);

        for ($i = 0; $i < 3; $i++) {
            Livewire::test(MotDePasseOublie::class)->set('telephone', '+22670000001')->call('envoyerCode')->assertHasNoErrors();
        }

        Livewire::test(MotDePasseOublie::class)->set('telephone', '+22670000001')->call('envoyerCode')->assertHasErrors('telephone');
        $this->assertCount(3, $this->sms);
    }

    public function test_plus_acces_au_numero_mene_au_support(): void
    {
        config(['bookspace.support.whatsapp' => '+226 70 11 22 33', 'bookspace.support.email' => 'aide@bookspace.bf']);

        Livewire::test(MotDePasseOublie::class)
            ->assertSee('Je n’ai plus accès à mon numéro')
            ->set('telephone', '+22670000001')->call('envoyerCode')
            ->call('afficher', 'support')
            ->assertSet('etape', 'support')
            ->assertSee('Plus accès à votre numéro ?')
            ->assertSee('https://wa.me/22670112233?text=', false)
            ->assertSee('mailto:aide@bookspace.bf', false)
            ->call('afficher', 'code')
            ->assertSet('etape', 'code');
    }

    // ─── Mon compte ──────────────────────────────────────────────────────────

    public function test_modifier_ses_informations(): void
    {
        User::factory()->client()->create(['email' => 'pris@exemple.com']);
        $user = User::factory()->client()->create();

        $this->actingAs($user)->get('/profil/compte')->assertOk()->assertSee('Informations personnelles');

        Livewire::actingAs($user)->test(Compte::class)
            ->set('email', 'pris@exemple.com')
            ->call('enregistrerInfos')
            ->assertHasErrors('email')
            ->set('name', '  Awa Ouédraogo ')
            ->set('email', 'awa@exemple.com')
            ->call('enregistrerInfos')
            ->assertHasNoErrors()
            ->assertSet('enregistre', 'infos');

        $this->assertSame('Awa Ouédraogo', $user->fresh()->name);
        $this->assertSame('awa@exemple.com', $user->fresh()->email);
    }

    public function test_le_gerant_modifie_sa_raison_sociale_et_son_compte_de_versement(): void
    {
        $gerant = Gerant::factory()->valide()->create();

        Livewire::actingAs($gerant->user)->test(Compte::class)
            ->set('raison_sociale', 'Studio Harmonie SARL')
            ->call('enregistrerInfos')
            ->assertHasNoErrors()
            ->set('momo_numero', '123')
            ->call('enregistrerVersement')
            ->assertHasErrors(['momo_operateur', 'momo_numero', 'momo_titulaire'])
            ->set('momo_operateur', 'orange_money')
            ->set('momo_numero', '+226 70 11 22 33')
            ->set('momo_titulaire', 'Awa Traoré')
            ->call('enregistrerVersement')
            ->assertHasNoErrors();

        $gerant->refresh();
        $this->assertSame('Studio Harmonie SARL', $gerant->raison_sociale);
        $this->assertSame('orange_money', $gerant->momo_operateur);
        $this->assertSame('+22670112233', $gerant->momo_numero);
    }

    public function test_photo_de_profil(): void
    {
        Storage::fake('public');
        $user = User::factory()->client()->create();

        $composant = Livewire::actingAs($user)->test(Compte::class)
            ->set('photo', UploadedFile::fake()->image('moi.jpg', 600, 600))
            ->assertHasNoErrors();

        $chemin = $user->fresh()->avatar;
        $this->assertStringEndsWith('.webp', $chemin);
        Storage::disk('public')->assertExists($chemin);
        $this->actingAs($user->fresh())->get('/profil')->assertSee($user->fresh()->avatarUrl());

        $composant->set('photo', UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'))->assertHasErrors('photo');

        $composant->call('supprimerPhoto');
        $this->assertNull($user->fresh()->avatar);
        Storage::disk('public')->assertMissing($chemin);
    }

    public function test_changement_de_numero_verifie_par_sms(): void
    {
        User::factory()->client()->create(['telephone' => '+22670000099']);
        $user = User::factory()->client()->create(['telephone' => '+22670000001']);

        $composant = Livewire::actingAs($user)->test(Compte::class)
            ->set('nouveauTelephone', '+22670000099')->set('motDePasseTelephone', 'password')
            ->call('demanderCodeTelephone')
            ->assertHasErrors('nouveauTelephone')                 // numéro déjà pris
            ->set('nouveauTelephone', '+226 70 00 00 02')->set('motDePasseTelephone', 'mauvais')
            ->call('demanderCodeTelephone')
            ->assertHasErrors('motDePasseTelephone')
            ->set('motDePasseTelephone', 'password')
            ->call('demanderCodeTelephone')
            ->assertHasNoErrors()
            ->assertSet('codeEnvoye', true);

        $this->assertSame('+22670000002', end($this->sms)[0]); // code envoyé au NOUVEAU numéro
        $this->assertSame('+22670000001', $user->fresh()->telephone);

        $composant->set('codeTelephone', $this->dernierCode())->call('confirmerTelephone')
            ->assertHasNoErrors()
            ->assertSet('enregistre', 'telephone');

        $this->assertSame('+22670000002', $user->fresh()->telephone);

        // Connexion avec le nouveau numéro.
        auth()->logout();
        Livewire::test(Connexion::class)->set('telephone', '+22670000002')->set('password', 'password')
            ->call('connecter')->assertRedirect('/app');
    }

    // ─── Sécurité ────────────────────────────────────────────────────────────

    public function test_changer_de_mot_de_passe(): void
    {
        $user = User::factory()->client()->create();
        $user->createToken('mobile');

        Livewire::actingAs($user)->test(Securite::class)
            ->set('actuel', 'mauvais')->set('nouveau', 'nouveaumdp')->set('nouveau_confirmation', 'nouveaumdp')
            ->call('changerMotDePasse')
            ->assertHasErrors('actuel')
            ->set('actuel', 'password')->set('nouveau_confirmation', 'autre')
            ->call('changerMotDePasse')
            ->assertHasErrors('nouveau')
            ->set('nouveau_confirmation', 'nouveaumdp')
            ->call('changerMotDePasse')
            ->assertHasNoErrors()
            ->assertSet('enregistre', 'mot_de_passe');

        $this->assertTrue(Hash::check('nouveaumdp', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_deconnecter_les_autres_appareils(): void
    {
        $user = User::factory()->client()->create();
        $ancienHash = $user->password;

        Livewire::actingAs($user)->test(Securite::class)
            ->set('motDePasseAppareils', 'mauvais')->call('deconnecterAutresAppareils')->assertHasErrors('motDePasseAppareils')
            ->set('motDePasseAppareils', 'password')->call('deconnecterAutresAppareils')->assertHasNoErrors()
            ->assertSet('enregistre', 'appareils');

        // Mot de passe inchangé, mais re-haché : les sessions liées à l'ancien hachage tombent.
        $this->assertNotSame($ancienHash, $user->fresh()->password);
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_un_autre_appareil_est_deconnecte_apres_changement_de_mot_de_passe(): void
    {
        $user = User::factory()->client()->create();

        // « Autre appareil » : une session ouverte avec l'ancien mot de passe.
        $this->actingAs($user)->get('/app')->assertOk();

        $user->forceFill(['password' => 'nouveaumdp'])->save();

        $this->get('/app')->assertRedirect('/connexion');
    }

    public function test_supprimer_son_compte_client(): void
    {
        $user = User::factory()->client()->create(['email' => 'moi@exemple.com']);
        $avenir = Reservation::factory()->create(['client_id' => $user->id, 'statut' => 'confirmee']);

        $this->actingAs($user)->get('/profil/securite')->assertSee('réservation à venir');

        $composant = Livewire::actingAs($user)->test(Securite::class)
            ->set('motDePasseSuppression', 'password')->set('confirmeSuppression', true)
            ->call('supprimerCompte')
            ->assertHasErrors('suppression');
        $this->assertTrue($user->fresh()->actif);

        $avenir->update(['statut' => 'terminee']);

        $composant->set('confirmeSuppression', false)->call('supprimerCompte')->assertHasErrors('confirmeSuppression')
            ->set('confirmeSuppression', true)->set('motDePasseSuppression', 'mauvais')->call('supprimerCompte')->assertHasErrors('motDePasseSuppression')
            ->set('motDePasseSuppression', 'password')->call('supprimerCompte')
            ->assertRedirect('/');

        $user->refresh();
        $this->assertFalse($user->actif);
        $this->assertSame('Compte supprimé', $user->name);
        $this->assertNull($user->email);
        $this->assertStringStartsWith('supprime-', $user->telephone);
        $this->assertGuest();
        // L'historique est conservé.
        $this->assertDatabaseHas('reservations', ['id' => $avenir->id, 'client_id' => $user->id]);
        $this->assertDatabaseHas('audit_events', ['sujet_id' => $user->id, 'action' => 'compte_supprime']);
    }

    public function test_supprimer_un_compte_gerant_retire_ses_espaces(): void
    {
        $gerant = Gerant::factory()->valide()->create();
        $espace = Espace::factory()->create(['gerant_id' => $gerant->id]);

        Livewire::actingAs($gerant->user)->test(Securite::class)
            ->set('motDePasseSuppression', 'password')->set('confirmeSuppression', true)
            ->call('supprimerCompte')
            ->assertRedirect('/');

        $this->assertSoftDeleted($espace);
        $this->assertSame('Gérant supprimé', $gerant->fresh()->raison_sociale);
    }

    public function test_un_administrateur_ne_supprime_pas_son_compte(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/profil/securite')->assertOk()->assertDontSee('Supprimer mon compte');
        Livewire::actingAs($admin)->test(Securite::class)->call('supprimerCompte')->assertForbidden();
    }

    // ─── Favoris ─────────────────────────────────────────────────────────────

    public function test_favoris_du_client(): void
    {
        $user = User::factory()->client()->create();
        $espace = Espace::factory()->create(['nom' => 'Studio Harmonie']);

        Livewire::actingAs($user)->test(EspaceFiche::class, ['espace' => $espace])
            ->assertSee('Ajouter aux favoris')
            ->call('basculerFavori')
            ->assertSee('Retirer des favoris');

        $this->actingAs($user)->get('/profil/favoris')->assertOk()->assertSee('Studio Harmonie');
        $this->actingAs($user)->get('/profil')->assertSeeInOrder(['>1<', '>favori<'], false);

        // Un espace suspendu disparaît de la liste.
        $espace->update(['statut' => 'suspendu']);
        $this->get('/profil/favoris')->assertDontSee('Studio Harmonie');
        $espace->update(['statut' => 'valide']);

        Livewire::actingAs($user)->test(Favoris::class)->call('retirer', $espace->id)->assertSee('Aucun favori');
        $this->assertSame(0, $user->favoris()->count());
    }

    public function test_seul_un_client_a_des_favoris(): void
    {
        $gerant = Gerant::factory()->valide()->create();
        $espace = Espace::factory()->create();

        $this->actingAs($gerant->user)->get('/profil/favoris')->assertRedirect('/gerant');
        Livewire::actingAs($gerant->user)->test(EspaceFiche::class, ['espace' => $espace])->call('basculerFavori')->assertForbidden();
    }

    // ─── Revenus ─────────────────────────────────────────────────────────────

    public function test_releve_des_revenus_du_gerant(): void
    {
        $gerant = Gerant::factory()->valide()->create(['momo_operateur' => 'moov_money', 'momo_numero' => '+22660000000', 'momo_titulaire' => 'Awa']);
        $espace = Espace::factory()->create(['gerant_id' => $gerant->id]);
        $debut = CarbonImmutable::now()->startOfMonth()->addDays(2)->setTime(10, 0);

        // En ligne : 10 000 FCFA, acompte 3 000, commission 300, versé 2 700.
        Reservation::factory()->create(['espace_id' => $espace->id, 'statut' => 'terminee', 'date_debut' => $debut, 'date_fin' => $debut->addHours(2)]);
        // Manuelle : 5 000 FCFA encaissés sur place.
        Reservation::factory()->create([
            'espace_id' => $espace->id, 'statut' => 'confirmee', 'origine' => 'gerant', 'client_id' => null,
            'client_nom' => 'Comptoir', 'client_telephone' => '+22670000000',
            'date_debut' => $debut->addHours(3), 'date_fin' => $debut->addHours(4),
            'prix_total' => 5000, 'montant_acompte' => 5000, 'montant_commission' => 0, 'montant_reverse_gerant' => 5000,
        ]);
        // Annulée : ignorée.
        Reservation::factory()->create(['espace_id' => $espace->id, 'statut' => 'annulee', 'date_debut' => $debut->addHours(5), 'date_fin' => $debut->addHours(6)]);

        $this->actingAs($gerant->user)->get('/profil/revenus')->assertOk()
            ->assertSee('15 000 FCFA')     // chiffre d'affaires
            ->assertSee('2 700 FCFA')      // versé par BookSpace
            ->assertSee('12 000 FCFA')     // sur place : 7 000 + 5 000
            ->assertSee('Moov Money');

        $this->actingAs(User::factory()->client()->create())->get('/profil/revenus')->assertRedirect('/app');
    }
}
