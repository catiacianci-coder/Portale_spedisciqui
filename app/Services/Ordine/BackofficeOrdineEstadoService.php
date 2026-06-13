<?php

namespace App\Services\Ordine;

use App\Models\metodo_pagamento_ordine;
use App\Models\ordine;
use App\Models\spedizione;
use App\Models\stato_spedizione;
use App\Services\Liccardi\LiccardiTmsAcquistoService;
use App\Services\Sendcloud\SendcloudAcquistoService;
use App\Services\SpedisciOnline\SpedisciOnlineAcquistoService;
use App\Support\OrdineDatiPagamento;
use App\Support\OrdinePagamentoEffettivo;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Azioni amministrative sugli ordini: conferma pagamento manuale (senza addebito wallet) e annullamento.
 */
final class BackofficeOrdineEstadoService
{
    public function marcarPagoManual(
        ordine $ordine,
        int $metodoPagamentoOrdineId,
        ?string $token2,
        string $dataPagamento,
    ): void {
        DB::transaction(function () use ($ordine, $metodoPagamentoOrdineId, $token2, $dataPagamento): void {
            $locked = ordine::query()
                ->whereKey($ordine->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->haStato(ordine::STATO_NON_PAGATO)) {
                throw new DomainException('Solo gli ordini non pagati possono essere segnati come pagati.');
            }

            $metodo = metodo_pagamento_ordine::query()
                ->whereKey($metodoPagamentoOrdineId)
                ->where('abilitato', true)
                ->firstOrFail();

            $attrs = [
                'stato_ordine_id' => \App\Models\stato_ordine::idPerCodice(ordine::STATO_PAGATO),
                'data_pagamento' => $dataPagamento,
                'metodo_pagamento_ordinis_id' => $metodoPagamentoOrdineId,
                'metodo_pagamento' => trim((string) $metodo->metodo_pagamento),
                'commissioni' => round((float) $metodo->commissioni, 4),
                'pag_effettivo_or' => OrdinePagamentoEffettivo::importoOrdine($locked, $metodoPagamentoOrdineId),
                'varie4' => ordine::VARIE4_OPERAZIONE_BACKOFFICE,
            ];

            $token2Trim = trim((string) ($token2 ?? ''));
            if ($token2Trim !== '') {
                $attrs['token_2'] = $token2Trim;
            }

            $locked->forceFill($attrs)->save();
            OrdinePagamentoEffettivo::registraSuTariffe($locked->fresh(), $metodoPagamentoOrdineId);

            spedizione::query()
                ->where('ordine_id', $locked->id)
                ->where('spedizione_stato_id', stato_spedizione::NON_PAGATA)
                ->update(['spedizione_stato_id' => stato_spedizione::PAGATA]);
        });
    }

    public function anularOrdineNonPagato(ordine $ordine): void
    {
        DB::transaction(function () use ($ordine): void {
            $locked = ordine::query()
                ->whereKey($ordine->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->haStato(ordine::STATO_NON_PAGATO)) {
                throw new DomainException('Solo gli ordini non pagati possono essere annullati.');
            }

            $locked->forceFill(OrdineDatiPagamento::attributiAnnullamento())->save();

            spedizione::query()
                ->where('ordine_id', $locked->id)
                ->update([
                    'spedizione_stato_id' => stato_spedizione::ANNULLATA,
                    'cancellata_il' => now(),
                ]);
        });
    }

    /**
     * @return list<string>
     */
    public function processarEtichettePosPagamento(ordine $ordine): array
    {
        $ordine = $ordine->fresh(['spedizioni']);
        $avvisi = [];

        foreach ([
            app(SpedisciOnlineAcquistoService::class),
            app(LiccardiTmsAcquistoService::class),
            app(SendcloudAcquistoService::class),
        ] as $svc) {
            $risultati = $svc->elaboraOrdinePagato($ordine);
            foreach ($risultati as $r) {
                if (($r['ok'] ?? false) === false && ($r['message'] ?? '') !== '') {
                    $avvisi[] = 'Spedizione #'.($r['spedizione_id'] ?? '?').': '.$r['message'];
                }
            }
        }

        return $avvisi;
    }
}
