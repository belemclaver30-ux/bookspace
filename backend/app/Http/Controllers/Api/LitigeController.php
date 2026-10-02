<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Litige;
use App\Models\Reservation;
use Illuminate\Http\Request;

/** Ouverture d'un litige par un client ou un gérant (TDR §5.3). */
class LitigeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return Litige::with('reservation')
            ->whereHas('reservation', function ($q) use ($user) {
                $q->where('client_id', $user->id)
                    ->orWhere('gerant_id', $user->gerant?->id);
            })
            ->latest()
            ->paginate(20);
    }

    public function store(Request $request, Reservation $reservation)
    {
        $user = $request->user();
        abort_unless(
            $reservation->client_id === $user->id || $reservation->gerant_id === $user->gerant?->id,
            403,
        );

        $data = $request->validate([
            'sujet' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $litige = $reservation->litiges()->create($data + [
            'ouvert_par' => $user->id,
            'statut' => 'ouvert',
        ]);

        return response()->json(['data' => $litige], 201);
    }
}
