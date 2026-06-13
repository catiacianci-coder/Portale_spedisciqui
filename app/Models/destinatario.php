<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class destinatario extends Model
{
    protected $table = 'destinatari';

    protected $fillable = [
        'user_id',
        'nome',
        'cognome',
        'denominazione_ragione_sociale',
        'indirizzo',
        'civico',
        'cap',
        'citta',
        'provincia',
        'id_comune',
        'telefono',
        'email',
        'varie1',
        'varie2',
        'varie3',
        'varie4',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comune(): BelongsTo
    {
        return $this->belongsTo(comune::class, 'id_comune');
    }
}
