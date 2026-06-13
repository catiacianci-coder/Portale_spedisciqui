<?php

namespace App\Services\Sendcloud;

use App\Services\ParametriApiConfig;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SendcloudClient
{
    public static function isConfigured(): bool
    {
        return ParametriApiConfig::sendcloudPublicKey() !== ''
            && ParametriApiConfig::sendcloudSecretKey() !== '';
    }

    public function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->withBasicAuth(
                ParametriApiConfig::sendcloudPublicKey(),
                ParametriApiConfig::sendcloudSecretKey(),
            )
            ->acceptJson()
            ->asJson()
            ->timeout(ParametriApiConfig::sendcloudTimeout());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function post(string $path, array $payload = []): Response
    {
        return $this->http()->post(ltrim($path, '/'), $payload);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function get(string $path, array $query = []): Response
    {
        return $this->http()->get(ltrim($path, '/'), $query);
    }

    public function delete(string $path, array $payload = []): Response
    {
        return $this->http()->delete(ltrim($path, '/'), $payload);
    }

    /**
     * GET documento (es. etichetta PDF) — URL assoluto o path relativo API v3.
     */
    public function getDocument(string $urlOrPath): Response
    {
        $url = trim($urlOrPath);
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return Http::withBasicAuth(
                ParametriApiConfig::sendcloudPublicKey(),
                ParametriApiConfig::sendcloudSecretKey(),
            )
                ->accept('application/pdf')
                ->timeout(ParametriApiConfig::sendcloudTimeout())
                ->get($url);
        }

        return $this->http()
            ->accept('application/pdf')
            ->get(ltrim($url, '/'));
    }

    public function baseUrl(): string
    {
        return ParametriApiConfig::sendcloudApiBase();
    }
}
