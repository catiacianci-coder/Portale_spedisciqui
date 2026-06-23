<?php

namespace App\Services\Sendcloud;

use App\Models\ordine;
use App\Models\spedizione;
use App\Models\stato_spedizione;
use App\Support\PiattaformaCorriere;
use App\Support\RitiroCheckoutDomicilio;
use App\Support\SendcloudIntegrazione;
use Illuminate\Support\Facades\Log;

class SendcloudAcquistoService
{
    public function __construct(
        private readonly SendcloudLabelService $labelService,
        private readonly SendcloudPickupService $pickupService,
    ) {}

    /**
     * Dopo pagamento ordine: crea spedizioni Sendcloud per le righe con piattaforma sendcloud.
     *
     * @return array<int, array{spedizione_id: int, ok: bool, message: string, pickup?: array<string, mixed>|null}>
     */
    public function elaboraOrdinePagato(ordine $ordine): array
    {
        $ordine->loadMissing(['spedizioni.corriereRecord', 'spedizioni.serviziAggiuntiviRighe']);
        $risultati = [];

        foreach ($ordine->spedizioni as $spedizione) {
            $corriere = $spedizione->corriereRecord;
            if (! $corriere || ! PiattaformaCorriere::corriereUsaAcquistoSendcloud($corriere)) {
                continue;
            }

            if (! SendcloudClient::isConfigured()) {
                Log::warning('Sendcloud create saltato: API non configurata', [
                    'ordine_id' => $ordine->id,
                    'spedizione_id' => $spedizione->id,
                ]);

                continue;
            }

            if (
                SendcloudIntegrazione::shipmentId($spedizione) !== null
                || (int) $spedizione->spedizione_stato_id === stato_spedizione::GENERATA
            ) {
                $risultati[] = [
                    'spedizione_id' => $spedizione->id,
                    'ok' => true,
                    'message' => 'Etichetta Sendcloud già generata.',
                    'pickup' => null,
                ];

                continue;
            }

            $outcome = $this->labelService->createFromSpedizione($spedizione);
            $pickupTrace = null;

            if ($outcome->ok) {
                $pickupTrace = $this->prenotaRitiroSeRichiesto($spedizione->fresh(), $ordine);
            }

            $risultati[] = [
                'spedizione_id' => $spedizione->id,
                'ok' => $outcome->ok,
                'message' => $outcome->message,
                'pickup' => $pickupTrace,
            ];

            if (! $outcome->ok) {
                Log::warning('Sendcloud create fallito', [
                    'ordine_id' => $ordine->id,
                    'spedizione_id' => $spedizione->id,
                    'http_status' => $outcome->httpStatus,
                    'message' => $outcome->message,
                ]);
            } elseif ($pickupTrace !== null && ! ($pickupTrace['ok'] ?? false)) {
                Log::warning('Sendcloud pickup fallito', [
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
    private function prenotaRitiroSeRichiesto(?spedizione $spedizione, ordine $ordine): ?array
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
     * Annullamento ordine / rimborso: cancella spedizioni su Sendcloud.
     *
     * @return array<int, array{spedizione_id: int, ok: bool, message: string}>
     */
    public function eliminaEtichettePerOrdine(ordine $ordine): array
    {
        $ordine->loadMissing(['spedizioni.corriereRecord']);
        $risultati = [];

        foreach ($ordine->spedizioni as $spedizione) {
            $corriere = $spedizione->corriereRecord;
            if (! $corriere || ! PiattaformaCorriere::corriereUsaAcquistoSendcloud($corriere)) {
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
                Log::warning('Sendcloud delete fallito', [
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
