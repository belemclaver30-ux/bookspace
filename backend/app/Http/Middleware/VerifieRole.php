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
        abort_unless(in_array($user->role, $roles, true), 403, 'Accès réservé.');

        return $next($request);
    }
}
