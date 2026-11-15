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

        $jeton = Str::random(60);
        $utilisateur->forceFill(['api_token' => hash('sha256', $jeton)])->save();

        return response()->json(['token' => $jeton, 'user' => $this->profil($utilisateur)]);
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
