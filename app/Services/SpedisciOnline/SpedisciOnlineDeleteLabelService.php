<?php

namespace App\Services\SpedisciOnline;

/**
 * Payload per POST /shipping/delete (doc: https://apidocs.spedisci.online/api/delete).
 */
class SpedisciOnlineDeleteLabelService
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>|null  null se mancano identificativi
     */
    public function buildPayload(array $input): ?array
    {
        $payload = [];

        $trackingNumber = trim((string) ($input['delete_shipment_id'] ?? ''));
        if ($trackingNumber !== '') {
            $payload['trackingNumber'] = $trackingNumber;
        }

        $incrementId = trim((string) ($input['delete_increment_id'] ?? ''));
        if ($incrementId !== '' && ctype_digit($incrementId)) {
            $payload['increment_id'] = (int) $incrementId;
        }

        return $payload === [] ? null : $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function decodeCustomPayload(?string $json): ?array
    {
        $json = trim((string) $json);
        if ($json === '') {
            return null;
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }
}
