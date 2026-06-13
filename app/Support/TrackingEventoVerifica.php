<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DomainException;

/** Confronto eventi tracking per elegibilità correzione / rimborso etichetta. */
final class TrackingEventoVerifica
{
    /**
     * @param  array<string, mixed>  $responseBody
     * @return array<int, array{status: string, data: string}>
     */
    public static function eventiDaResponseSendcloud(array $responseBody): array
    {
        $events = $responseBody['events'] ?? null;
        if (! is_array($events) || $events === []) {
            $stato = trim((string) (
                $responseBody['status']['message']
                ?? $responseBody['status']['code']
                ?? $responseBody['status_description']
                ?? ''
            ));
            if ($stato === '') {
                return [];
            }

            return [['status' => $stato, 'data' => (string) ($responseBody['updated_at'] ?? '')]];
        }

        $out = [];
        foreach ($events as $ev) {
            if (! is_array($ev)) {
                continue;
            }
            $status = trim((string) (
                $ev['status_description']
                ?? $ev['description']
                ?? $ev['status_code']
                ?? $ev['phase']
                ?? ''
            ));
            if ($status === '') {
                continue;
            }
            $out[] = [
                'status' => $status,
                'data' => (string) ($ev['event_at'] ?? $ev['updated_at'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $responseBody
     * @return array<int, array{status: string, data: string}>
     */
    public static function eventiDaResponseLiccardi(array $responseBody): array
    {
        foreach (['events', 'trackingEvents', 'eventi', 'tracking'] as $key) {
            $list = $responseBody[$key] ?? null;
            if (! is_array($list) || $list === []) {
                continue;
            }

            if (! array_is_list($list)) {
                $list = [$list];
            }

            $out = [];
            foreach ($list as $ev) {
                if (! is_array($ev)) {
                    continue;
                }
                $status = trim((string) (
                    $ev['eventDescription']
                    ?? $ev['description']
                    ?? $ev['statusDescription']
                    ?? $ev['status']
                    ?? ''
                ));
                if ($status === '') {
                    continue;
                }
                $out[] = [
                    'status' => $status,
                    'data' => (string) ($ev['eventDateTime'] ?? $ev['event_at'] ?? $ev['date'] ?? ''),
                ];
            }

            if ($out !== []) {
                return $out;
            }
        }

        $stato = trim((string) ($responseBody['status'] ?? $responseBody['statusDescription'] ?? ''));
        if ($stato === '') {
            return [];
        }

        return [['status' => $stato, 'data' => (string) ($responseBody['eventDateTime'] ?? '')]];
    }

    /**
     * @param  array<int, array{status: string, data: string}>  $eventi
     * @param  list<string>  $fragmentiBloqueio
     */
    public static function assertUltimoEventoNonSpedito(array $eventi, array $fragmentiBloqueio, string $messaggioBlocco): void
    {
        $status = self::statusPiuRecente($eventi);
        if ($status === null || $status === '') {
            return;
        }

        $normStatus = self::normalizza($status);
        foreach ($fragmentiBloqueio as $frag) {
            if (! is_string($frag)) {
                continue;
            }
            $f = self::normalizza($frag);
            if ($f !== '' && str_contains($normStatus, $f)) {
                throw new DomainException($messaggioBlocco);
            }
        }
    }

    /**
     * @param  array<int, array{status: string, data: string}>  $eventi
     */
    private static function statusPiuRecente(array $eventi): ?string
    {
        if ($eventi === []) {
            return null;
        }

        $best = null;
        $bestTs = null;

        foreach ($eventi as $ev) {
            $status = trim((string) ($ev['status'] ?? ''));
            if ($status === '') {
                continue;
            }
            $dataRaw = trim((string) ($ev['data'] ?? ''));
            try {
                $ts = $dataRaw !== '' ? CarbonImmutable::parse($dataRaw) : null;
            } catch (\Throwable) {
                $ts = null;
            }

            if ($best === null) {
                $best = $status;
                $bestTs = $ts;

                continue;
            }

            if ($ts !== null && ($bestTs === null || $ts->gt($bestTs))) {
                $best = $status;
                $bestTs = $ts;
            }
        }

        return $best;
    }

    private static function normalizza(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        if (class_exists(\Normalizer::class)) {
            $s = \Normalizer::normalize($s, \Normalizer::FORM_D);
            $s = preg_replace('/\p{Mn}/u', '', $s) ?? $s;
        }

        return $s;
    }

    /** @return list<string> */
    public static function fragmentiBloccoCorrecao(): array
    {
        $raw = config('etichetta.correcao_tracking_status_blocca', []);

        return is_array($raw)
            ? array_values(array_filter($raw, static fn ($v): bool => is_string($v) && trim($v) !== ''))
            : [];
    }
}
