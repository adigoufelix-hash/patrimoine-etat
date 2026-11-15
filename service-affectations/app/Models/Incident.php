<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    protected $fillable = ['bien_id', 'signale_par', 'type', 'description', 'statut', 'date_signalement'];
}
