<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\VerifieRole::class,
        ]);

        // Web : visiteur non connecté -> écran de connexion ; utilisateur déjà
        // connecté sur une page « invité » -> accueil de son rôle.
        // Une session dont le mot de passe a changé depuis (autre appareil) est
        // fermée : « Se déconnecter des autres appareils », changement de mot de passe.
        $middleware->web(append: [
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \App\Http\Middleware\EntetesSecurite::class,
        ]);

        $middleware->redirectGuestsTo('/connexion');
        $middleware->redirectUsersTo(fn ($request) => $request->user()?->accueil() ?? '/app');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Les routes /api renvoient toujours du JSON, même sans en-tête Accept.
        $exceptions->shouldRenderJsonWhen(
            fn ($request) => $request->is('api/*') || $request->expectsJson()
        );
    })->create();
