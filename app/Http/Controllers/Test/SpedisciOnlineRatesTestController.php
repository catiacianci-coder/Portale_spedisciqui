<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;

use App\Models\corriere;
use App\Services\SpedisciOnline\SpedisciOnlineClient;
use App\Services\SpedisciOnline\SpedisciOnlineCreateLabelService;
use App\Services\SpedisciOnline\SpedisciOnlineDeleteLabelService;
use App\Services\SpedisciOnline\SpedisciOnlinePickupService;
use App\Services\SpedisciOnline\SpedisciOnlineRatesService;
use App\Support\PiattaformaCorriere;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpedisciOnlineRatesTestController extends Controller
{
    private const PIATTAFORMA = PiattaformaCorriere::QUICK_PREVENTIVI_PROPRI;

    private const CORRIERE_RITIRO_ID = 4;

    public function show(
        Request $request,
        SpedisciOnlineRatesService $rates,
        SpedisciOnlinePickupService $pickup,
        SpedisciOnlineCreateLabelService $createLabel,
        SpedisciOnlineDeleteLabelService $deleteLabel,
    ): View {
        $corriereRitiro = corriere::query()->find(self::CORRIERE_RITIRO_ID);

        $defaults = [
            'cap_origine' => '80129',
            'cap_destino' => '83048',
            'altezza' => '15',
            'larghezza' => '20',
            'spessore' => '30',
            'peso' => '1',
            'data_ritiro' => date('Y-m-d', strtotime('+1 weekday')),
            'ora_inizio' => '09:00',
            'colli' => '1',
            'tracking' => '',
            'pickup_carrier_code' => trim((string) ($corriereRitiro?->carrier_code ?? '')),
            'pickup_contract_code' => trim((string) ($corriereRitiro?->contract_code ?? '')),
            'note_ritiro' => 'Test ritiro portale',
            'pickup_payload_json' => '',
            'create_carrier_code' => trim((string) ($corriereRitiro?->carrier_code ?? '')),
            'create_contract_code' => trim((string) ($corriereRitiro?->contract_code ?? '')),
            'create_auto_from_rates' => '1',
            'create_payload_json' => '',
            'label_format' => 'PDF',
            'mittente_nome' => 'Mittente test',
            'mittente_azienda' => 'K91 adv s.r.l.',
            'mittente_indirizzo' => 'Via Roma 1',
            'mittente_telefono' => '0811234567',
            'mittente_email' => 'mittente@test.local',
            'destinatario_nome' => 'Destinatario test',
            'destinatario_indirizzo' => 'Via Principale 1',
            'destinatario_telefono' => '0825123456',
            'destinatario_email' => 'destinatario@test.local',
            'note_spedizione' => 'Etichetta test portale',
            'delete_shipment_id' => '',
            'delete_increment_id' => '',
            'delete_payload_json' => '',
        ];

        $input = array_merge($defaults, $request->only(array_keys($defaults)));
        $azione = $request->input('azione', 'rates');
        $endpoint = match ($azione) {
            'pickup' => '/pickup/create',
            'create' => '/shipping/create',
            'delete' => '/shipping/delete',
            default => '/shipping/rates',
        };

        $payload = null;
        $httpStatus = null;
        $rawBody = null;
        $errorMessage = null;
        $searched = $request->isMethod('post');
        $jsonCustomError = null;
        $ratesPreviewBody = null;
        $ratesPreviewStatus = null;
        $infoMessage = null;

        $client = SpedisciOnlineClient::forPiattaforma(self::PIATTAFORMA);

        if (! $client->isConfigured()) {
            $errorMessage = 'API key mancante per tenant quick in parametri globali (spedisci_online_quick_api_key).';
        } elseif ($searched) {
            if ($azione === 'pickup') {
                $custom = $pickup->decodeCustomPayload($input['pickup_payload_json']);
                if (trim((string) $input['pickup_payload_json']) !== '' && $custom === null) {
                    $jsonCustomError = 'JSON payload ritiro non valido.';
                } else {
                    $input['piattaforma'] = self::PIATTAFORMA;
                    $payload = $custom ?? $pickup->buildPayload($input);
                }
            } elseif ($azione === 'create') {
                $custom = $createLabel->decodeCustomPayload($input['create_payload_json']);
                if (trim((string) $input['create_payload_json']) !== '' && $custom === null) {
                    $jsonCustomError = 'JSON payload create non valido.';
                } else {
                    $payload = $custom ?? $createLabel->buildCreatePayload($input, $corriereRitiro);

                    if ($request->has('create_auto_from_rates')) {
                        $preview = $createLabel->previewRates($input, self::PIATTAFORMA);
                        $ratesPreviewStatus = $preview['ratesResponse']->status();
                        $ratesPreviewBody = $preview['ratesResponse']->body();
                        if ($preview['ratesList'] === [] && $preview['ratesResponse']->successful()) {
                            $infoMessage = 'Anteprima /shipping/rates: risposta vuota [].';
                        }
                    }
                }
            } elseif ($azione === 'delete') {
                $custom = $deleteLabel->decodeCustomPayload($input['delete_payload_json']);
                if (trim((string) $input['delete_payload_json']) !== '' && $custom === null) {
                    $jsonCustomError = 'JSON payload delete non valido.';
                } else {
                    $payload = $custom ?? $deleteLabel->buildPayload($input);
                    if ($payload === null) {
                        $errorMessage = 'Inserisci shipment-id (LDV) e/o increment_id dalla risposta di /shipping/create.';
                    }
                }
            } else {
                $apiInput = $createLabel->buildRatesInput($input);
                $payload = $rates->buildPayload($apiInput);
            }

            if ($jsonCustomError === null && $payload !== null && $errorMessage === null) {
                $response = $client->post($endpoint, $payload);
                $httpStatus = $response->status();
                $rawBody = $response->body();

                if (! $response->successful()) {
                    $decoded = $response->json();
                    $apiError = is_array($decoded)
                        ? trim((string) ($decoded['error'] ?? $decoded['message'] ?? ''))
                        : '';
                    $errorMessage = $apiError !== ''
                        ? 'Errore HTTP '.$httpStatus.': '.$apiError
                        : 'Errore HTTP '.$httpStatus.' da Spedisci.online.';
                }
            } elseif ($jsonCustomError !== null) {
                $errorMessage = $jsonCustomError;
            }
        }

        return view('test.spedisci-online-rates', [
            'input' => $input,
            'azione' => $azione,
            'endpoint' => $endpoint,
            'searched' => $searched,
            'configured' => $client->isConfigured(),
            'piattaforma' => self::PIATTAFORMA,
            'apiBase' => $client->baseUrl(),
            'payload' => $payload,
            'httpStatus' => $httpStatus,
            'rawBody' => $rawBody,
            'errorMessage' => $errorMessage,
            'jsonCustomError' => $jsonCustomError,
            'corriereRitiro' => $corriereRitiro,
            'ratesPreviewBody' => $ratesPreviewBody,
            'ratesPreviewStatus' => $ratesPreviewStatus,
            'infoMessage' => $infoMessage,
        ]);
    }
}
