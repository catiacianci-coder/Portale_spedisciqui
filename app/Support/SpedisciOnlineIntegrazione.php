<?php

namespace App\Support;

use App\Models\spedizione;
use App\Models\stato_spedizione;
use App\Services\SpedisciOnline\SpedisciOnlineEtichettaPdfService;

/**
 * Lettura/scrittura dati integrazione Spedisci.online (file sidecar per spedizione).
 */
final class SpedisciOnlineIntegrazione
{
    private static function path(spedizione $spedizione): string
    {
        $dir = storage_path('app/spedizioni_integrazioni');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir.DIRECTORY_SEPARATOR.$spedizione->id.'.json';
    }

    /**
     * @return array<string, mixed>
     */
    public static function decode(mixed $raw): array
    {
        if ($raw instanceof spedizione) {
            $path = self::path($raw);
            if (! is_file($path)) {
                return [];
            }
            $decoded = json_decode((string) file_get_contents($path), true);

            return is_array($decoded) ? $decoded : [];
        }

        if (is_array($raw)) {
            return $raw;
        }
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function encode(spedizione $spedizione, array $data): void
    {
        file_put_contents(
            self::path($spedizione),
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        );
    }

    /**
     * Payload per POST /shipping/delete (tracking e id restituiti da create).
     *
     * @return array<string, mixed>
     */
    public static function payloadDelete(spedizione $spedizione): array
    {
        $data = self::decode($spedizione);
        $response = is_array($data['response'] ?? null) ? $data['response'] : [];
        $payload = [];

        foreach (['increment_id', 'id', 'shipmentId', 'shipment_id', 'labelId', 'label_id', 'shippingId', 'shipping_id'] as $key) {
            $val = $response[$key] ?? (is_array($response['data'] ?? null) ? ($response['data'][$key] ?? null) : null);
            if ($val !== null && $val !== '') {
                if ($key === 'shipmentId' || $key === 'shipment_id') {
                    $payload['increment_id'] = (int) $val;
                }
                $payload[$key] = $val;
            }
        }

        $tracking = trim((string) ($spedizione->tracking ?? $data['tracking'] ?? ''));
        if ($tracking !== '') {
            $payload['tracking'] = $tracking;
        }

        if ($payload === [] && isset($response['data']) && is_array($response['data'])) {
            return $response['data'];
        }

        return $payload;
    }

    /**
     * Etichetta annullata su Spedisci.online (API delete ok) o spedizione in attesa di rimborso / rimborsata.
     */
    public static function etichettaCancellata(spedizione $spedizione): bool
    {
        $data = self::decode($spedizione);
        if (trim((string) ($data['deleted_at'] ?? '')) !== '') {
            return true;
        }

        if (! $spedizione->ldv_emessa_il || $spedizione->esiste_integrazione) {
            return false;
        }

        return in_array((int) $spedizione->spedizione_stato_id, [
            stato_spedizione::ANNULLATA,
            stato_spedizione::RIMBORSATA,
        ], true);
    }

    public static function etichettaStampabile(spedizione $spedizione): bool
    {
        if (self::etichettaCancellata($spedizione)) {
            return false;
        }

        return $spedizione->esiste_integrazione
            || trim((string) $spedizione->etiqueta_pdf_path) !== '';
    }

    /**
     * @param  array<string, mixed>  $deleteResponse
     */
    public static function segnaEliminata(spedizione $spedizione, int $httpStatus, array $deleteResponse): void
    {
        app(SpedisciOnlineEtichettaPdfService::class)->rimuovi($spedizione);

        $data = self::decode($spedizione);
        $data['deleted_at'] = now()->toIso8601String();
        $data['delete_http_status'] = $httpStatus;
        $data['delete_response'] = $deleteResponse;

        self::encode($spedizione, $data);
        $spedizione->forceFill([
            'esiste_integrazione' => false,
        ])->saveQuietly();
    }
}
