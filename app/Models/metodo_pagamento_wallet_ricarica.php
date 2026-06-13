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
}
