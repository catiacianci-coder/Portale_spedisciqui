<?php

namespace App\Services\Liccardi;

use App\Models\spedizione;
use App\Support\LiccardiTmsIntegrazione;
use Carbon\Carbon;

final class LiccardiTmsTrackingService
{
    public function __construct(
        private readonly LiccardiTmsClient $client,
    ) {}

    /**
     * @return array{ok: bool, stato: string|null, evento_at: Carbon|null, response: array<string, mixed>|null, errore: string|null}
     */
    public function consulta(spedizione $spedizione): array
    {
        if (! $this->client->isConfigured()) {
            return $this->errore('API Liccardi TMS non configurata.');
        }

        $ldv = trim((string) (LiccardiTmsIntegrazione::courierLdv($spedizione) ?? $spedizione->tracking ?? ''));
        if ($ldv === '') {
            return $this->errore('Numero lettera di vettura non disponibile.');
        }

        $path = 'tracking/'.rawurlencode($ldv);
        $response = $this->client->get($path);

        if (! $response->successful()) {
            $messaggio = trim((string) ($response->json('message') ?? $response->json('error') ?? $response->body()));

            return $this->errore($messaggio !== '' ? $messaggio : 'Tracking Liccardi non disponibile (HTTP '.$response->status().').');
        }

        $body = $response->json();
        if (! is_array($body)) {
            return $this->errore('Risposta tracking Liccardi non valida.');
        }

        $evento = $this->ultimoEvento($body);
        $stato = $this->etichettaStato($evento, $body);
        $eventoAt = $this->parseData(
            $evento['eventDateTime'] ?? $evento['event_at'] ?? $evento['date'] ?? $body['eventDateTime'] ?? null,
        );

        return [
            'ok' => true,
            'stato' => $stato !== '' ? $stato : null,
            'evento_at' => $eventoAt,
            'response' => $body,
            'errore' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function ultimoEvento(array $body): array
    {
        foreach (['events', 'trackingEvents', 'eventi', 'tracking'] as $key) {
            $list = $body[$key] ?? null;
            if (! is_array($list) || $list === []) {
                continue;
            }

            if (array_is_list($list)) {
                $last = end($list);

                return is_array($last) ? $last : [];
            }
        }

        return $body;
    }

    /**
     * @param  array<string, mixed>  $evento
     * @param  array<string, mixed>  $body
     */
    private function etichettaStato(array $evento, array $body): string
    {
        $desc = trim((string) ($evento['eventDescription'] ?? $evento['description'] ?? $evento['statusDescription'] ?? $body['status'] ?? ''));
        $code = trim((string) ($evento['eventCode'] ?? $evento['code'] ?? $evento['statusCode'] ?? ''));

        if ($desc !== '' && $code !== '') {
            return $code.': '.$desc;
        }

        if ($desc !== '') {
            return $desc;
        }

        if ($code !== '') {
            return $code;
        }

        return '';
    }

    private function parseData(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{ok: false, stato: null, evento_at: null, response: null, errore: string}
     */
    private function errore(string $messaggio): array
    {
        return [
            'ok' => false,
            'stato' => null,
            'evento_at' => null,
            'response' => null,
            'errore' => $messaggio,
        ];
    }
}
