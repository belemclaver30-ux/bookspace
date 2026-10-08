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
     * (`bookspace.mobile_money.webhook_secret`). Sans secret, il n'est accepté
     * qu'en développement avec le driver `fake` (voir `secretWebhook()`).
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
     * Rejette le webhook si la signature `X-Signature` ne correspond pas au
     * HMAC-SHA256 du corps brut.
     */
    private function verifierSignature(Request $request): void
    {
        $secret = $this->secretWebhook('bookspace.mobile_money.webhook_secret');

        if ($secret === null) {
            return;
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
        $secret = $this->secretWebhook('bookspace.mobile_money.yenga.webhook_secret');

        if ($secret === null) {
            return;
        }

        $attendu = hash_hmac('sha256', $request->getContent(), $secret);
        $recu = (string) $request->header('X-Webhook-Hash', '');

        abort_unless(
            $recu !== '' && hash_equals($attendu, $recu),
            401,
            'Signature du webhook Yenga invalide.',
        );
    }

    /**
     * Secret de signature d'un webhook. Sans secret, la vérification n'est
     * sautée (null) qu'en développement avec le driver `fake` : autrement,
     * n'importe qui pourrait confirmer une réservation sans payer en postant
     * sa référence. On refuse donc le webhook tant qu'il n'est pas configuré.
     */
    private function secretWebhook(string $cle): ?string
    {
        $secret = (string) config($cle);

        if ($secret !== '') {
            return $secret;
        }

        abort_unless(
            config('bookspace.mobile_money.driver') === 'fake' && ! app()->isProduction(),
            503,
            'Webhook de paiement non configuré.',
        );

        return null;
    }
}
