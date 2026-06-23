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

    /** Importo pagato in tabella ordini: «€ 12,50 (wallet)». */
    public static function importoPagatoTabella(ordine $ordine): string
    {
        $importoFmt = \App\Support\ImportoEuro::format(self::totaleIvatoAttivo($ordine));
        $metodo = self::labelMetodoPagamentoTabella($ordine);

        return $metodo !== '' ? $importoFmt.' ('.$metodo.')' : $importoFmt;
    }

    /** Importo ivato riga spedizione: «€ 6,69 (wallet)» se richiesto. */
    public static function importoIvatoRigaTabella(?float $importo, ordine $ordine, bool $conMetodoPagamento = false): string
    {
        $importoFmt = ImportoEuro::format($importo);
        if (! $conMetodoPagamento || $importo === null) {
            return $importoFmt;
        }

        $metodo = self::labelMetodoPagamentoTabella($ordine);

        return $metodo !== '' ? $importoFmt.' ('.$metodo.')' : $importoFmt;
    }

    public static function labelMetodoPagamentoTabella(ordine $ordine): string
    {
        $ordine->loadMissing('metodoPagamentoOrdine');
        $metodo = $ordine->metodoPagamentoOrdine;

        if ($metodo) {
            $codice = strtolower(trim((string) ($metodo->codice ?? '')));
            if ($codice !== '') {
                return $codice;
            }
        }

        $suOrdine = strtolower(trim((string) ($ordine->metodo_pagamento ?? '')));
        if ($suOrdine !== '') {
            return $suOrdine;
        }

        return '';
    }

    /**
     * Totali ivato standard (Carte/Bonifico) e Wallet per ordini non pagati.
     *
     * @return array{standard: float, wallet: float}
     */
    public static function totaliDualiNonPagato(ordine $ordine): array
    {
        $standard = round((float) ($ordine->total_pagamento ?? 0), 2);
        $wallet = round((float) ($ordine->total_pagamento_wallet ?? 0), 2);

        if ($standard > 0 && $wallet > 0) {
            return ['standard' => $standard, 'wallet' => $wallet];
        }

        $ordine->loadMissing(['spedizioni.tariffaSpedizione', 'spedizioni.ordine']);

        $standard = 0.0;
        $wallet = 0.0;
        foreach ($ordine->spedizioni as $sp) {
            if ((int) $sp->spedizione_stato_id === stato_spedizione::ANNULLATA) {
                continue;
            }
            $standard += (float) ($sp->prezzoClienteIvato() ?? 0);
            $wallet += (float) ($sp->prezzoClienteIvatoWallet() ?? 0);
        }

        return [
            'standard' => round($standard, 2),
            'wallet' => round($wallet, 2),
        ];
    }
}
