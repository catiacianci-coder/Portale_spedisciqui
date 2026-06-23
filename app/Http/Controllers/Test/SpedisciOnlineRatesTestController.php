<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use App\Models\corriere;
use App\Services\SpedisciOnline\SpedisciOnlineClient;
use App\Services\SpedisciOnline\SpedisciOnlineCreateLabelService;
use App\Services\SpedisciOnline\SpedisciOnlineDeleteLabelService;
use App\Services\SpedisciOnline\SpedisciOnlineEtichettaPdfService;
use App\Services\SpedisciOnline\SpedisciOnlinePickupService;
use App\Services\SpedisciOnline\SpedisciOnlineRatesService;
use App\Support\PiattaformaCorriere;
use App\Support\SpedisciOnlineEamultiContratti;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SpedisciOnlineRatesTestController extends Controller
{
    private const PIATTAFORMA = PiattaformaCorriere::EAMULTIEXP_SPEDISCIONLINE;

    private const SESSION_KEY_GLS = 'spedisci_online_test_last_label_gls';

    private const SESSION_KEY_SDA = 'spedisci_online_test_last_label_sda';

    private const PDF_STORAGE_DIR = 'spedisci_online_test';

    public function show(
        Request $request,
        SpedisciOnlineRatesService $rates,
        SpedisciOnlineCreateLabelService $createLabel,
        SpedisciOnlineDeleteLabelService $deleteLabel,
        SpedisciOnlinePickupService $pickup,
        SpedisciOnlineEtichettaPdfService $etichettaPdf,
    ): View {
        $glsLight = corriere::query()->find(SpedisciOnlineEamultiContratti::CORRIERE_GLS_LIGHT);
        $sdaM = corriere::query()->find(SpedisciOnlineEamultiContratti::CORRIERE_SDA_M);
        $lastLabelGls = $this->normalizzaEtichettaSessione($request->session()->get(self::SESSION_KEY_GLS, []));
        $lastLabelSda = $this->normalizzaEtichettaSessione($request->session()->get(self::SESSION_KEY_SDA, []));

        $defaults = [
            'cap_origine' => '80129',
            'cap_destino' => '83048',
            'altezza' => '15',
            'larghezza' => '20',
            'spessore' => '30',
            'peso' => '1',
            'tracking_numero' => (string) ($lastLabelGls['tracking'] ?? $lastLabelSda['tracking'] ?? ''),
            'increment_id' => (string) ($lastLabelGls['increment_id'] ?? $lastLabelSda['increment_id'] ?? ''),
            'data_ritiro' => date('Y-m-d', strtotime('+1 weekday')),
            'ora_inizio' => '09:00',
            'colli' => '1',
            'note_ritiro' => '',
            'note_spedizione' => '',
            'mittente_nome' => '',
            'mittente_azienda' => '',
            'mittente_indirizzo' => '',
            'mittente_telefono' => '',
            'mittente_email' => '',
            'destinatario_nome' => '',
            'destinatario_azienda' => '',
            'destinatario_indirizzo' => '',
            'destinatario_telefono' => '',
            'destinatario_email' => '',
        ];

        $input = array_merge($defaults, $request->only(array_keys($defaults)));
        $client = SpedisciOnlineClient::forPiattaforma(self::PIATTAFORMA);

        $result = [
            'azione' => null,
            'endpoint' => null,
            'method' => null,
            'httpStatus' => null,
            'payload' => null,
            'rawBody' => null,
            'errorMessage' => null,
            'summary' => null,
            'preventivo' => null,
            'corriereLabel' => null,
            'pdfUrl' => null,
            'pdfWarning' => null,
        ];

        if (! $client->isConfigured()) {
            $result['errorMessage'] = 'API key mancante (parametro spedisci_online_eamulti_api_key).';
        } elseif ($request->isMethod('post')) {
            $azione = (string) $request->input('azione', '');
            $result['azione'] = $azione;

            if (preg_match('/^(preventivo|create|delete|pickup)_(gls|sda)$/', $azione, $matches)) {
                $operazione = $matches[1];
                $carrierKey = $matches[2];
                $corriere = $this->corriereForKey($carrierKey);
                $result['corriereLabel'] = $this->corriereLabel($carrierKey);

                match ($operazione) {
                    'preventivo' => $this->runPreventivo($rates, $corriere, $carrierKey, $input, $result),
                    'create' => $this->runCreate($request, $client, $createLabel, $etichettaPdf, $corriere, $carrierKey, $input, $result),
                    'delete' => $this->runDelete($request, $client, $deleteLabel, $carrierKey, $input, $result),
                    'pickup' => $this->runPickup($client, $pickup, $corriere, $carrierKey, $input, $result),
                    default => $result['errorMessage'] = 'Azione non valida.',
                };

                $sessionKey = $this->sessionKeyForCarrier($carrierKey);
                $lastForCarrier = $request->session()->get($sessionKey, []);
                if (in_array($operazione, ['create', 'delete'], true)) {
                    $input['tracking_numero'] = (string) ($input['tracking_numero'] ?: ($lastForCarrier['tracking'] ?? ''));
                    $input['increment_id'] = (string) ($input['increment_id'] ?: ($lastForCarrier['increment_id'] ?? ''));
                }
            } elseif ($azione === 'tracking') {
                $this->runTracking($client, $input, $result);
            } else {
                $result['errorMessage'] = 'Azione non valida.';
            }

            $lastLabelGls = $this->normalizzaEtichettaSessione($request->session()->get(self::SESSION_KEY_GLS, []));
            $lastLabelSda = $this->normalizzaEtichettaSessione($request->session()->get(self::SESSION_KEY_SDA, []));
        }

        return view('test.spedisci-online-rates', [
            'input' => $input,
            'configured' => $client->isConfigured(),
            'piattaforma' => self::PIATTAFORMA,
            'apiBase' => $client->baseUrl(),
            'glsLight' => $glsLight,
            'sdaM' => $sdaM,
            'lastLabelGls' => $lastLabelGls,
            'lastLabelSda' => $lastLabelSda,
            'result' => $result,
            'searched' => $request->isMethod('post'),
        ]);
    }

    public function downloadEtichetta(Request $request, string $carrier): BinaryFileResponse
    {
        if (! in_array($carrier, ['gls', 'sda'], true)) {
            abort(404);
        }

        $lastLabel = $this->normalizzaEtichettaSessione(
            $request->session()->get($this->sessionKeyForCarrier($carrier), []),
        );
        $path = $this->percorsoPdfAssoluto((string) ($lastLabel['pdf_file'] ?? ''));
        if ($path === null) {
            abort(404, 'Etichetta PDF non trovata. Crea prima l’etichetta da /test/spedisci-online.');
        }

        $tracking = preg_replace('/[^\w\-]/', '_', (string) ($lastLabel['tracking'] ?? '')) ?: $carrier;

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="etichetta-'.$carrier.'-'.$tracking.'.pdf"',
        ]);
    }

    private function corriereForKey(string $key): ?corriere
    {
        $id = match ($key) {
            'gls' => SpedisciOnlineEamultiContratti::CORRIERE_GLS_LIGHT,
            'sda' => SpedisciOnlineEamultiContratti::CORRIERE_SDA_M,
            default => 0,
        };

        return $id > 0 ? corriere::query()->find($id) : null;
    }

    private function corriereLabel(string $key): string
    {
        return match ($key) {
            'gls' => 'GLS Light',
            'sda' => 'SDA M',
            default => strtoupper($key),
        };
    }

    private function sessionKeyForCarrier(string $key): string
    {
        return $key === 'sda' ? self::SESSION_KEY_SDA : self::SESSION_KEY_GLS;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validaIndirizziSpedizione(array $input): ?string
    {
        $required = [
            'mittente_nome' => 'nome mittente',
            'mittente_indirizzo' => 'indirizzo mittente',
            'destinatario_nome' => 'nome destinatario',
            'destinatario_indirizzo' => 'indirizzo destinatario',
        ];

        $missing = [];
        foreach ($required as $field => $label) {
            if (trim((string) ($input[$field] ?? '')) === '') {
                $missing[] = $label;
            }
        }

        if ($missing === []) {
            return null;
        }

        return 'Compila i campi obbligatori: '.implode(', ', $missing).'.';
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validaIndirizzoMittente(array $input): ?string
    {
        $required = [
            'mittente_nome' => 'nome mittente',
            'mittente_indirizzo' => 'indirizzo mittente',
        ];

        $missing = [];
        foreach ($required as $field => $label) {
            if (trim((string) ($input[$field] ?? '')) === '') {
                $missing[] = $label;
            }
        }

        if ($missing === []) {
            return null;
        }

        return 'Per il ritiro compila: '.implode(', ', $missing).'.';
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $result
     */
    private function runPreventivo(
        SpedisciOnlineRatesService $rates,
        ?corriere $corriere,
        string $carrierKey,
        array $input,
        array &$result,
    ): void {
        $result['endpoint'] = '/shipping/rates';
        $result['method'] = 'POST';
        $label = $this->corriereLabel($carrierKey);

        if ($corriere === null) {
            $result['errorMessage'] = "Corriere {$label} non trovato in tabella corrieres.";

            return;
        }

        $quote = $rates->quoteForPreventivo(
            $rates->buildPreventivoStubFromInput($input),
            $corriere,
        );

        $result['httpStatus'] = $quote['http_status'] ?? null;
        $result['payload'] = $quote['payload'] ?? null;
        $result['rawBody'] = $quote['raw_body'] ?? null;
        $result['preventivo'] = $quote;

        $price = data_get($quote, 'quote.price_amount');
        if ($price !== null && (float) $price > 0) {
            $result['summary'] = "{$label}: €".number_format((float) $price, 2, ',', '.');
        } elseif (! empty($quote['error'])) {
            $result['errorMessage'] = (string) $quote['error'];
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $result
     */
    private function runCreate(
        Request $request,
        SpedisciOnlineClient $client,
        SpedisciOnlineCreateLabelService $createLabel,
        SpedisciOnlineEtichettaPdfService $etichettaPdf,
        ?corriere $corriere,
        string $carrierKey,
        array $input,
        array &$result,
    ): void {
        $result['endpoint'] = '/shipping/create';
        $result['method'] = 'POST';
        $label = $this->corriereLabel($carrierKey);

        if ($corriere === null) {
            $result['errorMessage'] = "Corriere {$label} non trovato.";

            return;
        }

        $validationError = $this->validaIndirizziSpedizione($input);
        if ($validationError !== null) {
            $result['errorMessage'] = $validationError;

            return;
        }

        $createInput = array_merge($input, [
            'create_carrier_code' => $corriere->carrier_code,
            'create_contract_code' => SpedisciOnlineEamultiContratti::contractCodeForCorriere($corriere),
            'label_format' => 'PDF',
        ]);

        $payload = $createLabel->buildCreatePayload($createInput, $corriere);
        $result['payload'] = $payload;

        $response = $client->post('/shipping/create', $payload);
        $result['httpStatus'] = $response->status();
        $result['rawBody'] = $this->rawBodyPerVisualizzazione($response->body());

        if (! $response->successful()) {
            $decoded = $response->json();
            $apiError = is_array($decoded)
                ? trim((string) ($decoded['error'] ?? $decoded['message'] ?? ''))
                : '';
            $result['errorMessage'] = $apiError !== ''
                ? $apiError
                : 'Errore HTTP '.$response->status().'.';

            return;
        }

        $body = is_array($response->json()) ? $response->json() : [];
        $ids = $this->estraiIdentificativiDaCreate($body);
        $pdfFile = $this->salvaPdfTest($etichettaPdf, $body, $carrierKey, $ids['tracking']);

        $sessionData = array_merge($ids, [
            'pdf_file' => $pdfFile ?? '',
        ]);
        $request->session()->put($this->sessionKeyForCarrier($carrierKey), $sessionData);

        $result['summary'] = "Etichetta {$label} creata.";
        if ($ids['tracking'] !== '') {
            $result['summary'] .= ' Tracking: '.$ids['tracking'];
        }
        if ($ids['increment_id'] !== '') {
            $result['summary'] .= ' · increment_id: '.$ids['increment_id'];
        }
        if ($pdfFile !== null) {
            $result['pdfUrl'] = route('test.spedisci-online.pdf', ['carrier' => $carrierKey]);
            $result['summary'] .= ' · PDF pronto per il download.';
        } else {
            $result['pdfWarning'] = 'Etichetta creata ma labelData PDF non trovato o non decodificabile nella risposta API.';
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $result
     */
    private function runPickup(
        SpedisciOnlineClient $client,
        SpedisciOnlinePickupService $pickup,
        ?corriere $corriere,
        string $carrierKey,
        array $input,
        array &$result,
    ): void {
        $result['endpoint'] = '/pickup/create';
        $result['method'] = 'POST';
        $label = $this->corriereLabel($carrierKey);

        if ($corriere === null) {
            $result['errorMessage'] = "Corriere {$label} non trovato.";

            return;
        }

        $validationError = $this->validaIndirizzoMittente($input);
        if ($validationError !== null) {
            $result['errorMessage'] = $validationError;

            return;
        }

        $pickupInput = array_merge($input, [
            'piattaforma' => self::PIATTAFORMA,
            'pickup_contract_code' => SpedisciOnlineEamultiContratti::contractCodeForCorriere($corriere),
            'pickup_carrier_code' => $corriere->carrier_code,
            'tracking' => trim((string) ($input['tracking_numero'] ?? '')),
        ]);

        $payload = $pickup->buildPayload($pickupInput);
        $result['payload'] = $payload;

        $response = $client->post('/pickup/create', $payload);
        $result['httpStatus'] = $response->status();
        $result['rawBody'] = $response->body();

        if (! $response->successful()) {
            $decoded = $response->json();
            $apiError = is_array($decoded)
                ? trim((string) ($decoded['error'] ?? $decoded['message'] ?? ''))
                : '';
            $result['errorMessage'] = $apiError !== ''
                ? $apiError
                : 'Errore HTTP '.$response->status().'.';

            return;
        }

        $body = is_array($response->json()) ? $response->json() : [];
        $pickupId = trim((string) ($body['pickupId'] ?? ''));
        $result['summary'] = "Ritiro {$label} prenotato.";
        if ($pickupId !== '') {
            $result['summary'] .= ' pickupId: '.$pickupId;
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $result
     */
    private function runTracking(SpedisciOnlineClient $client, array $input, array &$result): void
    {
        $tracking = trim((string) ($input['tracking_numero'] ?? ''));
        if ($tracking === '') {
            $result['errorMessage'] = 'Inserisci il numero tracking (LDV) — viene compilato automaticamente dopo “Crea etichetta”.';

            return;
        }

        $result['endpoint'] = '/tracking/'.rawurlencode($tracking);
        $result['method'] = 'GET';
        $result['payload'] = null;

        $response = $client->get($result['endpoint']);
        $result['httpStatus'] = $response->status();
        $result['rawBody'] = $response->body();

        if ($response->successful()) {
            $result['summary'] = 'Risposta tracking ricevuta (HTTP '.$response->status().').';
        } else {
            $result['errorMessage'] = 'Tracking non disponibile o endpoint non supportato (HTTP '.$response->status().').';
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $result
     */
    private function runDelete(
        Request $request,
        SpedisciOnlineClient $client,
        SpedisciOnlineDeleteLabelService $deleteLabel,
        string $carrierKey,
        array $input,
        array &$result,
    ): void {
        $result['endpoint'] = '/shipping/delete';
        $result['method'] = 'POST';
        $label = $this->corriereLabel($carrierKey);

        $payload = $deleteLabel->buildPayload([
            'delete_shipment_id' => $input['tracking_numero'] ?? '',
            'delete_increment_id' => $input['increment_id'] ?? '',
        ]);

        if ($payload === null) {
            $result['errorMessage'] = 'Serve almeno il tracking (LDV) oppure increment_id dalla risposta create.';

            return;
        }

        $result['payload'] = $payload;
        $response = $client->post('/shipping/delete', $payload);
        $result['httpStatus'] = $response->status();
        $result['rawBody'] = $response->body();

        if ($response->successful()) {
            $this->rimuoviPdfSessione($request, $carrierKey);
            $request->session()->forget($this->sessionKeyForCarrier($carrierKey));
            $result['summary'] = "Etichetta {$label} cancellata su Spedisci.online.";
        } else {
            $decoded = $response->json();
            $apiError = is_array($decoded)
                ? trim((string) ($decoded['error'] ?? $decoded['message'] ?? ''))
                : '';
            $result['errorMessage'] = $apiError !== ''
                ? $apiError
                : 'Errore HTTP '.$response->status().'.';
        }
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array{tracking: string, increment_id: string}
     */
    private function estraiIdentificativiDaCreate(array $body): array
    {
        $tracking = '';
        foreach (['tracking', 'trackingNumber', 'tracking_number', 'barcode', 'shipmentId'] as $key) {
            $v = trim((string) ($body[$key] ?? ''));
            if ($v !== '') {
                $tracking = $v;
                break;
            }
        }

        foreach (['data', 'shipment', 'label'] as $wrap) {
            if ($tracking !== '' || ! isset($body[$wrap]) || ! is_array($body[$wrap])) {
                continue;
            }
            foreach (['tracking', 'trackingNumber', 'tracking_number', 'shipmentId'] as $key) {
                $v = trim((string) ($body[$wrap][$key] ?? ''));
                if ($v !== '') {
                    $tracking = $v;
                    break 2;
                }
            }
        }

        $incrementId = '';
        foreach (['increment_id', 'incrementId', 'id'] as $key) {
            $v = $body[$key] ?? null;
            if ($v !== null && $v !== '' && is_numeric($v)) {
                $incrementId = (string) $v;
                break;
            }
        }

        if ($incrementId === '' && isset($body['data']) && is_array($body['data'])) {
            foreach (['increment_id', 'incrementId', 'id'] as $key) {
                $v = $body['data'][$key] ?? null;
                if ($v !== null && $v !== '' && is_numeric($v)) {
                    $incrementId = (string) $v;
                    break;
                }
            }
        }

        return [
            'tracking' => $tracking,
            'increment_id' => $incrementId,
        ];
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function salvaPdfTest(
        SpedisciOnlineEtichettaPdfService $etichettaPdf,
        array $body,
        string $carrierKey,
        string $tracking,
    ): ?string {
        $base64 = $etichettaPdf->estraiLabelBase64($body);
        if ($base64 === null) {
            return null;
        }

        $binary = base64_decode($base64, true);
        if ($binary === false || $binary === '' || ! str_starts_with($binary, '%PDF')) {
            return null;
        }

        $dir = storage_path('app/'.self::PDF_STORAGE_DIR);
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            return null;
        }

        $safeTracking = preg_replace('/[^\w\-]/', '_', $tracking);
        if ($safeTracking === '') {
            $safeTracking = preg_replace('/[^\w\-]/', '_', uniqid('label_', true));
        }
        $filename = $carrierKey.'_'.$safeTracking.'.pdf';
        $path = $dir.DIRECTORY_SEPARATOR.$filename;

        if (file_put_contents($path, $binary) === false) {
            return null;
        }

        return $filename;
    }

    /**
     * @param  array<string, mixed>  $lastLabel
     * @return array<string, mixed>
     */
    private function normalizzaEtichettaSessione(array $lastLabel): array
    {
        $pdfFile = trim((string) ($lastLabel['pdf_file'] ?? ''));
        if ($pdfFile !== '' && $this->percorsoPdfAssoluto($pdfFile) === null) {
            $lastLabel['pdf_file'] = '';
        }

        return $lastLabel;
    }

    private function percorsoPdfAssoluto(string $filename): ?string
    {
        $filename = basename($filename);
        if ($filename === '' || ! preg_match('/^(gls|sda)_[\w\-]+\.pdf$/', $filename)) {
            return null;
        }

        $path = storage_path('app/'.self::PDF_STORAGE_DIR.DIRECTORY_SEPARATOR.$filename);

        return is_file($path) ? $path : null;
    }

    private function rimuoviPdfSessione(Request $request, string $carrierKey): void
    {
        $sessionKey = $this->sessionKeyForCarrier($carrierKey);
        $lastLabel = $request->session()->get($sessionKey, []);
        $path = $this->percorsoPdfAssoluto((string) ($lastLabel['pdf_file'] ?? ''));
        if ($path !== null) {
            @unlink($path);
        }
    }

    private function rawBodyPerVisualizzazione(string $rawBody): string
    {
        $decoded = json_decode($rawBody, true);
        if (! is_array($decoded)) {
            return $rawBody;
        }

        $sanitized = $this->sanitizzaLabelDataInArray($decoded);

        return json_encode($sanitized, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: $rawBody;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizzaLabelDataInArray(array $data): array
    {
        $labelKeys = [
            'labelData', 'label_data', 'labelPdf', 'label_pdf', 'pdf', 'pdfLabel', 'pdf_label',
        ];

        foreach ($labelKeys as $key) {
            if (isset($data[$key]) && is_string($data[$key]) && strlen($data[$key]) > 80) {
                $data[$key] = '[PDF base64 omesso — usa Scarica PDF]';
            }
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizzaLabelDataInArray($value);
            }
        }

        return $data;
    }
}
