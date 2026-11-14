<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Affectation extends Model
{
    protected $fillable = ['bien_id', 'service_id', 'date_debut', 'date_fin', 'motif'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
