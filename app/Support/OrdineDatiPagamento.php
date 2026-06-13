<?php

namespace App\Support;

use App\Models\metodo_pagamento_ordine;
use App\Models\ordine;
use App\Models\stato_ordine;

/**
 * Attributi ordine coerenti con tabella ordinis (schema CSV) al momento del pagamento.
 */
final class OrdineDatiPagamento
{
    /**
     * @return array<string, mixed>
     */
    public static function attributiPagamentoCompletato(
        ordine $ordine,
        int $metodoPagamentoOrdineId,
        ?metodo_pagamento_ordine $metodo = null,
        ?string $paymentId = null,
    ): array {
        $metodo ??= metodo_pagamento_ordine::query()->find($metodoPagamentoOrdineId);

        $attrs = [
            'stato_ordine_id' => stato_ordine::idPerCodice(ordine::STATO_PAGATO),
            'metodo_pagamento_ordinis_id' => $metodoPagamentoOrdineId,
            'metodo_pagamento' => $metodo?->metodo_pagamento,
            'commissioni' => $metodo ? round((float) $metodo->commissioni, 4) : 0.0,
            'pag_effettivo_or' => OrdinePagamentoEffettivo::importoOrdine($ordine, $metodoPagamentoOrdineId),
            'data_pagamento' => now(),
        ];

        if ($paymentId !== null && $paymentId !== '') {
            $attrs['payment_id'] = $paymentId;
        }

        return $attrs;
    }

    /**
     * @return array<string, mixed>
     */
    public static function attributiAnnullamento(): array
    {
        return [
            'stato_ordine_id' => stato_ordine::idPerCodice(ordine::STATO_ANNULLATO),
            'annullato_in' => now(),
        ];
    }
}
