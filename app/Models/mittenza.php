<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class mittenza extends Model
{
    protected $table = 'mittenzes';

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
        'is_preferito',
        'is_fatturazione',
        'sede_liccardi',
        'varie1',
        'varie2',
        'varie3',
        'varie4',
    ];

    protected function casts(): array
    {
        return [
            'is_preferito' => 'boolean',
            'is_fatturazione' => 'boolean',
            'sede_liccardi' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comune(): BelongsTo
    {
        return $this->belongsTo(comune::class, 'id_comune');
    }
}
