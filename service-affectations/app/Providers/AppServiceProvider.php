<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
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
        // Chaque appel vers un autre service porte la clé de service
        Http::globalRequestMiddleware(
            fn ($request) => $request->withHeader('X-Service-Key', (string) config('microservices.cle'))
        );
    }
}
