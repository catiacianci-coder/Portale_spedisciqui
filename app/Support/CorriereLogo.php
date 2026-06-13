<?php

namespace App\Support;

/**
 * Risolve l'URL pubblico del logo corriere (stesse cartelle di PreventiviController).
 */
final class CorriereLogo
{
    private const DIRS = ['images/loghi_corrieri', 'loghi_corrieri'];

    public static function pubblico(int $idCorriere): ?string
    {
        foreach (self::DIRS as $dir) {
            foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
                $rel = $dir.'/'.$idCorriere.'.'.$ext;
                if (is_file(public_path($rel))) {
                    return asset($rel);
                }
            }
        }

        return null;
    }
}
