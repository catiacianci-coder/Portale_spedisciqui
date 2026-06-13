<?php

namespace App\Models;

use App\Support\MetodoPagamentoCodice;
use Illuminate\Database\Eloquent\Model;

class metodo_pagamento_ordine extends Model
{
    protected $table = 'metodo_pagamento_ordinis';

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

    public function isWallet(): bool
    {
        return MetodoPagamentoCodice::isWalletCodice($this->codice);
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
     * Metodi selezionabili dal cliente (esclusi quelli riservati al back-office).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeParaPagamentoCliente($query)
    {
        return $query
            ->where('abilitato', true)
            ->where('metodo_pagamento', 'not like', '%BackOffice%')
            ->where('metodo_pagamento', 'not like', '%Back-office%');
    }
}
