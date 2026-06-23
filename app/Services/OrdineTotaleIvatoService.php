<?php

namespace App\Services;

use App\Models\metodo_pagamento_ordine;
use App\Models\ordine;
use App\Models\parametri_globali;
use App\Support\OrdineTotaliPagamento;

class OrdineTotaleIvatoService
{
    public function aliquotaIvaPerOrdine(ordine $ordine): float
    {
        $raw = $ordine->dettaglio_json['aliquota_iva'] ?? null;
        if ($raw !== null && is_numeric($raw)) {
            return (float) $raw;
        }

        $v = parametri_globali::recordAttivo('Aliquota IVA')?->valore_percentuale;

        return $v !== null ? (float) $v : 22.0;
    }

    /** Netto IVA esclusa (trasporto + servizi) dalle righe salvate sull’ordine. */
    public function nettoIvaEsc(ordine $ordine): float
    {
        $righe = $ordine->dettaglio_json['righe'] ?? [];
        if (! is_array($righe)) {
            return round((float) $ordine->costo_servizo, 2);
        }

        $totaleTrasporto = 0.0;
        $totaleServizi = 0.0;
        foreach ($righe as $r) {
            if (! is_array($r)) {
                continue;
            }
            $totaleTrasporto += (float) ($r['trasporto_iva_esc'] ?? 0);
            $totaleServizi += (float) ($r['extra_servizi_iva_esc'] ?? 0);
        }

        return round($totaleTrasporto + $totaleServizi, 2);
    }

    /**
     * @return array{imponibile: float, iva: float, totale: float, commissioni_pct: float}
     */
    public function totaliPerMetodo(ordine $ordine, int $metodoPagamentoOrdineId): array
    {
        return OrdineTotaliPagamento::totaliPerMetodo($ordine, $metodoPagamentoOrdineId);
    }

    public function metodoIsWallet(int $metodoPagamentoOrdineId): bool
    {
        $m = metodo_pagamento_ordine::query()->find($metodoPagamentoOrdineId);

        return $m !== null && $m->isWallet();
    }

    public function metodoIsCarta(int $metodoPagamentoOrdineId): bool
    {
        $m = metodo_pagamento_ordine::query()->find($metodoPagamentoOrdineId);

        return $m !== null && $m->isCarta();
    }

    public function metodoIsBonifico(int $metodoPagamentoOrdineId): bool
    {
        $m = metodo_pagamento_ordine::query()->find($metodoPagamentoOrdineId);

        return $m !== null && $m->isBonifico();
    }
}
