<?php

namespace App\Services\Rimborso;

use App\Models\ordine;
use App\Models\rimborso;
use App\Models\spedizione;
use App\Models\stato_spedizione;
use App\Services\SpedizioneStatoService;
use App\Support\RimborsoRecordBuilder;
use App\Support\SpedizioneCampiPersistenza;

/**
 * Dopo rimborso Stripe: registra i rimborsi per spedizione e imposta stato Rimborsata.
 * L’ordine resta pagato.
 */
final class RimborsoStripeSpedizioniService
{
    /**
     * @return int Numero di spedizioni aggiornate
     */
    public function applicaRimborsoStripe(
        ordine $ordine,
        string $stripeRefundId,
        string $stripePaymentIntentId,
        float $importoRimborsatoEuro,
        ?string $motivo = null,
        bool $rimborsoIntero = true,
    ): int {
        $ordine->loadMissing(['spedizioni.tariffaSpedizione', 'spedizioni.rimborso']);
        $motivo ??= 'Rimborso Stripe';
        $aggiornate = 0;

        if ($rimborsoIntero) {
            foreach ($ordine->spedizioni as $spedizione) {
                if ($this->applicaSuSpedizione(
                    $spedizione,
                    $ordine,
                    $stripeRefundId,
                    $stripePaymentIntentId,
                    $motivo,
                )) {
                    $aggiornate++;
                }
            }

            return $aggiornate;
        }

        $spedizione = $ordine->spedizioni->first(fn (spedizione $s) => ! $s->rimborso);
        if ($spedizione === null) {
            return 0;
        }

        return $this->creaRimborsoEMarcaRimborsata(
            $spedizione,
            $ordine,
            $stripeRefundId,
            $stripePaymentIntentId,
            round($importoRimborsatoEuro, 2),
            $motivo,
        ) ? 1 : 0;
    }

    private function applicaSuSpedizione(
        spedizione $spedizione,
        ordine $ordine,
        string $stripeRefundId,
        string $stripePaymentIntentId,
        string $motivo,
    ): bool {
        if ($spedizione->rimborso) {
            if ((int) $spedizione->spedizione_stato_id !== stato_spedizione::RIMBORSATA) {
                SpedizioneStatoService::segnaRimborsata($spedizione);
            }

            return false;
        }

        $importo = SpedizioneCampiPersistenza::pagEffettivoSp($spedizione);
        if ($importo === null || $importo <= 0) {
            $importo = round((float) ($spedizione->prezzoClienteIvato() ?? 0), 2);
        }
        if ($importo <= 0) {
            return false;
        }

        return $this->creaRimborsoEMarcaRimborsata(
            $spedizione,
            $ordine,
            $stripeRefundId,
            $stripePaymentIntentId,
            $importo,
            $motivo,
        );
    }

    private function creaRimborsoEMarcaRimborsata(
        spedizione $spedizione,
        ordine $ordine,
        string $stripeRefundId,
        string $stripePaymentIntentId,
        float $importo,
        string $motivo,
    ): bool {
        $record = rimborso::query()->create(
            RimborsoRecordBuilder::daRimborsoStripe(
                $ordine,
                $stripeRefundId,
                $stripePaymentIntentId,
                $importo,
                $motivo,
                $spedizione,
            ),
        );
        $record->update(['token' => 'RIMB-'.$record->id]);

        SpedizioneStatoService::segnaRimborsata($spedizione->fresh());

        return true;
    }
}
