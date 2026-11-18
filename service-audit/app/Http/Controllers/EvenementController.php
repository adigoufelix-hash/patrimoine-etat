<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use Illuminate\Http\Request;

class EvenementController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'du' => 'nullable|date',
            'au' => 'nullable|date',
            'role' => 'nullable|in:admin,gestionnaire,responsable',
            'page' => 'nullable|integer|min:1',
        ]);

        $q = Evenement::query()->orderByDesc('id');

        if ($v = $request->query('utilisateur')) {
            $q->where('utilisateur_nom', 'ilike', '%' . $v . '%');
        }
        if ($v = $request->query('action')) {
            $q->where('action', 'ilike', '%' . $v . '%');
        }
        if ($v = $request->query('role')) {
            $q->where('role', $v);
        }
        if ($v = $request->query('du')) {
            $q->whereDate('created_at', '>=', $v);
        }
        if ($v = $request->query('au')) {
            $q->whereDate('created_at', '<=', $v);
        }

        return $q->paginate(30)->withQueryString();
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'utilisateur_id' => 'nullable|integer',
            'utilisateur_nom' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:30',
            'action' => 'required|string|max:120',
            'objet' => 'nullable|string|max:120',
            'detail' => 'nullable|array',
            'adresse_ip' => 'nullable|string|max:45',
        ]);

        return response()->json(Evenement::create($donnees), 201);
    }
}
