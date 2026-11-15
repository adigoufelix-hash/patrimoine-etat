<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BienWebController extends Controller
{
    private function http(): PendingRequest
    {
        return Http::acceptJson()->timeout(120);
    }

    private function urlBiens(): string
    {
        return rtrim(config('microservices.biens'), '/') . '/api';
    }

    private function urlAffectations(): string
    {
        return rtrim(config('microservices.affectations'), '/') . '/api';
    }

    private function categories(): array
    {
        try {
            $r = $this->http()->get($this->urlBiens() . '/categories');
            return $r->successful() ? $r->json() : [];
        } catch (ConnectionException $e) {
            return [];
        }
    }

    public function index(Request $request)
    {
        $filtres = $request->only(['q', 'categorie', 'etat', 'service']);
        $vide = ['biens' => [], 'categories' => [], 'services' => [], 'filtres' => $filtres, 'total' => 0];

        try {
            $reponse = $this->http()->get($this->urlBiens() . '/biens');
            $categories = $this->http()->get($this->urlBiens() . '/categories');
        } catch (ConnectionException $e) {
            return view('biens.index', $vide + ['erreur' => 'Le service Biens est indisponible.']);
        }

        if ($reponse->failed()) {
            return view('biens.index', $vide + ['erreur' => 'Le service Biens a répondu avec une erreur.']);
        }

        // Service actuel de chaque bien : information facultative (service Affectations)
        $serviceParBien = [];
        $services = [];
        try {
            $aff = $this->http()->get($this->urlAffectations() . '/affectations');
            if ($aff->successful()) {
                foreach ($aff->json() as $a) {
                    if (empty($a['date_fin'])) {
                        $serviceParBien[$a['bien_id']] = $a['service']['nom'] ?? null;
                    }
                }
            }
            $s = $this->http()->get($this->urlAffectations() . '/services');
            if ($s->successful()) {
                $services = $s->json();
            }
        } catch (ConnectionException $e) {
            // le tableau s'affiche quand même, sans la colonne Service
        }

        $liste = collect($reponse->json())->map(function ($b) use ($serviceParBien) {
            $b['service'] = $serviceParBien[$b['id']] ?? null;
            return $b;
        });
        $total = $liste->count();

        $q = trim((string) $request->query('q'));
        if ($q !== '') {
            $liste = $liste->filter(fn ($b) => str_contains(
                mb_strtolower($b['code_inventaire'] . ' ' . $b['designation']),
                mb_strtolower($q)
            ));
        }
        if ($c = $request->query('categorie')) {
            $liste = $liste->where('categorie_id', (int) $c);
        }
        if ($e = $request->query('etat')) {
            $liste = $liste->where('etat', $e);
        }
        if ($sv = $request->query('service')) {
            $liste = $sv === 'aucun'
                ? $liste->filter(fn ($b) => $b['service'] === null)
                : $liste->filter(fn ($b) => $b['service'] === $sv);
        }

        return view('biens.index', [
            'biens' => $liste->values()->all(),
            'categories' => $categories->successful() ? $categories->json() : [],
            'services' => $services,
            'filtres' => $filtres,
            'total' => $total,
            'erreur' => null,
        ]);
    }

    public function create()
    {
        return view('biens.form', ['bien' => null, 'categories' => $this->categories()]);
    }

    public function edit(int $id)
    {
        try {
            $r = $this->http()->get($this->urlBiens() . '/biens/' . $id);
        } catch (ConnectionException $e) {
            return redirect('/biens')->with('erreur', 'Le service Biens est indisponible.');
        }
        abort_if($r->status() === 404, 404);

        return view('biens.form', ['bien' => $r->json(), 'categories' => $this->categories()]);
    }

    public function store(Request $request)
    {
        return $this->enregistrer($request, null);
    }

    public function update(Request $request, int $id)
    {
        return $this->enregistrer($request, $id);
    }

    private function enregistrer(Request $request, ?int $id)
    {
        $donnees = collect($request->only([
            'code_inventaire', 'designation', 'description',
            'date_acquisition', 'valeur', 'etat', 'categorie_id',
        ]))->map(fn ($v) => $v === '' ? null : $v)->all();

        if ($id) {
            unset($donnees['code_inventaire']); // le code d'inventaire ne change jamais (RG1)
        }

        try {
            $r = $id
                ? $this->http()->put($this->urlBiens() . '/biens/' . $id, $donnees)
                : $this->http()->post($this->urlBiens() . '/biens', $donnees);
        } catch (ConnectionException $e) {
            return back()->withInput()->with('erreur', 'Le service Biens est indisponible.');
        }

        if ($r->status() === 422) {
            return back()->withInput()->withErrors($r->json('errors') ?? ['api' => $r->json('message')]);
        }
        if ($r->failed()) {
            return back()->withInput()->with('erreur', 'Le service Biens a répondu avec une erreur.');
        }

        return redirect('/biens')->with('succes', $id ? 'Bien modifié.' : 'Bien ajouté.');
    }
}
