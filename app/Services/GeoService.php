<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Géocodage (adresse ⇄ coordonnées) via Nominatim et calcul d'itinéraire via
 * OSRM — deux services OpenStreetMap sans clé. Les réponses sont mises en cache
 * pour rester dans les limites d'usage des serveurs publics.
 */
class GeoService
{
    private string $nominatim;
    private string $osrm;
    private string $pays;
    private string $userAgent;

    public function __construct()
    {
        $this->nominatim = rtrim(config('bookspace.geo.nominatim_url'), '/');
        $this->osrm = rtrim(config('bookspace.geo.osrm_url'), '/');
        $this->pays = config('bookspace.geo.pays', 'bf');
        $this->userAgent = 'BookSpace/1.0 (' . config('bookspace.geo.contact') . ')';
    }

    /**
     * Recherche d'adresse -> liste de résultats.
     *
     * @return list<array{label: string, latitude: float, longitude: float}>
     */
    public function rechercher(string $requete): array
    {
        $requete = trim($requete);
        if (mb_strlen($requete) < 3) {
            return [];
        }

        return Cache::remember('geo:search:' . md5($this->pays . $requete), now()->addDay(), function () use ($requete) {
            $reponse = Http::withHeaders(['User-Agent' => $this->userAgent])
                ->acceptJson()
                ->get("{$this->nominatim}/search", [
                    'q' => $requete,
                    'format' => 'jsonv2',
                    'addressdetails' => 0,
                    'limit' => 6,
                    'countrycodes' => $this->pays,
                ]);

            if ($reponse->failed()) {
                return [];
            }

            return collect($reponse->json())
                ->map(fn ($r) => [
                    'label' => $r['display_name'],
                    'latitude' => (float) $r['lat'],
                    'longitude' => (float) $r['lon'],
                ])
                ->all();
        });
    }

    /** Coordonnées -> adresse lisible. */
    public function adresseDe(float $latitude, float $longitude): ?string
    {
        return Cache::remember(
            "geo:reverse:{$latitude},{$longitude}",
            now()->addDays(7),
            function () use ($latitude, $longitude) {
                $reponse = Http::withHeaders(['User-Agent' => $this->userAgent])
                    ->acceptJson()
                    ->get("{$this->nominatim}/reverse", [
                        'lat' => $latitude,
                        'lon' => $longitude,
                        'format' => 'jsonv2',
                    ]);

                return $reponse->ok() ? ($reponse->json('display_name') ?: null) : null;
            },
        );
    }

    /**
     * Itinéraire entre deux points.
     *
     * @param 'driving'|'walking'|'cycling' $profil
     * @return array{distance_m: float, duree_s: float, coordonnees: list<array{0: float, 1: float}>}
     */
    public function itineraire(float $latDep, float $lngDep, float $latArr, float $lngArr, string $profil = 'driving'): array
    {
        $cle = "geo:route:{$profil}:{$latDep},{$lngDep}:{$latArr},{$lngArr}";

        return Cache::remember($cle, now()->addHours(6), function () use ($latDep, $lngDep, $latArr, $lngArr, $profil) {
            $coords = "{$lngDep},{$latDep};{$lngArr},{$latArr}";
            $reponse = Http::acceptJson()->get("{$this->osrm}/route/v1/{$profil}/{$coords}", [
                'overview' => 'full',
                'geometries' => 'geojson',
            ]);

            if ($reponse->failed() || ($reponse->json('code') !== 'Ok')) {
                throw new RuntimeException("Itinéraire indisponible pour le moment.");
            }

            $route = $reponse->json('routes.0');

            return [
                'distance_m' => (float) $route['distance'],
                'duree_s' => (float) $route['duration'],
                // GeoJSON renvoie [lng, lat] -> on remet [lat, lng] pour Leaflet.
                'coordonnees' => array_map(fn ($p) => [$p[1], $p[0]], $route['geometry']['coordinates']),
            ];
        });
    }

    /** Distance à vol d'oiseau en km (formule de haversine) — sans appel réseau. */
    public static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return round($r * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
    }
}
