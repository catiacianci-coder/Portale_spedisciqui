<?php

namespace App\Support;

/**
 * Etichette per elenco spedizioni cliente (JSON mittente/destinatario).
 */
class SpedizioneClienteDati
{
    /**
     * @param  array<string, mixed>|null  $dest
     */
    public static function nomeDestinatario(?array $dest): string
    {
        if ($dest === null || $dest === []) {
            return '';
        }
        $n = trim((string) ($dest['nome'] ?? ''));
        $c = trim((string) ($dest['cognome'] ?? ''));
        $nome = trim($n.' '.$c);
        if ($nome === '') {
            $nome = trim((string) ($dest['nome_destinatario'] ?? ''));
        }
        if ($nome === '') {
            $nome = trim((string) ($dest['nome_cognome'] ?? ''));
        }
        if ($nome === '') {
            $nome = trim((string) ($dest['ragione_sociale'] ?? ''));
        }

        return $nome;
    }

    /**
     * Nome e cognome del destinatario (campi separati), con fallback agli altri campi se mancanti.
     *
     * @param  array<string, mixed>|null  $dest
     */
    public static function nomeECognomeDestinatario(?array $dest): string
    {
        if ($dest === null || $dest === []) {
            return '';
        }
        $n = trim((string) ($dest['nome'] ?? ''));
        $c = trim((string) ($dest['cognome'] ?? ''));
        if ($n !== '' && $c !== '') {
            return $n.' '.$c;
        }
        if ($n !== '') {
            return $n;
        }
        if ($c !== '') {
            return $c;
        }

        return self::nomeDestinatario($dest);
    }

    /**
     * @param  array<string, mixed>|null  $json
     */
    public static function cap(?array $json): string
    {
        if ($json === null || $json === []) {
            return '';
        }

        return trim((string) ($json['cap'] ?? ''));
    }
}
