<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class EnsureConnecte
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('user')) {
            return redirect('/connexion');
        }

        // Toutes les 60 secondes, on revérifie le jeton auprès du service Auth :
        // un compte désactivé est déconnecté, un changement de rôle s'applique (RG7, RG8).
        if (now()->timestamp - (int) session('verifie_a', 0) > 60) {
            try {
                $r = Http::withToken(session('jeton'))->acceptJson()->timeout(30)
                    ->get(rtrim(config('microservices.auth'), '/') . '/api/me');

                if ($r->status() === 401) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect('/connexion');
                }

                if ($r->successful()) {
                    $profil = $r->json();
                    $u = session('user');

                    if (($u['service_id'] ?? null) != ($profil['service_id'] ?? null)) {
                        $u['service_nom'] = null;
                        if (! empty($profil['service_id'])) {
                            $s = Http::acceptJson()->timeout(30)
                                ->get(rtrim(config('microservices.affectations'), '/') . '/api/services');
                            if ($s->successful()) {
                                $u['service_nom'] = collect($s->json())->firstWhere('id', $profil['service_id'])['nom'] ?? null;
                            }
                        }
                    }

                    session(['user' => array_merge($u, [
                        'name' => $profil['name'],
                        'role' => $profil['role'],
                        'service_id' => $profil['service_id'],
                    ])]);
                }

                session(['verifie_a' => now()->timestamp]);
            } catch (ConnectionException $e) {
                // Service Auth injoignable : on garde la session en cours (disponibilité)
            }
        }

        return $next($request);
    }
}
