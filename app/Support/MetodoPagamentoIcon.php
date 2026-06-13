<?php

namespace App\Support;

/**
 * Icona metodo di pagamento: public/images/metodi_pagamento/{id}.png (o altre estensioni).
 */
final class MetodoPagamentoIcon
{
    private const DIR = 'images/metodi_pagamento';

    public static function pubblico(int $metodoId): ?string
    {
        if ($metodoId < 1) {
            return null;
        }

        foreach (['png', 'jpg', 'jpeg', 'webp', 'svg'] as $ext) {
            $rel = self::DIR.'/'.$metodoId.'.'.$ext;
            if (is_file(public_path($rel))) {
                return asset($rel);
            }
        }

        return null;
    }
}
