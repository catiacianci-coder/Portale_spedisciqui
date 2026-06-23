<?php

namespace App\Services\Rimborso;

use App\Models\corriere;
use App\Models\rimborso;
use App\Models\spedizione;
use App\Models\stato_spedizione;
use App\Services\Sendcloud\SendcloudVerificaAnnullamentoService;
use App\Services\Tracking\SpedizioneTrackingService;
use App\Support\PiattaformaCorriere;
use App\Support\RimborsoFlussoEtichetta;
use DomainException;

/**
 * Prima dell’accredito wallet (backoffice): verifiche in base al tipo di rimborso.
 */
final class RimborsoVerificaEtichettaAnnullataService
{
    public function __construct(
        private readonly SendcloudVerificaAnnullamentoService $sendcloudVerifica,
        private readonly SpedizioneTrackingService $tracking,
    ) {}

    /**
     * @throws DomainException
     */
    public function assertProntaPerPagamento(rimborso $rimborso, spedizione $spedizione): void
    {
        $spedizione->loadMissing(['corriereRecord', 'spedizioneStato']);

        if ((int) $rimborso->motivo === rimborso::MOTIVO_SENZA_ETICHETTA) {
            $this->assertEtichettaNonGenerata($spedizione);

            return;
        }

        // Con etichetta, corriere senza tracking: nessun controllo automatico (operatore).
        if (! RimborsoFlussoEtichetta::corrierePermetteTracking($spedizione)) {
            return;
        }

        $this->assertStatoInAttesaDiRimborso($spedizione);
        $this->tracking->assertEtichettaNonSpeditaPerRimborsoPagamento($spedizione);

        $corriere = $spedizione->corriereRecord;
        if ($corriere !== null) {
            $this->verificaAnnullamentoSuCorriere($spedizione, $corriere);
        }
    }

    /**
     * @throws DomainException
     */
    private function assertEtichettaNonGenerata(spedizione $spedizione): void
    {
        if (RimborsoFlussoEtichetta::haEtichettaGenerata($spedizione->fresh())) {
            throw new DomainException(
                (string) config(
                    'rimborso.messaggio_etichetta_generata_dopo_richiesta',
                    'L’etichetta risulta ora generata sul corriere (possibile ritardo): verificare prima di accreditare il rimborso.',
                )
            );
        }
    }

    /**
     * @throws DomainException
     */
    private function assertStatoInAttesaDiRimborso(spedizione $spedizione): void
    {
        if (RimborsoFlussoEtichetta::isInAttesaDiRimborso($spedizione)) {
            return;
        }

        $label = trim((string) ($spedizione->spedizioneStato?->denominazione_stato ?? ''));
        $atteso = stato_spedizione::query()
            ->whereKey(RimborsoFlussoEtichetta::idStatoInAttesaDiRimborso())
            ->value('denominazione_stato');

        throw new DomainException(
            (string) config(
                'rimborso.messaggio_stato_non_in_attesa',
                'La spedizione non risulta «'.($atteso ?: 'in attesa di rimborso').'» (stato attuale: '.($label !== '' ? $label : '—').').',
            )
        );
    }

    /**
     * @throws DomainException
     */
    private function verificaAnnullamentoSuCorriere(spedizione $spedizione, corriere $corriere): void
    {
        if (PiattaformaCorriere::corriereUsaAcquistoSendcloud($corriere)) {
            $result = $this->sendcloudVerifica->verificaAnnullata($spedizione);
            if (! $result['ok']) {
                throw new DomainException($result['message']);
            }
        }
    }
}
