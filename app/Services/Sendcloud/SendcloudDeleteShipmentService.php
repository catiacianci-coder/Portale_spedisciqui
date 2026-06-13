<?php

namespace App\Services\Sendcloud;

use App\Models\spedizione;
use App\Support\SendcloudIntegrazione;

/**
 * POST /shipments/{id}/cancel — annulla spedizione Sendcloud.
 */
final class SendcloudDeleteShipmentService
{
    public function __construct(
        private readonly SendcloudClient $client,
    ) {}

    /**
     * @return array{ok: bool, message: string, http_status: int|null, response: array<string, mixed>|null}
     */
    public function deleteFromSpedizione(spedizione $spedizione): array
    {
        $shipmentId = SendcloudIntegrazione::shipmentId($spedizione);
        if ($shipmentId === null) {
            return [
                'ok' => false,
                'message' => 'Nessun shipment id Sendcloud salvato per questa spedizione.',
                'http_status' => null,
                'response' => null,
            ];
        }

        if (! SendcloudClient::isConfigured()) {
            return [
                'ok' => false,
                'message' => 'API Sendcloud non configurata.',
                'http_status' => null,
                'response' => null,
            ];
        }

        $path = '/shipments/'.rawurlencode($shipmentId).'/cancel';
        $response = $this->client->post($path);
        $body = $response->json();
        $bodyArr = is_array($body) ? $body : null;

        if (in_array($response->status(), [200, 202], true)) {
            SendcloudIntegrazione::segnaEliminata($spedizione, $bodyArr);

            return [
                'ok' => true,
                'message' => 'Spedizione Sendcloud '.$shipmentId.' annullata.',
                'http_status' => $response->status(),
                'response' => $bodyArr,
            ];
        }

        $detail = '';
        if (is_array($bodyArr['errors'] ?? null) && is_array($bodyArr['errors'][0] ?? null)) {
            $detail = trim((string) ($bodyArr['errors'][0]['detail'] ?? ''));
        }
        if ($detail === '' && is_array($bodyArr['data'] ?? null)) {
            $detail = trim((string) ($bodyArr['data']['message'] ?? ''));
        }

        return [
            'ok' => false,
            'message' => $detail !== '' ? $detail : 'Cancellazione Sendcloud non riuscita (HTTP '.$response->status().').',
            'http_status' => $response->status(),
            'response' => $bodyArr,
        ];
    }
}
