<?php

namespace App\Services\Liccardi;

use App\Models\ordine;
use App\Support\PiattaformaCorriere;
use Illuminate\Support\Facades\Log;

class LiccardiTmsAcquistoService
{
    public function __construct(
        private readonly LiccardiTmsLabelService $labelService,
    ) {}

    /**
     * Dopo pagamento ordine: crea spedizioni Liccardi TMS per le righe con piattaforma liccardi_tms.
     *
     * @return array<int, array{spedizione_id: int, ok: bool, message: string}>
     */
    public function elaboraOrdinePagato(ordine $ordine): array
    {
        $ordine->loadMissing(['spedizioni.corriereRecord', 'spedizioni.serviziAggiuntiviRighe']);
        $risultati = [];

        foreach ($ordine->spedizioni as $spedizione) {
            $corriere = $spedizione->corriereRecord;
            if (! $corriere || ! PiattaformaCorriere::corriereUsaAcquistoLiccardiTms($corriere)) {
                continue;
            }

            if (! LiccardiTmsClient::isConfigured()) {
                Log::warning('Liccardi TMS create saltato: API non configurata', [
                    'ordine_id' => $ordine->id,
                    'spedizione_id' => $spedizione->id,
                ]);

                continue;
            }

            if ($spedizione->esiste_integrazione) {
                $risultati[] = [
                    'spedizione_id' => $spedizione->id,
                    'ok' => true,
                    'message' => 'Integrazione già presente.',
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
                Log::warning('Liccardi TMS create fallito', [
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
     * Annullamento ordine / rimborso: elimina spedizioni su TMS Liccardi.
     *
     * @return array<int, array{spedizione_id: int, ok: bool, message: string}>
     */
    public function eliminaEtichettePerOrdine(ordine $ordine): array
    {
        $ordine->loadMissing(['spedizioni.corriereRecord']);
        $risultati = [];

        foreach ($ordine->spedizioni as $spedizione) {
            $corriere = $spedizione->corriereRecord;
            if (! $corriere || ! PiattaformaCorriere::corriereUsaAcquistoLiccardiTms($corriere)) {
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
                Log::warning('Liccardi TMS delete fallito', [
                    'ordine_id' => $ordine->id,
                    'spedizione_id' => $spedizione->id,
                    'message' => $outcome->message,
                ]);
            }
        }

        return $risultati;
    }
}
