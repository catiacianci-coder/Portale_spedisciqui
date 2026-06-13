<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class modalita_pagamento_nconformita extends Model
{
    protected $table = 'modalita_pagamento_nconformitas';

    protected $fillable = [
        'codice',
        'nome',
        'abilitato',
        'ordine',
    ];

    protected function casts(): array
    {
        return [
            'abilitato' => 'boolean',
        ];
    }
}
