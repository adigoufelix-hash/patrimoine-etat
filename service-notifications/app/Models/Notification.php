<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    public $timestamps = false;

    protected $fillable = ['titre', 'message', 'lien', 'role_cible', 'utilisateur_id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn ($n) => $n->created_at = now());
    }

    /** RG15 : seules les notifications destinées à cet utilisateur ou à son rôle sont visibles. */
    public function scopePour($q, ?int $utilisateurId, ?string $role)
    {
        if (! $utilisateurId && ! $role) {
            return $q->whereRaw('1 = 0'); // sans destinataire, on ne montre rien
        }

        return $q->where(function ($w) use ($utilisateurId, $role) {
            if ($utilisateurId) {
                $w->orWhere('utilisateur_id', $utilisateurId);
            }
            if ($role) {
                $w->orWhere('role_cible', $role);
            }
        });
    }
}
