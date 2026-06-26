<?php

namespace App\Models;

use App\Services\WalletSaldoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class wallet_movimento extends Model
{
    protected $table = 'wallet_movimentis';

    protected $fillable = [
        'user_id',
        'tipo',
        'wallet_descrizione_id',
        'importo',
        'data_movimento',
        'riferimento',
        'nota_interna',
        'ordine_id',
    ];

    protected function casts(): array
    {
        return [
            'importo' => 'decimal:2',
            'data_movimento' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (wallet_movimento $m): void {
            if ($m->importo < 0) {
                throw new \InvalidArgumentException('L\'importo del movimento wallet deve essere positivo.');
            }
            $d = wallet_descrizione::query()->find($m->wallet_descrizione_id);
            if ($d && $d->tipo !== $m->tipo) {
                throw new \InvalidArgumentException('Il tipo del movimento non coincide con la descrizione scelta.');
            }
        });

        static::created(function (wallet_movimento $m): void {
            app(WalletSaldoService::class)->applicaMovimento($m);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function descrizione(): BelongsTo
    {
        return $this->belongsTo(wallet_descrizione::class, 'wallet_descrizione_id');
    }

    public function ordine(): BelongsTo
    {
        return $this->belongsTo(ordine::class, 'ordine_id');
    }

    /** Richiesta ricarica wallet che ha generato questo movimento (accredito da back-office / flusso ORW). */
    public function ricaricaRichiesta(): HasOne
    {
        return $this->hasOne(wallet_ricarica_richiesta::class, 'wallet_movimento_id');
    }
}
