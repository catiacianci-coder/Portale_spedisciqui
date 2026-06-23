<?php

namespace App\Services\SpedisciOnline;

use App\Models\comune;
use App\Models\spedizione;
use App\Support\SpedisciOnlineEamultiContratti;
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

        $mittenteAzienda = trim((string) ($input['mittente_azienda'] ?? ''));
        $destinatarioAzienda = trim((string) ($input['destinatario_azienda'] ?? ''));

        $shipFrom = [
            'name' => trim((string) ($input['mittente_nome'] ?? '')),
            'street1' => trim((string) ($input['mittente_indirizzo'] ?? '')),
            'street2' => '',
            'city' => $fromMeta['city'],
            'state' => $fromMeta['state'],
            'postalCode' => $capFrom,
            'country' => 'IT',
            'phone' => trim((string) ($input['mittente_telefono'] ?? '')),
            'email' => trim((string) ($input['mittente_email'] ?? '')),
        ];
        if ($mittenteAzienda !== '') {
            $shipFrom['company'] = $mittenteAzienda;
        }

        $shipTo = [
            'name' => trim((string) ($input['destinatario_nome'] ?? '')),
            'street1' => trim((string) ($input['destinatario_indirizzo'] ?? '')),
            'street2' => '',
            'city' => $toMeta['city'],
            'state' => $toMeta['state'],
            'postalCode' => $capTo,
            'country' => 'IT',
            'phone' => trim((string) ($input['destinatario_telefono'] ?? '')),
            'email' => trim((string) ($input['destinatario_email'] ?? '')),
        ];
        if ($destinatarioAzienda !== '') {
            $shipTo['company'] = $destinatarioAzienda;
        }

        return [
            'packages' => [[
                'length' => (float) ($input['lunghezza'] ?? 30),
                'width' => (float) ($input['larghezza'] ?? 20),
                'height' => (float) ($input['altezza'] ?? 15),
                'weight' => (float) ($input['peso'] ?? 1),
            ]],
            'shipFrom' => $shipFrom,
            'shipTo' => $shipTo,
            'notes' => trim((string) ($input['note'] ?? '')),
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
            'destinatario_azienda' => trim((string) ($destinazione['denominazione_impresa'] ?? $destinazione['denominazione_ragione_sociale'] ?? $destinazione['ragione_sociale'] ?? '')),
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
            'mittente_azienda' => trim((string) ($mitt['denominazione_impresa'] ?? $mitt['ragione_sociale'] ?? '')),
            'mittente_indirizzo' => trim((string) ($mitt['indirizzo'] ?? '')),
            'mittente_telefono' => trim((string) ($mitt['telefono'] ?? '')),
            'mittente_email' => trim((string) ($mitt['email'] ?? '')),
            'destinatario_nome' => trim((string) (($dest['nome'] ?? '').' '.($dest['cognome'] ?? ''))) ?: 'Destinatario',
            'destinatario_azienda' => trim((string) ($dest['denominazione_impresa'] ?? $dest['ragione_sociale'] ?? '')),
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
     * Quotazione per singolo corriere (carrierCode + contractCode) da sessione preventivo.
     *
     * @param  array<string, mixed>  $preventivo
     * @return array<string, mixed>
     */
    public function quoteForPreventivo(array $preventivo, \App\Models\corriere $corriere): array
    {
        $piattaforma = $corriere->piattaforma;
        $client = SpedisciOnlineClient::forPiattaforma($piattaforma);

        if (! $client->isConfigured()) {
            return [
                'configured' => false,
                'tenant' => $client->tenant(),
                'api_base' => $client->baseUrl(),
                'error' => 'API key mancante per tenant '.$client->tenant().'.',
            ];
        }

        $carrierCode = trim((string) ($corriere->carrier_code ?? ''));
        $contractCode = SpedisciOnlineEamultiContratti::contractCodeForCorriere($corriere);

        if ($carrierCode === '' || $contractCode === '') {
            return [
                'configured' => true,
                'tenant' => $client->tenant(),
                'api_base' => $client->baseUrl(),
                'carrier_code' => $carrierCode,
                'contract_code' => $contractCode,
                'error' => 'carrier_code o codice_servizio (contractCode) mancante sul corriere.',
            ];
        }

        $input = $this->buildInputFromPreventivo($preventivo);
        $payload = $this->buildPayload($input);
        $payload['carrierCode'] = $carrierCode;
        $payload['contractCode'] = $contractCode;

        $response = $client->post('/shipping/rates', $payload);
        $ratesList = $this->parseRatesFromResponse($response);
        $matched = $this->matchRateForCorriere($ratesList, $corriere, $carrierCode, $contractCode);
        $priceAmount = is_array($matched) ? $this->extractPriceFromRate($matched) : null;

        $error = null;
        if (! $response->successful()) {
            $error = 'Errore HTTP '.$response->status().' da Spedisci.online';
        } elseif ($priceAmount === null) {
            $error = 'Quotazione Spedisci.online non disponibile per questo contratto.';
        }

        return [
            'configured' => true,
            'tenant' => $client->tenant(),
            'api_base' => $client->baseUrl(),
            'http_status' => $response->status(),
            'carrier_code' => $carrierCode,
            'contract_code' => $contractCode,
            'payload' => $payload,
            'raw_body' => $response->body(),
            'rates' => $ratesList,
            'quote' => $priceAmount !== null ? [
                'price_amount' => $priceAmount,
                'currency' => (string) ($matched['currency'] ?? 'EUR'),
                'rate' => $matched,
            ] : null,
            'error' => $error,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $ratesList
     * @return array<string, mixed>|null
     */
    public function matchRateForCorriere(
        array $ratesList,
        \App\Models\corriere $corriere,
        string $carrierCode,
        string $contractCode,
    ): ?array {
        $carrierCode = trim($carrierCode);
        $contractCode = trim($contractCode);
        $contractNameHint = $this->contractNameHintForCorriere($corriere);

        foreach ($ratesList as $rate) {
            if (! is_array($rate)) {
                continue;
            }

            $rateContract = trim((string) ($rate['contractCode'] ?? $rate['contract_code'] ?? ''));
            if ($rateContract !== '' && strcasecmp($rateContract, $contractCode) === 0) {
                return $rate;
            }
        }

        if ($contractNameHint !== '') {
            foreach ($ratesList as $rate) {
                if (! is_array($rate)) {
                    continue;
                }
                $name = strtoupper(trim((string) ($rate['contract_name'] ?? $rate['contractName'] ?? '')));
                if ($name !== '' && str_contains($name, strtoupper($contractNameHint))) {
                    return $rate;
                }
            }
        }

        $carrierMatches = array_values(array_filter(
            $ratesList,
            static function ($rate) use ($carrierCode): bool {
                if (! is_array($rate)) {
                    return false;
                }
                $rateCarrier = trim((string) ($rate['carrierCode'] ?? $rate['carrier_code'] ?? ''));

                return $rateCarrier !== '' && strcasecmp($rateCarrier, $carrierCode) === 0;
            },
        ));

        if ($contractNameHint !== '' && $carrierMatches !== []) {
            foreach ($carrierMatches as $rate) {
                $name = strtoupper(trim((string) ($rate['contract_name'] ?? $rate['contractName'] ?? '')));
                if ($name !== '' && str_contains($name, strtoupper($contractNameHint))) {
                    return $rate;
                }
            }
        }

        if (count($carrierMatches) === 1) {
            return $carrierMatches[0];
        }

        return null;
    }

    private function contractNameHintForCorriere(\App\Models\corriere $corriere): string
    {
        return match ((int) $corriere->id) {
            SpedisciOnlineEamultiContratti::CORRIERE_SDA_M => 'SDA M',
            SpedisciOnlineEamultiContratti::CORRIERE_GLS_STANDARD => 'GLS STANDARD',
            SpedisciOnlineEamultiContratti::CORRIERE_GLS_LIGHT => 'GLS LIGHT',
            SpedisciOnlineEamultiContratti::CORRIERE_UPS => 'UPS',
            default => trim((string) ($corriere->nome_servizio ?? '')),
        };
    }

    /**
     * @param  array<string, mixed>  $rate
     */
    public function extractPriceFromRate(array $rate): ?float
    {
        foreach ([
            'total_price',
            'totalPrice',
            'weight_price',
            'total',
            'price',
            'amount',
            'shippingCost',
            'cost',
            'importo',
            'tariffa',
        ] as $key) {
            if (! array_key_exists($key, $rate) || $rate[$key] === null || $rate[$key] === '') {
                continue;
            }

            $value = (float) $rate[$key];
            if ($value > 0) {
                return round($value, 2);
            }
        }

        return null;
    }

    /**
     * Preventivi eamulti per SDA M, GLS Standard, GLS Light e UPS (pagina test).
     *
     * @param  array<string, mixed>  $input  cap_origine, cap_destino, peso, spessore, larghezza, altezza
     * @return list<array<string, mixed>>
     */
    public function quoteTreCorrieriEamulti(array $input): array
    {
        $preventivo = $this->buildPreventivoStubFromInput($input);
        $out = [];

        foreach (SpedisciOnlineEamultiContratti::corrieriIdsPreventivo() as $corriereId) {
            $corriere = \App\Models\corriere::query()->find($corriereId);
            if (! $corriere) {
                $out[] = [
                    'corriere_id' => $corriereId,
                    'nome' => 'Corriere #'.$corriereId,
                    'ok' => false,
                    'error' => 'Corriere non trovato in tabella corrieres.',
                ];

                continue;
            }

            $quote = $this->quoteForPreventivo($preventivo, $corriere);
            $price = data_get($quote, 'quote.price_amount');

            $out[] = [
                'corriere_id' => $corriereId,
                'nome' => (string) ($corriere->nome_visualizzato ?: $corriere->nome_servizio),
                'carrier_code' => $quote['carrier_code'] ?? $corriere->carrier_code,
                'contract_code' => $quote['contract_code'] ?? null,
                'ok' => $price !== null && (float) $price > 0,
                'price_amount' => $price !== null ? (float) $price : null,
                'currency' => data_get($quote, 'quote.currency', 'EUR'),
                'http_status' => $quote['http_status'] ?? null,
                'error' => $quote['error'] ?? null,
                'rates' => $quote['rates'] ?? null,
                'payload' => $quote['payload'] ?? null,
                'raw_body' => $quote['raw_body'] ?? null,
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function buildPreventivoStubFromInput(array $input): array
    {
        $capOrigine = str_pad(trim((string) ($input['cap_origine'] ?? '')), 5, '0', STR_PAD_LEFT);
        $capDestino = str_pad(trim((string) ($input['cap_destino'] ?? '')), 5, '0', STR_PAD_LEFT);

        $origineComune = $capOrigine !== ''
            ? comune::query()->where('cap', $capOrigine)->where('attivo', true)->orderBy('id')->value('comune')
            : null;
        $destinoComune = $capDestino !== ''
            ? comune::query()->where('cap', $capDestino)->where('attivo', true)->orderBy('id')->value('comune')
            : null;

        return [
            'input' => [
                'cap_origine' => $capOrigine,
                'cap_destino' => $capDestino,
                'peso' => (float) ($input['peso'] ?? 1),
                'spessore' => (float) ($input['spessore'] ?? 30),
                'larghezza' => (float) ($input['larghezza'] ?? 20),
                'altezza' => (float) ($input['altezza'] ?? 15),
            ],
            'origine' => ['comune' => (string) ($origineComune ?? 'Roma')],
            'destino' => ['comune' => (string) ($destinoComune ?? 'Milano')],
        ];
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
