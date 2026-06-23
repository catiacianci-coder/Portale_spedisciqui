<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class stato_spedizione extends Model
{
    protected $table = 'stato_spedizionis';

    public const NON_PAGATA = 1;

    public const PAGATA = 2;

    public const GENERATA = 3;

    /** In tabella stato_spedizionis id=4, denominazione «in attesa di rimborso». */
    public const ANNULLATA = 4;

    public const RIMBORSATA = 5;

    protected $fillable = [
        'denominazione_stato',
    ];

    public function spedizioni(): HasMany
    {
        return $this->hasMany(spedizione::class, 'spedizione_stato_id');
    }
}
