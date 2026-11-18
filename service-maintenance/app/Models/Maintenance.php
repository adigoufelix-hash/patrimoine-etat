<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    protected $fillable = ['bien_id', 'type', 'description', 'date_prevue', 'date_realisee', 'statut', 'remarque', 'cree_par', 'alerte_le'];

    protected $appends = ['en_retard'];

    protected function casts(): array
    {
        return ['date_prevue' => 'date', 'date_realisee' => 'date', 'alerte_le' => 'date'];
    }

    /** Une maintenance planifiée dont la date est passée est « en retard ». */
    public function getEnRetardAttribute(): bool
    {
        return $this->statut === 'planifiee' && $this->date_prevue !== null && $this->date_prevue->lt(today());
    }
}
