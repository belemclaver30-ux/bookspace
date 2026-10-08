<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * En-têtes de sécurité des pages web :
 * - interdit l'affichage du site dans un cadre d'un autre site (clickjacking) ;
 * - empêche le navigateur de deviner le type des fichiers servis ;
 * - ne transmet que le domaine (pas l'URL complète) aux sites externes ;
 * - n'autorise que la géolocalisation (« près de moi », itinéraire) et l'appareil photo
 *   (photos d'espace / de profil prises depuis le téléphone) ;
 * - en production HTTPS, impose HTTPS au navigateur pour un an (HSTS).
 */
class EntetesSecurite
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(self), camera=(self), microphone=()');

        if ($request->isSecure() && app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
