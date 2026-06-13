<?php

namespace App\Services\SpedisciOnline;

use App\Models\comune;
use App\Models\spedizione;
use App\Support\SpedizioneCampiPersistenza;
use Illuminate\Http\Client\Response;

class SpedisciOnlineRatesService
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function buildPayload(array $input): array
    {
        $capFrom = strtoupper(trim((string) ($input['cap_origine'] ?? '')));
        $capTo = strtoupper(trim((string) ($input['cap_destino'] ?? '')));
        $cityFrom = trim((string) ($input['citta_origine'] ?? ''));
        $cityTo = trim((string) ($input['citta_destino'] ?? ''));

        $fromMeta = $this->resolveCapMeta($capFrom, $cityFrom);
        $toMeta = $this->resolveCapMeta($capTo, $cityTo);

        return [
            'packages' => [[
                'length' => (float) ($input['lunghezza'] ?? 30),
                'width' => (float) ($input['larghezza'] ?? 20),
                'height' => (float) ($input['altezza'] ?? 15),
                'weight' => (float) ($input['peso'] ?? 1),
            ]],
            'shipFrom' => [
                'name' => trim((string) ($input['mittente_nome'] ?? 'Mittente test')),
                'company' => trim((string) ($input['mittente_azienda'] ?? 'Spedisciqui')),
                'street1' => trim((string) ($input['mittente_indirizzo'] ?? 'Via test 1')),
                'street2' => '',
                'city' => $fromMeta['city'],
                'state' => $fromMeta['state'],
                'postalCode' => $capFrom,
                'country' => 'IT',
                'phone' => trim((string) ($input['mittente_telefono'] ?? '0612345678')),
                'email' => trim((string) ($input['mittente_email'] ?? 'mittente@test.local')),
            ],
            'shipTo' => [
                'name' => trim((string) ($input['destinatario_nome'] ?? 'Destinatario test')),
                'company' => trim((string) ($input['destinatario_azienda'] ?? '')),
                'street1' => trim((string) ($input['destinatario_indirizzo'] ?? 'Via test 2')),
                'street2' => '',
                'city' => $toMeta['city'],
                'state' => $toMeta['state'],
                'postalCode' => $capTo,
                'country' => 'IT',
                'phone' => trim((string) ($input['destinatario_telefono'] ?? '0212345678')),
                'email' => trim((string) ($input['destinatario_email'] ?? 'destinatario@test.local')),
            ],
            'notes' => trim((string) ($input['note'] ?? 'Preventivo test portale')),
            'insuranceValue' => (float) ($input['valore_assicurazione'] ?? 0),
            'codValue' => (float) ($input['contrassegno'] ?? 0),
            'accessoriServices' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function fetchRates(array $input, ?string $piattaforma = null): Response
    {
        $client = SpedisciOnlineClient::forPiattaforma($piattaforma);

        return $client->post('/shipping/rates', $this->buildPayload($input));
    }

    /**
     * Input API da sessione preventivo (CAP/comuni; indirizzi reali se già compilati).
     *
     * @param  array<string, mixed>  $preventivo
     * @param  array<string, mixed>|null  $indirizzi
     * @return array<string, mixed>
     */
    public function buildInputFromPreventivo(array $preventivo, ?array $indirizzi = null): array
    {
        $input = is_array($preventivo['input'] ?? null) ? $preventivo['input'] : [];
        $origine = is_array($preventivo['origine'] ?? null) ? $preventivo['origine'] : [];
        $destino = is_array($preventivo['destino'] ?? null) ? $preventivo['destino'] : [];
        $indirizzi = is_array($indirizzi) ? $indirizzi : (is_array($preventivo['indirizzi'] ?? null) ? $preventivo['indirizzi'] : []);

        $partenza = is_array($indirizzi['partenza'] ?? null) ? $indirizzi['partenza'] : [];
        $destinazione = is_array($indirizzi['destinazione'] ?? null) ? $indirizzi['destinazione'] : [];

        $mittNome = trim((string) (($partenza['nome'] ?? '').' '.($partenza['cognome'] ?? '')));
        $destNome = trim((string) (($destinazione['nome'] ?? '').' '.($destinazione['cognome'] ?? '')));

        return [
            'cap_origine' => (string) ($input['cap_origine'] ?? $partenza['cap'] ?? ''),
            'citta_origine' => (string) ($origine['comune'] ?? $partenza['comune'] ?? ''),
            'cap_destino' => (string) ($input['cap_destino'] ?? $destinazione['cap'] ?? ''),
            'citta_destino' => (string) ($destino['comune'] ?? $destinazione['comune'] ?? ''),
            'peso' => (float) ($input['peso'] ?? 1),
            'lunghezza' => (float) ($input['spessore'] ?? 30),
            'larghezza' => (float) ($input['larghezza'] ?? 20),
            'altezza' => (float) ($input['altezza'] ?? 15),
            'mittente_nome' => $mittNome !== '' ? $mittNome : 'Mittente',
            'mittente_azienda' => trim((string) ($partenza['denominazione_impresa'] ?? $partenza['denominazione_ragione_sociale'] ?? '')),
            'mittente_indirizzo' => trim((string) ($partenza['indirizzo'] ?? '')),
            'mittente_telefono' => trim((string) ($partenza['telefono'] ?? $indirizzi['telefono'] ?? '')),
            'mittente_email' => trim((string) ($partenza['email'] ?? $indirizzi['email'] ?? '')),
            'destinatario_nome' => $destNome !== '' ? $destNome : 'Destinatario',
            'destinatario_azienda' => trim((string) ($destinazione['denominazione_impresa'] ?? '')),
            'destinatario_indirizzo' => trim((string) ($destinazione['indirizzo'] ?? '')),
            'destinatario_telefono' => trim((string) ($destinazione['telefono'] ?? '')),
            'destinatario_email' => trim((string) ($destinazione['email'] ?? '')),
            'note' => 'Preventivo portale Spedisciqui',
            'valore_assicurazione' => 0,
            'contrassegno' => 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildInputFromSpedizione(spedizione $spedizione): array
    {
        $mitt = SpedizioneCampiPersistenza::mittenteArray($spedizione);
        $dest = SpedizioneCampiPersistenza::destinatarioArray($spedizione);
        $pacco = SpedizioneCampiPersistenza::paccoArray($spedizione);

        $mittNome = trim((string) (($mitt['nome'] ?? '').' '.($mitt['cognome'] ?? '')));

        return [
            'cap_origine' => (string) ($mitt['cap'] ?? $spedizione->cap_o ?? ''),
            'citta_origine' => (string) ($mitt['comune'] ?? $spedizione->citta_o ?? ''),
            'cap_destino' => (string) ($dest['cap'] ?? $spedizione->cap_d ?? ''),
            'citta_destino' => (string) ($dest['comune'] ?? $spedizione->citta_d ?? ''),
            'peso' => (float) ($pacco['peso_kg'] ?? $spedizione->peso ?? 1),
            'lunghezza' => (float) ($pacco['spessore_cm'] ?? $spedizione->spessore ?? 30),
            'larghezza' => (float) ($pacco['larghezza_cm'] ?? $spedizione->larghezza ?? 20),
            'altezza' => (float) ($pacco['altezza_cm'] ?? $spedizione->altezza ?? 15),
            'mittente_nome' => $mittNome !== '' ? $mittNome : 'Mittente',
            'mittente_azienda' => trim((string) ($mitt['denominazione_impresa'] ?? '')),
            'mittente_indirizzo' => trim((string) ($mitt['indirizzo'] ?? '')),
            'mittente_telefono' => trim((string) ($mitt['telefono'] ?? '')),
            'mittente_email' => trim((string) ($mitt['email'] ?? '')),
            'destinatario_nome' => trim((string) (($dest['nome'] ?? '').' '.($dest['cognome'] ?? ''))) ?: 'Destinatario',
            'destinatario_azienda' => trim((string) ($dest['denominazione_impresa'] ?? '')),
            'destinatario_indirizzo' => trim((string) ($dest['indirizzo'] ?? '')),
            'destinatario_telefono' => trim((string) ($dest['telefono'] ?? '')),
            'destinatario_email' => trim((string) ($dest['email'] ?? '')),
            'note' => trim((string) ($mitt['note'] ?? $dest['note'] ?? 'Ordine Spedisciqui')),
            'valore_assicurazione' => 0,
            'contrassegno' => 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $preventivo
     * @return array<string, mixed>
     */
    public function probeRatesForPreventivo(array $preventivo, ?string $piattaforma): array
    {
        $client = SpedisciOnlineClient::forPiattaforma($piattaforma);

        if (! $client->isConfigured()) {
            return [
                'configured' => false,
                'tenant' => $client->tenant(),
                'api_base' => $client->baseUrl(),
                'error' => 'API key mancante per tenant '.$client->tenant().' (vedi .env).',
            ];
        }

        $input = $this->buildInputFromPreventivo($preventivo);
        $payload = $this->buildPayload($input);
        $response = $client->post('/shipping/rates', $payload);
        $ratesList = $this->parseRatesFromResponse($response);

        $error = null;
        if (! $response->successful()) {
            $error = 'Errore HTTP '.$response->status().' da Spedisci.online';
        } elseif ($ratesList === []) {
            $error = 'Risposta OK ma elenco tariffe vuoto ([]).';
        }

        return [
            'configured' => true,
            'tenant' => $client->tenant(),
            'api_base' => $client->baseUrl(),
            'http_status' => $response->status(),
            'payload' => $payload,
            'raw_body' => $response->body(),
            'rates' => $ratesList,
            'error' => $error,
            'input' => $input,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function parseRatesFromResponse(Response $response): array
    {
        $body = $response->json();

        if (! is_array($body)) {
            return [];
        }

        if (array_is_list($body)) {
            return array_values(array_filter($body, 'is_array'));
        }

        foreach (['rates', 'data', 'results', 'carriers'] as $key) {
            if (isset($body[$key]) && is_array($body[$key]) && array_is_list($body[$key])) {
                return array_values(array_filter($body[$key], 'is_array'));
            }
        }

        return [];
    }

    /**
     * @return array{city: string, state: string}
     */
    private function resolveCapMeta(string $cap, string $fallbackCity): array
    {
        if ($cap !== '') {
            $row = comune::query()
                ->where('cap', $cap)
                ->where('attivo', true)
                ->orderBy('id')
                ->first(['comune', 'provincia']);

            if ($row) {
                return [
                    'city' => (string) $row->comune,
                    'state' => strtoupper(substr((string) $row->provincia, 0, 2)),
                ];
            }
        }

        return [
            'city' => $fallbackCity !== '' ? $fallbackCity : 'Roma',
            'state' => 'RM',
        ];
    }
}
