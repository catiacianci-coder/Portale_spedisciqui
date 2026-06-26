<?php

namespace App\Services\Wallet;

use App\Models\metodo_pagamento_wallet_ricarica;
use App\Models\wallet_ricarica_richiesta;
use Illuminate\Http\RedirectResponse;

final class WalletRicaricaPagamentoService
{
    public function registraBonifico(wallet_ricarica_richiesta $ricarica, int $metodoId): RedirectResponse
    {
        if ($ricarica->stato !== 'in_attesa') {
            return redirect()
                ->route('wallet.ricariche')
                ->withErrors(['ricarica' => 'Questa ricarica non è in attesa di pagamento.']);
        }

        $metodo = metodo_pagamento_wallet_ricarica::query()
            ->where('abilitato', true)
            ->findOrFail($metodoId);

        if (! $metodo->isBonifico()) {
            abort(404);
        }

        $codice = (string) ($ricarica->numero_ordine_wallet ?? wallet_ricarica_richiesta::PREFIX_NUMERO_ORDINE_WALLET.$ricarica->id);

        return redirect()
            ->route('wallet.ricariche')
            ->with(
                'ok',
                'Istruzioni bonifico registrate per '.$codice.'. Effettua il bonifico con l\'IBAN e la causale indicati; l\'importo sarà accreditato al ricevimento.',
            );
    }
}
