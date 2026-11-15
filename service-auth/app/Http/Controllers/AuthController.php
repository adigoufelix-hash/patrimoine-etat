<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private function profil(User $u): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role,
            'service_id' => $u->service_id,
        ];
    }

    private function depuisJeton(Request $request): ?User
    {
        $jeton = $request->bearerToken();
        if (! $jeton) {
            return null;
        }

        return User::where('api_token', hash('sha256', $jeton))->where('actif', true)->first();
    }

    private function ouvrirSession(User $u, int $statut = 200)
    {
        $jeton = Str::random(60);
        $u->forceFill(['api_token' => hash('sha256', $jeton)])->save();

        return response()->json(['token' => $jeton, 'user' => $this->profil($u)], $statut);
    }

    public function register(Request $request)
    {
        $donnees = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:100',
            'service_id' => 'nullable|integer|min:1',
        ]);

        // RG7 : le rôle est imposé par le serveur, jamais lu dans la requête
        $utilisateur = User::create([
            'name' => $donnees['name'],
            'email' => $donnees['email'],
            'password' => $donnees['password'],
            'service_id' => $donnees['service_id'] ?? null,
            'role' => 'responsable',
            'actif' => true,
        ]);

        return $this->ouvrirSession($utilisateur, 201);
    }

    public function login(Request $request)
    {
        $donnees = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $utilisateur = User::where('email', $donnees['email'])->first();

        if (! $utilisateur || ! $utilisateur->actif || ! Hash::check($donnees['password'], $utilisateur->password)) {
            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }

        return $this->ouvrirSession($utilisateur);
    }

    public function me(Request $request)
    {
        $utilisateur = $this->depuisJeton($request);

        return $utilisateur
            ? response()->json($this->profil($utilisateur))
            : response()->json(['message' => 'Non authentifié.'], 401);
    }

    public function logout(Request $request)
    {
        if ($utilisateur = $this->depuisJeton($request)) {
            $utilisateur->forceFill(['api_token' => null])->save();
        }

        return response()->noContent();
    }
}
