<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class BienWebController extends Controller
{
    public function index()
    {
        try {
            $reponse = Http::acceptJson()
                ->timeout(120)
                ->get(config('services.biens.url') . '/api/biens');

            if ($reponse->failed()) {
                return view('biens.index', ['biens' => [], 'erreur' => 'Le service Biens a répondu avec une erreur.']);
            }

            return view('biens.index', ['biens' => $reponse->json(), 'erreur' => null]);
        } catch (ConnectionException $e) {
            return view('biens.index', ['biens' => [], 'erreur' => 'Le service Biens est indisponible.']);
        }
    }
}