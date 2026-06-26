<?php

namespace App\Services\Wallet;

use App\Models\User;
use App\Models\wallet_descrizione;
use App\Models\wallet_movimento;
use App\Services\WalletSaldoService;
use App\Support\ImportoEuro;
use App\Support\WalletMovimentoRiferimentoPresenter;

final class WalletMovimentoManualeService
{
    /**
     * @return array{ok: bool, message?: string}
     */
    public function crea(
        User $user,
        string $tipo,
        int $descrizioneId,
        float $importo,
        string $riferimento,
        ?string $notaInterna = null,
    ): array {
        $descr = wallet_descrizione::query()->find($descrizioneId);
        if ($descr === null) {
            return ['ok' => false, 'message' => 'Descrizione movimento non valida.'];
        }

        if ($descr->tipo !== $tipo) {
            return ['ok' => false, 'message' => 'La descrizione selezionata non corrisponde al tipo scelto.'];
        }

        if (WalletMovimentoRiferimentoPresenter::isRiferimentoAutomatico((string) $descr->codice)) {
            return [
                'ok' => false,
                'message' => 'La causale «'.$descr->descrizione.'» è gestita automaticamente dal sistema e non può essere inserita manualmente.',
            ];
        }

        $riferimento = trim($riferimento);
        if ($riferimento === '') {
            return ['ok' => false, 'message' => 'Indica il riferimento (Ordine/LdV) del movimento.'];
        }

        if ($importo <= 0) {
            return ['ok' => false, 'message' => 'L\'importo deve essere maggiore di zero.'];
        }

        if ($tipo === 'debito') {
            $saldo = app(WalletSaldoService::class)->saldoUtente((int) $user->id);
            if (round($saldo, 2) + 1e-9 < round($importo, 2)) {
                return [
                    'ok' => false,
                    'message' => 'Saldo insufficiente per un addebito di '
                        .ImportoEuro::format($importo)
                        .' (saldo attuale: '.ImportoEuro::format($saldo).').',
                ];
            }
        }

        $nota = trim((string) ($notaInterna ?? ''));

        wallet_movimento::query()->create([
            'user_id' => $user->id,
            'tipo' => $tipo,
            'wallet_descrizione_id' => $descr->id,
            'importo' => round($importo, 2),
            'data_movimento' => now(),
            'riferimento' => $riferimento,
            'nota_interna' => $nota !== '' ? $nota : null,
        ]);

        return ['ok' => true];
    }
}
