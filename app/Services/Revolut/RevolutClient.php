<?php

namespace App\Services\Revolut;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class RevolutClient
{
    public static function isConfigured(): bool
    {
        return RevolutConfig::isConfigured();
    }

    public function http(): PendingRequest
    {
        return Http::baseUrl(RevolutConfig::apiBase())
            ->withToken(RevolutConfig::accessToken())
            ->acceptJson()
            ->asJson()
            ->timeout(RevolutConfig::timeout());
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
}
