<?php

namespace App\Services\Rimborso;

use App\Models\rimborso;
use App\Models\spedizione;
use App\Models\User;
use App\Models\wallet_descrizione;
use App\Models\wallet_movimento;
use App\Services\WalletSaldoService;

final class RimborsoCreditoWalletService
{
    public function __construct(
        private readonly WalletSaldoService $walletSaldo,
    ) {}

    public function creditar(rimborso $rimborso, spedizione $spedizione, User $user): void
    {
        $descr = wallet_descrizione::query()
            ->where('codice', 'rimborso_spedizione')
            ->where('tipo', 'credito')
            ->first();

        if (! $descr) {
            throw new \RuntimeException('Descrizione wallet «rimborso_spedizione» non configurata.');
        }

        $importo = round((float) $rimborso->valore, 2);
        $mov = wallet_movimento::create([
            'user_id' => $user->id,
            'tipo' => 'credito',
            'wallet_descrizione_id' => $descr->id,
            'importo' => $importo,
            'data_movimento' => now(),
            'riferimento' => (string) ($spedizione->codice_interno ?? $rimborso->token ?? ''),
            'ordine_id' => $rimborso->ordine_id,
        ]);

        $this->walletSaldo->applicaMovimento($mov);
    }
}
