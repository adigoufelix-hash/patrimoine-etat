<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'statut' => 'nullable|in:planifiee,realisee,annulee,en_retard',
            'bien_id' => 'nullable|integer',
        ]);

        $q = Maintenance::query();

        if ($s = $request->query('statut')) {
            if ($s === 'en_retard') {
                $q->where('statut', 'planifiee')->whereDate('date_prevue', '<', today());
            } else {
                $q->where('statut', $s);
            }
        }
        if ($b = $request->query('bien_id')) {
            $q->where('bien_id', $b);
        }

        return $q->orderByRaw("CASE WHEN statut = 'planifiee' THEN 0 ELSE 1 END")->orderBy('date_prevue')->get();
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'bien_id' => 'required|integer|min:1',
            'type' => 'required|in:entretien,reparation,controle',
            'date_prevue' => 'required|date|after_or_equal:today',
            'description' => 'nullable|string|max:1000',
            'cree_par' => 'nullable|integer',
        ]);

        // RG14 : le bien doit exister et ne pas être réformé (on interroge le service Biens)
        try {
            $reponse = Http::acceptJson()->timeout(120)
                ->get(rtrim(config('microservices.biens'), '/') . '/api/biens/' . $donnees['bien_id']);
        } catch (ConnectionException $e) {
            return response()->json(['message' => 'Service Biens indisponible.'], 503);
        }

        if ($reponse->status() === 404) {
            return response()->json(['message' => 'Bien introuvable.'], 422);
        }
        if ($reponse->failed()) {
            return response()->json(['message' => 'Le service Biens a répondu avec une erreur.'], 502);
        }
        if ($reponse->json('etat') === 'reforme') {
            return response()->json(['message' => 'Ce bien est réformé : aucune maintenance ne peut être planifiée.'], 422);
        }

        return response()->json(Maintenance::create($donnees + ['statut' => 'planifiee']), 201);
    }

    // RG14 : planifiée -> réalisée ou annulée, puis plus de changement
    public function update(Request $request, Maintenance $maintenance)
    {
        $d = $request->validate([
            'statut' => 'required|in:realisee,annulee',
            'remarque' => 'nullable|string|max:1000',
        ]);

        if ($maintenance->statut !== 'planifiee') {
            return response()->json(['message' => 'Cette maintenance est déjà clôturée.'], 422);
        }

        $maintenance->update([
            'statut' => $d['statut'],
            'date_realisee' => $d['statut'] === 'realisee' ? today()->toDateString() : null,
            'remarque' => $d['remarque'] ?? null,
        ]);

        return $maintenance;
    }

    /**
     * RG16 : renvoie les maintenances à signaler (en retard ou prévues dans les 3 jours),
     * une seule fois par jour pour chacune, puis les marque comme signalées.
     */
    public function alertes()
    {
        $a = Maintenance::where('statut', 'planifiee')
            ->whereDate('date_prevue', '<=', today()->addDays(3))
            ->where(fn ($q) => $q->whereNull('alerte_le')->orWhereDate('alerte_le', '<', today()))
            ->get();

        foreach ($a as $m) {
            $m->forceFill(['alerte_le' => today()->toDateString()])->save();
        }

        return $a;
    }
}
