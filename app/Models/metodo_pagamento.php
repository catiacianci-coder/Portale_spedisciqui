<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tabella legacy: usata solo da {@see parametri_globali} (parametri storici).
 * I metodi operativi sono in metodo_pagamento_ordinis, metodo_pagamento_wallet_ricariches, metodo_pagamento_rimborsi.
 */
class metodo_pagamento extends Model
{
    protected $table = 'metodo_pagamentos';

    protected $fillable = [
        'metodo_pagamento',
        'abilitato',
        'varie',
    ];

    protected function casts(): array
    {
        return [
            'abilitato' => 'boolean',
        ];
    }


    public function parametriGlobali(): HasMany
    {
        return $this->hasMany(parametri_globali::class, 'id_metodo_pagamentos');
    }
}
