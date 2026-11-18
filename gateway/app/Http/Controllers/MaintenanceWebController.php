<?php

namespace App\Http\Controllers;

use App\Services\Notifier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MaintenanceWebController extends Controller
{
    private const TYPES = ['entretien' => 'Entretien préventif', 'reparation' => 'Réparation', 'controle' => 'Contrôle'];

    private function http(): PendingRequest
    {
        return Http::acceptJson()->timeout(120);
    }

    private function url(string $service): string
    {
        return rtrim(config('microservices.' . $service), '/') . '/api';
    }

    /** Biens indexés par identifiant ; vide si le service Biens est indisponible (la page s'affiche quand même). */
    private function biens(): array
    {
        try {
            $r = $this->http()->get($this->url('biens') . '/biens');
        } catch (ConnectionException $e) {
            return [];
        }

        return $r->successful() ? collect($r->json())->keyBy('id')->all() : [];
    }

    /** RG16 : le service renvoie, une seule fois par jour et par maintenance, celles à signaler. */
    private function alerter(array $biens): void
    {
        try {
            $r = Http::acceptJson()->timeout(10)->post($this->url('maintenance') . '/maintenances/alertes');
        } catch (\Throwable $e) {
            return;
        }

        if (! $r->successful()) {
            return;
        }

        foreach ($r->json() as $m) {
            $b = $biens[$m['bien_id']] ?? null;
            $nom = $b ? $b['designation'] . ' (' . $b['code_inventaire'] . ')' : 'bien n°' . $m['bien_id'];
            $date = \Carbon\Carbon::parse($m['date_prevue'])->format('d/m/Y');

            foreach (['gestionnaire', 'admin'] as $role) {
                Notifier::envoyer([
                    'titre' => $m['en_retard'] ? 'Maintenance en retard' : 'Maintenance à venir',
                    'message' => (self::TYPES[$m['type']] ?? $m['type']) . ' : ' . $nom . ', prévue le ' . $date . '.',
                    'lien' => '/maintenances',
                    'role_cible' => $role,
                ]);
            }
        }
    }

    public function index(Request $request)
    {
        $statut = $request->query('statut');
        $biens = $this->biens();
        $this->alerter($biens);

        $vide = ['lignes' => [], 'statut' => $statut, 'types' => self::TYPES];

        try {
            $r = $this->http()->get($this->url('maintenance') . '/maintenances', array_filter(['statut' => $statut]));
        } catch (ConnectionException $e) {
            return view('maintenances.index', $vide + ['erreur' => 'Le service Maintenance est indisponible.']);
        }

        if ($r->failed()) {
            return view('maintenances.index', $vide + ['erreur' => 'Le service Maintenance a répondu avec une erreur.']);
        }

        $lignes = collect($r->json())->map(function ($m) use ($biens) {
            $b = $biens[$m['bien_id']] ?? null;
            $m['bien_libelle'] = $b ? $b['code_inventaire'] . ' · ' . $b['designation'] : 'Bien n°' . $m['bien_id'];

            return $m;
        })->all();

        return view('maintenances.index', ['lignes' => $lignes, 'statut' => $statut, 'types' => self::TYPES, 'erreur' => null]);
    }

    public function create(Request $request)
    {
        $biens = collect($this->biens())->where('etat', '!=', 'reforme')->values()->all();

        return view('maintenances.form', ['biens' => $biens, 'types' => self::TYPES, 'choisi' => (int) $request->query('bien')]);
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'bien_id' => 'required|integer',
            'type' => 'required|in:entretien,reparation,controle',
            'date_prevue' => 'required|date|after_or_equal:today',
            'description' => 'nullable|string|max:1000',
        ], [], ['type' => 'type de maintenance', 'date_prevue' => 'date prévue', 'bien_id' => 'bien']);

        try {
            $r = $this->http()->post($this->url('maintenance') . '/maintenances', $donnees + ['cree_par' => session('user.id')]);
        } catch (ConnectionException $e) {
            return back()->withInput()->with('erreur', 'Le service Maintenance est indisponible.');
        }

        if ($r->status() === 422) {
            return back()->withInput()->withErrors($r->json('errors') ?? ['bien_id' => $r->json('message') ?? 'Données invalides.']);
        }
        if ($r->failed()) {
            return back()->withInput()->with('erreur', $r->json('message') ?? 'Le service Maintenance a répondu avec une erreur.');
        }

        return redirect('/maintenances')->with('succes', 'Maintenance planifiée.');
    }

    // RG14 : réalisée ou annulée, puis plus de changement
    public function statut(Request $request, int $id)
    {
        $donnees = $request->validate(['statut' => 'required|in:realisee,annulee']);

        try {
            $r = $this->http()->patch($this->url('maintenance') . '/maintenances/' . $id, $donnees);
        } catch (ConnectionException $e) {
            return back()->with('erreur', 'Le service Maintenance est indisponible.');
        }

        if ($r->status() === 422) {
            return back()->with('erreur', $r->json('message') ?? 'Modification refusée.');
        }
        if ($r->failed()) {
            return back()->with('erreur', 'Impossible de modifier la maintenance.');
        }

        return back()->with('succes', $donnees['statut'] === 'realisee' ? 'Maintenance marquée comme réalisée.' : 'Maintenance annulée.');
    }
}
