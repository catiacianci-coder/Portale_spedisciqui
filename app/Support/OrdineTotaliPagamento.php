<?php

namespace App\Support;

use App\Models\metodo_pagamento_ordine;
use App\Models\ordine;
use App\Services\OrdineTotaleIvatoService;

/**
 * Totali ivato ordine (standard Carte/Bonifico e Wallet) da righe carrello/ordine.
 */
final class OrdineTotaliPagamento
{
    /**
     * @param  array<int, array<string, mixed>>  $righe
     * @return array{
     *     costo_servizo: float,
     *     total_pagamento: float,
     *     total_pagamento_wallet: float
     * }
     */
    public static function daRighe(array $righe, float $aliquotaIva): array
    {
        $netto = 0.0;
        $nettoWallet = 0.0;

        foreach ($righe as $riga) {
            if (! is_array($riga)) {
                continue;
            }
            $netto += (float) ($riga['netto_iva_esc'] ?? 0);
            $nettoWallet += (float) ($riga['netto_wallet_iva_esc'] ?? $riga['netto_iva_esc'] ?? 0);
        }

        $netto = round($netto, 2);
        $nettoWallet = round($nettoWallet, 2);

        return [
            'costo_servizo' => $netto,
            'total_pagamento' => TariffaSpedizioneClienteIvato::calcolaDaNetto($netto, $aliquotaIva, 0),
            'total_pagamento_wallet' => TariffaSpedizioneClienteIvato::calcolaDaNetto($nettoWallet, $aliquotaIva, 0),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $righe
     */
    public static function applicaSuOrdine(ordine $ordine, array $righe, ?float $aliquotaIva = null): void
    {
        if ($ordine->haStato(ordine::STATO_PAGATO)) {
            return;
        }

        $aliquota = $aliquotaIva ?? TariffaSpedizioneClienteIvato::aliquotaIva($ordine);
        $totali = self::daRighe($righe, $aliquota);

        $ordine->costo_servizo = $totali['costo_servizo'];
        $ordine->total_pagamento = $totali['total_pagamento'];
        $ordine->total_pagamento_wallet = $totali['total_pagamento_wallet'];
    }

    /**
     * @return array{imponibile: float, iva: float, totale: float}
     */
    public static function breakdownSalvato(ordine $ordine, bool $wallet): array
    {
        $righe = $ordine->dettaglio_json['righe'] ?? [];
        if (! is_array($righe)) {
            $righe = [];
        }

        $aliquota = TariffaSpedizioneClienteIvato::aliquotaIva($ordine);
        $netto = 0.0;
        foreach ($righe as $riga) {
            if (! is_array($riga)) {
                continue;
            }
            if ($wallet) {
                $netto += (float) ($riga['netto_wallet_iva_esc'] ?? $riga['netto_iva_esc'] ?? 0);
            } else {
                $netto += (float) ($riga['netto_iva_esc'] ?? 0);
            }
        }
        $netto = round($netto, 2);

        $totale = $wallet
            ? round((float) ($ordine->total_pagamento_wallet ?? 0), 2)
            : round((float) ($ordine->total_pagamento ?? 0), 2);

        if ($totale <= 0 && $netto > 0) {
            $totale = TariffaSpedizioneClienteIvato::calcolaDaNetto($netto, $aliquota, 0);
        }

        $imponibile = $netto;
        $iva = round(max(0, $totale - $imponibile), 2);

        return [
            'imponibile' => $imponibile,
            'iva' => $iva,
            'totale' => $totale,
        ];
    }

    /**
     * Totali per metodo pagamento: usa valori salvati su ordine quando disponibili.
     *
     * @return array{imponibile: float, iva: float, totale: float, commissioni_pct: float}
     */
    public static function totaliPerMetodo(ordine $ordine, int $metodoPagamentoOrdineId): array
    {
        $metodo = metodo_pagamento_ordine::query()
            ->where('abilitato', true)
            ->findOrFail($metodoPagamentoOrdineId);

        $totaleSvc = app(OrdineTotaleIvatoService::class);

        if ($totaleSvc->metodoIsWallet($metodoPagamentoOrdineId)
            && (float) ($ordine->total_pagamento_wallet ?? 0) > 0) {
            $b = self::breakdownSalvato($ordine, true);

            return [
                'imponibile' => $b['imponibile'],
                'iva' => $b['iva'],
                'totale' => $b['totale'],
                'commissioni_pct' => (float) $metodo->commissioni,
            ];
        }

        if ((float) ($ordine->total_pagamento ?? 0) > 0 && (float) $metodo->commissioni === 0.0) {
            $b = self::breakdownSalvato($ordine, false);

            return [
                'imponibile' => $b['imponibile'],
                'iva' => $b['iva'],
                'totale' => $b['totale'],
                'commissioni_pct' => (float) $metodo->commissioni,
            ];
        }

        return $totaleSvc->totaliPerMetodo($ordine, $metodoPagamentoOrdineId);
    }
}
