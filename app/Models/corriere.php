<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class corriere extends Model
{
    protected $fillable = [
        'nome_corriere',
        'nome_corriere_preventivo',
        'nome_servizio',
        'codice_servizio',
        'istat',
        'nome_area',
        'nome_visualizzato',
        'tipo_o_d',
        'numero_contratto',
        'attivo',
        'tariffa_interna',
        'id_ricarico',
        'piattaforma',
        'carrier_code',
        'contract_code',
        'sicilia',
        'calabria',
        'sardegna',
        'fuel',
        'soglia_esenzione',
        'pickup',
        'consegna',
        'punto_ritiro',
        'punto_consegna',
        'trackingsn',
        'url_tracking',
    ];

    protected function casts(): array
    {
        return [
            'attivo' => 'boolean',
            'tariffa_interna' => 'boolean',
            'sicilia' => 'boolean',
            'calabria' => 'boolean',
            'sardegna' => 'boolean',
            'trackingsn' => 'boolean',
            'fuel' => 'float',
            'soglia_esenzione' => 'float',
        ];
    }

    public function ricarico(): BelongsTo
    {
        return $this->belongsTo(ricarico::class, 'id_ricarico');
    }

    public function messaggiTracciamento(): HasMany
    {
        return $this->hasMany(msg_traccaimento::class, 'corriere_id');
    }

    public function percentualeRicarico(): float
    {
        if ($this->relationLoaded('ricarico')) {
            return (float) ($this->ricarico?->percentuale ?? 0);
        }

        if (! $this->id_ricarico) {
            return 0.0;
        }

        return (float) ($this->ricarico()->value('percentuale') ?? 0);
    }

    /**
     * @return array{costo_api: float, prezzo_cliente: float, ricarico_percentuale: float}
     */
    public function prezzoTrasportoDaCostoApi(float $costoApi): array
    {
        $pct = $this->percentualeRicarico();
        $costo = round($costoApi, 2);

        return [
            'costo_api' => $costo,
            'prezzo_cliente' => round($costo * (1 + ($pct / 100)), 2),
            'ricarico_percentuale' => $pct,
        ];
    }
}
