<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class spedizione_servizio_aggiuntivi extends Model
{
    protected $table = 'spedizione_servizio_aggiuntivis';

    protected $fillable = [
        'id_spedizionis',
        'id_corrieri_servizi_aggiuntivis',
        'testo_servizio',
        'valore_merce',
        'maggiorazione_pct',
        'maggiorazione_abs',
        'nostro_acquisto_stimato_iva_esc',
        'costo_cliente',
        'link_banca',
        'd_p_p_t',
        'd_r_p_t',
        'd_p_p_c',
        'd_r_p_c',
    ];

    protected function casts(): array
    {
        return [
            'valore_merce' => 'float',
            'maggiorazione_pct' => 'float',
            'maggiorazione_abs' => 'float',
            'nostro_acquisto_stimato_iva_esc' => 'float',
            'costo_cliente' => 'float',
            'd_p_p_t' => 'date',
            'd_r_p_t' => 'date',
            'd_p_p_c' => 'date',
            'd_r_p_c' => 'date',
        ];
    }

    public function spedizione(): BelongsTo
    {
        return $this->belongsTo(spedizione::class, 'id_spedizionis');
    }

    public function corriereServizioAggiuntivo(): BelongsTo
    {
        return $this->belongsTo(corrieri_servizi_aggiuntivi::class, 'id_corrieri_servizi_aggiuntivis');
    }

    /** Etichetta servizio (snapshot o pivot corriere). */
    public function getDenominazioneServizioAttribute(): ?string
    {
        $t = trim((string) ($this->testo_servizio ?? ''));
        if ($t !== '') {
            return $t;
        }

        return $this->corriereServizioAggiuntivo?->testo_servizio;
    }
}
