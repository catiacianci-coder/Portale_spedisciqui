<?php

namespace App\Services\Liccardi;

use App\Models\spedizione;
use App\Services\SpedizioneStatoService;
use App\Support\LiccardiTmsIntegrazione;
use App\Support\PiattaformaCorriere;
use App\Support\UserPostingEnablement;
use Illuminate\Support\Facades\Log;

/**
 * Creazione spedizione + etichetta TMS in produzione (stesso flusso pagina /test/liccardi-tms).
 */
class LiccardiTmsLabelService
{
    public function __construct(
        private readonly LiccardiTmsClient $client,
        private readonly LiccardiTmsSpedizioneMapper $mapper,
        private readonly LiccardiTmsProbeRunner $probeRunner,
        private readonly LiccardiTmsEtichettaPdfService $etichettaPdf,
        private readonly LiccardiTmsDeleteShipmentService $deleteShipment,
    ) {}

    public function createFromSpedizione(spedizione $spedizione): LiccardiTmsLabelResult
    {
        $spedizione->loadMissing(['corriereRecord', 'serviziAggiuntiviRighe', 'user']);

        if (UserPostingEnablement::tentaSegnaBloccoPostPagamento($spedizione)) {
            return $this->segnaErrore($spedizione, UserPostingEnablement::messaggioBlocco($spedizione->user));
        }

        if (! $this->client->isConfigured()) {
            return $this->segnaErrore($spedizione, 'API Liccardi TMS non configurata (parametri globali).');
        }

        $corriere = $spedizione->corriereRecord;
        if (! $corriere || ! PiattaformaCorriere::corriereUsaAcquistoLiccardiTms($corriere)) {
            return $this->segnaErrore(
                $spedizione,
                'Corriere non abilitato per acquisto Liccardi TMS (piattaforma='.$corriere?->piattaforma.', tariffa_interna='.(int) ($corriere->tariffa_interna ?? 1).').',
            );
        }

        if ($spedizione->esiste_integrazione && LiccardiTmsIntegrazione::spedizioneId($spedizione) !== null) {
            return new LiccardiTmsLabelResult(
                true,
                'Integrazione Liccardi TMS già presente.',
                spedizioneId: LiccardiTmsIntegrazione::spedizioneId($spedizione),
                tracking: LiccardiTmsIntegrazione::courierLdv($spedizione),
            );
        }

        $input = $this->mapper->buildInput($spedizione, $corriere);
        $validazione = $this->validaInput($input);
        if ($validazione !== null) {
            return $this->segnaErrore($spedizione, $validazione, $input);
        }

        $create = $this->probeRunner->run('create_fast', $input);
        $createResult = $this->parseCreateProbe($spedizione, $create);
        if (! $createResult->ok || $createResult->spedizioneId === null) {
            return $createResult;
        }

        $inputPdf = array_merge($input, ['spedizione_id' => (string) $createResult->spedizioneId]);
        $pdfProbe = $this->probeRunner->run('labels_pdf', $inputPdf);
        $pdfPath = $this->salvaPdfDaProbe($spedizione, $pdfProbe);

        $msg = $createResult->message;
        if ($pdfPath === null) {
            $msg .= ' PDF etichetta non salvato.';
            Log::warning('Liccardi TMS: create OK ma PDF assente', [
                'spedizione_id' => $spedizione->id,
                'tms_spedizione_id' => $createResult->spedizioneId,
                'pdf_http_status' => $pdfProbe['httpStatus'] ?? null,
                'pdf_error' => $pdfProbe['errorMessage'] ?? null,
            ]);
        }

        return new LiccardiTmsLabelResult(
            true,
            $msg,
            $createResult->httpStatus,
            $createResult->spedizioneId,
            $createResult->tracking,
            is_array($create['responseJson'] ?? null) ? $create['responseJson'] : null,
        );
    }

    public function deleteFromSpedizione(spedizione $spedizione): LiccardiTmsLabelResult
    {
        if (! LiccardiTmsIntegrazione::spedizioneId($spedizione) && ! $spedizione->esiste_integrazione) {
            return new LiccardiTmsLabelResult(true, 'Nessuna etichetta Liccardi TMS da eliminare.');
        }

        if (LiccardiTmsIntegrazione::eliminataSuTms($spedizione)) {
            return new LiccardiTmsLabelResult(true, 'Spedizione già eliminata su TMS Liccardi.');
        }

        $outcome = $this->deleteShipment->deleteFromSpedizione($spedizione);
        if ($outcome['ok'] ?? false) {
            $this->etichettaPdf->rimuovi($spedizione);
            $spedizione->forceFill([
                'esiste_integrazione' => false,
                'ldverro' => true,
            ])->saveQuietly();
        }

        return new LiccardiTmsLabelResult(
            (bool) ($outcome['ok'] ?? false),
            (string) ($outcome['message'] ?? 'Eliminazione TMS non riuscita.'),
        );
    }

