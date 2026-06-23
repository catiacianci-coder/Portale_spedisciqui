<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Normalizza le risposte GET /tracking/{ldv} Spedisci.online (stesso endpoint per SDA, GLS, Poste, …).
 * Formato documentato: TrackingDettaglio[] con Stato, Data, Luogo.
 */
final class SpedisciOnlineTrackingParser
{
    /**
     * @param  array<string, mixed>  $body
     * @return array{stato: string, luogo: string, evento_at: ?Carbon}
     */
    public static function ultimoEvento(array $body): array
    {
        $eventi = self::eventi($body);
        if ($eventi === []) {
            return ['stato' => '', 'luogo' => '', 'evento_at' => null];
        }

        $ultimo = self::scegliEventoPiuRecente($eventi);

        return [
            'stato' => $ultimo['status'],
            'luogo' => $ultimo['luogo'] ?? '',
            'evento_at' => self::parseData($ultimo['data'] ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return list<array{status: string, data: string, luogo?: string}>
     */
    public static function eventi(array $body): array
    {
        $dettaglio = self::listaTrackingDettaglio($body);
        if ($dettaglio !== []) {
            return $dettaglio;
        }

        foreach (['events', 'trackingEvents', 'eventi', 'history'] as $key) {
            $parsed = self::eventiDaLista($body[$key] ?? null);
            if ($parsed !== []) {
                return $parsed;
            }
        }

        $parsed = self::eventiDaLista($body['tracking'] ?? null);
        if ($parsed !== []) {
            return $parsed;
        }

        $stato = self::testoStato($body);
        if ($stato !== '') {
            return [[
                'status' => $stato,
                'data' => self::testoData($body),
                'luogo' => self::testoLuogo($body),
            ]];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return list<array{status: string, data: string, luogo?: string}>
     */
    private static function listaTrackingDettaglio(array $body): array
    {
        $list = $body['TrackingDettaglio'] ?? $body['trackingDettaglio'] ?? null;
        if (! is_array($list) || $list === []) {
            return [];
        }

        if (! array_is_list($list)) {
            $list = [$list];
        }

        $out = [];
        foreach ($list as $row) {
            if (! is_array($row)) {
                continue;
            }
            $stato = self::testoStato($row);
            if ($stato === '') {
                continue;
            }
            $out[] = [
                'status' => $stato,
                'data' => self::testoData($row),
                'luogo' => self::testoLuogo($row),
            ];
        }

        return $out;
    }

    /**
     * @param  mixed  $list
     * @return list<array{status: string, data: string, luogo?: string}>
     */
    private static function eventiDaLista(mixed $list): array
    {
        if (! is_array($list) || $list === []) {
            return [];
        }

        if (! array_is_list($list)) {
            $list = [$list];
        }

        $out = [];
        foreach ($list as $row) {
            if (! is_array($row)) {
                continue;
            }
            $stato = self::testoStato($row);
            if ($stato === '') {
                continue;
            }
            $out[] = [
                'status' => $stato,
                'data' => self::testoData($row),
                'luogo' => self::testoLuogo($row),
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function testoStato(array $row): string
    {
        foreach ([
            'Stato',
            'stato',
            'eventDescription',
            'description',
            'statusDescription',
            'status_description',
            'status',
            'message',
            'messaggio',
        ] as $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }
            $testo = self::normalizzaTesto($row[$key]);
            if ($testo !== '') {
                return $testo;
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function testoData(array $row): string
    {
        foreach ([
            'Data',
            'data',
            'eventDateTime',
            'event_at',
            'date',
            'updated_at',
            'timestamp',
        ] as $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }
            $testo = trim((string) $row[$key]);
            if ($testo !== '') {
                return $testo;
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function testoLuogo(array $row): string
    {
        foreach (['Luogo', 'luogo', 'location', 'place', 'city'] as $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }
            $testo = trim((string) $row[$key]);
            if ($testo !== '') {
                return $testo;
            }
        }

        return '';
    }

    private static function normalizzaTesto(mixed $value): string
    {
        if (is_bool($value) || is_array($value) || is_object($value)) {
            return '';
        }

        $testo = trim((string) $value);
        if ($testo === '') {
            return '';
        }

        if (preg_match('/^\d+$/', $testo)) {
            return '';
        }

        return $testo;
    }

    /**
     * @param  list<array{status: string, data: string, luogo?: string}>  $eventi
     * @return array{status: string, data: string, luogo?: string}
     */
    private static function scegliEventoPiuRecente(array $eventi): array
    {
        $best = $eventi[0];
        $bestTs = self::parseData($best['data'] ?? '');

        foreach (array_slice($eventi, 1) as $ev) {
            $ts = self::parseData($ev['data'] ?? '');
            if ($ts !== null && ($bestTs === null || $ts->gt($bestTs))) {
                $best = $ev;
                $bestTs = $ts;
            }
        }

        return $best;
    }

    private static function parseData(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})(?:\s+(\d{1,2}:\d{2}(?::\d{2})?))?/', $value, $m)) {
                $time = $m[4] ?? '00:00:00';
                if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
                    $time .= ':00';
                }

                return Carbon::createFromFormat('d/m/Y H:i:s', $m[1].'/'.$m[2].'/'.$m[3].' '.$time);
            }

            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /** Testo stato per cliente (solo {@see $stato}, senza luogo). */
    public static function etichettaCliente(string $stato, string $luogo = ''): string
    {
        return trim($stato);
    }
}
