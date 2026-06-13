<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class wallet_descrizione extends Model
{
    protected $table = 'wallet_descrizionis';

    protected $fillable = [
        'tipo',
        'codice',
        'descrizione',
    ];

    public function movimenti(): HasMany
    {
        return $this->hasMany(wallet_movimento::class, 'wallet_descrizione_id');
    }
}
