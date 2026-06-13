<?php

namespace App\Support;

use App\Models\spedizione;

/**
 * Sidecar integrazione TMS Liccardi (spedizioneId, LDV, risposta create).
 */
final class LiccardiTmsIntegrazione
{
    private static function path(spedizione $spedizione): string
    {
        $dir = storage_path('app/spedizioni_integrazioni');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir.DIRECTORY_SEPARATOR.'liccardi_tms_'.$spedizione->id.'.json';
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

    public static function spedizioneId(spedizione $spedizione): ?int
    {
        if ((int) ($spedizione->id_shipment ?? 0) > 0) {
            return (int) $spedizione->id_shipment;
        }

        $data = self::decode($spedizione);
        $id = (int) ($data['spedizione_id'] ?? $data['spedizioneId'] ?? 0);

        return $id > 0 ? $id : null;
    }

    public static function courierLdv(spedizione $spedizione): ?string
    {
        $t = trim((string) ($spedizione->tracking ?? ''));
        if ($t !== '') {
            return $t;
        }

        $data = self::decode($spedizione);

        return trim((string) ($data['courier_ldv'] ?? $data['courierLdv'] ?? '')) ?: null;
    }

    /**
     * @param  array<string, mixed>|null  $responseJson
     */
    public static function salvaDopoCreate(spedizione $spedizione, ?array $responseJson, ?int $spedizioneId, ?string $courierLdv): void
    {
        $data = self::decode($spedizione);
        if ($spedizioneId !== null && $spedizioneId > 0) {
            $data['spedizione_id'] = $spedizioneId;
        }
        if ($courierLdv !== null && $courierLdv !== '') {
            $data['courier_ldv'] = $courierLdv;
        }
        if (is_array($responseJson)) {
            $data['create_response'] = $responseJson;
            $data['created_at'] = now()->toIso8601String();
        }
        self::encode($spedizione, $data);
    }

    public static function segnaEliminata(spedizione $spedizione, ?array $responseJson = null): void
    {
        $data = self::decode($spedizione);
        $data['deleted_at'] = now()->toIso8601String();
        if (is_array($responseJson)) {
            $data['delete_response'] = $responseJson;
        }
        self::encode($spedizione, $data);
    }

    public static function eliminataSuTms(spedizione $spedizione): bool
    {
        $data = self::decode($spedizione);

        return trim((string) ($data['deleted_at'] ?? '')) !== '';
    }

    /**
     * Append evento webhook tracking al sidecar. Ritorna true se duplicato (già registrato).
     *
     * @param  array<string, mixed>  $payload
     */
    public static function registraEventoWebhook(spedizione $spedizione, array $payload, ?\DateTimeInterface $eventDateTime): bool
    {
        $data = self::decode($spedizione);
        $events = is_array($data['tracking_events'] ?? null) ? $data['tracking_events'] : [];

        $fingerprint = self::eventoFingerprint($payload, $eventDateTime);
        foreach ($events as $existing) {
            if (! is_array($existing)) {
                continue;
            }
            if (($existing['fingerprint'] ?? '') === $fingerprint) {
                return true;
            }
        }

        $events[] = array_filter([
            'fingerprint' => $fingerprint,
            'received_at' => now()->toIso8601String(),
            'shipmentIdentifier' => $payload['shipmentIdentifier'] ?? null,
            'courierLdv' => $payload['courierLdv'] ?? null,
            'customerCode' => $payload['customerCode'] ?? null,
            'shipmentReference' => $payload['shipmentReference'] ?? null,
            'parcelIdentifier' => $payload['parcelIdentifier'] ?? null,
            'parcelReference' => $payload['parcelReference'] ?? null,
            'warehouseCode' => $payload['warehouseCode'] ?? null,
            'warehouseDescription' => $payload['warehouseDescription'] ?? null,
            'eventCode' => $payload['eventCode'] ?? null,
            'eventDescription' => $payload['eventDescription'] ?? null,
            'eventDateTime' => $eventDateTime?->format(\DateTimeInterface::ATOM)
                ?? ($payload['eventDateTime'] ?? null),
        ], static fn ($v) => $v !== null && $v !== '');

        $data['tracking_events'] = $events;
        $data['last_tracking_event_at'] = now()->toIso8601String();
        self::encode($spedizione, $data);

        return false;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function eventoFingerprint(array $payload, ?\DateTimeInterface $eventDateTime): string
    {
        $parts = [
            (string) ($payload['shipmentIdentifier'] ?? ''),
            trim((string) ($payload['courierLdv'] ?? '')),
            trim((string) ($payload['eventCode'] ?? '')),
            $eventDateTime?->format(\DateTimeInterface::ATOM) ?? trim((string) ($payload['eventDateTime'] ?? '')),
        ];

        return hash('sha256', implode('|', $parts));
    }
}
