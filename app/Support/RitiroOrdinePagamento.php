<?php

namespace App\Support;

use App\Models\ordine;
use App\Services\Liccardi\LiccardiTmsAcquistoService;
use App\Services\Sendcloud\SendcloudAcquistoService;
use App\Services\SpedisciOnline\SpedisciOnlineAcquistoService;
use Illuminate\Support\Carbon;

/**
 * Normalizzazione date ritiro e orchestrazione pickup post-pagamento ordine.
 */
final class RitiroOrdinePagamento
{
    /**
     * Allinea data_ritiro sulla spedizione al pagamento: se la scelta non è più
     * nella finestra valida, usa il primo giorno lavorativo successivo al pagamento.
     */
    public static function normalizzaDateRitiroAlPagamento(ordine $ordine, ?Carbon $pagamento = null): void
    {
        $pagamento = ($pagamento ?? now())->copy()->startOfDay();
        $ordine->loadMissing(['spedizioni.corriereRecord']);

        foreach ($ordine->spedizioni as $spedizione) {
            if (! RitiroCheckoutDomicilio::corriereRichiedeDataRitiro($spedizione->corriereRecord)) {
                continue;
            }

            $selezionata = $spedizione->data_ritiro?->format('Y-m-d');
            $effettiva = RitiroDateSelezionabili::dataEffettivaAlPagamento($selezionata, $pagamento);

            $spedizione->forceFill([
                'data_ritiro' => Carbon::parse($effettiva)->startOfDay(),
            ])->saveQuietly();
        }
    }

    /**
     * Etichetta + pickup Spedisci.online / Sendcloud / Liccardi TMS.
     *
     * @return array<string, mixed>|null Traccia pickup per checkout (se presente)
     */
    public static function elaboraAcquistiEtPickupTrace(ordine $ordine): ?array
    {
        self::normalizzaDateRitiroAlPagamento($ordine);

        $spedisciRisultati = app(SpedisciOnlineAcquistoService::class)->elaboraOrdinePagato($ordine);
        app(LiccardiTmsAcquistoService::class)->elaboraOrdinePagato($ordine);
        $sendcloudRisultati = app(SendcloudAcquistoService::class)->elaboraOrdinePagato($ordine);

        return app(SpedisciOnlineAcquistoService::class)->primoPickupTrace($spedisciRisultati)
            ?? app(SendcloudAcquistoService::class)->primoPickupTrace($sendcloudRisultati);
    }

    public static function salvaPickupTraceInSessione(?array $pickupTrace): void
    {
        if ($pickupTrace === null) {
            return;
        }

        request()->session()->put('checkout_ritiro_api_risposta', $pickupTrace);
    }
}
