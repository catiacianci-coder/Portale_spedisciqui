<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class wallet_ricarica_richiesta extends Model
{
    protected $table = 'wallet_ricarica_richiestas';

    /** Prefisso pubblico per riferimento ricarica (concatenato con id). */
    public const PREFIX_NUMERO_ORDINE_WALLET = 'ORW-';

    protected $fillable = [
        'user_id',
        'importo',
        'id_metodo_pagamento_wallet_ricariches',
        'token_pagamento',
        'stato',
        'wallet_movimento_id',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'importo' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $row): void {
            $code = self::PREFIX_NUMERO_ORDINE_WALLET.$row->id;
            if ($row->numero_ordine_wallet === $code) {
                return;
            }
            $row->numero_ordine_wallet = $code;
            $row->saveQuietly();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movimento(): BelongsTo
    {
        return $this->belongsTo(wallet_movimento::class, 'wallet_movimento_id');
    }

    public function metodoPagamentoWalletRicarica(): BelongsTo
    {
        return $this->belongsTo(metodo_pagamento_wallet_ricarica::class, 'id_metodo_pagamento_wallet_ricariches');
    }

    public function metodoPagamento(): BelongsTo
    {
        return $this->metodoPagamentoWalletRicarica();
    }
}
