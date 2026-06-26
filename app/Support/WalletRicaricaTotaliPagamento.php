<?php

namespace App\Support;

use App\Models\metodo_pagamento_wallet_ricarica;
use App\Models\wallet_ricarica_richiesta;

final class WalletRicaricaTotaliPagamento
{
    /**
     * @return array{imponibile: float, iva: float, totale: float, commissioni_pct: float}
     */
    public static function perMetodo(wallet_ricarica_richiesta $ricarica, int $metodoId): array
    {
        $metodo = metodo_pagamento_wallet_ricarica::query()
            ->where('abilitato', true)
            ->findOrFail($metodoId);

        $imponibile = round((float) $ricarica->importo, 2);
        $pct = max(0.0, (float) $metodo->commissioni);
        $extra = round($imponibile * $pct / 100, 2);
        $totale = round($imponibile + $extra, 2);

        return [
            'imponibile' => $imponibile,
            'iva' => 0.0,
            'totale' => $totale,
            'commissioni_pct' => $pct,
        ];
    }
}
