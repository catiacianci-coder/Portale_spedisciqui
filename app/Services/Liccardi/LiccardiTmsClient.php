<?php

namespace App\Services\Liccardi;

use App\Services\ParametriApiConfig;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class LiccardiTmsClient
{
    public static function isConfigured(): bool
    {
        return ParametriApiConfig::liccardiTmsApiKey() !== ''
            && ParametriApiConfig::liccardiTmsCompanyId() !== '';
    }

    public function baseUrl(): string
    {
        return ParametriApiConfig::liccardiTmsApiBase();
    }

    public function companyId(): string
    {
        return ParametriApiConfig::liccardiTmsCompanyId();
    }

    /**
     * Header inviati (API-KEY mascherata per log UI).
     *
     * @return array<string, string>
     */
    public function requestHeadersForDisplay(): array
    {
        $key = ParametriApiConfig::liccardiTmsApiKey();

        return [
            'API-KEY' => $this->maskSecret($key),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    public function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->withHeaders([
                'API-KEY' => ParametriApiConfig::liccardiTmsApiKey(),
                'Accept' => 'application/json',
            ])
            ->timeout(ParametriApiConfig::liccardiTmsTimeout());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function postJson(string $path, array $payload = [], array $query = []): Response
    {
        $req = $this->http()->asJson();
        $url = $this->buildPath($path, $query);

        return $req->post($url, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function putJson(string $path, array $payload = [], array $query = []): Response
    {
        $req = $this->http()->asJson();
        $url = $this->buildPath($path, $query);

        return $req->put($url, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function deleteJson(string $path, array $payload = []): Response
    {
        $req = $this->http()->asJson();

        return $req->delete($this->buildPath($path), $payload);
    }

    public function get(string $path, array $query = []): Response
    {
        return $this->http()->get($this->buildPath($path, $query));
    }

    public function fullUrl(string $path, array $query = []): string
    {
        $path = ltrim($path, '/');
        $base = $this->baseUrl();
        $url = $base.'/'.$path;
        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        return $url;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function buildPath(string $path, array $query = []): string
    {
        $path = ltrim($path, '/');
        if ($query === []) {
            return $path;
        }

        return $path.'?'.http_build_query($query);
    }

    private function maskSecret(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '(non configurata)';
        }
        if (strlen($value) <= 8) {
            return '****';
        }

        return substr($value, 0, 4).'…'.substr($value, -4);
    }
}
