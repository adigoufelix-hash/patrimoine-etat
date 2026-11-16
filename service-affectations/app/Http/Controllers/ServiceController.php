<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function index()
    {
        return Service::withCount('affectations')->orderBy('nom')->get();
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'nom' => 'required|string|max:150|unique:services,nom', // RG12
            'code' => 'nullable|string|max:20',
            'localisation' => 'nullable|string|max:150',
        ]);

        return response()->json(Service::create($donnees), 201);
    }

    public function update(Request $request, Service $service)
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:150', Rule::unique('services', 'nom')->ignore($service->id)],
            'code' => 'nullable|string|max:20',
            'localisation' => 'nullable|string|max:150',
        ]);

        $service->update($donnees);

        return $service;
    }
}
