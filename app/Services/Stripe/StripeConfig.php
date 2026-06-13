<?php

namespace App\Services\Stripe;

use App\Services\ParametriApiConfig;

final class StripeConfig
{
    public static function isConfigured(): bool
    {
        $key = self::secretKey();

        return $key !== ''
            && ! str_contains($key, 'replace_me')
            && str_starts_with($key, 'sk_');
    }

    public static function secretKey(): string
    {
        return ParametriApiConfig::stripeSecretKey();
    }

    public static function publicKey(): string
    {
        return ParametriApiConfig::stripePublicKey();
    }

    public static function webhookSecret(): string
    {
        return ParametriApiConfig::stripeWebhookSecret();
    }

    public static function currency(): string
    {
        return ParametriApiConfig::stripeCurrency();
    }
}
