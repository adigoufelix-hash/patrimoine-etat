<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class IncidentController extends Controller
{
    public function index()
    {
        return Incident::latest()->get();
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'bien_id'      => 'required|integer|min:1',
            'type'         => 'required|in:panne,perte,degradation',
            'description'  => 'required|string|max:2000',
            'date_constat' => 'nullable|date',
            'signale_par'  => 'nullable|integer',
        ]);

        // Le bien existe-t-il ? On interroge le service Biens.
        try {
            $reponse = Http::acceptJson()->timeout(120)
                ->get(config('services.biens.url') . '/api/biens/' . $donnees['bien_id']);
        } catch (ConnectionException $e) {
            return response()->json(['message' => 'Service Biens indisponible.'], 503);
        }

        if ($reponse->status() === 404) {
            return response()->json(['message' => 'Bien introuvable.'], 422);
        }

        $incident = Incident::create([
            'bien_id'          => $donnees['bien_id'],
            'type'             => $donnees['type'],
            'description'      => $donnees['description'],
            'signale_par'      => $donnees['signale_par'] ?? null,
            'statut'           => 'ouvert',
            'date_signalement' => $donnees['date_constat'] ?? now()->toDateString(),
        ]);

        return response()->json($incident, 201);
    }

    // RG9 : ouvert -> en_cours -> resolu
    public function update(Request $request, Incident $incident)
    {
        $donnees = $request->validate(['statut' => 'required|in:ouvert,en_cours,resolu']);
        $incident->update($donnees);

        return $incident;
    }
}
