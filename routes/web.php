<?php

use App\Livewire\Admin;
use App\Livewire\Auth\Connexion;
use App\Livewire\Auth\Inscription;
use App\Livewire\Auth\MotDePasseOublie;
use App\Livewire\Client;
use App\Livewire\Gerant;
use App\Livewire\Messages;
use App\Livewire\Notifications;
use App\Livewire\Profil;
use App\Livewire\Profil\Aide;
use App\Livewire\Profil\Compte;
use App\Livewire\Profil\Favoris;
use App\Livewire\Profil\Preferences;
use App\Livewire\Profil\Revenus;
use App\Livewire\Profil\Securite;
use App\Models\Reservation;
use App\Services\MessagerieService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Application web BookSpace (Blade + Livewire)
|--------------------------------------------------------------------------
| Trois espaces protégés par rôle : client, gérant, administrateur.
| L'API JSON (routes/api.php) reste disponible pour les webhooks de paiement
| et un éventuel client mobile.
*/

// ─── Visiteur ────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::view('/', 'onboarding')->name('onboarding');
    Route::get('connexion', Connexion::class)->name('login');
    Route::get('inscription', Inscription::class)->name('inscription');
    Route::get('mot-de-passe-oublie', MotDePasseOublie::class)->name('mot-de-passe-oublie');
});

// ─── Pages légales (publiques : liées depuis l'inscription et le profil) ─────
Route::view('conditions', 'legal.conditions')->name('conditions');
Route::view('confidentialite', 'legal.confidentialite')->name('confidentialite');

// ─── Authentifié ─────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::post('deconnexion', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    })->name('deconnexion');

    // Commun à tous les rôles
    Route::get('espaces/{espace}', Client\EspaceFiche::class)->name('espaces.fiche');
    Route::get('notifications', Notifications::class)->name('notifications');
    Route::get('profil', Profil::class)->name('profil');
    Route::get('profil/preferences', Preferences::class)->name('profil.preferences');
    Route::get('profil/aide', Aide::class)->name('profil.aide');
    Route::get('profil/compte', Compte::class)->name('profil.compte');
    Route::get('profil/securite', Securite::class)->name('profil.securite');
    Route::get('profil/favoris', Favoris::class)->middleware('role:client')->name('profil.favoris');
    Route::get('profil/revenus', Revenus::class)->middleware('role:gerant')->name('profil.revenus');

    // ─── Messagerie client <-> gérant ────────────────────────────────────────
    Route::middleware('role:client,gerant')->group(function () {
        Route::get('messages', Messages\Boite::class)->name('messages');
        // Ouvre (ou crée) la conversation liée à une réservation.
        Route::get('messages/reservation/{reservation}', function (Reservation $reservation, MessagerieService $messagerie) {
            $conversation = $messagerie->pourReservation($reservation, auth()->user());

            return redirect("/messages/{$conversation->id}?reservation={$reservation->id}");
        })->name('messages.reservation');
        Route::get('messages/{conversation}', Messages\Fil::class)->name('messages.fil');
    });

    // ─── Client ──────────────────────────────────────────────────────────────
    Route::middleware('role:client')->group(function () {
        Route::get('app', Client\Accueil::class)->name('accueil');
        Route::get('espaces/{espace}/reserver', Client\Reserver::class)->name('espaces.reserver');
        Route::get('reservations', Client\MesReservations::class)->name('reservations');
        Route::get('reservations/{reservation}', Client\ReservationDetail::class)->name('reservations.detail');
        Route::get('reservations/{reservation}/paiement', Client\Paiement::class)->name('reservations.paiement');
        Route::get('reservations/{reservation}/confirmation', Client\Confirmation::class)->name('reservations.confirmation');

        // ─── Abonnements (réservations récurrentes, TDR §8 — amélioration) ──
        Route::get('abonnements', Client\MesAbonnements::class)->name('abonnements');
        Route::get('abonnements/{abonnement}/paiement', Client\AbonnementPaiement::class)->name('abonnements.paiement');
        Route::get('abonnements/{abonnement}/confirmation', Client\AbonnementConfirmation::class)->name('abonnements.confirmation');
    });

    // ─── Gérant ──────────────────────────────────────────────────────────────
    Route::middleware('role:gerant')->prefix('gerant')->name('gerant.')->group(function () {
        Route::get('/', Gerant\TableauDeBord::class)->name('tableau');
        Route::get('espaces', Gerant\Espaces::class)->name('espaces');
        Route::get('espaces/{espace}', Gerant\EspaceGestion::class)->name('espaces.gestion');
        Route::get('reservations', Gerant\Reservations::class)->name('reservations');
        Route::get('demandes', Gerant\Demandes::class)->name('demandes');
    });

    // ─── Administrateur ──────────────────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', Admin\TableauDeBord::class)->name('tableau');
        Route::get('validations', Admin\Validations::class)->name('validations');
        Route::get('gerants', Admin\Gerants::class)->name('gerants');
        Route::get('litiges', Admin\Litiges::class)->name('litiges');
        Route::get('parametres', Admin\Parametres::class)->name('parametres');
    });
});
