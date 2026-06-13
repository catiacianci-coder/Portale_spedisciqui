<?php

namespace App\Support;

/**
 * Normalizza righe carrello/ordine: array puri (niente stdClass), pacco e destinatario da snapshot/fallback.
 */
class RigaCarrelloOrdine
{
    public static function normalizza(mixed $it): array
    {
        $it = json_decode(json_encode($it), true);
        if (! is_array($it)) {
            return [];
        }

        $ind = $it['indirizzi'] ?? null;
        if (is_string($ind)) {
            $decoded = json_decode($ind, true);
            $ind = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($ind)) {
            $ind = [];
        }

        $part = $ind['partenza'] ?? null;
        if (is_string($part)) {
            $decoded = json_decode($part, true);
            $part = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($part)) {
            $part = [];
        }
        $telRoot = trim((string) ($ind['telefono'] ?? ''));
        $emailRoot = trim((string) ($ind['email'] ?? ''));
        if ($telRoot !== '' && trim((string) ($part['telefono'] ?? '')) === '') {
            $part['telefono'] = $telRoot;
        }
        if ($emailRoot !== '' && trim((string) ($part['email'] ?? '')) === '') {
            $part['email'] = $emailRoot;
        }
        $viaP = trim((string) ($part['via'] ?? ''));
        $numP = trim((string) ($part['numero'] ?? ''));
        if (trim((string) ($part['indirizzo'] ?? '')) === '' && ($viaP !== '' || $numP !== '')) {
            $part['indirizzo'] = IndirizzoSpedizioneSnapshot::componeIndirizzo($viaP, $numP);
        }
        $ind['partenza'] = $part;

        $dest = $ind['destinazione'] ?? null;
        if (is_string($dest)) {
            $decoded = json_decode($dest, true);
            $dest = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($dest)) {
            $dest = [];
        }

        $n = trim((string) ($dest['nome'] ?? ''));
        $c = trim((string) ($dest['cognome'] ?? ''));
        $nome = trim($n.' '.$c);
        if ($nome === '') {
            $nome = trim((string) ($dest['nome_destinatario'] ?? ''));
        }
        if ($nome === '') {
            $nome = trim((string) ($it['nome_destinatario_linea'] ?? ''));
        }
        if ($nome === '') {
            $nome = trim((string) ($dest['nome_cognome'] ?? ''));
        }
        if ($nome === '') {
            $nome = trim((string) ($dest['ragione_sociale'] ?? ''));
        }
        if ($nome !== '') {
            $dest['nome_destinatario'] = $nome;
        }
        $viaD = trim((string) ($dest['via'] ?? ''));
        $numD = trim((string) ($dest['numero'] ?? ''));
        if (trim((string) ($dest['indirizzo'] ?? '')) === '' && ($viaD !== '' || $numD !== '')) {
            $dest['indirizzo'] = IndirizzoSpedizioneSnapshot::componeIndirizzo($viaD, $numD);
        }

        $ind['destinazione'] = $dest;
        $it['indirizzi'] = $ind;

        $pacco = $it['dati_pacco'] ?? null;
        if (is_string($pacco)) {
            $decoded = json_decode($pacco, true);
            $pacco = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($pacco)) {
            $pacco = [];
        }

        $in = $it['preventivo_input'] ?? null;
        if (! is_array($in)) {
            $in = [];
        }

        $fill = function (string $fromKey, string $toKey) use (&$pacco, $in, $it): void {
            $cur = $pacco[$toKey] ?? null;
            if ($cur !== null && $cur !== '' && is_numeric($cur)) {
                return;
            }
            $v = $in[$fromKey] ?? $it[$fromKey] ?? null;
            if ($v === null || $v === '') {
                return;
            }
            $f = self::parseNumero($v);
            if ($f !== null) {
                $pacco[$toKey] = $f;
            }
        };

        $fill('peso', 'peso_kg');
        $fill('altezza', 'altezza_cm');
        $fill('larghezza', 'larghezza_cm');
        $fill('spessore', 'spessore_cm');

        $it['dati_pacco'] = $pacco;

        return $it;
    }

    /** @return array<string, float|null> */
    public static function paccoPerSpedizione(array $it): array
    {
        $p = is_array($it['dati_pacco'] ?? null) ? $it['dati_pacco'] : [];

        return [
            'peso_kg' => isset($p['peso_kg']) && $p['peso_kg'] !== '' ? (float) $p['peso_kg'] : null,
            'altezza_cm' => isset($p['altezza_cm']) && $p['altezza_cm'] !== '' ? (float) $p['altezza_cm'] : null,
            'larghezza_cm' => isset($p['larghezza_cm']) && $p['larghezza_cm'] !== '' ? (float) $p['larghezza_cm'] : null,
            'spessore_cm' => isset($p['spessore_cm']) && $p['spessore_cm'] !== '' ? (float) $p['spessore_cm'] : null,
        ];
    }

    public static function parseNumero(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_int($v) || is_float($v)) {
            return round((float) $v, 4);
        }
        $s = str_replace(' ', '', trim((string) $v));
        $s = str_replace(',', '.', $s);

        return is_numeric($s) ? round((float) $s, 4) : null;
    }
}
