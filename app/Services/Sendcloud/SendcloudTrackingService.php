<?php

namespace App\Services\Sendcloud;

use App\Models\spedizione;
use App\Support\SendcloudIntegrazione;
use Carbon\Carbon;

final class SendcloudTrackingService
{
    public function __construct(
        private readonly SendcloudClient $client,
    ) {}

    /**
     * @return array{ok: bool, stato: string|null, evento_at: Carbon|null, response: array<string, mixed>|null, errore: string|null}
     */
    public function consulta(spedizione $spedizione): array
    {
        if (! $this->client->isConfigured()) {
            return $this->errore('API Sendcloud non configurata.');
        }

        $tracking = trim((string) (SendcloudIntegrazione::tracking($spedizione) ?? ''));
        if ($tracking === '') {
            return $this->errore('Numero di tracking non disponibile.');
        }

        $path = 'parcels/tracking/'.rawurlencode($tracking);
        $response = $this->client->get($path);

        if ($response->successful()) {
            $body = $response->json('data') ?? $response->json();

            return $this->okFromTrackingBody(is_array($body) ? $body : []);
        }

        if ($response->status() === 404) {
            $fallback = $this->consultaFallback($spedizione);
            if ($fallback !== null) {
                return $fallback;
            }
        }

        $detail = trim((string) ($response->json('detail') ?? $response->body()));

        return $this->errore($detail !== '' ? $detail : 'Tracking Sendcloud non disponibile (HTTP '.$response->status().').');
    }

    /**
     * @return array{ok: bool, stato: string|null, evento_at: Carbon|null, response: array<string, mixed>|null, errore: string|null}|null
     */
    private function consultaFallback(spedizione $spedizione): ?array
    {
        $shipmentId = SendcloudIntegrazione::shipmentId($spedizione);
        if ($shipmentId !== null) {
            $response = $this->client->get('shipments/'.rawurlencode($shipmentId));
            if ($response->successful()) {
                $parcel = $response->json('data.parcels.0');
                if (is_array($parcel)) {
                    return $this->okFromShipmentParcel($parcel);
                }
            }
        }

        $data = SendcloudIntegrazione::decode($spedizione);
        $parcel = $data['announce_response']['data']['parcels'][0] ?? null;
        if (is_array($parcel)) {
            return $this->okFromShipmentParcel($parcel);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array{ok: bool, stato: string|null, evento_at: Carbon|null, response: array<string, mixed>|null, errore: string|null}
     */
    private function okFromTrackingBody(array $body): array
    {
        $evento = $this->ultimoEvento($body);
        $stato = $this->statoDaEvento($evento);
        $eventoAt = $this->parseData($evento['event_at'] ?? $body['updated_at'] ?? null);

        return [
            'ok' => true,
            'stato' => $stato !== '' ? $stato : null,
            'evento_at' => $eventoAt,
            'response' => $body,
            'errore' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $parcel
     * @return array{ok: bool, stato: string|null, evento_at: Carbon|null, response: array<string, mixed>|null, errore: string|null}
     */
    private function okFromShipmentParcel(array $parcel): array
    {
        $status = is_array($parcel['status'] ?? null) ? $parcel['status'] : [];
        $stato = trim((string) ($status['message'] ?? $status['code'] ?? ''));
        $eventoAt = $this->parseData($parcel['updated_at'] ?? $parcel['created_at'] ?? null);

        return [
            'ok' => true,
            'stato' => $stato !== '' ? $stato : null,
            'evento_at' => $eventoAt,
            'response' => $parcel,
            'errore' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function ultimoEvento(array $body): array
    {
        $events = $body['events'] ?? null;
        if (! is_array($events) || $events === []) {
            return [];
        }

        usort($events, static function (array $a, array $b): int {
            $ta = strtotime((string) ($a['event_at'] ?? '')) ?: 0;
            $tb = strtotime((string) ($b['event_at'] ?? '')) ?: 0;

            return $tb <=> $ta;
        });

        $first = $events[0] ?? null;

        return is_array($first) ? $first : [];
    }

    /**
     * @param  array<string, mixed>  $evento
     */
    private function statoDaEvento(array $evento): string
    {
        foreach (['status_description', 'description', 'status_code', 'phase'] as $key) {
            $value = trim((string) ($evento[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function parseData(mixed $value): ?Carbon
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

    /**
     * @return array{ok: false, stato: null, evento_at: null, response: null, errore: string}
     */
    private function errore(string $messaggio): array
    {
        return [
            'ok' => false,
            'stato' => null,
            'evento_at' => null,
            'response' => null,
            'errore' => $messaggio,
        ];
    }
}
