<?php

namespace App\Support;

use App\Models\corriere;

final class CorriereTrackingUrl
{
    public static function perSpedizione(corriere $corriere, string $tracking): ?string
    {
        $tracking = trim($tracking);
        if ($tracking === '') {
            return null;
        }

        $template = trim((string) ($corriere->url_tracking ?? ''));
        if ($template === '') {
            return null;
        }

        if (str_contains($template, '{tracking}')) {
            return str_replace('{tracking}', rawurlencode($tracking), $template);
        }

        $separator = str_contains($template, '?') ? '&' : '?';

        return $template.$separator.'tracking='.rawurlencode($tracking);
    }
}
