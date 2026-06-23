<?php

namespace App\Support;

use App\Models\ordine;
use App\Models\stato_spedizione;
use App\Models\tariffa_spedizione;
use App\Services\OrdineTotaleIvatoService;

/**
 * Importo effettivamente pagato (ordine e singole spedizioni), congelato al pagamento.
 */
final class OrdinePagamentoEffettivo
{
    public static function isWalletMetodo(int $metodoPagamentoOrdineId): bool
    {
        return app(OrdineTotaleIvatoService::class)->metodoIsWallet($metodoPagamentoOrdineId);
    }

    public static function importoOrdine(ordine $ordine, int $metodoPagamentoOrdineId): float
    {
        if (self::isWalletMetodo($metodoPagamentoOrdineId)) {
            return round((float) ($ordine->total_pagamento_wallet ?? 0), 2);
        }

        return round((float) ($ordine->total_pagamento ?? 0), 2);
    }

    public static function importoSpedizioneDaTariffa(
        tariffa_spedizione $tariffa,
        int $metodoPagamentoOrdineId,
        ?float $aliquotaIva = null,
    ): float {
        if (self::isWalletMetodo($metodoPagamentoOrdineId)) {
            $ivato = round((float) ($tariffa->cliente_ivato_wallet ?? 0), 2);
            if ($ivato > 0) {
                return $ivato;
            }

            $netto = round((float) ($tariffa->totale_spedizione_wallet ?? $tariffa->totale_spedizione ?? 0), 2);

            return TariffaSpedizioneClienteIvato::calcolaDaNetto(
                $netto,
                $aliquotaIva ?? TariffaSpedizioneClienteIvato::aliquotaIva(),
                0,
            );
        }

        $ivato = round((float) ($tariffa->cliente_ivato ?? 0), 2);
        if ($ivato > 0) {
            return $ivato;
        }

        $netto = round((float) ($tariffa->totale_spedizione ?? 0), 2);

        return TariffaSpedizioneClienteIvato::calcolaDaNetto(
            $netto,
            $aliquotaIva ?? TariffaSpedizioneClienteIvato::aliquotaIva(),
            0,
        );
    }

    public static function registraSuTariffe(ordine $ordine, int $metodoPagamentoOrdineId): void
    {
        $ordine->loadMissing(['spedizioni.tariffaSpedizione']);

        $aliquota = TariffaSpedizioneClienteIvato::aliquotaIva($ordine);

        /** @var array<int, tariffa_spedizione> $tariffe */
        $tariffe = [];
        foreach ($ordine->spedizioni as $spedizione) {
            if ((int) $spedizione->spedizione_stato_id === stato_spedizione::ANNULLATA) {
                continue;
            }
            $tariffa = $spedizione->tariffaSpedizione;
            if ($tariffa) {
                $tariffe[] = $tariffa;
            }
        }

        if ($tariffe === []) {
            return;
        }

        foreach ($tariffe as $tariffa) {
            $importo = self::importoSpedizioneDaTariffa($tariffa, $metodoPagamentoOrdineId, $aliquota);
            $tariffa->update(['pag_effettivo_sp' => round($importo, 2)]);
        }
    }
}
