<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tipo_spedizone extends Model
{
    protected $table = 'tipo_spediziones';

    protected $fillable = [
        'tipo_spedizione',
        'varie',
    ];
}
