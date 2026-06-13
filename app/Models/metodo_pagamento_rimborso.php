<?php

namespace App\Models;

use App\Support\MetodoPagamentoCodice;
use Illuminate\Database\Eloquent\Model;

class metodo_pagamento_rimborso extends Model
{
    protected $table = 'metodo_pagamento_rimborsi';

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

    public static function idMetodoWalletAttivo(): ?int
    {
        $id = self::query()->where('codice', 'wallet')->where('abilitato', true)->value('id');

        return $id !== null ? (int) $id : null;
    }

    public static function idMetodoCartaAttivo(): ?int
    {
        $id = self::query()->where('codice', 'carta')->where('abilitato', true)->value('id');

        return $id !== null ? (int) $id : null;
    }
}
