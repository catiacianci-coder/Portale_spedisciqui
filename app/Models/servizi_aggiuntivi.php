<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class servizi_aggiuntivi extends Model
{
    protected $table = 'servizi_aggiuntivis';

    protected $fillable = [
        'denominazione_servizio',
        'abbrev',
        'varie',
    ];
}
