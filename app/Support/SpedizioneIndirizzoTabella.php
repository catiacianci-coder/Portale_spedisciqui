<?php

namespace App\Support;

use App\Models\spedizione;

/** Righe indirizzo destinatario nelle tabelle spedizione (lettere di vettura, ordini). */
final class SpedizioneIndirizzoTabella
{
    public static function destinatarioNome(spedizione $s): string
    {
        $nome = trim((string) trim((string) (($s->nome_d ?? '').' '.($s->sobrenome_d ?? ''))));
        if ($nome !== '') {
            return $nome;
        }

        return trim((string) ($s->razione_sociale_d ?? ''));
    }

    public static function destinatarioVia(spedizione $s): string
    {
        return trim(implode(' ', array_filter([
            trim((string) ($s->indirizzo_d ?? '')),
            trim((string) ($s->numero_d ?? '')),
        ])));
    }

    public static function destinatarioLocalita(spedizione $s): string
    {
        $cap = trim((string) ($s->cap_d ?? ''));
        $citta = trim((string) ($s->citta_d ?? ''));
        $prov = trim((string) ($s->stato_d ?? ''));
        $nazione = trim((string) ($s->frazione_d ?? ''));
        if ($nazione === '') {
            $nazione = 'Italia';
        }

        $cittaProv = $citta;
        if ($prov !== '') {
            $cittaProv = $cittaProv !== ''
                ? $cittaProv.' ('.$prov.')'
                : '('.$prov.')';
        }

        $parts = array_values(array_filter([
            $cap !== '' ? $cap : null,
            $cittaProv !== '' ? $cittaProv : null,
            $nazione !== '' ? $nazione : null,
        ], static fn ($v) => $v !== null && $v !== ''));

        return implode(' - ', $parts);
    }

    /** @return list<string> */
    public static function destinatarioRighe(spedizione $s): array
    {
        return array_values(array_filter([
            self::destinatarioVia($s),
            self::destinatarioLocalita($s),
        ], static fn (string $r): bool => $r !== ''));
    }
}
