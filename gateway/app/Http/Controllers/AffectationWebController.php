<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AffectationWebController extends Controller
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

    public function index()
    {
        try {
            $aff = $this->http()->get($this->urlAffectations() . '/affectations');
            $biens = $this->http()->get($this->urlBiens() . '/biens');
        } catch (ConnectionException $e) {
            return view('affectations.index', ['lignes' => [], 'erreur' => 'Un des services est indisponible.']);
        }

        if ($aff->failed()) {
            return view('affectations.index', ['lignes' => [], 'erreur' => 'Le service Affectations a répondu avec une erreur.']);
        }

        $parId = collect($biens->successful() ? $biens->json() : [])->keyBy('id');

        $lignes = collect($aff->json())->map(function ($a) use ($parId) {
            $b = $parId->get($a['bien_id']);
            $a['bien_libelle'] = $b ? $b['code_inventaire'] . ' · ' . $b['designation'] : 'Bien n°' . $a['bien_id'];
            return $a;
        })->all();

        return view('affectations.index', ['lignes' => $lignes, 'erreur' => null]);
    }

    public function create(Request $request)
    {
        try {
            $biens = $this->http()->get($this->urlBiens() . '/biens');
            $services = $this->http()->get($this->urlAffectations() . '/services');
        } catch (ConnectionException $e) {
            return redirect('/affectations')->with('erreur', 'Un des services est indisponible.');
        }

        $liste = $biens->successful() ? $biens->json() : [];
        $choisi = null;
        $historique = [];

        if ($id = (int) $request->query('bien')) {
            $choisi = collect($liste)->firstWhere('id', $id);
            if ($choisi) {
                try {
                    $h = $this->http()->get($this->urlAffectations() . '/biens/' . $id . '/affectations');
                    $historique = $h->successful() ? $h->json() : [];
                } catch (ConnectionException $e) {
                    // l'historique est facultatif pour afficher le formulaire
                }
            }
        }

        $actuelle = collect($historique)->first(fn ($a) => empty($a['date_fin']));

        return view('affectations.form', [
            'biens' => $liste,
            'services' => $services->successful() ? $services->json() : [],
            'choisi' => $choisi,
            'historique' => $historique,
            'actuelle' => $actuelle,
        ]);
    }

    public function store(Request $request)
    {
        $donnees = collect($request->only(['bien_id', 'service_id', 'motif']))
            ->map(fn ($v) => $v === '' ? null : $v)
            ->all();

        try {
            $r = $this->http()->post($this->urlAffectations() . '/affectations', $donnees);
        } catch (ConnectionException $e) {
            return back()->withInput()->with('erreur', 'Le service Affectations est indisponible.');
        }

        if ($r->status() === 422) {
            $messages = $r->json('errors') ?? ['api' => $r->json('message') ?? 'Données invalides.'];
            return back()->withInput()->withErrors($messages);
        }

        if ($r->failed()) {
            return back()->withInput()->with('erreur', $r->json('message') ?? 'Le service Affectations a répondu avec une erreur.');
        }

        return redirect('/affectations')->with('succes', 'Affectation enregistrée.');
    }
}
