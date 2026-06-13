<?php

namespace App\Services;

use App\Models\metodo_pagamento_ordine;
use App\Models\ordine;
use App\Models\parametri_globali;

class OrdineTotaleIvatoService
{
    public function aliquotaIvaPerOrdine(ordine $ordine): float
    {
        $raw = $ordine->dettaglio_json['aliquota_iva'] ?? null;
        if ($raw !== null && is_numeric($raw)) {
            return (float) $raw;
        }

        $v = parametri_globali::query()
            ->where('denominazione', 'Aliquota IVA')
            ->attivoOggi()
            ->value('valore_percentuale');

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
        $metodo = metodo_pagamento_ordine::query()
            ->where('abilitato', true)
            ->findOrFail($metodoPagamentoOrdineId);

        $netto = $this->nettoIvaEsc($ordine);
        $aliquota = $this->aliquotaIvaPerOrdine($ordine);
        $pct = (float) $metodo->commissioni;

        $imponibile = round($netto * (1 + $pct / 100), 2);
        $iva = round($imponibile * ($aliquota / 100), 2);
        $totale = round($imponibile + $iva, 2);

        return [
            'imponibile' => $imponibile,
            'iva' => $iva,
            'totale' => $totale,
            'commissioni_pct' => $pct,
        ];
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

    /** Sconto/commissione % wallet sugli ordini (da metodo_pagamento_ordinis). */
    public function commissioniWalletOrdine(): float
    {
        $m = metodo_pagamento_ordine::query()
            ->where('abilitato', true)
            ->where('codice', 'wallet')
            ->first();

        return $m ? (float) $m->commissioni : -2.0;
    }
}