    /**
     * @param  array<string, mixed>  $create
     */
    private function parseCreateProbe(spedizione $spedizione, array $create): LiccardiTmsLabelResult
    {
        $httpStatus = isset($create['httpStatus']) ? (int) $create['httpStatus'] : null;
        $decoded = is_array($create['responseJson'] ?? null) ? $create['responseJson'] : null;
        $payload = is_array($create['payload'] ?? null) ? $create['payload'] : null;

        if (! ($create['ok'] ?? false)) {
            $msg = trim((string) ($create['errorMessage'] ?? 'Creazione spedizione TMS non riuscita.'));

            return $this->segnaErrore($spedizione, $msg, $payload, $httpStatus, $decoded);
        }

        $hints = is_array($create['hints'] ?? null) ? $create['hints'] : [];
        $tmsId = (int) ($hints['spedizioneId'] ?? 0);
        if ($tmsId < 1 && is_array($decoded)) {
            $tmsId = (int) ($decoded['spedizioneId'] ?? 0);
        }
        $ldv = trim((string) ($hints['courierLdv'] ?? ''));
        if ($ldv === '' && is_array($decoded)) {
            $ldv = trim((string) ($decoded['courierLdv'] ?? $decoded['ldv'] ?? ''));
        }

        if ($tmsId < 1) {
            return $this->segnaErrore(
                $spedizione,
                'Risposta TMS senza spedizioneId.',
                $payload,
                $httpStatus,
                $decoded,
            );
        }

        LiccardiTmsIntegrazione::salvaDopoCreate($spedizione, $decoded, $tmsId, $ldv !== '' ? $ldv : null);
        if ($payload !== null) {
            LiccardiTmsIntegrazione::encode($spedizione, array_merge(
                LiccardiTmsIntegrazione::decode($spedizione),
                ['create_request' => $payload],
            ));
        }

        $fill = [
            'esiste_integrazione' => true,
            'id_shipment' => (string) $tmsId,
            'ldv_emessa_il' => now(),
            'ldverro' => false,
        ];
        if ($ldv !== '') {
            $fill['tracking'] = $ldv;
        }
        $spedizione->forceFill($fill)->saveQuietly();
        SpedizioneStatoService::segnaGenerata($spedizione->fresh());

        $msg = $ldv !== ''
            ? 'Spedizione Liccardi TMS creata. LDV: '.$ldv
            : 'Spedizione Liccardi TMS creata (LDV non presente in risposta).';

        return new LiccardiTmsLabelResult(
            true,
            $msg,
            $httpStatus,
            $tmsId,
            $ldv !== '' ? $ldv : null,
            $decoded,
        );
    }

    /**
     * @param  array<string, mixed>  $pdfProbe
     */
    private function salvaPdfDaProbe(spedizione $spedizione, array $pdfProbe): ?string
    {
        $binary = $pdfProbe['rawBodyBinary'] ?? null;
        if (is_string($binary) && str_starts_with($binary, '%PDF')) {
            return $this->etichettaPdf->salvaBinary($spedizione, $binary);
        }

        $decoded = is_array($pdfProbe['responseJson'] ?? null) ? $pdfProbe['responseJson'] : null;
        if ($decoded !== null) {
            return $this->etichettaPdf->salvaDaJson($spedizione, $decoded);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $input
     * @param  array<string, mixed>|null  $response
     */
    private function segnaErrore(
        spedizione $spedizione,
        string $message,
        ?array $input = null,
        ?int $httpStatus = null,
        ?array $response = null,
    ): LiccardiTmsLabelResult {
        LiccardiTmsIntegrazione::encode($spedizione, array_merge(
            LiccardiTmsIntegrazione::decode($spedizione),
            array_filter([
                'last_error_at' => now()->toIso8601String(),
                'last_error' => $message,
                'last_http_status' => $httpStatus,
                'last_create_request' => $input,
                'last_create_response' => $response,
            ], static fn ($v) => $v !== null),
        ));

        $spedizione->forceFill(['ldverro' => true])->saveQuietly();

        Log::warning('Liccardi TMS create fallito', [
            'spedizione_id' => $spedizione->id,
            'codice_interno' => $spedizione->codice_interno,
            'http_status' => $httpStatus,
            'message' => $message,
        ]);

        return new LiccardiTmsLabelResult(false, $message, $httpStatus, responseBody: $response);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validaInput(array $input): ?string
    {
        foreach ([
            'cap_origine' => 'CAP mittente',
            'citta_origine' => 'comune mittente',
            'via_origine' => 'via mittente',
            'cap_destino' => 'CAP destinatario',
            'citta_destino' => 'comune destinatario',
            'via_destino' => 'via destinatario',
        ] as $key => $label) {
            if (trim((string) ($input[$key] ?? '')) === '') {
                return 'Dato obbligatorio mancante per TMS: '.$label.'.';
            }
        }

        if ((float) ($input['peso'] ?? 0) <= 0) {
            return 'Peso collo non valido per TMS.';
        }

        return null;
    }
}
