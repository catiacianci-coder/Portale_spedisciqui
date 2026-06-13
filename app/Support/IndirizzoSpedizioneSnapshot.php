<?php

namespace App\Support;

/**
 * Struttura canonica mittente/destinatario per sessione preventivo e per {@see \App\Models\spedizione} JSON.
 *
 * Chiavi principali: nome, cognome, cap, comune, provincia, indirizzo (via + civico), note.
 * Per mittente e destinatario: telefono ed email quando presenti.
 */
final class IndirizzoSpedizioneSnapshot
{
    public static function componeIndirizzo(string $via, string $numero): string
    {
        $v = trim($via);
        $n = trim($numero);

        return trim($v.($v !== '' && $n !== '' ? ' ' : '').$n);
    }

    /**
     * @param  array<string, mixed>  $partenza
     * @param  array<string, mixed>  $indRoot
     */
    public static function mittentePerDatabase(array $partenza, array $indRoot): array
    {
        $tel = trim((string) ($partenza['telefono'] ?? $indRoot['telefono'] ?? ''));
        $email = trim((string) ($partenza['email'] ?? $indRoot['email'] ?? ''));
        $via = trim((string) ($partenza['via'] ?? ''));
        $num = trim((string) ($partenza['numero'] ?? ''));
        $indirizzo = trim((string) ($partenza['indirizzo'] ?? ''));
        if ($indirizzo === '') {
            $indirizzo = self::componeIndirizzo($via, $num);
        }

        $pv = self::normalizzaProvincia($partenza['provincia'] ?? '');
        $denomImp = trim((string) ($partenza['denominazione_impresa'] ?? $partenza['denominazione_ragione_sociale'] ?? ''));

        $out = [
            'nome' => trim((string) ($partenza['nome'] ?? '')),
            'cognome' => trim((string) ($partenza['cognome'] ?? '')),
            'cap' => trim((string) ($partenza['cap'] ?? '')),
            'comune' => trim((string) ($partenza['comune'] ?? '')),
            'provincia' => $pv,
            'indirizzo' => $indirizzo,
            'telefono' => $tel,
            'email' => $email,
            'note' => trim((string) ($partenza['note'] ?? '')),
            'via' => $via !== '' ? $via : null,
            'numero' => $num !== '' ? $num : null,
        ];
        if ($denomImp !== '') {
            $out['denominazione_impresa'] = $denomImp;
        }

        return $out;
    }

    /** @param  array<string, mixed>  $dest */
    public static function destinatarioPerDatabase(array $dest): array
    {
        $via = trim((string) ($dest['via'] ?? ''));
        $num = trim((string) ($dest['numero'] ?? ''));
        $indirizzo = trim((string) ($dest['indirizzo'] ?? ''));
        if ($indirizzo === '') {
            $indirizzo = self::componeIndirizzo($via, $num);
        }

        $nome = trim((string) ($dest['nome'] ?? ''));
        $cognome = trim((string) ($dest['cognome'] ?? ''));
        if ($nome === '' && $cognome === '') {
            $legacy = trim((string) ($dest['nome_destinatario'] ?? ''));
            if ($legacy !== '') {
                $nome = $legacy;
            }
        }

        $pv = self::normalizzaProvincia($dest['provincia'] ?? '');
        $tel = trim((string) ($dest['telefono'] ?? ''));
        $email = trim((string) ($dest['email'] ?? ''));
        $denomImp = trim((string) ($dest['denominazione_impresa'] ?? $dest['denominazione_ragione_sociale'] ?? ''));

        $out = [
            'nome' => $nome,
            'cognome' => $cognome,
            'cap' => trim((string) ($dest['cap'] ?? '')),
            'comune' => trim((string) ($dest['comune'] ?? '')),
            'provincia' => $pv,
            'indirizzo' => $indirizzo,
            'telefono' => $tel,
            'email' => $email,
            'note' => trim((string) ($dest['note'] ?? '')),
            'via' => $via !== '' ? $via : null,
            'numero' => $num !== '' ? $num : null,
            'nome_destinatario' => trim($nome.' '.$cognome) !== '' ? trim($nome.' '.$cognome) : null,
        ];
        if ($denomImp !== '') {
            $out['denominazione_impresa'] = $denomImp;
        }

        return $out;
    }

    private static function normalizzaProvincia(mixed $v): string
    {
        $s = strtoupper(trim((string) $v));

        return strlen($s) >= 2 ? substr($s, 0, 2) : $s;
    }
}
