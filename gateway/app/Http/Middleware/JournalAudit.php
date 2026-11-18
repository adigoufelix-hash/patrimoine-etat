<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enregistre dans le Journal d'audit les actions réussies.
 * L'envoi se fait après la réponse et ne doit jamais bloquer ni casser le service principal.
 */
class JournalAudit
{
    private const ACTIONS = [
        'POST connexion' => 'Connexion',
        'POST inscription' => 'Création de compte',
        'POST deconnexion' => 'Déconnexion',
        'POST biens' => 'Création d’un bien',
        'PUT biens/{id}' => 'Modification d’un bien',
        'POST affectations' => 'Affectation d’un bien',
        'POST incidents' => 'Signalement d’un incident',
        'PATCH incidents/{id}/statut' => 'Changement de statut d’un incident',
        'POST utilisateurs' => 'Création d’un utilisateur',
        'PATCH utilisateurs/{id}' => 'Modification d’un utilisateur',
        'POST {type}' => 'Création (catégorie ou service)',
        'PUT {type}/{id}' => 'Modification (catégorie ou service)',
        'GET inventaire/export' => 'Export de l’inventaire (Excel)',
        'GET inventaire/imprimer' => 'Impression de l’inventaire (PDF)',
        'POST maintenances' => 'Planification d’une maintenance',
        'PATCH maintenances/{id}/statut' => 'Clôture d’une maintenance',
    ];

    // Seuls ces champs sont conservés : jamais de mot de passe ni de jeton
    private const CHAMPS = ['code_inventaire', 'designation', 'nom', 'bien_id', 'service_id', 'categorie_id', 'statut', 'role', 'type', 'email', 'code'];

    public function handle(Request $request, Closure $next): Response
    {
        // Pour la déconnexion, l'utilisateur doit être relevé avant que la session soit détruite
        $request->attributes->set('audit_user', session('user'));

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            $route = $request->route();
            if (! $route) {
                return;
            }

            $action = self::ACTIONS[$request->method() . ' ' . $route->uri()] ?? null;
            if (! $action) {
                return;
            }

            $session = $request->hasSession() ? $request->session() : null;
            $echec = $session && ($session->has('errors') || $session->has('erreur'));

            if ($request->isMethod('GET')) {
                if ($response->getStatusCode() !== 200) {
                    return;
                }
            } else {
                if (! $response->isRedirection()) {
                    return;
                }
                if ($echec) {
                    // Seul l'échec de connexion est une trace utile ; les autres échecs de saisie ne le sont pas
                    if ($route->uri() !== 'connexion') {
                        return;
                    }
                    $action = 'Échec de connexion';
                }
            }

            $u = $request->attributes->get('audit_user') ?: ($session ? $session->get('user') : null);

            $detail = array_filter(
                $request->only(self::CHAMPS),
                fn ($v) => is_scalar($v) && $v !== ''
            );
            if ($id = $route->parameter('id')) {
                $detail['id'] = $id;
            }

            $ressource = explode('/', $route->uri())[0];
            if ($ressource === '{type}') {
                $ressource = (string) $route->parameter('type');
            }
            $objet = $ressource . ($route->parameter('id') ? '/' . $route->parameter('id') : '');

            Http::acceptJson()->timeout(2)->connectTimeout(1)
                ->post(rtrim(config('microservices.audit'), '/') . '/api/evenements', [
                    'utilisateur_id' => $u['id'] ?? null,
                    'utilisateur_nom' => $u['name'] ?? ($request->input('email') ?: null),
                    'role' => $u['role'] ?? null,
                    'action' => $action,
                    'objet' => $objet,
                    'detail' => $detail ?: null,
                    'adresse_ip' => $request->ip(),
                ]);
        } catch (\Throwable $e) {
            // Le journal est secondaire : une panne ne doit jamais gêner l'utilisateur
        }
    }
}
