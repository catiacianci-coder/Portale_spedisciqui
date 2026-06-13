<?php

namespace App\Services\Liccardi;

use App\Models\spedizione;
use App\Services\ParametriApiConfig;
use App\Support\LiccardiTmsIntegrazione;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LiccardiTmsWebhookHandler
{
    /**
     * @throws LiccardiTmsWebhookRejected
     */
    public function handle(Request $request): void
    {
        $this->assertAuthorized($request);

        /** @var array<string, mixed> $payload */
        $payload = $this->parsePayload($request);

        $this->validateCustomerCode($payload);

        $spedizione = $this->findSpedizione($payload);
        if ($spedizione === null) {
            Log::warning('Liccardi TMS webhook: spedizione non trovata', [
                'shipmentIdentifier' => $payload['shipmentIdentifier'] ?? null,
                'courierLdv' => $payload['courierLdv'] ?? null,
            ]);

            return;
        }

        $this->applyEvent($spedizione, $payload);
    }

    /**
     * @throws LiccardiTmsWebhookRejected
     */
    private function assertAuthorized(Request $request): void
    {
        $expectedToken = ParametriApiConfig::liccardiTmsWebhookToken();
        if ($expectedToken === '') {
            Log::warning('Liccardi TMS webhook: liccardi_tms_webhook_token mancante in parametri globali');

            throw new LiccardiTmsWebhookRejected('Webhook non configurato', 503);
        }

        $headerName = ParametriApiConfig::liccardiTmsWebhookHeader();
        $received = trim((string) $request->header($headerName, ''));

        if ($received === '' || ! hash_equals($expectedToken, $received)) {
            Log::warning('Liccardi TMS webhook: token non valido', ['header' => $headerName]);

            throw new LiccardiTmsWebhookRejected('Unauthorized', 401);
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws LiccardiTmsWebhookRejected
     */
    private function parsePayload(Request $request): array
    {
        $raw = trim($request->getContent());
        if ($raw === '') {
            throw new LiccardiTmsWebhookRejected('Body vuoto', 400);
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            throw new LiccardiTmsWebhookRejected('JSON non valido', 400);
        }

        $hasShipmentId = isset($decoded['shipmentIdentifier']) && (string) $decoded['shipmentIdentifier'] !== '';
        $hasLdv = isset($decoded['courierLdv']) && trim((string) $decoded['courierLdv']) !== '';

        if (! $hasShipmentId && ! $hasLdv) {
            throw new LiccardiTmsWebhookRejected('Manca shipmentIdentifier o courierLdv', 400);
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws LiccardiTmsWebhookRejected
     */
    private function validateCustomerCode(array $payload): void
    {
        $expected = ParametriApiConfig::liccardiTmsCompanyId();
        if ($expected === '') {
            return;
        }

        $received = trim((string) ($payload['customerCode'] ?? ''));
        if ($received === '') {
            return;
        }

        if (! hash_equals(strtoupper($expected), strtoupper($received))) {
            Log::warning('Liccardi TMS webhook: customerCode non corrisponde', [
                'expected' => $expected,
                'received' => $received,
            ]);

            throw new LiccardiTmsWebhookRejected('customerCode non valido', 422);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function findSpedizione(array $payload): ?spedizione
    {
        $ldv = trim((string) ($payload['courierLdv'] ?? ''));
        if ($ldv !== '') {
            $byLdv = spedizione::query()->where('tracking', $ldv)->first();
            if ($byLdv !== null) {
                return $byLdv;
            }
        }

        $shipmentId = trim((string) ($payload['shipmentIdentifier'] ?? ''));
        if ($shipmentId !== '') {
            return spedizione::query()->where('id_shipment', $shipmentId)->first();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyEvent(spedizione $spedizione, array $payload): void
    {
        $eventCode = trim((string) ($payload['eventCode'] ?? ''));
        $eventDescription = trim((string) ($payload['eventDescription'] ?? ''));
        $eventDateTime = $this->parseEventDateTime($payload['eventDateTime'] ?? null);

        $statusLabel = $eventDescription !== ''
            ? $eventDescription
            : ($eventCode !== '' ? 'Evento '.$eventCode : 'Aggiornamento tracking');

        if ($eventCode !== '' && $eventDescription !== '') {
            $statusLabel = $eventCode.': '.$eventDescription;
        }

        $isDuplicate = LiccardiTmsIntegrazione::registraEventoWebhook($spedizione, $payload, $eventDateTime);
        if ($isDuplicate) {
            Log::info('Liccardi TMS webhook: evento duplicato ignorato', [
                'spedizione_id' => $spedizione->id,
                'eventCode' => $eventCode,
                'eventDateTime' => $eventDateTime?->toIso8601String(),
            ]);

            return;
        }

        $spedizione->forceFill([
            'tracking_status' => $statusLabel,
            'traking_evento_em' => $eventDateTime ?? now(),
        ])->save();

        Log::info('Liccardi TMS webhook: tracking aggiornato', [
            'spedizione_id' => $spedizione->id,
            'tracking_status' => $statusLabel,
        ]);
    }

    private function parseEventDateTime(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
