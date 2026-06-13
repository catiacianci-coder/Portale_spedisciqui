<?php

namespace App\Support;

use App\Models\spedizione;

/**
 * Copia in colonne scalari i dati presenti in mittente_json, destinatario_json e pacco_json.
 * Il JSON resta la fonte strutturata; le colonne servono per query, export e report.
 */
final class SpedizioneCampiScalariFromJson
{
    public static function applicaSuModello(spedizione $s): void
    {
        $m = is_array($s->mittente_json) ? $s->mittente_json : [];
        $d = is_array($s->destinatario_json) ? $s->destinatario_json : [];
        $p = is_array($s->pacco_json) ? $s->pacco_json : [];

        $s->forceFill(self::estrai($m, $d, $p));
    }

    /**
     * @param  array<string, mixed>  $mittente
     * @param  array<string, mixed>  $destinatario
     * @param  array<string, mixed>  $pacco
     * @return array<string, float|string|null>
     */
    public static function estrai(array $mittente, array $destinatario, array $pacco): array
    {
        $cittaM = self::str($mittente, 'comune') ?? self::str($mittente, 'citta');
        $cittaD = self::str($destinatario, 'comune') ?? self::str($destinatario, 'citta');

        return [
            'mittente_nome' => self::strAny($mittente, ['nome', 'first_name']),
            'mittente_cognome' => self::strAny($mittente, ['cognome', 'last_name']),
            'mittente_indirizzo' => self::strAny($mittente, ['indirizzo', 'via', 'street']),
            'mittente_numero' => self::strAny($mittente, ['numero', 'civico', 'street_number']),
            'mittente_cap' => self::str($mittente, 'cap'),
            'mittente_citta' => $cittaM,
            'mittente_provincia' => self::str($mittente, 'provincia'),
            'destinatario_nome' => self::strAny($destinatario, ['nome', 'first_name']),
            'destinatario_cognome' => self::strAny($destinatario, ['cognome', 'last_name']),
            'destinatario_indirizzo' => self::strAny($destinatario, ['indirizzo', 'via', 'street']),
            'destinatario_numero' => self::strAny($destinatario, ['numero', 'civico', 'street_number']),
            'destinatario_cap' => self::str($destinatario, 'cap'),
            'destinatario_citta' => $cittaD,
            'destinatario_provincia' => self::str($destinatario, 'provincia'),
            'pacco_peso_kg' => self::floatVal($pacco, 'peso_kg'),
            'pacco_altezza_cm' => self::floatVal($pacco, 'altezza_cm'),
            'pacco_larghezza_cm' => self::floatVal($pacco, 'larghezza_cm'),
            'pacco_spessore_cm' => self::floatVal($pacco, 'spessore_cm'),
        ];
    }

    /**
     * @param  array<string, mixed>  $a
     */
    private static function str(array $a, string $key): ?string
    {
        if (! array_key_exists($key, $a)) {
            return null;
        }
        $v = $a[$key];
        if ($v === null || $v === '') {
            return null;
        }

        $s = trim((string) $v);

        return $s !== '' ? $s : null;
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<int, string>  $keys
     */
    private static function strAny(array $a, array $keys): ?string
    {
        foreach ($keys as $k) {
            $v = self::str($a, $k);
            if ($v !== null) {
                return $v;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $a
     */
    private static function floatVal(array $a, string $key): ?float
    {
        if (! array_key_exists($key, $a)) {
            return null;
        }
        $v = $a[$key];
        if ($v === null || $v === '') {
            return null;
        }

        return is_numeric($v) ? round((float) $v, 4) : null;
    }
}
