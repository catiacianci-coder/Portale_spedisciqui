<?php

namespace App\Services\SpedisciOnline;

use App\Services\ParametriApiConfig;
use App\Support\PiattaformaCorriere;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SpedisciOnlineClient
{
    public function __construct(
        private readonly string $tenant = 'eamulti',
    ) {}

    public static function forPiattaforma(?string $piattaforma): self
    {
        $tenant = PiattaformaCorriere::tenantSpedisciOnline($piattaforma) ?? 'eamulti';

        return new self($tenant);
    }

    public function tenant(): string
    {
        return $this->tenant;
    }

    public function isConfigured(): bool
    {
        return ParametriApiConfig::spedisciOnlineApiKey($this->tenant) !== '';
    }

    /** @deprecated Usare isConfigured() sull'istanza o forPiattaforma(). */
    public static function isConfiguredFor(?string $piattaforma): bool
    {
        return self::forPiattaforma($piattaforma)->isConfigured();
    }

    public function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->withToken(ParametriApiConfig::spedisciOnlineApiKey($this->tenant))
            ->acceptJson()
            ->asJson()
            ->timeout(ParametriApiConfig::spedisciOnlineTimeout());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function post(string $path, array $payload = []): Response
    {
        return $this->http()->post(ltrim($path, '/'), $payload);
    }

    public function get(string $path): Response
    {
        return $this->http()->get(ltrim($path, '/'));
    }

    public function baseUrl(): string
    {
        return ParametriApiConfig::spedisciOnlineApiBase($this->tenant);
    }
}
