<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Chaque appel vers un autre service porte la clé de service
        Http::globalRequestMiddleware(
            fn ($request) => $request->withHeader('X-Service-Key', (string) config('microservices.cle'))
        );

        // Nombre de notifications non lues dans le menu : mis en cache 30 secondes dans la session,
        // même en cas d'échec, pour qu'un service endormi ne ralentisse pas chaque page.
        View::composer('layouts.app', function ($view) {
            $u = session('user');
            if (! $u) {
                return;
            }

            $cache = session('notif_cache');
            if ($cache && now()->timestamp - $cache['t'] < 30) {
                $view->with('nbNotifs', $cache['n']);

                return;
            }

            $n = null;
            try {
                $r = Http::acceptJson()->timeout(2)->connectTimeout(1)
                    ->get(rtrim(config('microservices.notifications'), '/') . '/api/notifications/compteur', [
                        'utilisateur_id' => $u['id'],
                        'role' => $u['role'],
                    ]);
                $n = $r->successful() ? (int) $r->json('non_lues') : null;
            } catch (\Throwable $e) {
                $n = null;
            }

            session(['notif_cache' => ['t' => now()->timestamp, 'n' => $n]]);
            $view->with('nbNotifs', $n);
        });
    }
}
