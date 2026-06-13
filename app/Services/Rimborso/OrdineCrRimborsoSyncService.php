<?php

namespace App\Services\Rimborso;

use App\Models\ordine;
use App\Models\spedizione;
use App\Support\OrdineDatiPagamento;

/**
 * Quando tutte le spedizioni dell’ordine hanno un rimborso richiesto: cr = 1 e ordine annullato.
 */
final class OrdineCrRimborsoSyncService
{
    public function syncPerOrdineId(?int $ordineId): void
    {
        if ($ordineId === null || $ordineId <= 0) {
            return;
        }

        $total = spedizione::query()->where('ordine_id', $ordineId)->count();
        if ($total === 0) {
            ordine::query()->whereKey($ordineId)->update(['cr' => null]);

            return;
        }

        $conRimborso = spedizione::query()
            ->where('ordine_id', $ordineId)
            ->whereHas('rimborso')
            ->count();

        if ($conRimborso !== $total) {
            return;
        }

        $ordine = ordine::query()->find($ordineId);
        if (! $ordine) {
            return;
        }

        $attrs = ['cr' => '1'];
        if (! $ordine->haStato(ordine::STATO_ANNULLATO)) {
            $attrs = array_merge($attrs, OrdineDatiPagamento::attributiAnnullamento());
        }

        $ordine->update($attrs);
    }
}
