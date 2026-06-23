<?php

namespace App\Support;

use App\Models\metodo_pagamento_rimborso;
use App\Models\ordine;
use App\Models\parametri_globali;
use App\Models\rimborso;
use App\Models\spedizione;
use DomainException;

final class RimborsoRecordBuilder
{
    /**
     * Snapshot richiesta cliente: tutti i riferimenti dalla spedizione (non dall’ordine).
     *
     * @return array<string, mixed>
     */
    public static function attributiRichiestaDaSpedizione(spedizione $spedizione, int $motivo): array
    {
        $spedizione->loadMissing(['tariffaSpedizione', 'ordine']);

        $giorni = parametri_globali::giorniRimborsoPerMotivo($motivo);
        $dataRichiesta = now();
        $dataPrevista = RimborsoCalendario::dataPrevistaDiasUteis($dataRichiesta, $giorni);

        if ($spedizione->ordine?->stato !== ordine::STATO_PAGATO) {
            throw new DomainException('Solo le spedizioni di ordini pagati possono essere rimborsate.');
        }

        $ivato = SpedizioneCampiPersistenza::pagEffettivoSp($spedizione);
        if ($ivato === null || $ivato <= 0) {
            throw new DomainException('Importo pagato non disponibile (pag_effettivo_sp mancante sulla tariffa spedizione).');
        }

        $stripePi = self::paymentIntentDaSpedizione($spedizione);

        return [
            'spedizione_id' => $spedizione->id,
            'codice_interno' => $spedizione->codice_interno,
            'ordine_id' => $spedizione->ordine_id,
            'motivo' => $motivo,
            'payment_id' => $stripePi,
            'stripe_payment_intent_id' => $stripePi,
            'token' => null,
            'id_metodo_pagamento_rimborsi' => null,
            'data_richiesta' => $dataRichiesta,
            'valore' => round($ivato, 2),
            'giorni' => $giorni,
            'data_prevista' => $dataPrevista,
            'data_reale' => null,
            'stripe_refund_id' => null,
            'credito_avviso_letto_in' => null,
        ];
    }

    /**
     * Attributi per tabella rimborsi (schema storage/app/rimborsi.csv) — rimborso Stripe ordine intero.
     *
     * @return array<string, mixed>
     */
    public static function daRimborsoStripe(
        ordine $ordine,
        string $stripeRefundId,
        string $stripePaymentIntentId,
        float $valore,
        ?string $motivo = null,
        ?spedizione $spedizione = null,
    ): array {
        $spedizione ??= $ordine->spedizioni()->orderBy('id')->first();
        $metodoId = metodo_pagamento_rimborso::query()
            ->where('codice', 'carta')
            ->value('id');

        $now = now();

        return [
            'spedizione_id' => $spedizione?->id,
            'codice_interno' => $spedizione?->codice_interno,
            'ordine_id' => $ordine->id,
            'motivo' => $motivo ?? 'Rimborso Stripe',
            'payment_id' => $ordine->payment_id ?: $stripePaymentIntentId,
            'stripe_refund_id' => $stripeRefundId,
            'token' => null,
            'id_metodo_pagamento_rimborsi' => $metodoId,
            'data_richiesta' => $now,
            'valore' => round($valore, 2),
            'giorni' => null,
            'data_prevista' => null,
            'data_reale' => $now,
            'stripe_payment_intent_id' => $stripePaymentIntentId,
            'credito_avviso_letto_in' => null,
        ];
    }

    public static function paymentIntentDaSpedizione(spedizione $spedizione): ?string
    {
        $pi = trim((string) ($spedizione->stripe_payment_intent_id ?? ''));
        if ($pi !== '' && str_starts_with($pi, 'pi_')) {
            return $pi;
        }

        return null;
    }

    public static function eseguiPagamentoImmediato(rimborso $rimborso): bool
    {
        return (int) ($rimborso->giorni ?? -1) === 0;
    }
}
