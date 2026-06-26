<?php

namespace App\Support;

use App\Models\wallet_ricarica_richiesta;

final class WalletRicaricaMetodoPagamento
{
    /**
     * Metodo mostrato in elenco: valorizzato solo dopo accredito (Pagato).
     */
    public static function labelCliente(?wallet_ricarica_richiesta $ricarica): string
    {
        if ($ricarica === null || $ricarica->stato !== 'accreditata') {
            return '—';
        }

        $nome = trim((string) ($ricarica->metodoPagamento?->metodo_pagamento ?? ''));

        return $nome !== '' ? $nome : '—';
    }
}
