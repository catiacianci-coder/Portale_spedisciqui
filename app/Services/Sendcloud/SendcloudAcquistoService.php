<?php

namespace App\Services\Sendcloud;

use App\Models\ordine;
use App\Models\stato_spedizione;
use App\Support\PiattaformaCorriere;
use App\Support\SendcloudIntegrazione;
use Illuminate\Support\Facades\Log;

class SendcloudAcquistoService
{
    public function __construct(
        private readonly SendcloudLabelService $labelService,
    ) {}

    /**
     * Dopo pagamento ordine: crea spedizioni Sendcloud per le righe con piattaforma sendcloud.
     *
     * @return array<int, array{spedizione_id: int, ok: bool, message: string}>
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
                ];

                continue;
            }

            $outcome = $this->labelService->createFromSpedizione($spedizione);
            $risultati[] = [
                'spedizione_id' => $spedizione->id,
                'ok' => $outcome->ok,
                'message' => $outcome->message,
            ];

            if (! $outcome->ok) {
                Log::warning('Sendcloud create fallito', [
                    'ordine_id' => $ordine->id,
                    'spedizione_id' => $spedizione->id,
                    'http_status' => $outcome->httpStatus,
                    'message' => $outcome->message,
                ]);
            }
        }

        return $risultati;
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
