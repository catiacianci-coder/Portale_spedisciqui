<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class italia_destino extends Model
{
    protected $fillable = [
        'id_corriere',
        'id_comune',
        'varie',
    ];
}