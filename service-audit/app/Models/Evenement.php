<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Journal d'audit : en ajout seul (RG13). Aucune modification ni suppression possible. */
class Evenement extends Model
{
    public $timestamps = false;

    protected $fillable = ['utilisateur_id', 'utilisateur_nom', 'role', 'action', 'objet', 'detail', 'adresse_ip'];

    protected function casts(): array
    {
        return ['detail' => 'array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn ($e) => $e->created_at = now());
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }
}
