<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AffectationController extends Controller
{
    public function index()
    {
        return Affectation::with('service')->latest()->get();
    }

    public function historique(int $bienId)
    {
        return Affectation::with('service')->where('bien_id', $bienId)->orderByDesc('date_debut')->get();
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'bien_id'    => 'required|integer',
            'service_id' => 'required|exists:services,id',
            'motif'      => 'nullable|string|max:255',
        ]);

        // On interroge le service Biens : le bien existe-t-il, et dans quel état ?
        try {
            $reponse = Http::acceptJson()->timeout(120)
                ->get(config('services.biens.url') . '/api/biens/' . $donnees['bien_id']);
        } catch (ConnectionException $e) {
            return response()->json(['message' => 'Service Biens indisponible.'], 503);
        }

        if ($reponse->status() === 404) {
            return response()->json(['message' => 'Bien introuvable.'], 422);
        }

        // RG4 : un bien réformé ne peut plus être affecté
        if (($reponse->json('etat')) === 'reforme') {
            return response()->json(['message' => 'Ce bien est réformé : affectation impossible.'], 422);
        }

        // RG2 et RG3 : on clôture l'affectation active, puis on crée la nouvelle (historique conservé)
        $affectation = DB::transaction(function () use ($donnees) {
            Affectation::where('bien_id', $donnees['bien_id'])
                ->whereNull('date_fin')
                ->update(['date_fin' => now()->toDateString()]);

            return Affectation::create($donnees + ['date_debut' => now()->toDateString()]);
        });

        return response()->json($affectation->load('service'), 201);
    }
}