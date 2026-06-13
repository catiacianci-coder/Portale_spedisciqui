<?php

namespace App\Support;

/**
 * Denominazioni parametri_globalis per integrazioni API esterne.
 *
 * @phpstan-type DefinizioneParametroApi array{label: string, gruppo: string, env_legacy?: string, default?: string}
 */
final class ParametriApi
{
    // Stripe
    public const STRIPE_PUBLIC_KEY = 'stripe_public_key';

    public const STRIPE_SECRET_KEY = 'stripe_secret_key';

    public const STRIPE_WEBHOOK_SECRET = 'stripe_webhook_secret';

    public const STRIPE_CURRENCY = 'stripe_currency';

    // Sendcloud
    public const SENDCLOUD_PUBLIC_KEY = 'sendcloud_public_key';

    public const SENDCLOUD_SECRET_KEY = 'sendcloud_secret_key';

    public const SENDCLOUD_API_BASE = 'sendcloud_api_base';

    public const SENDCLOUD_TIMEOUT = 'sendcloud_timeout';

    // Spedisci.online — tenant Quick
    public const SPEDISCI_ONLINE_QUICK_API_KEY = 'spedisci_online_quick_api_key';

    public const SPEDISCI_ONLINE_QUICK_API_BASE = 'spedisci_online_quick_api_base';

    // Spedisci.online — tenant Liccardi
    public const SPEDISCI_ONLINE_LICCARDI_API_KEY = 'spedisci_online_liccardi_api_key';

    public const SPEDISCI_ONLINE_LICCARDI_API_BASE = 'spedisci_online_liccardi_api_base';

    public const SPEDISCI_ONLINE_TIMEOUT = 'spedisci_online_timeout';

    // Liccardi TMS diretto
    public const LICCARDI_TMS_API_KEY = 'liccardi_tms_api_key';

    public const LICCARDI_TMS_COMPANY_ID = 'liccardi_tms_company_id';

    public const LICCARDI_TMS_API_BASE = 'liccardi_tms_api_base';

    public const LICCARDI_TMS_TIMEOUT = 'liccardi_tms_timeout';

    public const LICCARDI_TMS_WEBHOOK_HEADER = 'liccardi_tms_webhook_header';

    public const LICCARDI_TMS_WEBHOOK_TOKEN = 'liccardi_tms_webhook_token';

    // Cloudflare Turnstile
    public const TURNSTILE_SITE_KEY = 'turnstile_site_key';

    public const TURNSTILE_SECRET_KEY = 'turnstile_secret_key';

