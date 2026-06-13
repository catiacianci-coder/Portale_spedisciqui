<?php

namespace App\Services\Etichetta;

use App\Models\spedizione;
use App\Services\Liccardi\LiccardiTmsLabelService;
use App\Services\Sendcloud\SendcloudLabelService;
use App\Services\SpedisciOnline\SpedisciOnlineLabelService;
use App\Support\PiattaformaCorriere;

final class EtichettaAcquistoSingoloService
{
    public function __construct(
        private readonly SpedisciOnlineLabelService $spedisciOnline,
        private readonly LiccardiTmsLabelService $liccardiTms,
        private readonly SendcloudLabelService $sendcloud,
    ) {}

    /**
     * @return array{ok: bool, message: string}
     */
    public function genera(spedizione $spedizione): array
    {
        $spedizione->loadMissing('corriereRecord');
        $corriere = $spedizione->corriereRecord;

        if (! $corriere) {
            return ['ok' => false, 'message' => 'Corriere non associato alla spedizione.'];
        }

        if (PiattaformaCorriere::corriereUsaAcquistoSendcloud($corriere)) {
            $outcome = $this->sendcloud->createFromSpedizione($spedizione);

            return ['ok' => $outcome->ok, 'message' => $outcome->message];
        }

        if (PiattaformaCorriere::corriereUsaAcquistoLiccardiTms($corriere)) {
            $outcome = $this->liccardiTms->createFromSpedizione($spedizione);

            return ['ok' => $outcome->ok, 'message' => $outcome->message];
        }

        if (PiattaformaCorriere::corriereUsaAcquistoSpedisciOnline($corriere)) {
            $outcome = $this->spedisciOnline->createFromSpedizione($spedizione);

            return ['ok' => $outcome->ok, 'message' => $outcome->message];
        }

        return ['ok' => false, 'message' => 'Piattaforma corriere non supportata per la generazione etichetta.'];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function eliminaSePresente(spedizione $spedizione): array
    {
        if (! $spedizione->esiste_integrazione && trim((string) $spedizione->etiqueta_pdf_path) === '') {
            return ['ok' => true, 'message' => 'Nessuna etichetta da eliminare.'];
        }

        $spedizione->loadMissing('corriereRecord');
        $corriere = $spedizione->corriereRecord;

        if (! $corriere) {
            return ['ok' => true, 'message' => 'Nessuna integrazione corriere.'];
        }

        if (PiattaformaCorriere::corriereUsaAcquistoSendcloud($corriere)) {
            $outcome = $this->sendcloud->deleteFromSpedizione($spedizione);

            return ['ok' => $outcome->ok, 'message' => $outcome->message];
        }

        if (PiattaformaCorriere::corriereUsaAcquistoLiccardiTms($corriere)) {
            $outcome = $this->liccardiTms->deleteFromSpedizione($spedizione);

            return ['ok' => $outcome->ok, 'message' => $outcome->message];
        }

        if (PiattaformaCorriere::corriereUsaAcquistoSpedisciOnline($corriere)) {
            $outcome = $this->spedisciOnline->deleteFromSpedizione($spedizione);

            return ['ok' => $outcome->ok, 'message' => $outcome->message];
        }

        return ['ok' => true, 'message' => 'Nessuna integrazione supportata da cancellare.'];
    }
}
