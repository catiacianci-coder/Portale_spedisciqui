<?php

namespace App\Support;

use App\Models\metodo_pagamento_ordine;
use App\Models\ordine;
use App\Models\parametri_globali;
use App\Models\tariffa_spedizione;

/**
 * Importo ivato per riga (totale_spediziones.cliente_ivato), allineato a OrdineTotaleIvatoService.
 */
final class TariffaSpedizioneClienteIvato
{
    public const DENOM_ALIQUOTA_IVA = 'Aliquota IVA';

    public static function aliquotaIva(?ordine $ordine = null): float
    {
        if ($ordine !== null) {
            $raw = $ordine->dettaglio_json['aliquota_iva'] ?? null;
            if ($raw !== null && is_numeric($raw)) {
                return (float) $raw;
            }
        }

        $v = parametri_globali::recordAttivo(self::DENOM_ALIQUOTA_IVA)?->valore_percentuale;

        return $v !== null ? (float) $v : 22.0;
    }

    /**
     * Netto IVA esclusa riga → totale ivato (imponibile con commissioni % + IVA).
     */
    public static function calcolaDaNetto(float $nettoIvaEsc, float $aliquotaIva, float $commissioniPct = 0): float
    {
        $netto = round(max(0, $nettoIvaEsc), 2);
        $imponibile = round($netto * (1 + $commissioniPct / 100), 2);
        $iva = round($imponibile * ($aliquotaIva / 100), 2);

        return round($imponibile + $iva, 2);
    }

    /**
     * Aggiorna cliente_ivato per tutte le tariffe dell’ordine non pagato.
     * Ordini pagati: importi effettivi in pag_effettivo_* (non si ricalcolano).
     */
    public static function aggiornaPerOrdine(ordine $ordine, ?int $metodoPagamentoId = null): void
    {
        $ordine->loadMissing(['spedizioni.tariffaSpedizione', 'metodoPagamentoOrdine']);

        if ($ordine->haStato(ordine::STATO_PAGATO)) {
            return;
        }

        $aliquota = self::aliquotaIva($ordine);
        $pct = self::commissioniPctPerOrdine($ordine, $metodoPagamentoId);

        foreach ($ordine->spedizioni as $spedizione) {
            $tariffa = $spedizione->tariffaSpedizione;
            if (! $tariffa) {
                continue;
            }
            $netto = (float) $tariffa->totale_spedizione;
            $tariffa->update([
                'cliente_ivato' => self::calcolaDaNetto($netto, $aliquota, $pct),
            ]);
        }
    }

    /** Ricalcolo massivo (migrazione / manutenzione). */
    public static function ricalcolaTuttiGliOrdini(): int
    {
        $ordineIds = tariffa_spedizione::query()
            ->join('spedizionis', 'spedizionis.id', '=', 'tariffe_spediziones.spedizione_id')
            ->whereNotNull('spedizionis.ordine_id')
            ->distinct()
            ->pluck('spedizionis.ordine_id');

        $n = 0;
        foreach ($ordineIds as $ordineId) {
            $ordine = ordine::query()->find($ordineId);
            if ($ordine) {
                self::aggiornaPerOrdine($ordine);
                $n++;
            }
        }

        return $n;
    }

    private static function commissioniPctPerOrdine(ordine $ordine, ?int $metodoPagamentoId): float
    {
        if ($metodoPagamentoId !== null && $metodoPagamentoId > 0) {
            $m = metodo_pagamento_ordine::query()->find($metodoPagamentoId);

            return $m ? (float) $m->commissioni : 0.0;
        }

        if ($ordine->metodoPagamentoOrdine) {
            return (float) $ordine->metodoPagamentoOrdine->commissioni;
        }

        return 0.0;
    }

}
