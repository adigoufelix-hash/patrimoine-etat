<?php

namespace App\Http\Controllers;

use App\Models\Lecture;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** Le lecteur doit toujours être identifié : RG15. */
    private function lecteur(Request $request): array
    {
        return $request->validate([
            'utilisateur_id' => 'required|integer',
            'role' => 'required|in:admin,gestionnaire,responsable',
        ]);
    }

    public function index(Request $request)
    {
        $d = $this->lecteur($request);
        $lues = Lecture::where('utilisateur_id', $d['utilisateur_id'])->pluck('notification_id')->all();

        return Notification::pour($d['utilisateur_id'], $d['role'])
            ->orderByDesc('id')->limit(50)->get()
            ->map(fn ($n) => $n->toArray() + ['lue' => in_array($n->id, $lues)])
            ->values();
    }

    public function compteur(Request $request)
    {
        $d = $this->lecteur($request);
        $lues = Lecture::where('utilisateur_id', $d['utilisateur_id'])->pluck('notification_id');

        return [
            'non_lues' => Notification::pour($d['utilisateur_id'], $d['role'])->whereNotIn('id', $lues)->count(),
        ];
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'titre' => 'required|string|max:150',
            'message' => 'required|string|max:1000',
            'lien' => 'nullable|string|max:255',
            // un destinataire, et un seul : un rôle OU un utilisateur
            'role_cible' => 'nullable|in:admin,gestionnaire,responsable|required_without:utilisateur_id|prohibits:utilisateur_id',
            'utilisateur_id' => 'nullable|integer|required_without:role_cible|prohibits:role_cible',
        ]);

        return response()->json(Notification::create($d), 201);
    }

    public function lire(Request $request, Notification $notification)
    {
        $d = $this->lecteur($request);

        // RG15 : on ne peut marquer comme lue qu'une notification qui nous est destinée
        abort_unless(
            Notification::pour($d['utilisateur_id'], $d['role'])->whereKey($notification->id)->exists(),
            403
        );

        Lecture::firstOrCreate(['notification_id' => $notification->id, 'utilisateur_id' => $d['utilisateur_id']]);

        return response()->noContent();
    }

    public function toutesLues(Request $request)
    {
        $d = $this->lecteur($request);

        foreach (Notification::pour($d['utilisateur_id'], $d['role'])->pluck('id') as $id) {
            Lecture::firstOrCreate(['notification_id' => $id, 'utilisateur_id' => $d['utilisateur_id']]);
        }

        return response()->noContent();
    }
}
