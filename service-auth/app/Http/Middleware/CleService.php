<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Refuse tout appel qui ne porte pas la clé de service (seule la gateway la connaît). */
class CleService
{
    public function handle(Request $request, Closure $next): Response
    {
        $attendue = (string) config('microservices.cle');
        $recue = (string) $request->header('X-Service-Key');

        if ($attendue === '' || ! hash_equals($attendue, $recue)) {
            return response()->json(['message' => 'Appel non autorisé.'], 403);
        }

        return $next($request);
    }
}
