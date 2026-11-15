<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    /** Appelle un service ; renvoie null s'il est indisponible. */
    private function recuperer(string $service, string $chemin): ?array
    {
        try {
            $r = Http::acceptJson()->timeout(120)
                ->get(rtrim(config('microservices.' . $service), '/') . '/api' . $chemin);

            return $r->successful() ? $r->json() : null;
        } catch (ConnectionException $e) {
            return null;
        }
    }

    public function index()
    {
        $biens = $this->recuperer('biens', '/biens');
        $affectations = $this->recuperer('affectations', '/affectations');
        $incidents = $this->recuperer('affectations', '/incidents');

        $listeBiens = collect($biens ?? []);
        $listeAff = collect($affectations ?? []);

        $stats = [
            'total'     => $biens === null ? null : $listeBiens->count(),
            'affectes'  => ($biens === null || $affectations === null) ? null
                : $listeAff->whereNull('date_fin')->pluck('bien_id')->unique()
                    ->intersect($listeBiens->pluck('id'))->count(),
            'en_panne'  => $biens === null ? null : $listeBiens->where('etat', 'en_panne')->count(),
            'incidents' => $incidents === null ? null : collect($incidents)->where('statut', 'ouvert')->count(),
        ];

        $categories = $listeBiens
            ->groupBy(fn ($b) => $b['categorie']['nom'] ?? 'Sans catégorie')
            ->map->count()
            ->sortDesc();

        $parId = $listeBiens->keyBy('id');
        $mouvements = $listeAff->sortByDesc('id')->take(5)->map(function ($a) use ($parId) {
            $b = $parId->get($a['bien_id']);
            return [
                'texte' => ($b['designation'] ?? 'Bien n°' . $a['bien_id']) . ' → ' . ($a['service']['nom'] ?? '—'),
                'date'  => \Carbon\Carbon::parse($a['date_debut'])->format('d/m'),
            ];
        })->values();

        return view('dashboard', [
            'stats'            => $stats,
            'categories'       => $categories,
            'maxCategorie'     => max($categories->max() ?? 1, 1),
            'mouvements'       => $mouvements,
            'biensIndisponible' => $biens === null,
            'affIndisponible'  => $affectations === null,
        ]);
    }
}
