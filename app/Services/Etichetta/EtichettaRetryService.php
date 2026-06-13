<?php

namespace App\Services\Etichetta;

use App\Models\spedizione;
use App\Support\SpedizioneEtichettaStato;
use DomainException;

final class EtichettaRetryService
{
    public function __construct(
        private readonly EtichettaAcquistoSingoloService $acquisto,
    ) {}

    /**
     * @return array{ok: bool, message: string}
     */
    public function retry(spedizione $spedizione, int $userId): array
    {
        if ((int) $spedizione->user_id !== $userId) {
            throw new DomainException('Spedizione non trovata.');
        }

        if (! SpedizioneEtichettaStato::etichettaPendente($spedizione)) {
            throw new DomainException('Non è possibile rigenerare l\'etichetta per questa spedizione.');
        }

        $delete = $this->acquisto->eliminaSePresente($spedizione->fresh());
        if (! $delete['ok']) {
            return $delete;
        }

        $spedizione->refresh();
        $spedizione->forceFill([
            'etiqueta_pdf_path' => null,
            'ldverro' => false,
            'esiste_integrazione' => false,
            'tracking' => null,
            'id_shipment' => null,
        ])->save();

        $spedizione->refresh();

        return $this->acquisto->genera($spedizione);
    }
}
