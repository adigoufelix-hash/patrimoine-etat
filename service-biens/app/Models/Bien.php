<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bien extends Model
{
    protected $table = 'biens';

    protected $fillable = [
        'code_inventaire', 'designation', 'description',
        'date_acquisition', 'valeur', 'etat', 'categorie_id',
    ];

    public function categorie()
    {
        return $this->belongsTo(Categorie::class);
    }
}