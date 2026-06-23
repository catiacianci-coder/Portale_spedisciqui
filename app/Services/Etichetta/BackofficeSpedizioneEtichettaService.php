<?php

namespace App\Services\Etichetta;

use App\Models\ordine;
use App\Models\spedizione;
use App\Support\LdvStorage;
use DomainException;
use Illuminate\Http\UploadedFile;

final class BackofficeSpedizioneEtichettaService
{
    public function __construct(
        private readonly EtichettaAcquistoSingoloService $acquisto,
    ) {}

    public function assertModificabile(spedizione $spedizione): void
    {
        $spedizione->loadMissing('ordine');

        if ($spedizione->ordine === null || ! $spedizione->ordine->haStato(ordine::STATO_PAGATO)) {
            throw new DomainException('La spedizione non è associata a un ordine pagato.');
        }

        if ((bool) $spedizione->compensata || $spedizione->padre_comp !== null) {
            throw new DomainException('Spedizione compensata — usa l\'etichetta sostitutiva.');
        }
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function salvaManuale(spedizione $spedizione, ?string $tracking, ?UploadedFile $pdf): array
    {
        $this->assertModificabile($spedizione);

        $codice = trim((string) ($tracking ?? ''));
        $hasFile = $pdf !== null;

        if ($codice === '' && ! $hasFile) {
            return [
                'ok' => false,
                'message' => 'Indica il numero di tracking e/o carica il PDF dell\'etichetta generata fuori dal sistema.',
            ];
        }

        $savedTracking = false;
        $savedPdf = false;

        if ($codice !== '') {
            $spedizione->tracking = $codice;
            $savedTracking = true;
        }

        if ($hasFile) {
            $codicePerPdf = $codice !== '' ? $codice : trim((string) ($spedizione->tracking ?? ''));
            if ($codicePerPdf === '') {
                return [
                    'ok' => false,
                    'message' => 'Per salvare il PDF indica prima il numero di tracking dell\'etichetta esterna.',
                ];
            }

            $binary = (string) file_get_contents($pdf->getRealPath());
            if (! str_starts_with($binary, '%PDF')) {
                return ['ok' => false, 'message' => 'Il file caricato non è un PDF valido.'];
            }

            LdvStorage::rimuoviFile($spedizione);
            $relative = LdvStorage::salvaPdf($spedizione, $binary);
            if ($relative === null) {
                return ['ok' => false, 'message' => 'Impossibile salvare il PDF sul server.'];
            }

            $spedizione->etiqueta_pdf_path = $relative;
            $spedizione->ldv_emessa_il = now();
            if (! $savedTracking) {
                $spedizione->tracking = $codicePerPdf;
                $savedTracking = true;
            }
            $savedPdf = true;
        }

        $spedizione->ldverro = false;
        $spedizione->save();

        $parts = [];
        if ($savedTracking) {
            $parts[] = 'numero di tracking';
        }
        if ($savedPdf) {
            $parts[] = 'PDF etichetta';
        }

        return [
            'ok' => true,
            'message' => 'Salvato per spedizione #'.$spedizione->id.': '.implode(' e ', $parts).'.',
        ];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function retry(spedizione $spedizione): array
    {
        $this->assertModificabile($spedizione);

        if (! \App\Support\SpedizioneEtichettaStato::etichettaPendente($spedizione)
            && ! (bool) $spedizione->ldverro) {
            throw new DomainException('Non è possibile rigenerare l\'etichetta per questa spedizione.');
        }

        $delete = $this->acquisto->eliminaSePresente($spedizione->fresh());
        if (! $delete['ok']) {
            return $delete;
        }

        LdvStorage::rimuoviFile($spedizione);

        $spedizione->refresh();
        $spedizione->forceFill([
            'etiqueta_pdf_path' => null,
            'ldverro' => false,
            'ldv_emessa_il' => null,
            'esiste_integrazione' => false,
            'tracking' => null,
            'id_shipment' => null,
        ])->save();

        $spedizione->refresh();

        $outcome = $this->acquisto->genera($spedizione);
        if ($outcome['ok']) {
            return [
                'ok' => true,
                'message' => 'Generazione etichetta rilanciata per spedizione #'.$spedizione->id.'. Verifica l\'elenco.',
            ];
        }

        return $outcome;
    }
}
