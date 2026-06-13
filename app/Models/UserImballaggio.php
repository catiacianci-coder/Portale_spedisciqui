<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserImballaggio extends Model
{
    protected $table = 'user_imballaggi';

    protected $fillable = [
        'user_id',
        'id_tipo_spediziones',
        'nome',
        'altezza',
        'larghezza',
        'spessore',
        'peso',
        'is_preferito',
    ];

    protected function casts(): array
    {
        return [
            'altezza' => 'float',
            'larghezza' => 'float',
            'spessore' => 'float',
            'peso' => 'float',
            'is_preferito' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tipoSpedizione(): BelongsTo
    {
        return $this->belongsTo(tipo_spedizone::class, 'id_tipo_spediziones');
    }
}
