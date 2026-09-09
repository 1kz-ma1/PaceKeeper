<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Render terminates TLS in front of the container. Force generated
        // asset/form URLs to HTTPS so Vite assets are never blocked as mixed content.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
