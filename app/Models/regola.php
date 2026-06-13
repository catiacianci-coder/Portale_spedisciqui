<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class regola extends Model
{
    protected $table = 'regole';

    protected $fillable = [
        'nome',
        'descrizione',
        'peso_min',
        'peso_max',
        'percentuale',
        'applica_su',
        'valore_fisso',
        'cap_origine',
        'cap_destino',
        'tipo_formula',
        'blocco_peso_kg',
        'varie1',
        'varie2',
        'varie3',
        'varie4',
        'varie5',
        'attiva',
    ];
}
