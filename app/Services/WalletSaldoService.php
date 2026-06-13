<?php

namespace App\Services;

use App\Models\wallet_movimento;
use App\Models\wallet_saldo;
use Illuminate\Support\Facades\DB;

class WalletSaldoService
{
    /** Aggiorna (o crea) la riga saldo dopo inserimento movimento. */
    public function applicaMovimento(wallet_movimento $movimento): void
    {
        DB::transaction(function () use ($movimento): void {
            $uid = (int) $movimento->user_id;
            $delta = $movimento->tipo === 'credito'
                ? (float) $movimento->importo
                : -1 * (float) $movimento->importo;

            $row = wallet_saldo::query()
                ->where('user_id', $uid)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                wallet_saldo::query()->create([
                    'user_id' => $uid,
                    'saldo' => round($delta, 2),
                ]);

                return;
            }

            $row->saldo = round((float) $row->saldo + $delta, 2);
            $row->save();
        });
    }

    /** Saldo attuale (0 se ancora nessuna riga). */
    public function saldoUtente(int $userId): float
    {
        $row = wallet_saldo::query()->where('user_id', $userId)->first();

        return $row ? (float) $row->saldo : 0.0;
    }
}
