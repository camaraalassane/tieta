<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // N'oublie pas cet import !

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force le HTTPS si on n'est pas en local
        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
        }
    }
}
