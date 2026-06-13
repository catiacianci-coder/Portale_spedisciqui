<?php

namespace App\Services\Liccardi;

use App\Models\spedizione;
use App\Support\LdvStorage;
use App\Support\LiccardiTmsIntegrazione;
use Illuminate\Support\Facades\Log;

/**
 * Salva e serve il PDF etichetta Liccardi TMS (GET /spedizioni/{id}/etichette/pdf).
 */
class LiccardiTmsEtichettaPdfService
{
    public function salvaBinary(spedizione $spedizione, string $binary): ?string
    {
        if ($binary === '' || ! str_starts_with($binary, '%PDF')) {
            Log::warning('Liccardi TMS: contenuto etichetta non sembra un PDF', [
                'spedizione_id' => $spedizione->id,
            ]);

            return null;
        }

        $this->rimuovi($spedizione);

        $relative = LdvStorage::salvaPdf($spedizione, $binary);
        if ($relative === null) {
            return null;
        }

        $spedizione->forceFill(['etiqueta_pdf_path' => $relative])->saveQuietly();

        return $relative;
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    public function salvaDaJson(spedizione $spedizione, array $decoded): ?string
    {
        $binary = LiccardiTmsResponseFormatter::estraiPdfDaJson($decoded);
        if (! is_string($binary) || $binary === '') {
            return null;
        }

        return $this->salvaBinary($spedizione, $binary);
    }

    public function rimuovi(spedizione $spedizione): void
    {
        LdvStorage::rimuoviFile($spedizione);

        $spedizione->forceFill(['etiqueta_pdf_path' => null])->saveQuietly();
    }

    public function percorsoAssoluto(spedizione $spedizione): ?string
    {
        if (LiccardiTmsIntegrazione::eliminataSuTms($spedizione)) {
            return null;
        }

        if (! $this->spedizioneUsaLiccardiTms($spedizione)) {
            return null;
        }

        return LdvStorage::percorsoAssoluto($spedizione);
    }

    private function spedizioneUsaLiccardiTms(spedizione $spedizione): bool
    {
        $spedizione->loadMissing('corriereRecord');
        $corriere = $spedizione->corriereRecord;

        return ($corriere && \App\Support\PiattaformaCorriere::corriereUsaAcquistoLiccardiTms($corriere))
            || LiccardiTmsIntegrazione::spedizioneId($spedizione) !== null;
    }

}
