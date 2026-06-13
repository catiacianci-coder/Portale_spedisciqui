<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class corrieri_servizi_aggiuntivi extends Model
{
    protected $table = 'corrieri_servizi_aggiuntivis';

    protected $fillable = [
        'fonte_servizio',
        'id_tipo',
        'id_corriere',
        'codice_servizio_corriere',
        'testo_servizio',
        'visualizzato',
        'min_fascia',
        'max_fascia',
        'percentuale_cor',
        'ricarico_k91',
        'valore_fisso_cor',
        'valore_fisso_k91',
        'valore_percentuale',
        'valore_minimo',
        'valore_massimo',
        'rimessa_tra',
        'rimessa_clli',
        'varie1',
        'varie2',
        'varie3',
        'varie4',
    ];

    protected function casts(): array
    {
        return [
            'visualizzato' => 'boolean',
            'min_fascia' => 'float',
            'max_fascia' => 'float',
            'percentuale_cor' => 'float',
            'ricarico_k91' => 'float',
            'valore_fisso_cor' => 'float',
            'valore_fisso_k91' => 'float',
            'valore_percentuale' => 'float',
            'valore_minimo' => 'float',
            'valore_massimo' => 'float',
            'rimessa_tra' => 'integer',
            'rimessa_clli' => 'integer',
        ];
    }

    public function corriere(): BelongsTo
    {
        return $this->belongsTo(corriere::class, 'id_corriere');
    }

    public function tipoSpedizione(): BelongsTo
    {
        return $this->belongsTo(tipo_spedizone::class, 'id_tipo');
    }

    /** Etichetta per viste legacy che usavano denominazione_servizio. */
    public function getDenominazioneServizioAttribute(): string
    {
        return (string) $this->testo_servizio;
    }
}
