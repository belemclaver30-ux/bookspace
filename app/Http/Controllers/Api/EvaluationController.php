<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EvaluationResource;
use App\Models\Evaluation;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Évaluer un espace après utilisation (TDR §5.1). */
class EvaluationController extends Controller
{
    public function store(Request $request, Reservation $reservation): EvaluationResource
    {
        abort_unless($reservation->client_id === $request->user()->id, 403);

        if ($reservation->statut !== 'terminee') {
            throw ValidationException::withMessages([
                'reservation' => 'Vous pourrez évaluer cet espace une fois la réservation terminée.',
            ]);
        }

        if ($reservation->evaluation()->exists()) {
            throw ValidationException::withMessages(['reservation' => 'Cette réservation a déjà été évaluée.']);
        }

        $data = $request->validate([
            'note' => ['required', 'integer', 'min:1', 'max:5'],
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ]);

        $evaluation = Evaluation::create([
            'reservation_id' => $reservation->id,
            'client_id' => $request->user()->id,
            'espace_id' => $reservation->espace_id,
            'note' => $data['note'],
            'commentaire' => $data['commentaire'] ?? null,
        ]);

        return new EvaluationResource($evaluation->load('client'));
    }
}
