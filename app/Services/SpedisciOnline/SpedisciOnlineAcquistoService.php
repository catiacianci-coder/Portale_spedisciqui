<?php

namespace App\Services\SpedisciOnline;

use App\Models\ordine;
use App\Models\spedizione;
use App\Support\PiattaformaCorriere;
use App\Support\RitiroCheckoutDomicilio;
use Illuminate\Support\Facades\Log;

class SpedisciOnlineAcquistoService
{
    public function __construct(
        private readonly SpedisciOnlineLabelService $labelService,
        private readonly SpedisciOnlinePickupService $pickupService,
    ) {}

    /**
     * Dopo pagamento ordine: crea etichette Spedisci per le spedizioni con piattaforma compatibile.
     *
     * @return array<int, array{spedizione_id: int, ok: bool, message: string, pickup?: array<string, mixed>|null}>
     */
    public function elaboraOrdinePagato(ordine $ordine): array
    {
        $ordine->loadMissing(['spedizioni.corriereRecord']);
        $risultati = [];

        foreach ($ordine->spedizioni as $spedizione) {
            $corriere = $spedizione->corriereRecord;
            if (! $corriere || ! PiattaformaCorriere::corriereUsaAcquistoSpedisciOnline($corriere)) {
                continue;
            }

            $piattaforma = $corriere->piattaforma;
            if (! SpedisciOnlineClient::forPiattaforma($piattaforma)->isConfigured()) {
                Log::warning('Spedisci.online create saltato: API key mancante', [
                    'ordine_id' => $ordine->id,
                    'spedizione_id' => $spedizione->id,
                    'tenant' => PiattaformaCorriere::tenantSpedisciOnline($piattaforma),
                ]);

                continue;
            }

            if ($spedizione->esiste_integrazione) {
                $risultati[] = [
                    'spedizione_id' => $spedizione->id,
                    'ok' => true,
                    'message' => 'Integrazione già presente.',
                    'pickup' => null,
                ];

                continue;
            }

            $outcome = $this->labelService->createFromSpedizione($spedizione);
            $pickupTrace = null;

            if ($outcome->ok) {
                $pickupTrace = $this->prenotaRitiroSdaSeRichiesto($spedizione->fresh(), $ordine);
            }

            $risultati[] = [
                'spedizione_id' => $spedizione->id,
                'ok' => $outcome->ok,
                'message' => $outcome->message,
                'pickup' => $pickupTrace,
            ];

            if (! $outcome->ok) {
                Log::warning('Spedisci.online create fallito', [
                    'ordine_id' => $ordine->id,
                    'spedizione_id' => $spedizione->id,
                    'http_status' => $outcome->httpStatus,
                    'message' => $outcome->message,
                ]);
            } elseif ($pickupTrace !== null && ! ($pickupTrace['ok'] ?? false)) {
                Log::warning('Spedisci.online pickup fallito', [
                    'ordine_id' => $ordine->id,
                    'spedizione_id' => $spedizione->id,
                    'http_status' => $pickupTrace['http_status'] ?? null,
                    'message' => $pickupTrace['message'] ?? '',
                ]);
            }
        }

        return $risultati;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function primoPickupTrace(array $risultati): ?array
    {
        foreach ($risultati as $row) {
            if (! is_array($row['pickup'] ?? null)) {
                continue;
            }

            return $row['pickup'];
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function prenotaRitiroSdaSeRichiesto(?spedizione $spedizione, ordine $ordine): ?array
    {
        if ($spedizione === null || ! RitiroCheckoutDomicilio::spedizioneRichiedePickup($spedizione)) {
            return null;
        }

        $outcome = $this->pickupService->createFromSpedizione($spedizione);

        return array_merge($outcome->toCheckoutTrace(), [
            'spedizione_id' => $spedizione->id,
            'ordine_id' => $ordine->id,
        ]);
    }

    /**
     * Annullamento ordine / rimborso: elimina etichette Spedisci (tenant eamulti o liccardi).
     *
     * @return array<int, array{spedizione_id: int, ok: bool, message: string}>
     */
    public function eliminaEtichettePerOrdine(ordine $ordine): array
    {
        $ordine->loadMissing(['spedizioni.corriereRecord']);
        $risultati = [];

        foreach ($ordine->spedizioni as $spedizione) {
            $corriere = $spedizione->corriereRecord;
            if (! $corriere || ! PiattaformaCorriere::corriereUsaAcquistoSpedisciOnline($corriere)) {
                continue;
            }

            if (! $spedizione->esiste_integrazione) {
                continue;
            }

            $outcome = $this->labelService->deleteFromSpedizione($spedizione);
            $risultati[] = [
                'spedizione_id' => $spedizione->id,
                'ok' => $outcome->ok,
                'message' => $outcome->message,
            ];

            if (! $outcome->ok) {
                Log::warning('Spedisci.online delete fallito', [
                    'ordine_id' => $ordine->id,
                    'spedizione_id' => $spedizione->id,
                    'http_status' => $outcome->httpStatus,
                    'message' => $outcome->message,
                ]);
            }
        }

        return $risultati;
    }
}
