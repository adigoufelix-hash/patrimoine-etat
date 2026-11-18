<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuditWebController extends Controller
{
    public function index(Request $request)
    {
        $filtres = $request->only(['utilisateur', 'action', 'role', 'du', 'au']);
        $params = array_filter($filtres, fn ($v) => $v !== null && $v !== '');
        if ($page = (int) $request->query('page')) {
            $params['page'] = $page;
        }

        $vide = ['evenements' => [], 'meta' => null, 'filtres' => $filtres];

        try {
            $r = Http::acceptJson()->timeout(120)
                ->get(rtrim(config('microservices.audit'), '/') . '/api/evenements', $params);
        } catch (ConnectionException $e) {
            return view('audit.index', $vide + ['erreur' => 'Le service Journal d’audit est indisponible.']);
        }

        if ($r->failed()) {
            return view('audit.index', $vide + ['erreur' => 'Le service Journal d’audit a répondu avec une erreur.']);
        }

        $json = $r->json();

        return view('audit.index', [
            'evenements' => $json['data'] ?? [],
            'meta' => [
                'page' => $json['current_page'] ?? 1,
                'dernier' => $json['last_page'] ?? 1,
                'total' => $json['total'] ?? 0,
            ],
            'filtres' => $filtres,
            'erreur' => null,
        ]);
    }
}
