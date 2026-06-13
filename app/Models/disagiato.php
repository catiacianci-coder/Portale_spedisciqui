<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class disagiato extends Model
{
    // Se hai creato il modello con la "d" minuscola, 
    // assicurati che il nome della classe qui sopra corrisponda al nome del file.

    protected $table = 'disagiatos';

    protected $fillable = [
        'corriere_id',
        'comune_id',
        'id_regola',
        'varie_1'
    ];
}