<?php

namespace App\Models;

use App\Support\MetodoPagamentoCodice;
use Illuminate\Database\Eloquent\Model;

class metodo_pagamento_wallet_ricarica extends Model
{
    protected $table = 'metodo_pagamento_wallet_ricariches';

    protected $fillable = [
        'codice',
        'metodo_pagamento',
        'abilitato',
        'commissioni',
        'varie',
    ];

    protected function casts(): array
    {
        return [
            'abilitato' => 'boolean',
            'commissioni' => 'float',
        ];
    }

    public function isCarta(): bool
    {
        return MetodoPagamentoCodice::isCartaCodice($this->codice)
            || MetodoPagamentoCodice::isCartaNome($this->metodo_pagamento);
    }

    public function isBonifico(): bool
    {
        return MetodoPagamentoCodice::isBonificoCodice($this->codice)
            || str_contains(strtolower(trim((string) $this->metodo_pagamento)), 'bonifico');
    }

    /**
     * Metodi selezionabili dal cliente per ricaricare il wallet.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeAbilitatiCliente($query)
    {
        return $query
            ->where('abilitato', true)
            ->orderBy('metodo_pagamento');
    }
}
