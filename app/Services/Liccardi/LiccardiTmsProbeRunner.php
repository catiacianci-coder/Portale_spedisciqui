<?php

namespace App\Services\Liccardi;

use Illuminate\Http\Client\Response;

/**
 * Esecuzione probe API per pagina test (request/response per UI).
 */
final class LiccardiTmsProbeRunner
{
    public function __construct(
        private readonly LiccardiTmsClient $client,
        private readonly LiccardiTmsPayloadBuilder $payloads,
        private readonly LiccardiTmsResponseFormatter $formatter,
        private readonly LiccardiTmsDeleteShipmentService $deleteShipment,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function run(string $probe, array $input): array
    {
        if (! $this->client->isConfigured()) {
            return $this->emptyResult($probe, 'Configura liccardi_tms_api_key e liccardi_tms_company_id in parametri globali.');
        }

        return match ($probe) {
            'quote' => $this->probeQuote($input),
            'create_fast' => $this->probeCreateFast($input),
            'create_head' => $this->probeCreateHead($input),
            'add_parcels' => $this->probeAddParcels($input),
            'close' => $this->probeClose($input),
            'labels_pdf' => $this->probeLabels($input, 'pdf'),
            'labels_zpl' => $this->probeLabels($input, 'zpl'),
            'tracking' => $this->probeTracking($input),
            'delete' => $this->probeDelete($input),
            default => $this->emptyResult($probe, 'Probe non riconosciuto.'),
        };
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function probeQuote(array $input): array
    {
        $companyId = $this->client->companyId();
        $payload = $this->payloads->buildQuotePayload($input, $companyId);
        $path = 'spedizioni/importi/getImporto';
        $response = $this->client->postJson($path, $payload);

        return $this->formatResult('POST', $path, [], $payload, $response);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function probeCreateFast(array $input): array
    {
        $companyId = $this->client->companyId();
        $payload = $this->payloads->buildCreateFastPayload($input, $companyId);
        $generateRitiro = ($input['generate_ritiro'] ?? '1') === '1' ? 'true' : 'false';
        $path = 'spedizioni';
        $query = ['generateRitiro' => $generateRitiro];
        $response = $this->client->postJson($path, $payload, $query);

        return $this->formatResult('POST', $path, $query, $payload, $response, true);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function probeCreateHead(array $input): array
    {
        $companyId = $this->client->companyId();
        $payload = $this->payloads->buildCreateHeadPayload($input, $companyId);
        $generateRitiro = ($input['generate_ritiro_head'] ?? '0') === '1' ? 'true' : 'false';
        $path = 'spedizioni';
        $query = ['generateRitiro' => $generateRitiro];
        $response = $this->client->postJson($path, $payload, $query);

        return $this->formatResult('POST', $path, $query, $payload, $response, true);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function probeAddParcels(array $input): array
    {
        $shipmentId = (int) ($input['spedizione_id'] ?? 0);
        if ($shipmentId < 1) {
            return $this->emptyResult('add_parcels', 'Inserisci spedizioneId (dalla risposta create o sessione).');
        }

        $payload = $this->payloads->buildAddParcelsPayload($input);
        $path = "spedizioni/{$shipmentId}/colli";
        $response = $this->client->postJson($path, $payload);

        return $this->formatResult('POST', $path, [], $payload, $response, true);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function probeClose(array $input): array
    {
        $shipmentId = (int) ($input['spedizione_id'] ?? 0);
        if ($shipmentId < 1) {
            return $this->emptyResult('close', 'Inserisci spedizioneId.');
        }

        $path = "spedizioni/{$shipmentId}/close";
        $response = $this->client->putJson($path, []);

        return $this->formatResult('PUT', $path, [], [], $response, true);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function probeLabels(array $input, string $format): array
    {
        $shipmentId = (int) ($input['spedizione_id'] ?? 0);
        if ($shipmentId < 1) {
            return $this->emptyResult('labels_'.$format, 'Inserisci spedizioneId dalla creazione spedizione.');
        }

        $path = "spedizioni/{$shipmentId}/etichette/{$format}";
        $response = $this->client->get($path);

        return $this->formatResult('GET', $path, [], null, $response);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function probeTracking(array $input): array
    {
        $ldv = trim((string) ($input['ldv'] ?? ''));
        if ($ldv === '') {
            return $this->emptyResult('tracking', 'Inserisci LDV (courierLdv dalla risposta create).');
        }

        $path = 'tracking/'.rawurlencode($ldv);
        $response = $this->client->get($path);

        return $this->formatResult('GET', $path, [], null, $response);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function probeDelete(array $input): array
    {
        $shipmentId = (int) ($input['spedizione_id'] ?? 0);
        if ($shipmentId < 1) {
            return $this->emptyResult('delete', 'Inserisci spedizioneId da eliminare.');
        }

        return $this->deleteShipment->deleteBySpedizioneId($shipmentId);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function formatResult(
        string $method,
        string $path,
        array $query,
        ?array $payload,
        Response $response,
        bool $extractIds = false,
    ): array {
        return $this->formatter->fromHttp($method, $path, $query, $payload, $response, $extractIds);
    }

    /**
     * Importo numerico dalla risposta getImporto (struttura variabile).
     */
    public static function estraiImportoNumerico(?array $decoded): ?float
    {
        if (! is_array($decoded)) {
            return null;
        }

        $candidati = [];
        self::raccogliNumeriImporto($decoded, $candidati);

        foreach (['importo', 'importoTotale', 'totale', 'prezzo', 'amount', 'price', 'costo', 'importoTotaleSpedizione', 'totaleSpedizione'] as $key) {
            if (isset($decoded[$key]) && is_numeric($decoded[$key])) {
                return (float) $decoded[$key];
            }
        }

        if (isset($decoded['importoSpedizione']) && is_array($decoded['importoSpedizione'])) {
            $box = $decoded['importoSpedizione'];
            foreach (['tot', 'totDeclared', 'importoTotale', 'totale', 'importo', 'prezzo', 'importoNetto'] as $key) {
                if (isset($box[$key]) && is_numeric($box[$key])) {
                    return (float) $box[$key];
                }
            }
        }

        if ($candidati !== []) {
            return (float) $candidati[0];
        }

        return null;
    }

    /**
     * Cerca un importo nella risposta getImporto (struttura variabile).
     */
    public static function estraiPrezzoPreventivo(?array $decoded): ?string
    {
        $n = self::estraiImportoNumerico($decoded);

        return $n !== null ? self::formatEuro($n) : null;
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  list<float>  $out
     */
    private static function raccogliNumeriImporto(array $node, array &$out): void
    {
        foreach ($node as $key => $val) {
            if (is_array($val)) {
                self::raccogliNumeriImporto($val, $out);
                continue;
            }
            $k = strtolower((string) $key);
            if (is_numeric($val) && (
                str_contains($k, 'importo')
                || str_contains($k, 'prezzo')
                || str_contains($k, 'totale')
                || str_contains($k, 'amount')
            )) {
                $out[] = (float) $val;
            }
        }
    }

    private static function formatEuro(float $n): string
    {
        return \App\Support\ImportoEuro::format($n);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyResult(string $probe, string $message): array
    {
        return [
            'searched' => true,
            'probe' => $probe,
            'method' => null,
            'path' => null,
            'query' => [],
            'url' => null,
            'requestHeaders' => $this->client->requestHeadersForDisplay(),
            'payload' => null,
            'httpStatus' => null,
            'contentType' => null,
            'rawBody' => null,
            'bodyNote' => null,
            'errorMessage' => $message,
            'hints' => [],
            'ok' => false,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $decoded
     * @return array{spedizioneId?: int, courierLdv?: string, packageIds?: list<int>}
     */
    public static function extractIds(?array $decoded): array
    {
        if (! is_array($decoded)) {
            return [];
        }

        $hints = [];
        $sources = [$decoded];
        foreach (['data', 'result', 'shipment'] as $wrap) {
            if (isset($decoded[$wrap]) && is_array($decoded[$wrap])) {
                $sources[] = $decoded[$wrap];
            }
        }

        foreach ($sources as $src) {
            foreach (['spedizioneId', 'shipmentId', 'id'] as $key) {
                if (isset($src[$key]) && (int) $src[$key] > 0) {
                    $hints['spedizioneId'] = (int) $src[$key];
                    break 2;
                }
            }
        }

        foreach ($sources as $src) {
            foreach (['courierLdv', 'ldv', 'ldvNumber', 'barcode', 'trackingNumber'] as $key) {
                $val = trim((string) ($src[$key] ?? ''));
                if ($val !== '') {
                    $hints['courierLdv'] = $val;
                    break 2;
                }
            }
        }

        $bindings = $decoded['packageBindings'] ?? ($decoded['data']['packageBindings'] ?? null);
        if (is_array($bindings)) {
            $ids = [];
            foreach ($bindings as $b) {
                if (is_array($b) && isset($b['packageId'])) {
                    $ids[] = (int) $b['packageId'];
                }
            }
            if ($ids !== []) {
                $hints['packageIds'] = $ids;
            }
        }

        return $hints;
    }
}
