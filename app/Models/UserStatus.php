<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStatus extends Model
{
    // Corretto: punta alla tabella di collegamento
    protected $table = 'user_status';

    // Corretto: permette la scrittura di questi campi
    protected $fillable = [
        'id_utente',
        'id_status',
        'data_definizione'
    ];

    /**
     * Relazione verso l'utente.
     * Serve a Laravel per capire a chi appartiene lo stato.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_utente');
    }
}