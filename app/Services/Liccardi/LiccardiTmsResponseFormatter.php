<?php

namespace App\Services\Liccardi;

use Illuminate\Http\Client\Response;

/**
 * Normalizza request/response TMS per UI test e servizi applicativi.
 */
final class LiccardiTmsResponseFormatter
{
    public function __construct(
        private readonly LiccardiTmsClient $client,
    ) {}

    /**
     * @param  array<string, mixed>|null  $payload
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function fromHttp(
        string $method,
        string $path,
        array $query,
        ?array $payload,
        Response $response,
        bool $extractIds = false,
    ): array {
        $body = $response->body();
        $contentType = (string) $response->header('Content-Type');
        $decoded = $this->tryDecodeJson($body, $contentType);
        $displayBody = $body;
        $bodyNote = null;
        $rawBodyBinary = null;

        if ($this->looksLikeBinary($body, $contentType)) {
            $len = strlen($body);
            $rawBodyBinary = $body;
            $bodyNote = "File ricevuto ({$len} byte). Content-Type: {$contentType}";
            $displayBody = $bodyNote;
            if (str_contains(strtolower($contentType), 'pdf') || str_starts_with($body, '%PDF')) {
                $displayBody .= "\n\nPDF etichetta (contenuto binario non mostrato).";
            }
        } elseif ($decoded !== null) {
            $pdfBinary = self::estraiPdfDaJson($decoded);
            if ($pdfBinary !== null) {
                $rawBodyBinary = $pdfBinary;
                $len = strlen($pdfBinary);
                $bodyNote = "PDF in pdfData (base64 decodificato, {$len} byte).";
                $displayBody = self::jsonPerDisplay($decoded);
            } else {
                $displayBody = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: $body;
            }
        }

        $hints = $extractIds ? LiccardiTmsProbeRunner::extractIds($decoded) : [];
        $errorMessage = null;
        if (! $response->successful()) {
            $errorMessage = $this->formatError($response->status(), $decoded, $body);
        } elseif (is_array($decoded) && isset($decoded['status']['code']) && (int) $decoded['status']['code'] !== 200) {
            $errorMessage = 'status.code = '.(int) $decoded['status']['code'];
            if (! empty($decoded['status']['message'])) {
                $errorMessage .= ': '.$decoded['status']['message'];
            }
        }

        return [
            'searched' => true,
            'probe' => null,
            'method' => $method,
            'path' => $path,
            'query' => $query,
            'url' => $this->client->fullUrl($path, $query),
            'requestHeaders' => $this->client->requestHeadersForDisplay(),
            'payload' => $payload,
            'httpStatus' => $response->status(),
            'contentType' => $contentType,
            'rawBody' => $displayBody,
            'rawBodyBinary' => $rawBodyBinary,
            'bodyNote' => $bodyNote,
            'errorMessage' => $errorMessage,
            'hints' => $hints,
            'ok' => $response->successful() && $errorMessage === null,
            'responseJson' => $decoded,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function tryDecodeJson(string $body, string $contentType): ?array
    {
        if ($body === '' || $this->looksLikeBinary($body, $contentType)) {
            return null;
        }
        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function looksLikeBinary(string $body, string $contentType): bool
    {
        $ct = strtolower($contentType);
        if (str_contains($ct, 'json')) {
            return false;
        }
        if (str_contains($ct, 'pdf') || str_contains($ct, 'octet-stream') || str_contains($ct, 'zpl')) {
            return true;
        }

        return str_starts_with($body, '%PDF');
    }

    /**
     * Liccardi TMS: GET etichette/pdf restituisce JSON con campo pdfData (base64).
     *
     * @param  array<string, mixed>  $decoded
     */
    public static function estraiPdfDaJson(array $decoded): ?string
    {
        foreach (['pdfData', 'pdf', 'labelData', 'etichetta', 'content'] as $key) {
            if (! isset($decoded[$key]) || ! is_string($decoded[$key])) {
                continue;
            }
            $raw = base64_decode($decoded[$key], true);
            if (is_string($raw) && $raw !== '' && str_starts_with($raw, '%PDF')) {
                return $raw;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private static function jsonPerDisplay(array $decoded): string
    {
        $copy = $decoded;
        foreach (['pdfData', 'pdf', 'labelData', 'etichetta', 'content'] as $key) {
            if (! isset($copy[$key]) || ! is_string($copy[$key])) {
                continue;
            }
            $len = strlen($copy[$key]);
            $copy[$key] = "[base64 PDF, {$len} caratteri — decodificato e salvato]";
        }

        return json_encode($copy, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '';
    }

    /**
     * @param  array<string, mixed>|null  $decoded
     */
    private function formatError(int $status, ?array $decoded, string $rawBody): string
    {
        if (is_array($decoded)) {
            $msg = trim((string) ($decoded['status']['message'] ?? $decoded['message'] ?? $decoded['error'] ?? ''));
            if ($msg !== '') {
                return "HTTP {$status}: {$msg}";
            }
        }

        $snippet = mb_substr(trim($rawBody), 0, 200);

        return $snippet !== ''
            ? "HTTP {$status}: {$snippet}"
            : "HTTP {$status} senza messaggio.";
    }
}
