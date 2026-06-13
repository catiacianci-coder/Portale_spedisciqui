<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TurnstileVerifier
{
    public static function isConfigured(): bool
    {
        return ParametriApiConfig::turnstileSiteKey() !== ''
            && ParametriApiConfig::turnstileSecretKey() !== '';
    }

    public function verify(?string $token, ?string $remoteIp = null): bool
    {
        if (! self::isConfigured()) {
            return true;
        }

        if ($token === null || $token === '') {
            return false;
        }

        $response = Http::timeout(10)->asForm()->post(
            'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            [
                'secret' => ParametriApiConfig::turnstileSecretKey(),
                'response' => $token,
                'remoteip' => $remoteIp ?? '',
            ]
        );

        if (! $response->successful()) {
            return false;
        }

        return (bool) ($response->json('success', false));
    }
}
