<?php

namespace App\Support;

use App\Models\spedizione;
use Illuminate\Support\Facades\File;

/**
 * Lettere di vettura (PDF) su disco esterno: {data_path}/LdV/{anno}/{codice_interno}.pdf
 */
final class LdvStorage
{
    public static function rootPath(): string
    {
        return rtrim((string) config('spedisciqui.data_path'), '\\/');
    }

    public static function isLdVRelativePath(string $path): bool
    {
        $p = str_replace('\\', '/', trim($path));

        return str_starts_with($p, 'LdV/');
    }

    public static function relativePath(spedizione $spedizione): string
    {
        $anno = self::annoDaSpedizione($spedizione);
        $nome = self::nomeFile($spedizione);

        return 'LdV/'.$anno.'/'.$nome;
    }

    public static function absolutePath(string $relative): string
    {
        $relative = str_replace('\\', '/', ltrim($relative, '/'));

        return self::rootPath().DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    public static function salvaPdf(spedizione $spedizione, string $binary): ?string
    {
        if ($binary === '' || ! str_starts_with($binary, '%PDF')) {
            return null;
        }

        $relative = self::relativePath($spedizione);
        $absolute = self::absolutePath($relative);
        $dir = dirname($absolute);

        if (! is_dir($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        if (file_put_contents($absolute, $binary) === false) {
            return null;
        }

        return $relative;
    }

    public static function percorsoAssoluto(spedizione $spedizione): ?string
    {
        $relative = trim((string) $spedizione->etiqueta_pdf_path);
        if ($relative === '') {
            return null;
        }

        if (self::isLdVRelativePath($relative)) {
            $full = self::absolutePath($relative);

            return is_file($full) ? $full : null;
        }

        $legacy = storage_path('app/'.$relative);

        return is_file($legacy) ? $legacy : null;
    }

    public static function rimuoviFile(spedizione $spedizione): void
    {
        $relative = trim((string) $spedizione->etiqueta_pdf_path);
        if ($relative === '') {
            return;
        }

        if (self::isLdVRelativePath($relative)) {
            $full = self::absolutePath($relative);
            if (is_file($full)) {
                @unlink($full);
            }

            return;
        }

        $legacy = storage_path('app/'.$relative);
        if (is_file($legacy)) {
            @unlink($legacy);
        }
    }

    public static function annoDaSpedizione(spedizione $spedizione): string
    {
        $codice = trim((string) ($spedizione->codice_interno ?? ''));
        if (strlen($codice) >= 4 && ctype_digit(substr($codice, 0, 4))) {
            return substr($codice, 0, 4);
        }

        if ($spedizione->ldv_emessa_il !== null) {
            return $spedizione->ldv_emessa_il->format('Y');
        }

        if ($spedizione->created_at !== null) {
            return $spedizione->created_at->format('Y');
        }

        return date('Y');
    }

    public static function nomeFile(spedizione $spedizione): string
    {
        $codice = preg_replace('/[^A-Za-z0-9._-]+/', '', (string) ($spedizione->codice_interno ?? '')) ?? '';
        $codice = trim($codice, '_');

        if ($codice !== '') {
            return $codice.'.pdf';
        }

        return 'spedizione-'.$spedizione->id.'.pdf';
    }
}
