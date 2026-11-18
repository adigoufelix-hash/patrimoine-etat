<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class NotificationWebController extends Controller
{
    private function url(string $chemin): string
    {
        return rtrim(config('microservices.notifications'), '/') . '/api/notifications' . $chemin;
    }

    /** L'identité du lecteur vient toujours de la session, jamais de la requête (RG15). */
    private function lecteur(): array
    {
        return ['utilisateur_id' => session('user.id'), 'role' => session('user.role')];
    }

    public function index()
    {
        try {
            $r = Http::acceptJson()->timeout(120)->get($this->url(''), $this->lecteur());
        } catch (ConnectionException $e) {
            return view('notifications.index', ['lignes' => [], 'erreur' => 'Le service Notifications est indisponible.']);
        }

        if ($r->failed()) {
            return view('notifications.index', ['lignes' => [], 'erreur' => 'Le service Notifications a répondu avec une erreur.']);
        }

        return view('notifications.index', ['lignes' => $r->json(), 'erreur' => null]);
    }

    public function lire(Request $request, int $id)
    {
        try {
            Http::acceptJson()->timeout(30)->post($this->url('/' . $id . '/lue') . '?' . http_build_query($this->lecteur()));
        } catch (ConnectionException $e) {
            return redirect('/notifications')->with('erreur', 'Le service Notifications est indisponible.');
        }

        $request->session()->forget('notif_cache');

        // On ne redirige que vers une adresse interne du site
        $lien = (string) $request->input('lien');
        $ok = $lien !== '' && preg_match('#^/[^/\\\\]#', $lien) === 1;

        return redirect($ok ? $lien : '/notifications');
    }

    public function toutes(Request $request)
    {
        try {
            Http::acceptJson()->timeout(30)->post($this->url('/lues') . '?' . http_build_query($this->lecteur()));
        } catch (ConnectionException $e) {
            return redirect('/notifications')->with('erreur', 'Le service Notifications est indisponible.');
        }

        $request->session()->forget('notif_cache');

        return redirect('/notifications')->with('succes', 'Toutes les notifications sont marquées comme lues.');
    }
}
