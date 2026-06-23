<?php

namespace App\Services\SpedisciOnline;

use App\Models\corriere;
use App\Models\spedizione;
use App\Support\SpedisciOnlineTrackingParser;

final class SpedisciOnlineTrackingService
{
    /**
     * @return array{ok: bool, stato: string|null, evento_at: \Carbon\Carbon|null, response: array<string, mixed>|null, errore: string|null}
     */
    public function consulta(spedizione $spedizione, corriere $corriere): array
    {
        $client = SpedisciOnlineClient::forPiattaforma($corriere->piattaforma);
        if (! $client->isConfigured()) {
            return $this->errore('API Spedisci.online non configurata.');
        }

        $tracking = trim((string) ($spedizione->tracking ?? ''));
        if ($tracking === '') {
            return $this->errore('Numero di tracking non disponibile.');
        }

        $path = 'tracking/'.rawurlencode($tracking);
        $response = $client->get($path);

        if (! $response->successful()) {
            $messaggio = trim((string) (
                $response->json('message')
                ?? $response->json('error')
                ?? $response->json('detail')
                ?? $response->body()
            ));

            return $this->errore(
                $messaggio !== ''
                    ? $messaggio
                    : 'Tracking Spedisci.online non disponibile (HTTP '.$response->status().').',
            );
        }

        $body = $response->json();
        if (! is_array($body)) {
            return $this->errore('Risposta tracking Spedisci.online non valida.');
        }

        $ultimo = SpedisciOnlineTrackingParser::ultimoEvento($body);
        $stato = SpedisciOnlineTrackingParser::etichettaCliente($ultimo['stato'], $ultimo['luogo']);

        if ($stato === '') {
            return $this->errore('Tracking disponibile ma senza testo stato dal corriere.');
        }

        return [
            'ok' => true,
            'stato' => $stato,
            'evento_at' => $ultimo['evento_at'],
            'response' => $body,
            'errore' => null,
        ];
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
