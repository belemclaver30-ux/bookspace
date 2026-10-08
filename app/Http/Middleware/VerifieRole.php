<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restreint une route à un ou plusieurs rôles : `->middleware('role:gerant')`.
 */
class VerifieRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_if(! $user, 401);

        if (in_array($user->role, $roles, true)) {
            return $next($request);
        }

        // API / Livewire : refus net. Navigation web : retour à l'accueil du rôle.
        abort_if(
            $request->is('api/*') || $request->expectsJson() || $request->hasHeader('X-Livewire'),
            403,
            'Accès réservé.',
        );

        return redirect($user->accueil());
    }
}
