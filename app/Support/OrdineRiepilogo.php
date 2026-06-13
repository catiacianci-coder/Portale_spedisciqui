<?php

namespace App\Support;

use App\Models\ordine;
use App\Models\stato_spedizione;

final class OrdineRiepilogo
{
    public static function contaSpedizioniAttive(ordine $ordine): int
    {
        $ordine->loadMissing('spedizioni');

        return $ordine->spedizioni
            ->filter(fn ($s) => (int) $s->spedizione_stato_id !== stato_spedizione::ANNULLATA)
            ->count();
    }

    public static function totaleIvatoAttivo(ordine $ordine): float
    {
        if ($ordine->stato === ordine::STATO_PAGATO && (float) ($ordine->pag_effettivo_or ?? 0) > 0) {
            return round((float) $ordine->pag_effettivo_or, 2);
        }

        if ((float) ($ordine->total_pagamento ?? 0) > 0) {
            return round((float) $ordine->total_pagamento, 2);
        }

        $ordine->loadMissing(['spedizioni.tariffaSpedizione', 'spedizioni.ordine.metodoPagamentoOrdine']);

        $tot = 0.0;
        foreach ($ordine->spedizioni as $sp) {
            if ((int) $sp->spedizione_stato_id === stato_spedizione::ANNULLATA) {
                continue;
            }
            $tot += (float) ($sp->prezzoClienteIvato() ?? 0);
        }

        return round($tot, 2);
    }
}
