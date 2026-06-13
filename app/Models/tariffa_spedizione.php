<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class tariffa_spedizione extends Model
{
    protected $table = 'tariffe_spediziones';

    protected $fillable = [
        'spedizione_id',
        'codice_interno',
        'costo_trasporto',
        'costo_fuel',
        'ricarico_trasporto',
        'totale_cliente',
        'totale_cliente_wallet',
        'costo_servizi_aggiuntivi',
        'cliente_servizi_aggiuntivi',
        'totale_spedizione',
        'totale_spedizione_wallet',
        'margine_lordo',
        'cliente_ivato',
        'cliente_ivato_wallet',
        'pag_effettivo_sp',
    ];

    protected function casts(): array
    {
        return [
            'costo_trasporto' => 'float',
            'costo_fuel' => 'float',
            'ricarico_trasporto' => 'float',
            'totale_cliente' => 'float',
            'totale_cliente_wallet' => 'float',
            'costo_servizi_aggiuntivi' => 'float',
            'cliente_servizi_aggiuntivi' => 'float',
            'totale_spedizione' => 'float',
            'totale_spedizione_wallet' => 'float',
            'margine_lordo' => 'float',
            'cliente_ivato' => 'float',
            'cliente_ivato_wallet' => 'float',
            'pag_effettivo_sp' => 'float',
        ];
    }

    public function spedizione(): BelongsTo
    {
        return $this->belongsTo(spedizione::class, 'spedizione_id');
    }
}