    /**
     * @return array<string, DefinizioneParametroApi>
     */
    public static function definizioni(): array
    {
        return [
            self::STRIPE_PUBLIC_KEY => [
                'gruppo' => 'Stripe',
                'label' => 'Chiave pubblica Stripe (pk_live_… / pk_test_…)',
                'env_legacy' => 'STRIPE_PUBLIC_KEY',
            ],
            self::STRIPE_SECRET_KEY => [
                'gruppo' => 'Stripe',
                'label' => 'Chiave segreta Stripe (sk_live_… / sk_test_…)',
                'env_legacy' => 'STRIPE_SECRET_KEY',
            ],
            self::STRIPE_WEBHOOK_SECRET => [
                'gruppo' => 'Stripe',
                'label' => 'Segreto webhook Stripe (whsec_…)',
                'env_legacy' => 'STRIPE_WEBHOOK_SECRET',
            ],
            self::STRIPE_CURRENCY => [
                'gruppo' => 'Stripe',
                'label' => 'Valuta Stripe (es. eur)',
                'env_legacy' => 'STRIPE_CURRENCY',
                'default' => 'eur',
            ],
            self::SENDCLOUD_PUBLIC_KEY => [
                'gruppo' => 'Sendcloud',
                'label' => 'Sendcloud — public key',
                'env_legacy' => 'SENDCLOUD_PUBLIC_KEY',
            ],
            self::SENDCLOUD_SECRET_KEY => [
                'gruppo' => 'Sendcloud',
                'label' => 'Sendcloud — secret key',
                'env_legacy' => 'SENDCLOUD_SECRET_KEY',
            ],
            self::SENDCLOUD_API_BASE => [
                'gruppo' => 'Sendcloud',
                'label' => 'Sendcloud — URL base API v3',
                'env_legacy' => 'SENDCLOUD_API_V3_BASE',
                'default' => 'https://panel.sendcloud.sc/api/v3',
            ],
            self::SENDCLOUD_TIMEOUT => [
                'gruppo' => 'Sendcloud',
                'label' => 'Sendcloud — timeout richieste (secondi)',
                'env_legacy' => 'SENDCLOUD_TIMEOUT',
                'default' => '30',
            ],
            self::SPEDISCI_ONLINE_QUICK_API_KEY => [
                'gruppo' => 'Spedisci.online (Quick)',
                'label' => 'Spedisci.online Quick — API key',
                'env_legacy' => 'SPEDISCI_ONLINE_API_KEY',
            ],
            self::SPEDISCI_ONLINE_QUICK_API_BASE => [
                'gruppo' => 'Spedisci.online (Quick)',
                'label' => 'Spedisci.online Quick — URL base API',
                'env_legacy' => 'SPEDISCI_ONLINE_API_BASE',
                'default' => 'https://quicksrl.spedisci.online/api/v2',
            ],
            self::SPEDISCI_ONLINE_LICCARDI_API_KEY => [
                'gruppo' => 'Spedisci.online (Liccardi)',
                'label' => 'Spedisci.online Liccardi — API key',
                'env_legacy' => 'SPEDISCI_ONLINE_LICCARDI_API_KEY',
            ],
            self::SPEDISCI_ONLINE_LICCARDI_API_BASE => [
                'gruppo' => 'Spedisci.online (Liccardi)',
                'label' => 'Spedisci.online Liccardi — URL base API',
                'env_legacy' => 'SPEDISCI_ONLINE_LICCARDI_API_BASE',
                'default' => 'https://liccardi.spedisci.online/api/v2',
            ],
            self::SPEDISCI_ONLINE_TIMEOUT => [
                'gruppo' => 'Spedisci.online',
                'label' => 'Spedisci.online — timeout richieste (secondi)',
                'env_legacy' => 'SPEDISCI_ONLINE_TIMEOUT',
                'default' => '30',
            ],
            self::LICCARDI_TMS_API_KEY => [
                'gruppo' => 'Liccardi TMS',
                'label' => 'Liccardi TMS — API key',
                'env_legacy' => 'LICCARDI_TMS_API_KEY',
            ],
            self::LICCARDI_TMS_COMPANY_ID => [
                'gruppo' => 'Liccardi TMS',
                'label' => 'Liccardi TMS — codice cliente (company id)',
                'env_legacy' => 'LICCARDI_TMS_COMPANY_ID',
            ],
            self::LICCARDI_TMS_API_BASE => [
                'gruppo' => 'Liccardi TMS',
                'label' => 'Liccardi TMS — URL base API REST',
                'env_legacy' => 'LICCARDI_TMS_API_BASE',
                'default' => 'https://operativo.liccarditrasporti.com/ope/rest',
            ],
            self::LICCARDI_TMS_TIMEOUT => [
                'gruppo' => 'Liccardi TMS',
                'label' => 'Liccardi TMS — timeout richieste (secondi)',
                'env_legacy' => 'LICCARDI_TMS_TIMEOUT',
                'default' => '45',
            ],
            self::LICCARDI_TMS_WEBHOOK_HEADER => [
                'gruppo' => 'Liccardi TMS',
                'label' => 'Liccardi TMS webhook — nome header autenticazione',
                'env_legacy' => 'LICCARDI_TMS_WEBHOOK_HEADER',
                'default' => 'X-Liccardi-Webhook-Token',
            ],
            self::LICCARDI_TMS_WEBHOOK_TOKEN => [
                'gruppo' => 'Liccardi TMS',
                'label' => 'Liccardi TMS webhook — token segreto',
                'env_legacy' => 'LICCARDI_TMS_WEBHOOK_TOKEN',
            ],
            self::TURNSTILE_SITE_KEY => [
                'gruppo' => 'Cloudflare Turnstile',
                'label' => 'Turnstile — site key (widget registrazione)',
                'env_legacy' => 'TURNSTILE_SITE_KEY',
            ],
            self::TURNSTILE_SECRET_KEY => [
                'gruppo' => 'Cloudflare Turnstile',
                'label' => 'Turnstile — secret key (verifica server)',
                'env_legacy' => 'TURNSTILE_SECRET_KEY',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function denominazioni(): array
    {
        return array_keys(self::definizioni());
    }

    /**
     * @return array<string, list<string>>
     */
    public static function denominazioniPerGruppo(): array
    {
        $out = [];
        foreach (self::definizioni() as $denom => $meta) {
            $out[$meta['gruppo']][] = $denom;
        }

        return $out;
    }
}
