<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lecture extends Model
{
    public $timestamps = false;

    protected $table = 'lectures';

    protected $fillable = ['notification_id', 'utilisateur_id'];
}
