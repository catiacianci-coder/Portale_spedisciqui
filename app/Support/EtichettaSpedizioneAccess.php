<?php

namespace App\Support;

use App\Models\spedizione;
use App\Services\SpedisciOnline\SpedisciOnlineEtichettaPdfService;
use Illuminate\Support\Facades\Log;

/**
 * Serve il PDF etichetta già salvato (cartella esterna LdV) o tenta backfill da integrazione.
 */
final class EtichettaSpedizioneAccess
{
    public static function percorsoAssoluto(spedizione $spedizione): ?string
    {
        if (self::etichettaCancellata($spedizione)) {
            return null;
        }

        $full = LdvStorage::percorsoAssoluto($spedizione);
        if ($full !== null) {
            return $full;
        }

        return self::tentaBackfill($spedizione);
    }

    public static function etichettaCancellata(spedizione $spedizione): bool
    {
        return LiccardiTmsIntegrazione::eliminataSuTms($spedizione)
            || SpedisciOnlineIntegrazione::etichettaCancellata($spedizione)
            || SendcloudIntegrazione::etichettaCancellata($spedizione);
    }

    private static function tentaBackfill(spedizione $spedizione): ?string
    {
        $spedizione->loadMissing('corriereRecord');
        $corriere = $spedizione->corriereRecord;

        if ($corriere && (
            PiattaformaCorriere::corriereUsaAcquistoLiccardiTms($corriere)
            || PiattaformaCorriere::corriereUsaAcquistoSendcloud($corriere)
        )) {
            return null;
        }

        app(SpedisciOnlineEtichettaPdfService::class)->salvaDaIntegrazione($spedizione);
        $spedizione->refresh();

        $full = LdvStorage::percorsoAssoluto($spedizione);
        if ($full === null && trim((string) $spedizione->etiqueta_pdf_path) !== '') {
            Log::warning('Etichetta PDF: path in DB ma file assente su disco', [
                'spedizione_id' => $spedizione->id,
                'etiqueta_pdf_path' => $spedizione->etiqueta_pdf_path,
                'ldv_root' => LdvStorage::rootPath(),
            ]);
        }

        return $full;
    }
}
