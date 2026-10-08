<?php

namespace App\Providers;

use App\Services\Sms\AfricasTalkingSms;
use App\Services\Sms\HttpSms;
use App\Services\Sms\LogSms;
use App\Services\Sms\PasserelleSms;
use App\Http\Middleware\VerifieRole;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PasserelleSms::class, function () {
            return match (config('bookspace.sms.driver', 'log')) {
                'africastalking' => new AfricasTalkingSms(config('bookspace.sms.africastalking', [])),
                'http' => new HttpSms(config('bookspace.sms.http', [])),
                default => new LogSms(),
            };
        });
    }

    public function boot(): void
    {
        // Les requêtes de mise à jour Livewire rejouent le contrôle de rôle de
        // la page d'origine (un client ne peut pas piloter un composant gérant).
        Livewire::addPersistentMiddleware([VerifieRole::class]);
    }
}
