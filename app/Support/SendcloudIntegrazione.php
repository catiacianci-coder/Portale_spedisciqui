<?php

namespace App\Support;

use App\Models\spedizione;

/**
 * Sidecar integrazione Sendcloud (shipment id, parcel id, risposta announce).
 */
final class SendcloudIntegrazione
{
    private static function path(spedizione $spedizione): string
    {
        $dir = storage_path('app/spedizioni_integrazioni');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir.DIRECTORY_SEPARATOR.'sendcloud_'.$spedizione->id.'.json';
    }

    /**
     * @return array<string, mixed>
     */
    public static function decode(spedizione $spedizione): array
    {
        $path = self::path($spedizione);
        if (! is_file($path)) {
            return [];
        }
        $decoded = json_decode((string) file_get_contents($path), true);

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

    public static function shipmentId(spedizione $spedizione): ?string
    {
        $db = trim((string) ($spedizione->id_shipment ?? ''));
        if ($db !== '') {
            return $db;
        }

        $data = self::decode($spedizione);
        $id = trim((string) ($data['shipment_id'] ?? $data['id'] ?? ''));

        return $id !== '' ? $id : null;
    }

    public static function parcelId(spedizione $spedizione): ?int
    {
        $data = self::decode($spedizione);
        $id = (int) ($data['parcel_id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    public static function tracking(spedizione $spedizione): ?string
    {
        $t = trim((string) ($spedizione->tracking ?? ''));
        if ($t !== '') {
            return $t;
        }

        $data = self::decode($spedizione);
        $fromSidecar = trim((string) ($data['tracking_number'] ?? ''));

        return $fromSidecar !== '' ? $fromSidecar : null;
    }

    /**
     * @param  array<string, mixed>|null  $responseJson
     */
    public static function salvaDopoAnnounce(
        spedizione $spedizione,
        ?array $responseJson,
        ?string $shipmentId,
        ?int $parcelId,
        ?string $tracking,
        ?string $labelUrl = null,
    ): void {
        $data = self::decode($spedizione);
        if ($shipmentId !== null && $shipmentId !== '') {
            $data['shipment_id'] = $shipmentId;
        }
        if ($parcelId !== null && $parcelId > 0) {
            $data['parcel_id'] = $parcelId;
        }
        if ($tracking !== null && $tracking !== '') {
            $data['tracking_number'] = $tracking;
        }
        if ($labelUrl !== null && $labelUrl !== '') {
            $data['label_url'] = $labelUrl;
        }
        if (is_array($responseJson)) {
            $data['announce_response'] = $responseJson;
            $data['created_at'] = now()->toIso8601String();
        }
        self::encode($spedizione, $data);
    }

    public static function segnaEliminata(spedizione $spedizione, ?array $responseJson = null): void
    {
        $data = self::decode($spedizione);
        $data['deleted_at'] = now()->toIso8601String();
        if (is_array($responseJson)) {
            $data['cancel_response'] = $responseJson;
        }
        self::encode($spedizione, $data);
    }

    public static function eliminataSuSendcloud(spedizione $spedizione): bool
    {
        $data = self::decode($spedizione);

        return trim((string) ($data['deleted_at'] ?? '')) !== '';
    }

    public static function etichettaCancellata(spedizione $spedizione): bool
    {
        return self::eliminataSuSendcloud($spedizione);
    }

    /**
     * @return array{
     *     shipment_id: string|null,
     *     request: array<string, mixed>|null,
     *     response: array<string, mixed>|null,
     *     http_status: int|null,
     *     error: string|null
     * }
     */
    public static function tracciaApiAnnounce(spedizione $spedizione): array
    {
        $data = self::decode($spedizione);
        $request = is_array($data['announce_request'] ?? null) ? $data['announce_request'] : null;
        $response = is_array($data['announce_response'] ?? null)
            ? $data['announce_response']
            : (is_array($data['last_response'] ?? null) ? $data['last_response'] : null);

        return [
            'shipment_id' => self::shipmentId($spedizione),
            'request' => $request,
            'response' => $response,
            'http_status' => isset($data['last_http_status']) ? (int) $data['last_http_status'] : null,
            'error' => trim((string) ($data['last_error'] ?? '')) ?: null,
        ];
    }

    public static function haTracciaApi(spedizione $spedizione): bool
    {
        $traccia = self::tracciaApiAnnounce($spedizione);

        return $traccia['request'] !== null
            || $traccia['response'] !== null
            || $traccia['error'] !== null;
    }

}
