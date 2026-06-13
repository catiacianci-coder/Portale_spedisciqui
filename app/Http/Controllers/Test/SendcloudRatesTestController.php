<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;

use App\Models\corriere;
use App\Services\Sendcloud\SendcloudClient;
use App\Services\Sendcloud\SendcloudServicePointsService;
use App\Services\Sendcloud\SendcloudShippingOptionsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SendcloudRatesTestController extends Controller
{
    public function show(
        Request $request,
        SendcloudShippingOptionsService $shippingOptions,
        SendcloudServicePointsService $servicePoints,
    ): View {
        $defaults = [
            'cap_origine' => '80129',
            'cap_destino' => '20100',
            'citta_origine' => 'Napoli',
            'citta_destino' => 'Milano',
            'altezza' => '15',
            'larghezza' => '20',
            'spessore' => '30',
            'peso' => '2',
            'valore_assicurazione_test' => '500',
            'mitt_cap' => '80129',
            'mitt_citta' => 'Napoli',
            'mitt_radius' => '5000',
            'mitt_limit' => '40',
            'mitt_carrier_code' => 'poste_italiane',
            'mitt_shop_type' => '',
            'mitt_use_integration_carriers' => '1',
            'dest_cap' => '20100',
            'dest_citta' => 'Milano',
            'dest_radius' => '5000',
            'dest_limit' => '40',
            'dest_carrier_code' => 'poste_italiane',
            'dest_shop_type' => '',
            'dest_use_integration_carriers' => '1',
            'inpost_cap' => '20100',
            'inpost_citta' => 'Milano',
            'inpost_radius' => '5000',
            'inpost_limit' => '40',
            'inpost_carrier_code' => 'inpost_it',
            'inpost_shop_type' => 'locker',
            'inpost_use_integration_carriers' => '0',
        ];

        $input = array_merge($defaults, $request->only(array_keys($defaults)));
        $input['mitt_use_integration_carriers'] = $request->boolean('mitt_use_integration_carriers') ? '1' : '0';
        $input['dest_use_integration_carriers'] = $request->boolean('dest_use_integration_carriers') ? '1' : '0';
        $input['inpost_use_integration_carriers'] = $request->boolean('inpost_use_integration_carriers') ? '1' : '0';
        $probe = (string) $request->input('probe', 'rates');
        $configured = SendcloudClient::isConfigured();
        $apiBase = app(SendcloudClient::class)->baseUrl();

        $dbActiveServices = $this->dbActiveSendcloudServices();
        $catalogState = $this->loadSendcloudCatalog($shippingOptions, $input, $configured);

        $ratesState = $this->probeRates($request, $shippingOptions, $input, $configured, $probe, $dbActiveServices);
        $mittState = $this->probeServicePointsRole($request, $servicePoints, $input, $configured, $probe, 'points_mittente', 'mitt', 'mitt_');
        $destState = $this->probeServicePointsRole($request, $servicePoints, $input, $configured, $probe, 'points_destinatario', 'dest', 'dest_');
        $inpostState = $this->probeServicePointsRole($request, $servicePoints, $input, $configured, $probe, 'points_inpost', 'inpost', 'inpost_');

        return view('test.sendcloud-rates', array_merge(
            [
                'input' => $input,
                'configured' => $configured,
                'apiBase' => $apiBase,
                'activeProbe' => $probe,
                'dbActiveServices' => $dbActiveServices,
            ],
            $catalogState,
            $ratesState,
            $mittState,
            $destState,
            $inpostState,
        ));
    }

    /**
     * @return list<array{id: int, nome: string, codice: string}>
     */
    private function dbActiveSendcloudServices(): array
    {
        return corriere::query()
            ->where('piattaforma', 'sendcloud')
            ->where('attivo', true)
            ->orderBy('nome_visualizzato')
            ->orderBy('id')
            ->get(['id', 'nome_visualizzato', 'codice_servizio'])
            ->map(function (corriere $row): array {
                return [
                    'id' => (int) $row->id,
                    'nome' => trim((string) $row->nome_visualizzato),
                    'codice' => trim((string) $row->codice_servizio),
                ];
            })
            ->filter(fn (array $row): bool => $row['codice'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function loadSendcloudCatalog(
        SendcloudShippingOptionsService $shippingOptions,
        array $input,
        bool $configured,
    ): array {
        $state = [
            'catalogLoaded' => false,
            'catalogHttpStatus' => null,
            'catalogError' => null,
            'catalogRows' => [],
            'catalogPayload' => null,
        ];

        if (! $configured) {
            $state['catalogError'] = 'Chiavi Sendcloud mancanti: impossibile caricare il catalogo.';

            return $state;
        }

        $state['catalogLoaded'] = true;
        $quoteResult = $this->fetchShippingOptionRows($shippingOptions, $input);
        $state['catalogHttpStatus'] = $quoteResult['http_status'];
        $state['catalogPayload'] = $quoteResult['payload'];
        $state['valoreAssicurazioneTest'] = $quoteResult['valore_assicurazione_test'];

        if ($quoteResult['error'] !== null) {
            $state['catalogError'] = $quoteResult['error'];

            return $state;
        }

        $state['catalogRows'] = $quoteResult['rows'];
        if ($state['catalogRows'] === []) {
            $state['catalogError'] = 'Risposta OK ma nessuna opzione nel catalogo Sendcloud per i parametri predefiniti.';
        }

        return $state;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function probeRates(
        Request $request,
        SendcloudShippingOptionsService $shippingOptions,
        array $input,
        bool $configured,
        string $probe,
        array $dbActiveServices,
    ): array {
        $allowedCodes = array_values(array_unique(array_column($dbActiveServices, 'codice')));

        $state = [
            'ratesSearched' => false,
            'endpoint' => '/shipping-options',
            'payload' => null,
            'httpStatus' => null,
            'rawBody' => null,
            'errorMessage' => null,
            'quoteRows' => [],
            'allowedCodes' => $allowedCodes,
        ];

        if ($probe !== 'rates' || ! $request->isMethod('post')) {
            return $state;
        }

        $state['ratesSearched'] = true;

        if (! $configured) {
            $state['errorMessage'] = 'Chiavi Sendcloud mancanti in parametri globali (sendcloud_public_key / sendcloud_secret_key).';

            return $state;
        }

        if ($state['allowedCodes'] === []) {
            $state['errorMessage'] = 'Nessun corriere Sendcloud attivo in tabella corrieres con codice_servizio valorizzato.';

            return $state;
        }

        $quoteResult = $this->fetchShippingOptionRows($shippingOptions, $input);
        $state['payload'] = $quoteResult['payload'];
        $state['httpStatus'] = $quoteResult['http_status'];
        $state['rawBody'] = $quoteResult['raw_body'];
        $state['valoreAssicurazioneTest'] = $quoteResult['valore_assicurazione_test'];
        $state['insurancePayload'] = $quoteResult['insurance_payload'];
        $state['insuranceHttpStatus'] = $quoteResult['insurance_http_status'];
        $state['insuranceRawBody'] = $quoteResult['insurance_raw_body'];
        $state['insuranceError'] = $quoteResult['insurance_error'];

        if ($quoteResult['error'] !== null) {
            $state['errorMessage'] = $quoteResult['error'];

            return $state;
        }

        $state['quoteRows'] = array_values(array_filter(
            $quoteResult['rows'],
            fn (array $row): bool => in_array((string) ($row['code'] ?? ''), $state['allowedCodes'], true),
        ));
        if ($state['quoteRows'] === []) {
            $state['errorMessage'] = 'Risposta OK ma nessuna opzione combacia con i codici attivi in tabella corrieres.';
        }

        return $state;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     payload: array<string, mixed>|null,
     *     http_status: int|null,
     *     raw_body: string|null,
     *     valore_assicurazione_test: float,
     *     insurance_payload: array<string, mixed>|null,
     *     insurance_http_status: int|null,
     *     insurance_raw_body: string|null,
     *     insurance_error: string|null,
     *     error: string|null
     * }
     */
    private function fetchShippingOptionRows(
        SendcloudShippingOptionsService $shippingOptions,
        array $input,
    ): array {
        $valoreAssicurazioneTest = max(0.0, (float) ($input['valore_assicurazione_test'] ?? 0));
        $insurancePayload = null;
        $insuranceHttpStatus = null;
        $insuranceRawBody = null;
        $insuranceError = null;

        $payload = $shippingOptions->buildNationalPayload($input);
        $response = $shippingOptions->listWithQuotes($payload);

        if (! $response->successful()) {
            return [
                'rows' => [],
                'payload' => $payload,
                'http_status' => $response->status(),
                'raw_body' => $response->body(),
                'valore_assicurazione_test' => $valoreAssicurazioneTest,
                'insurance_payload' => null,
                'insurance_http_status' => null,
                'insurance_raw_body' => null,
                'insurance_error' => null,
                'error' => $this->formatApiError($response->status(), $response->json()),
            ];
        }

        $rows = $shippingOptions->parseQuoteRows($response->json());

        if ($valoreAssicurazioneTest > 0) {
            $insurancePayload = $shippingOptions->buildNationalPayload(array_merge($input, [
                'valore_assicurazione' => $valoreAssicurazioneTest,
            ]));
            $responseInsurance = $shippingOptions->listWithQuotes($insurancePayload);
            $insuranceHttpStatus = $responseInsurance->status();
            $insuranceRawBody = $responseInsurance->body();
            if ($responseInsurance->successful()) {
                $rows = $shippingOptions->enrichRowsWithInsurancePrices($rows, $responseInsurance->json());
            } else {
                $insuranceError = $this->formatApiError($responseInsurance->status(), $responseInsurance->json());
            }
        }

        return [
            'rows' => $rows,
            'payload' => $payload,
            'http_status' => $response->status(),
            'raw_body' => $response->body(),
            'valore_assicurazione_test' => $valoreAssicurazioneTest,
            'insurance_payload' => $insurancePayload,
            'insurance_http_status' => $insuranceHttpStatus,
            'insurance_raw_body' => $insuranceRawBody,
            'insurance_error' => $insuranceError,
            'error' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function probeServicePointsRole(
        Request $request,
        SendcloudServicePointsService $servicePoints,
        array $input,
        bool $configured,
        string $probe,
        string $probeKey,
        string $varPrefix,
        string $prefix,
    ): array {
        $state = [
            $varPrefix.'Searched' => false,
            $varPrefix.'Endpoint' => '/service-points',
            $varPrefix.'Query' => null,
            $varPrefix.'HttpStatus' => null,
            $varPrefix.'RawBody' => null,
            $varPrefix.'ErrorMessage' => null,
            $varPrefix.'Rows' => [],
            $varPrefix.'Geocoding' => ['status' => null, 'precision' => null],
        ];

        if ($probe !== $probeKey || ! $request->isMethod('post')) {
            return $state;
        }

        $state[$varPrefix.'Searched'] = true;

        if (! $configured) {
            $state[$varPrefix.'ErrorMessage'] = 'Chiavi Sendcloud mancanti in .env.';

            return $state;
        }

        $state[$varPrefix.'Query'] = $servicePoints->buildQueryPreview($input, $prefix);
        $response = $servicePoints->searchWithPrefix($input, $prefix);
        $state[$varPrefix.'HttpStatus'] = $response->status();
        $state[$varPrefix.'RawBody'] = $response->body();

        if ($response->successful()) {
            $json = $response->json();
            $state[$varPrefix.'Rows'] = $servicePoints->parseRows($json);
            $state[$varPrefix.'Geocoding'] = $servicePoints->parseGeocoding($json);
            if ($state[$varPrefix.'Rows'] === []) {
                $geoStatus = $state[$varPrefix.'Geocoding']['status'] ?? '';
                if ($geoStatus === 'not_found') {
                    $state[$varPrefix.'ErrorMessage'] = 'Geocoding non riuscito per CAP/città indicati.';
                } else {
                    $state[$varPrefix.'ErrorMessage'] = 'Risposta OK ma nessun punto trovato. Verifica Service Points nel pannello Sendcloud.';
                }
            }
        } else {
            $state[$varPrefix.'ErrorMessage'] = $this->formatApiError($response->status(), $response->json());
        }

        return $state;
    }

    /**
     * @param  array<string, mixed>|null  $decoded
     */
    private function formatApiError(int $status, mixed $decoded): string
    {
        $apiError = is_array($decoded)
            ? trim((string) ($decoded['error'] ?? $decoded['message'] ?? $decoded['detail'] ?? ''))
            : '';

        return $apiError !== ''
            ? 'Errore HTTP '.$status.': '.$apiError
            : 'Errore HTTP '.$status.' da Sendcloud.';
    }
}
