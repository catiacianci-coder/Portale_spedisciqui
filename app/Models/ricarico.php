<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ricarico extends Model
{
    protected $table = 'ricarichi';

    protected $fillable = [
        'nome',
        'percentuale',
    ];

    protected function casts(): array
    {
        return [
            'percentuale' => 'float',
        ];
    }
}
