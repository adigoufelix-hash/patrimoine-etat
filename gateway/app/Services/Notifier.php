<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/** Envoie une notification au service Notifications, après la réponse et sans jamais bloquer ni casser l'appelant. */
class Notifier
{
    public static function envoyer(array $notification): void
    {
        app()->terminating(function () use ($notification) {
            try {
                Http::acceptJson()->timeout(2)->connectTimeout(1)
                    ->post(rtrim(config('microservices.notifications'), '/') . '/api/notifications', $notification);
            } catch (\Throwable $e) {
                // le service Notifications est secondaire : on ignore l'échec
            }
        });
    }
}
