<?php

namespace App\Services\Sendcloud;

use App\Models\spedizione;
use App\Services\SpedizioneStatoService;
use App\Support\PiattaformaCorriere;
use App\Support\SendcloudIntegrazione;
use App\Support\UserPostingEnablement;
use Illuminate\Support\Facades\Log;

/**
 * Creazione e annullamento spedizione + etichetta Sendcloud (POST /shipments/announce).
 */
class SendcloudLabelService
{
    public function __construct(
        private readonly SendcloudClient $client,
        private readonly SendcloudShipmentMapper $mapper,
        private readonly SendcloudEtichettaPdfService $etichettaPdf,
        private readonly SendcloudDeleteShipmentService $deleteShipment,
    ) {}

    public function createFromSpedizione(spedizione $spedizione): SendcloudLabelResult
    {
        $spedizione->loadMissing(['corriereRecord', 'serviziAggiuntiviRighe', 'user']);

        if (UserPostingEnablement::tentaSegnaBloccoPostPagamento($spedizione)) {
            return new SendcloudLabelResult(false, UserPostingEnablement::messaggioBlocco($spedizione->user));
        }

        if (! SendcloudClient::isConfigured()) {
            return new SendcloudLabelResult(false, 'API Sendcloud non configurata (chiavi parametri globali).');
        }

        $corriere = $spedizione->corriereRecord;
        if (! $corriere || ! PiattaformaCorriere::corriereUsaAcquistoSendcloud($corriere)) {
            return new SendcloudLabelResult(
                false,
                'Corriere non abilitato per acquisto Sendcloud (piattaforma='.$corriere?->piattaforma.', tariffa_interna='.(int) ($corriere->tariffa_interna ?? 1).').',
            );
        }

        $shipmentIdEsistente = SendcloudIntegrazione::shipmentId($spedizione);
        if ($shipmentIdEsistente !== null) {
            return new SendcloudLabelResult(
                true,
                'Etichetta Sendcloud già presente.',
                shipmentId: $shipmentIdEsistente,
                tracking: SendcloudIntegrazione::tracking($spedizione),
            );
        }

        $built = $this->mapper->buildAnnouncePayload($spedizione, $corriere);
        if (($built['error'] ?? null) !== null) {
            return $this->segnaErrore($spedizione, (string) $built['error'], $built['payload'] ?? []);
        }

        /** @var array<string, mixed> $payload */
        $payload = $built['payload'];

        SendcloudIntegrazione::encode($spedizione, array_merge(
            SendcloudIntegrazione::decode($spedizione),
            ['announce_request' => $payload],
        ));

        $response = $this->client->post('/shipments/announce', $payload);

        return $this->parseAnnounceResponse($spedizione, $response->status(), $response->json(), $payload);
    }

    public function deleteFromSpedizione(spedizione $spedizione): SendcloudLabelResult
    {
        if (! SendcloudIntegrazione::shipmentId($spedizione) && ! $spedizione->esiste_integrazione) {
            return new SendcloudLabelResult(true, 'Nessuna etichetta Sendcloud da eliminare.');
        }

        if (SendcloudIntegrazione::eliminataSuSendcloud($spedizione)) {
            return new SendcloudLabelResult(true, 'Spedizione già annullata su Sendcloud.');
        }

        $outcome = $this->deleteShipment->deleteFromSpedizione($spedizione);
        if ($outcome['ok'] ?? false) {
            $this->etichettaPdf->rimuovi($spedizione);
            $spedizione->forceFill([
                'esiste_integrazione' => false,
                'ldverro' => true,
            ])->saveQuietly();
        }

        return new SendcloudLabelResult(
            (bool) ($outcome['ok'] ?? false),
            (string) ($outcome['message'] ?? 'Cancellazione Sendcloud non riuscita.'),
            $outcome['http_status'] ?? null,
        );
    }

