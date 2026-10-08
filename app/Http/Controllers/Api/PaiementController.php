<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Services\PaiementService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Paiement de l'acompte via Mobile Money (TDR §8).
 * `initier` est appelé par le client ; `callback` par la passerelle (webhook).
 */
class PaiementController extends Controller
{
    public function __construct(private readonly PaiementService $service) {}

    /**
     * Déclenche le paiement via Yenga : aucune saisie côté client, l'app
     * redirige ensuite vers la page de paiement hébergée (l'opérateur —
     * Orange/Moov/Telecel Money — est choisi là-bas).
     */
    public function initier(Request $request, Reservation $reservation): ReservationResource
    {
        abort_unless($reservation->client_id === $request->user()->id, 403);

        if ($reservation->statut !== 'en_attente') {
            throw ValidationException::withMessages([
                'reservation' => 'Cette réservation n\'attend pas de paiement.',
            ]);
        }

        $this->service->initier($reservation, 'yenga', $request->user()->telephone);

        return new ReservationResource(
            $reservation->fresh()->load(['espace.photos', 'paiement'])
        );
    }

    /** Interrogé par le client pendant l'attente de validation Mobile Money. */
    public function statut(Request $request, Reservation $reservation): ReservationResource
    {
        abort_unless($reservation->client_id === $request->user()->id, 403);

        $paiement = $reservation->paiement()->first();
        if ($paiement) {
            $this->service->synchroniser($paiement);
        }

        return new ReservationResource(
            $reservation->fresh()->load(['espace.photos', 'paiement'])
        );
    }

    /**
     * Webhook passerelle Mobile Money. Non protégé par Sanctum (appelé par
     * l'opérateur) mais authentifié par signature HMAC-SHA256 du corps brut
     * dès qu'un secret est configuré (`bookspace.mobile_money.webhook_secret`).
     */
    public function callback(Request $request)
    {
        $this->verifierSignature($request);

        $data = $request->validate([
            'reference_operateur' => ['required', 'string'],
            'statut' => ['required', Rule::in(['valide', 'echoue'])],
        ]);

        $this->service->confirmerCallback(
            $data['reference_operateur'],
            $data['statut'] === 'valide',
            $request->except(['reference_operateur', 'statut']),
        );

        return response()->json(['message' => 'ok']);
    }

    /**
     * Rejette le webhook si un secret est configuré et que la signature
     * `X-Signature` ne correspond pas au HMAC-SHA256 du corps brut.
     */
    private function verifierSignature(Request $request): void
    {
        $secret = config('bookspace.mobile_money.webhook_secret');

        if (! $secret) {
            return; // Dev / driver `fake` : pas de secret, vérification désactivée.
        }

        $attendu = hash_hmac('sha256', $request->getContent(), $secret);
        $recu = (string) $request->header('X-Signature', '');

        abort_unless(
            $recu !== '' && hash_equals($attendu, $recu),
            401,
            'Signature du webhook invalide.',
        );
    }

    /**
     * Webhook (IPN) Yenga — format propre à l'agrégateur : en-tête
     * `X-Webhook-Hash` (HMAC-SHA256 du corps brut) et payload
     * { reference, paymentStatus: DONE|FAILED|PENDING|CANCELLED, id }.
     */
    public function callbackYenga(Request $request)
    {
        $this->verifierSignatureYenga($request);

        $data = $request->validate([
            'reference' => ['required', 'string'],
            'paymentStatus' => ['required', 'string'],
        ]);

        // PENDING (et tout statut non définitif) : on attend la notification
        // suivante, rien à trancher pour l'instant.
        if (! in_array($data['paymentStatus'], ['DONE', 'FAILED', 'CANCELLED'], true)) {
            return response()->json(['message' => 'ignore']);
        }

        $this->service->confirmerCallback(
            $data['reference'],
            $data['paymentStatus'] === 'DONE',
            $request->except(['reference', 'paymentStatus']),
        );

        return response()->json(['message' => 'ok']);
    }

    private function verifierSignatureYenga(Request $request): void
    {
        $secret = config('bookspace.mobile_money.yenga.webhook_secret');

        if (! $secret) {
            return; // Dev / driver `fake` : pas de secret, vérification désactivée.
        }

        $attendu = hash_hmac('sha256', $request->getContent(), $secret);
        $recu = (string) $request->header('X-Webhook-Hash', '');

        abort_unless(
            $recu !== '' && hash_equals($attendu, $recu),
            401,
            'Signature du webhook Yenga invalide.',
        );
    }
}
