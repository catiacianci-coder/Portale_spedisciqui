<?php

namespace App\Services\Tracking;

use App\Models\corriere;
use App\Models\spedizione;
use App\Models\stato_spedizione;
use App\Services\Liccardi\LiccardiTmsTrackingService;
use App\Services\Sendcloud\SendcloudTrackingService;
use App\Support\CorriereTrackingUrl;
use App\Support\LiccardiTmsIntegrazione;
use App\Support\PiattaformaCorriere;
use App\Support\SendcloudIntegrazione;
use App\Support\TrackingEventoVerifica;
use DomainException;

final class SpedizioneTrackingService
{
    public function __construct(
        private readonly SendcloudTrackingService $sendcloudTracking,
        private readonly LiccardiTmsTrackingService $liccardiTracking,
        private readonly MsgTracciamentoService $msgTracciamento,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function consulta(spedizione $spedizione): array
    {
        $spedizione->loadMissing('corriereRecord');
        $corriere = $spedizione->corriereRecord;
        $tracking = trim((string) ($spedizione->tracking ?? ''));

        if (! $this->etichettaTracciabile($spedizione, $tracking)) {
            return [
                'tipo' => 'non_tracciabile',
                'messaggio' => 'Questa spedizione non può essere tracciata.',
            ];
        }

        if ($corriere === null) {
            return [
                'tipo' => 'errore',
                'messaggio' => 'Corriere non associato alla spedizione.',
            ];
        }

        if (! (bool) ($corriere->trackingsn ?? false)) {
            $url = CorriereTrackingUrl::perSpedizione($corriere, $tracking);

            return [
                'tipo' => 'manuale',
                'messaggio' => 'Questo corriere non permette il tracking in automatico.',
                'tracking' => $tracking,
                'url_tracking' => $url,
            ];
        }

        $risultato = $this->consultaApi($spedizione, $corriere);
        $this->persistiRisultato($spedizione, $risultato);

        if (! $risultato['ok']) {
            $messaggio = $this->msgTracciamento->messaggioPerCliente(
                (int) $corriere->id,
                (string) ($risultato['errore'] ?? ''),
            );

            return [
                'tipo' => 'errore',
                'messaggio' => $messaggio !== '' ? $messaggio : 'Errore durante il tracking.',
                'tracking' => $tracking,
                'tracking_errore' => (string) ($risultato['errore'] ?? ''),
            ];
        }

        $statoGrezzo = trim((string) ($risultato['stato'] ?? ''));
        $statoCliente = $statoGrezzo !== ''
            ? $this->msgTracciamento->messaggioPerCliente((int) $corriere->id, $statoGrezzo)
            : '';

        return [
            'tipo' => 'api',
            'stato' => $statoCliente,
            'data_evento' => $risultato['evento_at']?->format('d/m/Y H:i'),
            'tracking' => $tracking,
        ];
    }

    /**
     * Prima della correzione etichetta: consulta il tracking e verifica che non sia già spedita.
     *
     * @throws DomainException
     */
    public function assertEtichettaNonSpeditaPerCorrecao(spedizione $spedizione): void
    {
        $spedizione->loadMissing('corriereRecord');
        $corriere = $spedizione->corriereRecord;

        if ($corriere === null || ! (bool) ($corriere->trackingsn ?? false)) {
            throw new DomainException('Correzione non disponibile per questo corriere.');
        }

        if (! $this->haIdentificativoTrackingCorriere($spedizione)) {
            return;
        }

        try {
            $risultato = $this->consultaApi($spedizione, $corriere);
            $this->persistiRisultato($spedizione, $risultato);

            if (! $risultato['ok']) {
                throw new DomainException(
                    (string) config(
                        'etichetta.correcao_messaggio_corriere_non_risponde',
                        'Il server del corriere non risponde in questo momento. Riprovare tra qualche minuto.'
                    )
                );
            }

            $response = $risultato['response'];
            if (! is_array($response)) {
                return;
            }

            $eventi = PiattaformaCorriere::corriereUsaAcquistoSendcloud($corriere)
                ? TrackingEventoVerifica::eventiDaResponseSendcloud($response)
                : (PiattaformaCorriere::corriereUsaAcquistoLiccardiTms($corriere)
                    ? TrackingEventoVerifica::eventiDaResponseLiccardi($response)
                    : []);

            if ($eventi === []) {
                $statoGrezzo = trim((string) ($risultato['stato'] ?? ''));
                if ($statoGrezzo !== '') {
                    $eventi = [['status' => $statoGrezzo, 'data' => '']];
                }
            }

            TrackingEventoVerifica::assertUltimoEventoNonSpedito(
                $eventi,
                TrackingEventoVerifica::fragmentiBloccoCorrecao(),
                (string) config(
                    'etichetta.correcao_messaggio_gia_utilizzata',
                    'Questa Lettera di vettura è già stata utilizzata, non è possibile sostituirla con un\'altra lettera di vettura'
                )
            );
        } catch (DomainException $e) {
            throw $e;
        } catch (\Throwable) {
            throw new DomainException(
                (string) config(
                    'etichetta.correcao_messaggio_corriere_non_risponde',
                    'Il server del corriere non risponde in questo momento. Riprovare tra qualche minuto.'
                )
            );
        }
    }

    /** Etichetta ancora non registrata sul corriere: nessun codice da interrogare. */
    private function haIdentificativoTrackingCorriere(spedizione $spedizione): bool
    {
        if (trim((string) ($spedizione->tracking ?? '')) !== '') {
            return true;
        }

        $spedizione->loadMissing('corriereRecord');
        $corriere = $spedizione->corriereRecord;
        if ($corriere === null) {
            return false;
        }

        if (PiattaformaCorriere::corriereUsaAcquistoSendcloud($corriere)) {
            return SendcloudIntegrazione::shipmentId($spedizione) !== null;
        }

        if (PiattaformaCorriere::corriereUsaAcquistoLiccardiTms($corriere)) {
            return trim((string) (LiccardiTmsIntegrazione::courierLdv($spedizione) ?? '')) !== '';
        }

        return false;
    }

    private function etichettaTracciabile(spedizione $spedizione, string $tracking): bool
    {
        if ($tracking === '') {
            return false;
        }

        if ((int) $spedizione->spedizione_stato_id === stato_spedizione::GENERATA) {
            return true;
        }

        return (bool) ($spedizione->esiste_integrazione || $spedizione->ldv_emessa_il);
    }

    /**
     * @return array{ok: bool, stato: string|null, evento_at: \Carbon\Carbon|null, response: array<string, mixed>|null, errore: string|null}
     */
    private function consultaApi(spedizione $spedizione, corriere $corriere): array
    {
        if (PiattaformaCorriere::corriereUsaAcquistoSendcloud($corriere)) {
            return $this->sendcloudTracking->consulta($spedizione);
        }

        if (PiattaformaCorriere::corriereUsaAcquistoLiccardiTms($corriere)) {
            return $this->liccardiTracking->consulta($spedizione);
        }

        return [
            'ok' => false,
            'stato' => null,
            'evento_at' => null,
            'response' => null,
            'errore' => 'Tracking automatico non configurato per questo corriere.',
        ];
    }

    /**
     * @param  array{ok: bool, stato: string|null, evento_at: \Carbon\Carbon|null, response: array<string, mixed>|null, errore: string|null}  $risultato
     */
    private function persistiRisultato(spedizione $spedizione, array $risultato): void
    {
        $fill = [
            'traking_consultato_il' => now(),
        ];

        if ($risultato['ok']) {
            $responseJson = is_array($risultato['response'])
                ? json_encode($risultato['response'], JSON_UNESCAPED_UNICODE)
                : null;

            $fill['tracking_errore'] = null;
            $fill['tracking_status'] = $risultato['stato'];
            $fill['traking_evento_em'] = $risultato['evento_at'] ?? now();
            $fill['tracking_evento'] = $responseJson;
        } else {
            $fill['tracking_errore'] = $risultato['errore'];
        }

        $spedizione->forceFill($fill)->save();
    }
}
