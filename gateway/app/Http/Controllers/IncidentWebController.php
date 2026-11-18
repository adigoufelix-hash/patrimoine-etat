<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class IncidentWebController extends Controller
{
    private function http(): PendingRequest
    {
        return Http::acceptJson()->timeout(120);
    }

    private function url(string $service): string
    {
        return rtrim(config('microservices.' . $service), '/') . '/api';
    }

    /**
     * Biens accessibles à l'utilisateur connecté, indexés par identifiant.
     * RG6 : un responsable de service ne voit que les biens de son service.
     */
    private function biensVisibles(): ?array
    {
        try {
            $biens = $this->http()->get($this->url('biens') . '/biens');
            $aff = $this->http()->get($this->url('affectations') . '/affectations');
        } catch (ConnectionException $e) {
            return null;
        }

        if ($biens->failed()) {
            return null;
        }

        $serviceParBien = [];
        if ($aff->successful()) {
            foreach ($aff->json() as $a) {
                if (empty($a['date_fin'])) {
                    $serviceParBien[$a['bien_id']] = $a['service']['nom'] ?? null;
                }
            }
        }

        $liste = collect($biens->json())->map(function ($b) use ($serviceParBien) {
            $b['service'] = $serviceParBien[$b['id']] ?? null;
            return $b;
        });

        $u = session('user');
        if (($u['role'] ?? null) === 'responsable') {
            $liste = $liste->filter(fn ($b) => $b['service'] !== null && $b['service'] === ($u['service_nom'] ?? null));
        }

        return $liste->keyBy('id')->all();
    }

    /** Incidents des biens visibles, avec le libellé du bien. */
    private function incidentsVisibles(array $visibles): ?array
    {
        try {
            $r = $this->http()->get($this->url('affectations') . '/incidents');
        } catch (ConnectionException $e) {
            return null;
        }

        if ($r->failed()) {
            return null;
        }

        return collect($r->json())
            ->filter(fn ($i) => isset($visibles[$i['bien_id']]))
            ->map(function ($i) use ($visibles) {
                $b = $visibles[$i['bien_id']];
                $i['bien_libelle'] = $b['code_inventaire'] . ' · ' . $b['designation'];
                return $i;
            })->values()->all();
    }

    public function index()
    {
        $visibles = $this->biensVisibles();
        $lignes = $visibles === null ? null : $this->incidentsVisibles($visibles);

        if ($lignes === null) {
            return view('incidents.index', ['lignes' => [], 'erreur' => 'Un des services est indisponible.']);
        }

        return view('incidents.index', ['lignes' => $lignes, 'erreur' => null]);
    }

    public function create(Request $request)
    {
        $visibles = $this->biensVisibles();
        if ($visibles === null) {
            return redirect('/incidents')->with('erreur', 'Un des services est indisponible.');
        }

        $recents = array_slice($this->incidentsVisibles($visibles) ?? [], 0, 3);

        return view('incidents.form', [
            'biens' => array_values($visibles),
            'choisi' => (int) $request->query('bien'),
            'recents' => $recents,
        ]);
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'bien_id' => 'required|integer',
            'type' => 'required|in:panne,perte,degradation',
            'date_constat' => 'nullable|date',
            'description' => 'required|string|max:2000',
        ]);

        $visibles = $this->biensVisibles();
        if ($visibles === null) {
            return back()->withInput()->with('erreur', 'Un des services est indisponible.');
        }

        // RG6 vérifiée côté serveur : le bien doit être accessible avec ce rôle
        if (! isset($visibles[(int) $donnees['bien_id']])) {
            return back()->withInput()->withErrors(['bien_id' => 'Ce bien n’est pas accessible avec votre rôle.']);
        }

        try {
            $r = $this->http()->post($this->url('affectations') . '/incidents', $donnees + ['signale_par' => session('user.id')]);
        } catch (ConnectionException $e) {
            return back()->withInput()->with('erreur', 'Le service Affectations est indisponible.');
        }

        if ($r->status() === 422) {
            return back()->withInput()->withErrors($r->json('errors') ?? ['bien_id' => $r->json('message') ?? 'Données invalides.']);
        }
        if ($r->failed()) {
            return back()->withInput()->with('erreur', 'Le service Affectations a répondu avec une erreur.');
        }

        $libelleType = ['panne' => 'Panne', 'perte' => 'Perte', 'degradation' => 'Dégradation'][$donnees['type']] ?? $donnees['type'];
        foreach (['gestionnaire', 'admin'] as $roleCible) {
            \App\Services\Notifier::envoyer([
                'titre' => 'Nouvel incident',
                'message' => $libelleType . ' signalée par ' . session('user.name') . ' (bien n°' . $donnees['bien_id'] . ').',
                'lien' => '/incidents',
                'role_cible' => $roleCible,
            ]);
        }

        return redirect('/incidents')->with('succes', 'Incident signalé.');
    }

    // RG9 : réservé à l'administrateur et au gestionnaire (voir les routes)
    public function statut(Request $request, int $id)
    {
        $donnees = $request->validate(['statut' => 'required|in:ouvert,en_cours,resolu']);

        try {
            $r = $this->http()->patch($this->url('affectations') . '/incidents/' . $id, $donnees);
        } catch (ConnectionException $e) {
            return back()->with('erreur', 'Le service Affectations est indisponible.');
        }

        if ($r->failed()) {
            return back()->with('erreur', 'Impossible de modifier le statut.');
        }

        $incident = $r->json();
        if (! empty($incident['signale_par']) && $incident['signale_par'] != session('user.id')) {
            $libelles = ['ouvert' => 'ouvert', 'en_cours' => 'en cours', 'resolu' => 'résolu'];
            \App\Services\Notifier::envoyer([
                'titre' => 'Incident mis à jour',
                'message' => 'Votre incident n°' . $id . ' est maintenant : ' . $libelles[$donnees['statut']] . '.',
                'lien' => '/incidents',
                'utilisateur_id' => $incident['signale_par'],
            ]);
        }

        return back()->with('succes', 'Statut mis à jour.');
    }
}
