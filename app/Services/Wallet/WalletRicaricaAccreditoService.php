<?php

namespace App\Services\Wallet;

use App\Models\wallet_descrizione;
use App\Models\wallet_movimento;
use App\Models\wallet_ricarica_richiesta;
use Illuminate\Support\Facades\DB;

final class WalletRicaricaAccreditoService
{
    /**
     * @return array{ok: bool, already?: bool, message?: string}
     */
    public function accredita(
        wallet_ricarica_richiesta $richiesta,
        ?int $metodoId = null,
        ?string $tokenPagamento = null,
        ?string $riferimentoMovimento = null,
    ): array {
        if ($richiesta->stato === 'accreditata') {
            return ['ok' => true, 'already' => true];
        }

        if ($richiesta->stato !== 'in_attesa') {
            return ['ok' => false, 'message' => 'Questa ricarica non è in attesa di pagamento.'];
        }

        $desc = wallet_descrizione::query()->where('codice', 'ricarica')->first();
        if (! $desc) {
            return ['ok' => false, 'message' => 'Configurazione Wallet non disponibile.'];
        }

        $importo = (float) $richiesta->importo;
        $codice = (string) ($richiesta->numero_ordine_wallet ?? wallet_ricarica_richiesta::PREFIX_NUMERO_ORDINE_WALLET.$richiesta->id);
        $riferimento = $riferimentoMovimento ?? ('Ricarica '.$codice);

        $mov = DB::transaction(function () use ($richiesta, $desc, $importo, $metodoId, $tokenPagamento, $riferimento) {
            $row = wallet_ricarica_richiesta::query()
                ->whereKey($richiesta->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($row->stato === 'accreditata') {
                return false;
            }

            if ($row->stato !== 'in_attesa') {
                return null;
            }

            $movimento = wallet_movimento::query()->create([
                'user_id' => $row->user_id,
                'tipo' => 'credito',
                'wallet_descrizione_id' => $desc->id,
                'importo' => $importo,
                'data_movimento' => now(),
                'riferimento' => $riferimento,
                'ordine_id' => null,
            ]);

            $update = [
                'stato' => 'accreditata',
                'wallet_movimento_id' => $movimento->id,
            ];

            if ($metodoId !== null && $metodoId > 0) {
                $update['id_metodo_pagamento_wallet_ricariches'] = $metodoId;
            }

            if ($tokenPagamento !== null && trim($tokenPagamento) !== '') {
                $update['token_pagamento'] = trim($tokenPagamento);
            }

            $row->forceFill($update)->save();

            return $movimento;
        });

        if ($mov === false) {
            return ['ok' => true, 'already' => true];
        }

        if ($mov === null) {
            return ['ok' => false, 'message' => 'Stato ricarica cambiato: operazione non eseguita.'];
        }

        return ['ok' => true];
    }
}
