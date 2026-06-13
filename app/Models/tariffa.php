<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class tariffa extends Model
{
    protected $table = 'tariffas';

    protected $fillable = [
        'data_modifica',
        'data_sospensione',
        'id_corrieres',
        'servizio',
        'id_tipo_spediziones',
        'peso_da',
        'peso_a',
        'livello',
        'tariffa',
        'lato_max',
        'lato_med',
        'lato_min',
        'max',
        'peso_max_collo',
        'id_ricarico',
        'nazione_partenza',
        'nazione_arrivo',
        'sicilia',
        'calabria',
        'sardegna',
        'varie1',
        'varie2',
        'varie3',
    ];

    protected function casts(): array
    {
        return [
            'sicilia' => 'float',
            'calabria' => 'float',
            'sardegna' => 'float',
        ];
    }

    protected $appends = [
        'ricarico',
    ];

    public function tipoSpedizione(): BelongsTo
    {
        return $this->belongsTo(tipo_spedizone::class, 'id_tipo_spediziones');
    }

    public function ricaricoConfig(): BelongsTo
    {
        return $this->belongsTo(ricarico::class, 'id_ricarico');
    }

    public function getRicaricoAttribute(): float
    {
        if ($this->relationLoaded('ricaricoConfig')) {
            return (float) ($this->ricaricoConfig?->percentuale ?? 0);
        }

        return (float) ($this->ricaricoConfig()->value('percentuale') ?? 0);
    }
}
