<?php

namespace App\Services\Rimborso;

use App\Models\rimborso;
use App\Models\spedizione;
use App\Services\Etichetta\EtichettaAcquistoSingoloService;
use App\Services\SpedizioneStatoService;
use App\Services\Tracking\SpedizioneTrackingService;
use App\Support\EtichettaSpedizioneAccess;
use App\Support\RimborsoFlussoEtichetta;
use DomainException;

/**
 * Alla richiesta cliente: cancellazione etichetta sul corriere (solo se tracking API attivo).
 */
final class RimborsoAnnullamentoEtichettaService
{
    public function __construct(
        private readonly EtichettaAcquistoSingoloService $etichetta,
        private readonly SpedizioneTrackingService $tracking,
    ) {}

    /**
     * @throws DomainException
     */
    public function annullaAllaRichiesta(spedizione $spedizione, int $motivo): void
    {
        // Etichetta non prodotta: nessun controllo, solo coda rimborsi.
        if ((int) $motivo !== rimborso::MOTIVO_CON_ETICHETTA) {
            return;
        }

        $spedizione->loadMissing('corriereRecord');

        // Corriere senza tracking API: coda rimborsi, verifica manuale operatore.
        if (! RimborsoFlussoEtichetta::corrierePermetteTracking($spedizione)) {
            return;
        }

        if (EtichettaSpedizioneAccess::etichettaCancellata($spedizione)) {
            if (! RimborsoFlussoEtichetta::isInAttesaDiRimborso($spedizione)) {
                SpedizioneStatoService::segnaAnnullata($spedizione);
            }

            return;
        }

        if (! RimborsoFlussoEtichetta::haEtichettaGenerata($spedizione)) {
            return;
        }

        $this->tracking->assertEtichettaNonSpeditaPerRimborsoRichiesta($spedizione);

        $outcome = $this->etichetta->eliminaSePresente($spedizione->fresh());
        if (! $outcome['ok']) {
            throw new DomainException(
                (string) config(
                    'rimborso.messaggio_annullamento_fallito',
                    'Non è stato possibile annullare l’etichetta sul corriere. Riprova più tardi.',
                )
            );
        }

        $spedizione->update(['cancellata_il' => now()]);
        SpedizioneStatoService::segnaAnnullata($spedizione->fresh());
    }
}
