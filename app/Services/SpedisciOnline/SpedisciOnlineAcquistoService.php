<?php

namespace App\Services\SpedisciOnline;

use App\Models\ordine;
use App\Models\spedizione;
use App\Support\PiattaformaCorriere;
use Illuminate\Support\Facades\Log;

class SpedisciOnlineAcquistoService
{
    public function __construct(
        private readonly SpedisciOnlineLabelService $labelService,
    ) {}

    /**
     * Dopo pagamento ordine: crea etichette Spedisci per le spedizioni con piattaforma compatibile.
     *
     * @return array<int, array{spedizione_id: int, ok: bool, message: string}>
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
                Log::warning('Spedisci.online create fallito', [
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
     * Annullamento ordine / rimborso: elimina etichette Spedisci (tenant quick o liccardi).
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
