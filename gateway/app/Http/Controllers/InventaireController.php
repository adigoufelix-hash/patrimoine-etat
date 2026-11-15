<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class InventaireController extends Controller
{
    private const ETATS = ['neuf' => 'Neuf', 'bon' => 'Bon', 'en_panne' => 'En panne', 'reforme' => 'Réformé'];

    private function http(): PendingRequest
    {
        return Http::acceptJson()->timeout(120);
    }

    private function url(string $service): string
    {
        return rtrim(config('microservices.' . $service), '/') . '/api';
    }

    /** Biens visibles par l'utilisateur (RG6), avec leur service actuel, filtrés par la requête. */
    private function biens(Request $request): ?Collection
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

        return $liste->values();
    }

    public function show(Request $request, int $id)
    {
        $liste = $this->biens(new Request());
        if ($liste === null) {
            return redirect('/biens')->with('erreur', 'Un des services est indisponible.');
        }

        $bien = $liste->firstWhere('id', $id);
        if (! $bien) {
            // RG6 : un responsable ne peut pas ouvrir la fiche d'un bien hors de son service
            abort(session('user.role') === 'responsable' ? 403 : 404);
        }

        $historique = [];
        $incidents = [];
        try {
            $h = $this->http()->get($this->url('affectations') . '/biens/' . $id . '/affectations');
            if ($h->successful()) {
                $historique = $h->json();
            }
            $i = $this->http()->get($this->url('affectations') . '/incidents');
            if ($i->successful()) {
                $incidents = collect($i->json())->where('bien_id', $id)->values()->all();
            }
        } catch (ConnectionException $e) {
            // la fiche s'affiche quand même, sans historique
        }

        return view('biens.show', ['bien' => $bien, 'historique' => $historique, 'incidents' => $incidents]);
    }

    /** Neutralise les formules (=, +, -, @) pour éviter l'injection dans Excel. */
    private function cellule($valeur): string
    {
        $texte = (string) $valeur;

        return preg_match('/^[=+\-@]/', $texte) ? "'" . $texte : $texte;
    }

    public function export(Request $request)
    {
        $liste = $this->biens($request);
        if ($liste === null) {
            return redirect('/biens')->with('erreur', 'Export impossible : un des services est indisponible.');
        }

        $nom = 'inventaire-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($liste) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF"); // BOM : Excel lit correctement les accents
            fputcsv($f, ['Code d’inventaire', 'Désignation', 'Catégorie', 'État', 'Service', 'Date d’acquisition', 'Valeur (FCFA)'], ';');
            foreach ($liste as $b) {
                fputcsv($f, [
                    $this->cellule($b['code_inventaire']),
                    $this->cellule($b['designation']),
                    $this->cellule($b['categorie']['nom'] ?? ''),
                    self::ETATS[$b['etat']] ?? $b['etat'],
                    $this->cellule($b['service'] ?? 'Non affecté'),
                    $b['date_acquisition'] ? substr($b['date_acquisition'], 0, 10) : '',
                    $b['valeur'] ?? '',
                ], ';');
            }
            fclose($f);
        }, $nom, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function imprimer(Request $request)
    {
        $liste = $this->biens($request);
        if ($liste === null) {
            return redirect('/biens')->with('erreur', 'Impression impossible : un des services est indisponible.');
        }

        return view('biens.imprimer', ['biens' => $liste->all(), 'etats' => self::ETATS]);
    }
}
