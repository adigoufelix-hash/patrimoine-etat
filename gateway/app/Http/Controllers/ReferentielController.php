<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Gestion des catégories et des services (administrateur).
 * Un seul contrôleur pour les deux écrans : tout ce qui change est décrit dans TYPES.
 */
class ReferentielController extends Controller
{
    private const TYPES = [
        'categories' => [
            'service' => 'biens',
            'titre' => 'Catégories',
            'sous_titre' => 'Catégories de biens du patrimoine',
            'indefini' => 'une catégorie',
            'compte' => 'biens_count',
            'compte_libelle' => 'Biens',
            'champs' => ['nom' => 'Nom', 'description' => 'Description'],
        ],
        'services' => [
            'service' => 'affectations',
            'titre' => 'Services',
            'sous_titre' => 'Services et directions qui reçoivent des biens',
            'indefini' => 'un service',
            'compte' => 'affectations_count',
            'compte_libelle' => 'Affectations',
            'champs' => ['nom' => 'Nom', 'code' => 'Code', 'localisation' => 'Localisation'],
        ],
    ];

    private function config(string $type): array
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type] + ['type' => $type];
    }

    private function http(): PendingRequest
    {
        return Http::acceptJson()->timeout(120);
    }

    private function url(array $c): string
    {
        return rtrim(config('microservices.' . $c['service']), '/') . '/api/' . $c['type'];
    }

    public function index(string $type)
    {
        $c = $this->config($type);

        try {
            $r = $this->http()->get($this->url($c));
        } catch (ConnectionException $e) {
            return view('referentiel.index', ['c' => $c, 'lignes' => [], 'erreur' => 'Le service est indisponible.']);
        }

        if ($r->failed()) {
            return view('referentiel.index', ['c' => $c, 'lignes' => [], 'erreur' => 'Le service a répondu avec une erreur.']);
        }

        return view('referentiel.index', ['c' => $c, 'lignes' => $r->json(), 'erreur' => null]);
    }

    public function create(string $type)
    {
        return view('referentiel.form', ['c' => $this->config($type), 'ligne' => null]);
    }

    public function edit(string $type, int $id)
    {
        $c = $this->config($type);

        try {
            $r = $this->http()->get($this->url($c));
        } catch (ConnectionException $e) {
            return redirect('/' . $type)->with('erreur', 'Le service est indisponible.');
        }

        $ligne = collect($r->successful() ? $r->json() : [])->firstWhere('id', $id);
        abort_if(! $ligne, 404);

        return view('referentiel.form', ['c' => $c, 'ligne' => $ligne]);
    }

    public function store(Request $request, string $type)
    {
        return $this->enregistrer($request, $type, null);
    }

    public function update(Request $request, string $type, int $id)
    {
        return $this->enregistrer($request, $type, $id);
    }

    private function enregistrer(Request $request, string $type, ?int $id)
    {
        $c = $this->config($type);
        $request->validate(['nom' => 'required|string|max:150']);

        $donnees = collect($request->only(array_keys($c['champs'])))
            ->map(fn ($v) => $v === '' ? null : $v)
            ->all();

        try {
            $r = $id
                ? $this->http()->put($this->url($c) . '/' . $id, $donnees)
                : $this->http()->post($this->url($c), $donnees);
        } catch (ConnectionException $e) {
            return back()->withInput()->with('erreur', 'Le service est indisponible.');
        }

        if ($r->status() === 422) {
            return back()->withInput()->withErrors($r->json('errors') ?? ['nom' => $r->json('message') ?? 'Données invalides.']);
        }
        if ($r->failed()) {
            return back()->withInput()->with('erreur', 'Enregistrement impossible.');
        }

        return redirect('/' . $type)->with('succes', $id ? 'Modification enregistrée.' : 'Ajout enregistré.');
    }
}
