<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['nom', 'code', 'localisation'];

    public function affectations()
    {
        return $this->hasMany(Affectation::class);
    }
}
