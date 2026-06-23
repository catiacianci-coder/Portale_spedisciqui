<?php

namespace App\Services\SpedisciOnline;

use App\Models\spedizione;
use App\Services\SpedizioneStatoService;
use App\Support\PiattaformaCorriere;
use App\Support\SpedisciOnlineIntegrazione;
use App\Support\UserPostingEnablement;
use Illuminate\Http\Client\Response;

class SpedisciOnlineLabelService
{
    public function __construct(
        private readonly SpedisciOnlineCreateLabelService $createLabel,
        private readonly SpedisciOnlineEtichettaPdfService $etichettaPdf,
    ) {}

    public function createFromSpedizione(spedizione $spedizione): SpedisciOnlineLabelResult
    {
        $spedizione->loadMissing(['corriereRecord', 'user']);

        if (UserPostingEnablement::tentaSegnaBloccoPostPagamento($spedizione)) {
            return new SpedisciOnlineLabelResult(false, UserPostingEnablement::messaggioBlocco($spedizione->user));
        }

        $piattaforma = $spedizione->corriereRecord?->piattaforma;
        $client = SpedisciOnlineClient::forPiattaforma($piattaforma);

        if (! $client->isConfigured()) {
            return new SpedisciOnlineLabelResult(
                false,
                'API Spedisci.online non configurata per '.($client->tenant()).' (chiave .env mancante).',
            );
        }

        if (! PiattaformaCorriere::usaAcquistoSpedisciOnline($piattaforma)) {
            return new SpedisciOnlineLabelResult(false, 'Piattaforma corriere non supportata per Spedisci.online.');
        }

        $corriere = $spedizione->corriereRecord;
        if (! $corriere) {
            return new SpedisciOnlineLabelResult(false, 'Corriere non associato alla spedizione.');
        }

        $payload = $this->createLabel->buildCreatePayloadFromSpedizione($spedizione, $corriere);

        $carrierCode = trim((string) ($payload['carrierCode'] ?? ''));
        $contractCode = trim((string) ($payload['contractCode'] ?? ''));
        if ($carrierCode === '' || $contractCode === '') {
            return new SpedisciOnlineLabelResult(
                false,
                'carrier_code o codice_servizio (contractCode) mancante sul corriere della spedizione.',
            );
        }

        $response = $client->post('/shipping/create', $payload);

        return $this->parseCreateResponse($spedizione, $response, $client->tenant());
    }

    public function deleteFromSpedizione(spedizione $spedizione): SpedisciOnlineLabelResult
    {
        $spedizione->loadMissing('corriereRecord');
        $piattaforma = $spedizione->corriereRecord?->piattaforma;
        $client = SpedisciOnlineClient::forPiattaforma($piattaforma);

        if (! $client->isConfigured()) {
            return new SpedisciOnlineLabelResult(
                false,
                'API Spedisci.online non configurata per '.($client->tenant()).'.',
            );
        }

        if (! $spedizione->esiste_integrazione) {
            return new SpedisciOnlineLabelResult(true, 'Nessuna etichetta Spedisci da eliminare.');
        }

        $payload = SpedisciOnlineIntegrazione::payloadDelete($spedizione);
        if ($payload === []) {
            return new SpedisciOnlineLabelResult(
                false,
                'Dati insufficienti per /shipping/delete (manca tracking o id dalla risposta create).',
            );
        }

        $response = $client->post('/shipping/delete', $payload);

        return $this->parseDeleteResponse($spedizione, $response);
    }

    private function parseDeleteResponse(spedizione $spedizione, Response $response): SpedisciOnlineLabelResult
    {
        $httpStatus = $response->status();
        $body = $response->json();
        $bodyArr = is_array($body) ? $body : [];

        if (! $response->successful()) {
            $msg = trim((string) ($bodyArr['message'] ?? $bodyArr['error'] ?? ''));
            if ($msg === '') {
                $msg = 'Errore HTTP '.$httpStatus.' su /shipping/delete';
            }

            return new SpedisciOnlineLabelResult(false, $msg, $httpStatus, null, $bodyArr);
        }

        SpedisciOnlineIntegrazione::segnaEliminata($spedizione, $httpStatus, $bodyArr);

        return new SpedisciOnlineLabelResult(true, 'Etichetta eliminata su Spedisci.online.', $httpStatus, null, $bodyArr);
    }

    private function parseCreateResponse(spedizione $spedizione, Response $response, string $tenant): SpedisciOnlineLabelResult
    {
        $httpStatus = $response->status();
        $body = $response->json();
        $bodyArr = is_array($body) ? $body : [];

        if (! $response->successful()) {
            $msg = trim((string) ($bodyArr['message'] ?? $bodyArr['error'] ?? ''));
            if ($msg === '') {
                $msg = 'Errore HTTP '.$httpStatus.' su /shipping/create';
            }

            return new SpedisciOnlineLabelResult(false, $msg, $httpStatus, null, $bodyArr);
        }

        $tracking = $this->estraiTracking($bodyArr);
        $integrazione = [
            'provider' => 'spedisci_online',
            'tenant' => $tenant,
            'created_at' => now()->toIso8601String(),
            'http_status' => $httpStatus,
            'tracking' => $tracking,
            'response' => $bodyArr,
        ];

        if ($tracking) {
            $idShipment = trim((string) ($bodyArr['id'] ?? $bodyArr['shipmentId'] ?? $bodyArr['shipment_id'] ?? ''));
            if ($idShipment !== '') {
                $integrazione['id_shipment'] = $idShipment;
            }
        }

        SpedisciOnlineIntegrazione::encode($spedizione, $integrazione);

        $pdfPath = $this->etichettaPdf->salvaDaRispostaCreate($spedizione, $bodyArr);

        $fill = [
            'tracking' => $tracking ?: $spedizione->tracking,
            'esiste_integrazione' => true,
            'ldv_emessa_il' => now(),
            'ldverro' => false,
        ];
        if (! empty($integrazione['id_shipment'])) {
            $fill['id_shipment'] = (string) $integrazione['id_shipment'];
        }
        if ($pdfPath !== null) {
            $fill['etiqueta_pdf_path'] = $pdfPath;
        }
        $spedizione->forceFill($fill)->saveQuietly();
        SpedizioneStatoService::segnaGenerata($spedizione->fresh());

        $msg = $tracking
            ? 'Etichetta creata. Tracking: '.$tracking
            : 'Etichetta creata (tracking non presente in risposta).';
        if ($pdfPath === null) {
            $msg .= ' PDF non presente in risposta API.';
        }

        return new SpedisciOnlineLabelResult(true, $msg, $httpStatus, $tracking, $bodyArr);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function estraiTracking(array $body): ?string
    {
        foreach (['tracking', 'trackingNumber', 'tracking_number', 'barcode'] as $key) {
            $v = trim((string) ($body[$key] ?? ''));
            if ($v !== '') {
                return $v;
            }
        }

        foreach (['data', 'shipment', 'label'] as $wrap) {
            if (! isset($body[$wrap]) || ! is_array($body[$wrap])) {
                continue;
            }
            foreach (['tracking', 'trackingNumber', 'tracking_number'] as $key) {
                $v = trim((string) ($body[$wrap][$key] ?? ''));
                if ($v !== '') {
                    return $v;
                }
            }
        }

        return null;
    }
}
