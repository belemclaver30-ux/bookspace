<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GeoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Géocodage et itinéraires (OpenStreetMap). Utilisé par le gérant pour
 * localiser son espace et par le client pour se rendre sur place.
 */
class GeoController extends Controller
{
    public function __construct(private readonly GeoService $geo) {}

    public function rechercher(Request $request)
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:3', 'max:160']]);

        return ['data' => $this->geo->rechercher($data['q'])];
    }

    public function inverse(Request $request)
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        return ['adresse' => $this->geo->adresseDe($data['lat'], $data['lng'])];
    }

    public function itineraire(Request $request)
    {
        $data = $request->validate([
            'depart' => ['required', 'regex:/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/'],
            'arrivee' => ['required', 'regex:/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/'],
            'profil' => ['nullable', Rule::in(['driving', 'walking', 'cycling'])],
        ]);

        [$latD, $lngD] = array_map('floatval', explode(',', $data['depart']));
        [$latA, $lngA] = array_map('floatval', explode(',', $data['arrivee']));

        try {
            $route = $this->geo->itineraire($latD, $lngD, $latA, $lngA, $data['profil'] ?? 'driving');
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }

        return [
            'distance_km' => round($route['distance_m'] / 1000, 1),
            'duree_min' => (int) round($route['duree_s'] / 60),
            'coordonnees' => $route['coordonnees'],
        ];
    }
}
