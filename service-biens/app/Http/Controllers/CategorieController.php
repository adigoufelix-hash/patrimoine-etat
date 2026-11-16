<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategorieController extends Controller
{
    public function index()
    {
        return Categorie::withCount('biens')->orderBy('nom')->get();
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'nom' => 'required|string|max:100|unique:categories,nom', // RG12
            'description' => 'nullable|string|max:500',
        ]);

        return response()->json(Categorie::create($donnees), 201);
    }

    public function update(Request $request, Categorie $categorie)
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:100', Rule::unique('categories', 'nom')->ignore($categorie->id)],
            'description' => 'nullable|string|max:500',
        ]);

        $categorie->update($donnees);

        return $categorie;
    }
}
