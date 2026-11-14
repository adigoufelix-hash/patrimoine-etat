<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use Illuminate\Http\Request;

class BienController extends Controller
{
    private function regles(): array
    {
        return [
            'designation'      => 'required|string|max:255',
            'description'      => 'nullable|string',
            'date_acquisition' => 'nullable|date',
            'valeur'           => 'nullable|numeric|min:0',
            'etat'             => 'in:neuf,bon,en_panne,reforme',
            'categorie_id'     => 'required|exists:categories,id',
        ];
    }

    public function index()
    {
        return Bien::with('categorie')->get();
    }

    public function store(Request $request)
    {
        $donnees = $request->validate($this->regles() + [
            'code_inventaire' => 'required|string|unique:biens',
        ]);

        return response()->json(Bien::create($donnees), 201);
    }

    public function show(Bien $bien)
    {
        return $bien->load('categorie');
    }

    public function update(Request $request, Bien $bien)
    {
        $bien->update($request->validate($this->regles()));

        return $bien;
    }

    public function destroy(Bien $bien)
    {
        $bien->delete();

        return response()->noContent();
    }
}