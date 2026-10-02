<?php

namespace App\Providers;

use App\Services\Sms\AfricasTalkingSms;
use App\Services\Sms\HttpSms;
use App\Services\Sms\LogSms;
use App\Services\Sms\PasserelleSms;
use Illuminate\Support\ServiceProvider;

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
        //
    }
}
