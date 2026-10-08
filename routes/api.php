<?php

use App\Http\Controllers\Api\Admin\PlateformeController;
use App\Http\Controllers\Api\Admin\ValidationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\DemandeController;
use App\Http\Controllers\Api\EspaceController;
use App\Http\Controllers\Api\EvaluationController;
use App\Http\Controllers\Api\GeoController;
use App\Http\Controllers\Api\Gerant\DemandeController as GerantDemandeController;
use App\Http\Controllers\Api\Gerant\DisponibiliteController;
use App\Http\Controllers\Api\Gerant\EspaceController as GerantEspaceController;
use App\Http\Controllers\Api\Gerant\ReservationController as GerantReservationController;
use App\Http\Controllers\Api\Gerant\StatistiqueController;
use App\Http\Controllers\Api\LitigeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaiementController;
use App\Http\Controllers\Api\ReservationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API BookSpace — plateforme de réservation d'espaces (TDR §10.2)
|--------------------------------------------------------------------------
*/

// ─── Public ──────────────────────────────────────────────────────────────────
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);

Route::get('parametres-publics', ConfigController::class);

Route::get('espaces', [EspaceController::class, 'index']);
Route::get('espaces/{espace}', [EspaceController::class, 'show']);
Route::get('espaces/{espace}/creneaux', [EspaceController::class, 'creneaux']);

// Webhook passerelle Mobile Money (sécurisé par signature en prod).
Route::post('paiements/callback', [PaiementController::class, 'callback']);
// Webhook (IPN) Yenga — format propre à l'agrégateur.
Route::post('paiements/callback/yenga', [PaiementController::class, 'callbackYenga']);

// ─── Authentifié ─────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    // Géolocalisation / itinéraires
    Route::get('geo/recherche', [GeoController::class, 'rechercher']);
    Route::get('geo/inverse', [GeoController::class, 'inverse']);
    Route::get('geo/itineraire', [GeoController::class, 'itineraire']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{notification}/lu', [NotificationController::class, 'marquerLu']);
    Route::post('notifications/tout-lu', [NotificationController::class, 'toutMarquerLu']);

    Route::get('litiges', [LitigeController::class, 'index']);
    Route::post('reservations/{reservation}/litiges', [LitigeController::class, 'store']);

    // ─── Client ──────────────────────────────────────────────────────────────
    Route::middleware('role:client')->group(function () {
        Route::get('reservations', [ReservationController::class, 'index']);
        Route::post('reservations', [ReservationController::class, 'store']);
        Route::get('reservations/{reservation}', [ReservationController::class, 'show']);
        Route::post('reservations/{reservation}/annuler', [ReservationController::class, 'cancel']);
        // Demandes soumises à validation du gérant (résa confirmée)
        Route::post('reservations/{reservation}/demandes', [DemandeController::class, 'store']);
        Route::delete('demandes/{demande}', [DemandeController::class, 'retirer']);
        Route::post('reservations/{reservation}/paiement', [PaiementController::class, 'initier']);
        Route::get('reservations/{reservation}/paiement/statut', [PaiementController::class, 'statut']);
        Route::post('reservations/{reservation}/evaluation', [EvaluationController::class, 'store']);
    });

    // ─── Gérant ──────────────────────────────────────────────────────────────
    Route::middleware('role:gerant')->prefix('gerant')->group(function () {
        Route::get('espaces', [GerantEspaceController::class, 'index']);
        Route::post('espaces', [GerantEspaceController::class, 'store']);
        Route::get('espaces/{espace}', [GerantEspaceController::class, 'show']);
        Route::match(['put', 'patch'], 'espaces/{espace}', [GerantEspaceController::class, 'update']);
        Route::post('espaces/{espace}/soumettre', [GerantEspaceController::class, 'soumettre']);
        Route::delete('espaces/{espace}', [GerantEspaceController::class, 'destroy']);

        Route::post('espaces/{espace}/photos', [GerantEspaceController::class, 'ajouterPhotos']);
        Route::delete('espaces/{espace}/photos/{photo}', [GerantEspaceController::class, 'supprimerPhoto']);
        Route::post('espaces/{espace}/photos/{photo}/principale', [GerantEspaceController::class, 'photoPrincipale']);

        Route::post('espaces/{espace}/horaires', [DisponibiliteController::class, 'horaires']);
        Route::post('espaces/{espace}/blocages', [DisponibiliteController::class, 'bloquer']);
        Route::delete('espaces/{espace}/blocages/{blocage}', [DisponibiliteController::class, 'debloquer']);

        Route::post('espaces/{espace}/reservations', [GerantReservationController::class, 'store']);

        Route::get('reservations', [GerantReservationController::class, 'index']);
        Route::get('reservations/{reservation}', [GerantReservationController::class, 'show']);
        Route::post('reservations/{reservation}/annuler', [GerantReservationController::class, 'annuler']);
        Route::post('reservations/{reservation}/reporter', [GerantReservationController::class, 'reporter']);

        // Demandes client (annulation / report) à valider
        Route::get('demandes', [GerantDemandeController::class, 'index']);
        Route::post('demandes/{demande}/accepter', [GerantDemandeController::class, 'accepter']);
        Route::post('demandes/{demande}/refuser', [GerantDemandeController::class, 'refuser']);

        Route::get('statistiques', StatistiqueController::class);
    });

    // ─── Administrateur ──────────────────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('tableau-de-bord', [PlateformeController::class, 'tableauDeBord']);

        Route::get('gerants/en-attente', [ValidationController::class, 'gerantsEnAttente']);
        Route::post('gerants/{gerant}/valider', [ValidationController::class, 'validerGerant']);
        Route::post('gerants/{gerant}/rejeter', [ValidationController::class, 'rejeterGerant']);

        Route::get('espaces/en-attente', [ValidationController::class, 'espacesEnAttente']);
        Route::post('espaces/{espace}/valider', [ValidationController::class, 'validerEspace']);
        Route::post('espaces/{espace}/rejeter', [ValidationController::class, 'rejeterEspace']);
        Route::post('espaces/{espace}/suspendre', [ValidationController::class, 'suspendreEspace']);

        Route::get('parametres', [PlateformeController::class, 'parametres']);
        Route::put('parametres/{cle}', [PlateformeController::class, 'majParametre']);

        Route::get('litiges', [PlateformeController::class, 'litiges']);
        Route::post('litiges/{litige}/resoudre', [PlateformeController::class, 'resoudreLitige']);
    });
});