    /**
     * @param  array<string, mixed>|null  $body
     * @param  array<string, mixed>  $requestPayload
     */
    private function parseAnnounceResponse(
        spedizione $spedizione,
        int $httpStatus,
        ?array $body,
        array $requestPayload,
    ): SendcloudLabelResult {
        $data = is_array($body['data'] ?? null) ? $body['data'] : null;

        if (! in_array($httpStatus, [200, 201], true) || $data === null) {
            $msg = $this->estraiErrore($body, $httpStatus);

            return $this->segnaErrore($spedizione, $msg, $requestPayload, $httpStatus, $body);
        }

        $shipmentId = trim((string) ($data['id'] ?? ''));
        $parcel = is_array($data['parcels'][0] ?? null) ? $data['parcels'][0] : [];
        $parcelId = (int) ($parcel['id'] ?? 0);
        $tracking = trim((string) ($parcel['tracking_number'] ?? ''));
        $labelUrl = null;
        if (is_array($parcel['documents'] ?? null)) {
            foreach ($parcel['documents'] as $doc) {
                if (! is_array($doc) || strtolower((string) ($doc['type'] ?? '')) !== 'label') {
                    continue;
                }
                $labelUrl = trim((string) ($doc['link'] ?? ''));
                break;
            }
        }

        if ($shipmentId === '') {
            return $this->segnaErrore($spedizione, 'Risposta Sendcloud senza shipment id.', $requestPayload, $httpStatus, $body);
        }

        $errors = $data['errors'] ?? $parcel['errors'] ?? null;
        if (is_array($errors) && $errors !== []) {
            $detail = trim((string) ($errors[0]['detail'] ?? $errors[0]['title'] ?? ''));

            return $this->segnaErrore(
                $spedizione,
                $detail !== '' ? $detail : 'Sendcloud ha restituito errori di annuncio.',
                $requestPayload,
                $httpStatus,
                $body,
            );
        }

        SendcloudIntegrazione::salvaDopoAnnounce(
            $spedizione,
            $body,
            $shipmentId,
            $parcelId > 0 ? $parcelId : null,
            $tracking !== '' ? $tracking : null,
            $labelUrl !== '' ? $labelUrl : null,
        );

        $pdfPath = $this->etichettaPdf->salvaDaAnnounceResponse($spedizione, $data);

        $fill = [
            'esiste_integrazione' => true,
            'id_shipment' => $shipmentId,
            'ldv_emessa_il' => now(),
            'ldverro' => false,
        ];
        if ($tracking !== '') {
            $fill['tracking'] = $tracking;
        }
        if ($pdfPath !== null) {
            $fill['etiqueta_pdf_path'] = $pdfPath;
        }
        $spedizione->forceFill($fill)->saveQuietly();
        SpedizioneStatoService::segnaGenerata($spedizione->fresh());

        $msg = $tracking !== ''
            ? 'Spedizione Sendcloud creata. Tracking: '.$tracking
            : 'Spedizione Sendcloud creata (tracking non presente in risposta).';
        if ($pdfPath === null) {
            $msg .= ' PDF etichetta non salvato.';
            Log::warning('Sendcloud: announce OK ma PDF assente', [
                'spedizione_id' => $spedizione->id,
                'shipment_id' => $shipmentId,
            ]);
        }

        return new SendcloudLabelResult(
            true,
            $msg,
            $httpStatus,
            $shipmentId,
            $tracking !== '' ? $tracking : null,
            $body,
        );
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    private function estraiErrore(?array $body, int $httpStatus): string
    {
        if (is_array($body['errors'] ?? null) && is_array($body['errors'][0] ?? null)) {
            $detail = trim((string) ($body['errors'][0]['detail'] ?? ''));
            if ($detail !== '') {
                return $detail;
            }
        }

        return 'Errore HTTP '.$httpStatus.' da Sendcloud su /shipments/announce.';
    }

    /**
     * @param  array<string, mixed>  $requestPayload
     * @param  array<string, mixed>|null  $response
     */
    private function segnaErrore(
        spedizione $spedizione,
        string $message,
        array $requestPayload,
        ?int $httpStatus = null,
        ?array $response = null,
    ): SendcloudLabelResult {
        SendcloudIntegrazione::encode($spedizione, array_merge(
            SendcloudIntegrazione::decode($spedizione),
            array_filter([
                'last_error_at' => now()->toIso8601String(),
                'last_error' => $message,
                'last_http_status' => $httpStatus,
                'last_response' => $response,
                'announce_request' => $requestPayload,
            ]),
        ));

        $spedizione->forceFill(['ldverro' => true])->saveQuietly();

        return new SendcloudLabelResult(false, $message, $httpStatus, null, null, $response);
    }
}
