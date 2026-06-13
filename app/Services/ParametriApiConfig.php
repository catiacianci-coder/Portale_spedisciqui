<?php

namespace App\Services;

use App\Models\parametri_globali;
use App\Support\ParametriApi;

/**
 * Lettura configurazione API esterne da parametri_globalis (unica fonte operativa).
 */
final class ParametriApiConfig
{
    /** @var array<string, string> Solo per test automatizzati. */
    private static array $overrides = [];

    public static function setOverride(string $denominazione, string $value): void
    {
        self::$overrides[$denominazione] = $value;
    }

    public static function clearOverrides(): void
    {
        self::$overrides = [];
    }

    public static function get(string $denominazione): string
    {
        if (array_key_exists($denominazione, self::$overrides)) {
            return self::$overrides[$denominazione];
        }

        return parametri_globali::valoreTesto($denominazione);
    }

    public static function int(string $denominazione, int $default): int
    {
        $raw = self::get($denominazione);
        if ($raw !== '' && is_numeric($raw)) {
            return max(1, (int) $raw);
        }

        return $default;
    }

    public static function stripePublicKey(): string
    {
        return self::get(ParametriApi::STRIPE_PUBLIC_KEY);
    }

    public static function stripeSecretKey(): string
    {
        return self::get(ParametriApi::STRIPE_SECRET_KEY);
    }

    public static function stripeWebhookSecret(): string
    {
        return self::get(ParametriApi::STRIPE_WEBHOOK_SECRET);
    }

    public static function stripeCurrency(): string
    {
        $c = strtolower(self::get(ParametriApi::STRIPE_CURRENCY));

        return $c !== '' ? $c : 'eur';
    }

    public static function sendcloudPublicKey(): string
    {
        return self::get(ParametriApi::SENDCLOUD_PUBLIC_KEY);
    }

    public static function sendcloudSecretKey(): string
    {
        return self::get(ParametriApi::SENDCLOUD_SECRET_KEY);
    }

    public static function sendcloudApiBase(): string
    {
        $base = rtrim(self::get(ParametriApi::SENDCLOUD_API_BASE), '/');

        return $base !== '' ? $base : 'https://panel.sendcloud.sc/api/v3';
    }

    public static function sendcloudTimeout(): int
    {
        return self::int(ParametriApi::SENDCLOUD_TIMEOUT, 30);
    }

    public static function spedisciOnlineApiKey(string $tenant): string
    {
        return match ($tenant) {
            'liccardi' => self::get(ParametriApi::SPEDISCI_ONLINE_LICCARDI_API_KEY),
            default => self::get(ParametriApi::SPEDISCI_ONLINE_QUICK_API_KEY),
        };
    }

    public static function spedisciOnlineApiBase(string $tenant): string
    {
        $base = match ($tenant) {
            'liccardi' => self::get(ParametriApi::SPEDISCI_ONLINE_LICCARDI_API_BASE),
            default => self::get(ParametriApi::SPEDISCI_ONLINE_QUICK_API_BASE),
        };
        $base = rtrim($base, '/');

        if ($base !== '') {
            return $base;
        }

        return $tenant === 'liccardi'
            ? 'https://liccardi.spedisci.online/api/v2'
            : 'https://quicksrl.spedisci.online/api/v2';
    }

    public static function spedisciOnlineTimeout(): int
    {
        return self::int(ParametriApi::SPEDISCI_ONLINE_TIMEOUT, 30);
    }

    public static function liccardiTmsApiKey(): string
    {
        return self::get(ParametriApi::LICCARDI_TMS_API_KEY);
    }

    public static function liccardiTmsCompanyId(): string
    {
        return self::get(ParametriApi::LICCARDI_TMS_COMPANY_ID);
    }

    public static function liccardiTmsApiBase(): string
    {
        return rtrim(self::get(ParametriApi::LICCARDI_TMS_API_BASE), '/');
    }

    public static function liccardiTmsTimeout(): int
    {
        return self::int(ParametriApi::LICCARDI_TMS_TIMEOUT, 45);
    }

    public static function liccardiTmsWebhookHeader(): string
    {
        $h = self::get(ParametriApi::LICCARDI_TMS_WEBHOOK_HEADER);

        return $h !== '' ? $h : 'X-Liccardi-Webhook-Token';
    }

    public static function liccardiTmsWebhookToken(): string
    {
        return self::get(ParametriApi::LICCARDI_TMS_WEBHOOK_TOKEN);
    }

    public static function turnstileSiteKey(): string
    {
        return self::get(ParametriApi::TURNSTILE_SITE_KEY);
    }

    public static function turnstileSecretKey(): string
    {
        return self::get(ParametriApi::TURNSTILE_SECRET_KEY);
    }
}
