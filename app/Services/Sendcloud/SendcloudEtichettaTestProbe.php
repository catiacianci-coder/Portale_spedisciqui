<?php

namespace App\Services\Sendcloud;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;

/**
 * Probe API Sendcloud per pagina di test: preventivo + announce etichetta.
 */
final class SendcloudEtichettaTestProbe
{
    public function __construct(
        private readonly SendcloudClient $client,
        private readonly SendcloudShippingOptionsService $shippingOptions,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function runPreventivo(array $input): array
    {
        $payload = $this->buildQuotePayload($input);
        $response = $this->shippingOptions->listWithQuotes($payload);

        return $this->wrapResponse('POST', '/shipping-options', $payload, $response);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function runAnnounce(array $input): array
    {
        $resolved = $this->resolveShippingOptionCode($input);
        if (($resolved['error'] ?? null) !== null) {
            return [
                'ok' => false,
                'httpStatus' => null,
                'errorMessage' => (string) $resolved['error'],
                'payload' => $resolved['quote_payload'] ?? [],
                'rawBody' => $resolved['quote_raw'] ?? null,
                'method' => 'POST',
                'url' => $this->client->baseUrl().'/shipments/announce',
                'hints' => [],
            ];
        }

        $wantedCode = trim((string) ($input['shipping_option_code'] ?? ''));
        $input['shipping_option_code'] = (string) ($resolved['code'] ?? '');
        if ((int) ($input['contract_id'] ?? 0) < 1 && ($resolved['contract_id'] ?? null) !== null) {
            $input['contract_id'] = (string) $resolved['contract_id'];
        }

        $built = $this->buildAnnouncePayload($input);
        if (($built['error'] ?? null) !== null) {
            return [
                'ok' => false,
                'httpStatus' => null,
                'errorMessage' => (string) $built['error'],
                'payload' => $built['payload'] ?? [],
                'rawBody' => null,
                'method' => 'POST',
                'url' => $this->client->baseUrl().'/shipments/announce',
                'hints' => [],
            ];
        }

        /** @var array<string, mixed> $payload */
        $payload = $built['payload'];
        $response = $this->client->post('/shipments/announce', $payload);
        $wrapped = $this->wrapResponse('POST', '/shipments/announce', $payload, $response);
        $wrapped['hints'] = array_merge($this->estraiHintsAnnounce($response), [
            'shipping_option_code' => $input['shipping_option_code'],
            'code_sostituito' => ($wantedCode !== '' && $wantedCode !== $input['shipping_option_code'])
                ? $wantedCode.' → '.$input['shipping_option_code']
                : null,
        ]);

        return $wrapped;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public function filtraPosteExpress(array $rows): array
    {
        $express = array_values(array_filter($rows, function (array $row): bool {
            $code = strtolower((string) ($row['code'] ?? ''));
            $name = strtolower((string) ($row['name'] ?? ''));

            return str_contains($code, 'poste_it_delivery')
                && str_contains($code, 'express')
                && ! str_contains($code, 'puntoposte')
                && ! str_contains($code, 'postoffice')
                && ! str_contains($code, 'shop2')
                && ! str_contains($name, 'punto poste')
                && ! str_contains($name, 'ufficio postale');
        }));

        return $express !== [] ? $express : $rows;
    }

    public function resolveContractIdForPoste(): ?int
    {
        if (! SendcloudClient::isConfigured()) {
            return null;
        }

        $response = $this->client->get('/contracts');
        if (! $response->successful()) {
            return null;
        }

        $rows = $response->json('data') ?? $response->json('contracts') ?? [];
        if (! is_array($rows)) {
            return null;
        }

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $code = strtolower(trim((string) (
                $row['carrier']['code']
                ?? $row['carrier_code']
                ?? ''
            )));
            if ($code === 'poste_it_delivery') {
                $id = (int) ($row['id'] ?? 0);

                return $id > 0 ? $id : null;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $probeResult
     */
    public function runCancel(string $shipmentId): array
    {
        $shipmentId = trim($shipmentId);
        if ($shipmentId === '') {
            return [
                'ok' => false,
                'httpStatus' => null,
                'errorMessage' => 'shipment id mancante: crea prima un\'etichetta o incollalo nel campo dedicato.',
                'payload' => [],
                'rawBody' => null,
                'method' => 'POST',
                'url' => $this->client->baseUrl().'/shipments/{id}/cancel',
                'hints' => [],
            ];
        }

        $path = '/shipments/'.rawurlencode($shipmentId).'/cancel';
        $response = $this->client->post($path);
        $wrapped = $this->wrapResponse('POST', $path, [], $response);
        $wrapped['hints'] = ['shipmentId' => $shipmentId];

        return $wrapped;
    }

    public function rimuoviPdfTest(string $shipmentId): void
    {
        $safeId = preg_replace('/[^\w\-]/', '_', trim($shipmentId)) ?: 'unknown';
        $path = storage_path('app/sendcloud_test/etichetta_'.$safeId.'.pdf');
        if (is_file($path)) {
            unlink($path);
        }
    }

    public function salvaPdfDaAnnounce(array $probeResult, string $shipmentId): ?string
    {
        $decoded = $this->decodedBody($probeResult);
        if ($decoded === null) {
            return null;
        }

        $data = is_array($decoded['data'] ?? null) ? $decoded['data'] : null;
        if ($data === null) {
            return null;
        }

        $parcels = $data['parcels'] ?? [];
        $parcel = is_array($parcels[0] ?? null) ? $parcels[0] : null;
        if ($parcel === null) {
            return null;
        }

        $binary = null;
        $labelFile = $parcel['label_file'] ?? null;
        if (is_string($labelFile) && $labelFile !== '') {
            $binary = base64_decode($labelFile, true);
            if (! is_string($binary) || $binary === '') {
                $binary = null;
            }
        }

        if ($binary === null) {
            $url = $this->estraiLabelUrl($parcel);
            if ($url !== null) {
                $docResponse = $this->client->getDocument($url);
                if ($docResponse->successful()) {
                    $binary = $docResponse->body();
                }
            }
        }

        if (! is_string($binary) || $binary === '' || ! str_starts_with($binary, '%PDF')) {
            return null;
        }

        $dir = storage_path('app/sendcloud_test');
        if (! is_dir($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $safeId = preg_replace('/[^\w\-]/', '_', $shipmentId) ?: 'unknown';
        $path = $dir.'/etichetta_'.$safeId.'.pdf';
        file_put_contents($path, $binary);

        return $path;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function buildQuotePayload(array $input): array
    {
        return $this->buildQuotePayloadWithAddresses($input);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     code: string|null,
     *     contract_id: int|null,
     *     error: string|null,
     *     quote_payload?: array<string, mixed>,
     *     quote_raw?: string|null
     * }
     */
    private function resolveShippingOptionCode(array $input): array
    {
        $wanted = trim((string) ($input['shipping_option_code'] ?? ''));
        $quotePayload = $this->buildQuotePayloadWithAddresses($input);
        $quoteResponse = $this->shippingOptions->listWithQuotes($quotePayload);

        if (! $quoteResponse->successful()) {
            $decoded = $quoteResponse->json();

            return [
                'code' => null,
                'contract_id' => null,
                'error' => is_array($decoded)
                    ? 'Preventivo fallito prima dell\'announce: '.trim((string) ($decoded['error'] ?? $decoded['message'] ?? 'HTTP '.$quoteResponse->status()))
                    : 'Preventivo fallito prima dell\'announce (HTTP '.$quoteResponse->status().').',
                'quote_payload' => $quotePayload,
                'quote_raw' => $quoteResponse->body(),
            ];
        }

        $body = $quoteResponse->json();
        $codes = $this->shippingOptions->extractOptionCodes($body);
        if ($codes === []) {
            return [
                'code' => null,
                'contract_id' => null,
                'error' => 'Nessun shipping_option_code disponibile per questa tratta. Esegui prima il preventivo.',
                'quote_payload' => $quotePayload,
                'quote_raw' => $quoteResponse->body(),
            ];
        }

        if ($wanted !== '' && in_array($wanted, $codes, true)) {
            $code = $wanted;
        } else {
            $code = $this->scegliExpressDomicilio($codes, $this->shippingOptions->parseQuoteRows($body));
            if ($code === null) {
                $code = $codes[0];
            }
        }

        $contractId = (int) ($input['contract_id'] ?? 0);
        if ($contractId < 1) {
            $contractId = $this->resolveContractIdForPoste() ?? 0;
        }

        if ($contractId < 1) {
            return [
                'code' => $code,
                'contract_id' => null,
                'error' => 'contract_id mancante: impossibile annunciare la spedizione. Verifica /contracts Sendcloud.',
                'quote_payload' => $quotePayload,
                'quote_raw' => $quoteResponse->body(),
            ];
        }

        return [
            'code' => $code,
            'contract_id' => $contractId,
            'error' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function buildQuotePayloadWithAddresses(array $input): array
    {
        $from = $this->indirizzo($input, 'mitt');
        $to = $this->indirizzo($input, 'dest');

        if ($this->validaIndirizzo($from, 'mittente') !== null) {
            $from = [
                'country_code' => 'IT',
                'postal_code' => trim((string) ($input['cap_origine'] ?? $input['mitt_cap'] ?? '')),
                'city' => trim((string) ($input['citta_origine'] ?? $input['mitt_citta'] ?? '')),
            ];
        }
        if ($this->validaIndirizzo($to, 'destinatario') !== null) {
            $to = [
                'country_code' => 'IT',
                'postal_code' => trim((string) ($input['cap_destino'] ?? $input['dest_cap'] ?? '')),
                'city' => trim((string) ($input['citta_destino'] ?? $input['dest_citta'] ?? '')),
            ];
        }

        $payload = [
            'from_address' => $from,
            'to_address' => $to,
            'parcels' => [$this->buildCollo($input)],
        ];

        $carrier = trim((string) ($input['carrier_code'] ?? ''));
        if ($carrier !== '') {
            $payload['carrier_code'] = $carrier;
        }

        return $payload;
    }

    /**
     * @param  list<string>  $codes
     * @param  list<array<string, mixed>>  $rows
     */
    private function scegliExpressDomicilio(array $codes, array $rows): ?string
    {
        foreach ($rows as $row) {
            $code = (string) ($row['code'] ?? '');
            $name = strtolower((string) ($row['name'] ?? ''));
            if ($code === '' || ! in_array($code, $codes, true)) {
                continue;
            }
            if (str_contains(strtolower($code), 'express')
                && str_contains($name, 'domicilio')
                && ! str_contains(strtolower($code), 'puntoposte')
                && ! str_contains(strtolower($code), 'postoffice')) {
                return $code;
            }
        }

        foreach ($codes as $code) {
            if (str_contains(strtolower($code), 'express') && ! str_contains(strtolower($code), 'puntoposte')) {
                return $code;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function buildCollo(array $input): array
    {
        $peso = max(0.1, (float) ($input['peso'] ?? 1));
        $length = max(1, (int) ($input['spessore'] ?? 30));
        $width = max(1, (int) ($input['larghezza'] ?? 20));
        $height = max(1, (int) ($input['altezza'] ?? 15));

        $collo = [
            'weight' => [
                'value' => number_format($peso, 3, '.', ''),
                'unit' => 'kg',
            ],
            'dimensions' => [
                'length' => (string) $length,
                'width' => (string) $width,
                'height' => (string) $height,
                'unit' => 'cm',
            ],
        ];

        $assicurazione = max(0.0, (float) ($input['valore_assicurazione'] ?? 0));
        $insured = SendcloudShippingOptionsService::additionalInsuredPriceForShipment(
            $assicurazione > 0 ? $assicurazione : null,
        );
        if ($insured !== null) {
            $collo['additional_insured_price'] = $insured;
        }

        return $collo;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{payload: array<string, mixed>, error: string|null}
     */
    private function buildAnnouncePayload(array $input): array
    {
        $code = trim((string) ($input['shipping_option_code'] ?? ''));
        if ($code === '') {
            return ['payload' => [], 'error' => 'Seleziona un servizio (shipping_option_code) dal preventivo.'];
        }

        $from = $this->indirizzo($input, 'mitt');
        $to = $this->indirizzo($input, 'dest');
        $errFrom = $this->validaIndirizzo($from, 'mittente');
        if ($errFrom !== null) {
            return ['payload' => [], 'error' => $errFrom];
        }
        $errTo = $this->validaIndirizzo($to, 'destinatario');
        if ($errTo !== null) {
            return ['payload' => [], 'error' => $errTo];
        }

        $collo = $this->buildCollo($input);

        $contractId = (int) ($input['contract_id'] ?? 0);
        if ($contractId < 1) {
            return ['payload' => [], 'error' => 'contract_id Sendcloud mancante (obbligatorio per announce).'];
        }

        $shipProps = [
            'shipping_option_code' => $code,
            'contract_id' => $contractId,
        ];

        return [
            'payload' => [
                'label_details' => [
                    'mime_type' => 'application/pdf',
                    'dpi' => 72,
                ],
                'from_address' => $from,
                'to_address' => $to,
                'ship_with' => [
                    'type' => 'shipping_option_code',
                    'properties' => $shipProps,
                ],
                'order_number' => 'SC_TEST_'.now()->format('YmdHis'),
                'parcels' => [$collo],
            ],
            'error' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function indirizzo(array $input, string $prefix): array
    {
        $nome = trim((string) ($input[$prefix.'_nome'] ?? ''));
        $cognome = trim((string) ($input[$prefix.'_cognome'] ?? ''));
        $name = trim($nome.' '.$cognome);
        if ($name === '') {
            $name = $prefix === 'mitt' ? 'Mario Bianchi' : 'Giuseppe Verdi';
        }

        $prov = strtoupper(trim((string) ($input[$prefix.'_provincia'] ?? '')));
        $tel = trim((string) ($input[$prefix.'_telefono'] ?? ''));
        if ($tel !== '' && ! str_starts_with($tel, '+')) {
            $tel = '+39'.preg_replace('/\D/', '', $tel);
        }

        return array_filter([
            'name' => $name,
            'company_name' => trim((string) ($input[$prefix.'_azienda'] ?? '')) ?: null,
            'address_line_1' => trim((string) ($input[$prefix.'_via'] ?? '')),
            'house_number' => trim((string) ($input[$prefix.'_civico'] ?? '')),
            'postal_code' => trim((string) ($input[$prefix.'_cap'] ?? '')),
            'city' => trim((string) ($input[$prefix.'_citta'] ?? '')),
            'country_code' => 'IT',
            'phone_number' => $tel !== '' ? $tel : null,
            'email' => trim((string) ($input[$prefix.'_email'] ?? '')) ?: null,
            'state_province_code' => $prov !== '' ? 'IT-'.$prov : null,
        ], static fn ($v) => $v !== null && $v !== '');
    }

    /**
     * @param  array<string, mixed>  $addr
     */
    private function validaIndirizzo(array $addr, string $ruolo): ?string
    {
        foreach (['name', 'address_line_1', 'postal_code', 'city', 'country_code'] as $key) {
            if (trim((string) ($addr[$key] ?? '')) === '') {
                return 'Indirizzo '.$ruolo.' incompleto (manca '.$key.').';
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function wrapResponse(string $method, string $path, array $payload, Response $response): array
    {
        $ok = $response->successful();
        $decoded = $response->json();
        $error = null;
        if (! $ok) {
            $error = is_array($decoded)
                ? trim((string) ($decoded['error'] ?? $decoded['message'] ?? $decoded['detail'] ?? ''))
                : '';
            if ($error === '') {
                $error = 'Errore HTTP '.$response->status().' da Sendcloud.';
            } else {
                $error = 'Errore HTTP '.$response->status().': '.$error;
            }
        }

        return [
            'ok' => $ok,
            'httpStatus' => $response->status(),
            'errorMessage' => $error,
            'payload' => $payload,
            'rawBody' => $response->body(),
            'method' => $method,
            'url' => rtrim($this->client->baseUrl(), '/').'/'.ltrim($path, '/'),
            'hints' => [],
        ];
    }

    /**
     * @return array{shipmentId: string|null, tracking: string|null, parcelId: int|null}
     */
    private function estraiHintsAnnounce(Response $response): array
    {
        $decoded = $response->json();
        if (! is_array($decoded)) {
            return ['shipmentId' => null, 'tracking' => null, 'parcelId' => null];
        }

        $data = is_array($decoded['data'] ?? null) ? $decoded['data'] : [];
        $parcel = is_array($data['parcels'][0] ?? null) ? $data['parcels'][0] : [];

        return [
            'shipmentId' => trim((string) ($data['id'] ?? '')) ?: null,
            'tracking' => trim((string) ($parcel['tracking_number'] ?? '')) ?: null,
            'parcelId' => (int) ($parcel['id'] ?? 0) ?: null,
        ];
    }

    /**
     * @param  array<string, mixed>  $parcel
     */
    private function estraiLabelUrl(array $parcel): ?string
    {
        $documents = $parcel['documents'] ?? [];
        if (! is_array($documents)) {
            return null;
        }
        foreach ($documents as $doc) {
            if (! is_array($doc) || strtolower((string) ($doc['type'] ?? '')) !== 'label') {
                continue;
            }
            $link = trim((string) ($doc['link'] ?? ''));

            return $link !== '' ? $link : null;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $probeResult
     * @return array<string, mixed>|null
     */
    private function decodedBody(array $probeResult): ?array
    {
        $raw = $probeResult['rawBody'] ?? '';
        if (! is_string($raw) || $raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }
}
