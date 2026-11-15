<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /** Renvoie l'administrateur correspondant au jeton, ou null. */
    private function administrateur(Request $request): ?User
    {
        $jeton = $request->bearerToken();
        if (! $jeton) {
            return null;
        }

        $u = User::where('api_token', hash('sha256', $jeton))->where('actif', true)->first();

        return $u && $u->role === 'admin' ? $u : null;
    }

    private function refus()
    {
        return response()->json(['message' => 'Réservé à l’administrateur.'], 403);
    }

    private function vue(User $u): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role,
            'service_id' => $u->service_id,
            'actif' => $u->actif,
        ];
    }

    public function index(Request $request)
    {
        if (! $this->administrateur($request)) {
            return $this->refus();
        }

        return User::orderBy('id')->get()->map(fn ($u) => $this->vue($u))->values();
    }

    public function store(Request $request)
    {
        if (! $this->administrateur($request)) {
            return $this->refus();
        }

        $d = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:100',
            'role' => 'required|in:admin,gestionnaire,responsable',
            'service_id' => 'nullable|integer|min:1',
            'actif' => 'boolean',
        ]);

        $u = User::create([
            'name' => $d['name'],
            'email' => $d['email'],
            'password' => $d['password'],
            'role' => $d['role'],
            'service_id' => $d['service_id'] ?? null,
            'actif' => $d['actif'] ?? true,
        ]);

        return response()->json($this->vue($u), 201);
    }

    public function update(Request $request, User $user)
    {
        $admin = $this->administrateur($request);
        if (! $admin) {
            return $this->refus();
        }

        $d = $request->validate([
            'role' => 'required|in:admin,gestionnaire,responsable',
            'service_id' => 'nullable|integer|min:1',
            'actif' => 'required|boolean',
        ]);

        $perdLeDroitAdmin = $user->role === 'admin' && $user->actif && ($d['role'] !== 'admin' || ! $d['actif']);

        if ($perdLeDroitAdmin) {
            if ($user->id === $admin->id) {
                return response()->json(['message' => 'Vous ne pouvez pas retirer vos propres droits d’administration ni désactiver votre propre compte.'], 422);
            }
            $autres = User::where('role', 'admin')->where('actif', true)->where('id', '!=', $user->id)->count();
            if ($autres === 0) {
                return response()->json(['message' => 'Il doit rester au moins un administrateur actif.'], 422);
            }
        }

        $user->role = $d['role'];
        $user->service_id = $d['service_id'] ?? null;
        $user->actif = $d['actif'];

        // RG8 : un compte désactivé est déconnecté immédiatement côté service
        if (! $d['actif']) {
            $user->api_token = null;
        }

        $user->save();

        return $this->vue($user);
    }
}
