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

    private function services(): array
    {
        try {
            $s = Http::acceptJson()->timeout(120)->get($this->url('affectations') . '/services');

            return $s->successful() ? $s->json() : [];
        } catch (ConnectionException $e) {
            return [];
        }
    }

    /** Mémorise l'utilisateur et son jeton en session (utilisé par la connexion et l'inscription). */
    private function ouvrirSession(Request $request, array $reponse)
    {
        $utilisateur = $reponse['user'];
        $utilisateur['service_nom'] = null;

        // Le nom du service sert à appliquer RG6 (un responsable ne voit que son service)
        if (! empty($utilisateur['service_id'])) {
            $utilisateur['service_nom'] = collect($this->services())->firstWhere('id', $utilisateur['service_id'])['nom'] ?? null;
        }

        $request->session()->regenerate();
        session(['jeton' => $reponse['token'], 'user' => $utilisateur]);

        return redirect($utilisateur['role'] === 'responsable' ? '/biens' : '/');
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

        return $this->ouvrirSession($request, $r->json());
    }

    public function formInscription()
    {
        return session('user') ? redirect('/') : view('inscription', ['services' => $this->services()]);
    }

    public function inscription(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'service_id' => 'nullable|integer',
        ]);
        $saisie = $request->only('name', 'email', 'service_id');

        try {
            $r = Http::acceptJson()->timeout(120)->post($this->url('auth') . '/register', [
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => $request->input('password'),
                'service_id' => $request->input('service_id') ?: null,
            ]);
        } catch (ConnectionException $e) {
            return back()->withInput($saisie)->withErrors(['email' => 'Le service d’authentification est indisponible.']);
        }

        if ($r->status() === 422) {
            return back()->withInput($saisie)->withErrors($r->json('errors') ?? ['email' => 'Données invalides.']);
        }
        if ($r->failed()) {
            return back()->withInput($saisie)->withErrors(['email' => 'Le service d’authentification a répondu avec une erreur.']);
        }

        return $this->ouvrirSession($request, $r->json());
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
