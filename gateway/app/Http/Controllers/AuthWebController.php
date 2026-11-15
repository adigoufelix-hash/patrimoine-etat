<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthWebController extends Controller
{
    private function url(string $service): string
    {
        return rtrim(config('microservices.' . $service), '/') . '/api';
    }

    public function form()
    {
        return session('user') ? redirect('/') : view('connexion');
    }

    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required']);
        $email = $request->only('email');

        try {
            $r = Http::acceptJson()->timeout(120)
                ->post($this->url('auth') . '/login', $request->only('email', 'password'));
        } catch (ConnectionException $e) {
            return back()->withInput($email)->withErrors(['email' => 'Le service d’authentification est indisponible.']);
        }

        if (in_array($r->status(), [401, 422], true)) {
            return back()->withInput($email)->withErrors(['email' => 'Adresse e-mail ou mot de passe incorrect.']);
        }
        if ($r->failed()) {
            return back()->withInput($email)->withErrors(['email' => 'Le service d’authentification a répondu avec une erreur.']);
        }

        $utilisateur = $r->json('user');
        $utilisateur['service_nom'] = null;

        // Le nom du service sert à appliquer RG6 (un responsable ne voit que son service)
        if (! empty($utilisateur['service_id'])) {
            try {
                $s = Http::acceptJson()->timeout(120)->get($this->url('affectations') . '/services');
                if ($s->successful()) {
                    $utilisateur['service_nom'] = collect($s->json())->firstWhere('id', $utilisateur['service_id'])['nom'] ?? null;
                }
            } catch (ConnectionException $e) {
                // on poursuit : le responsable verra simplement une liste vide
            }
        }

        $request->session()->regenerate();
        session(['jeton' => $r->json('token'), 'user' => $utilisateur]);

        return redirect($utilisateur['role'] === 'responsable' ? '/biens' : '/');
    }

    public function logout(Request $request)
    {
        if ($jeton = session('jeton')) {
            try {
                Http::withToken($jeton)->acceptJson()->timeout(30)->post($this->url('auth') . '/logout');
            } catch (ConnectionException $e) {
                // la session locale est supprimée dans tous les cas
            }
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/connexion');
    }
}
