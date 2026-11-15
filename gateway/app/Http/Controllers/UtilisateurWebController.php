<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UtilisateurWebController extends Controller
{
    private function http(): PendingRequest
    {
        return Http::withToken(session('jeton'))->acceptJson()->timeout(120);
    }

    private function url(string $service): string
    {
        return rtrim(config('microservices.' . $service), '/') . '/api';
    }

    private function services(): array
    {
        try {
            $s = $this->http()->get($this->url('affectations') . '/services');

            return $s->successful() ? $s->json() : [];
        } catch (ConnectionException $e) {
            return [];
        }
    }

    private function utilisateurs(): ?array
    {
        try {
            $r = $this->http()->get($this->url('auth') . '/users');
        } catch (ConnectionException $e) {
            return null;
        }

        return $r->successful() ? $r->json() : null;
    }

    public function index()
    {
        $liste = $this->utilisateurs();

        if ($liste === null) {
            return view('utilisateurs.index', ['lignes' => [], 'erreur' => 'Le service d’authentification est indisponible ou a refusé la requête.']);
        }

        $noms = collect($this->services())->pluck('nom', 'id');
        $lignes = collect($liste)->map(fn ($u) => $u + ['service_nom' => $noms[$u['service_id']] ?? null])->all();

        return view('utilisateurs.index', ['lignes' => $lignes, 'erreur' => null]);
    }

    public function create()
    {
        return view('utilisateurs.form', ['utilisateur' => null, 'services' => $this->services()]);
    }

    public function edit(int $id)
    {
        $u = collect($this->utilisateurs() ?? [])->firstWhere('id', $id);
        abort_if(! $u, 404);

        return view('utilisateurs.form', ['utilisateur' => $u, 'services' => $this->services()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,gestionnaire,responsable',
            'service_id' => 'nullable|integer',
        ]);

        try {
            $r = $this->http()->post($this->url('auth') . '/users', [
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => $request->input('password'),
                'role' => $request->input('role'),
                'service_id' => $request->input('service_id') ?: null,
                'actif' => $request->boolean('actif'),
            ]);
        } catch (ConnectionException $e) {
            return back()->withInput($request->except('password'))->with('erreur', 'Le service d’authentification est indisponible.');
        }

        if ($r->status() === 422) {
            return back()->withInput($request->except('password'))
                ->withErrors($r->json('errors') ?? ['email' => $r->json('message') ?? 'Données invalides.']);
        }
        if ($r->failed()) {
            return back()->withInput($request->except('password'))->with('erreur', 'Création impossible avec votre rôle.');
        }

        return redirect('/utilisateurs')->with('succes', 'Utilisateur créé.');
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'role' => 'required|in:admin,gestionnaire,responsable',
            'service_id' => 'nullable|integer',
        ]);

        try {
            $r = $this->http()->patch($this->url('auth') . '/users/' . $id, [
                'role' => $request->input('role'),
                'service_id' => $request->input('service_id') ?: null,
                'actif' => $request->boolean('actif'),
            ]);
        } catch (ConnectionException $e) {
            return back()->withInput()->with('erreur', 'Le service d’authentification est indisponible.');
        }

        if ($r->status() === 422) {
            return back()->withInput()
                ->withErrors($r->json('errors') ?? ['role' => $r->json('message') ?? 'Modification refusée.']);
        }
        if ($r->failed()) {
            return back()->withInput()->with('erreur', 'Modification impossible avec votre rôle.');
        }

        return redirect('/utilisateurs')->with('succes', 'Utilisateur modifié.');
    }
}
