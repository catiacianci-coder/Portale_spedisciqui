<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;

use App\Services\Sendcloud\SendcloudClient;
use App\Services\Sendcloud\SendcloudEtichettaTestProbe;
use App\Services\Sendcloud\SendcloudShippingOptionsService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SendcloudEtichettaTestController extends Controller
{
    private const SESSION_SHIPMENT_ID = 'sendcloud_test_shipment_id';

    public function show(
        Request $request,
        SendcloudEtichettaTestProbe $probe,
        SendcloudShippingOptionsService $shippingOptions,
    ): View {
        $defaults = $this->defaultInput();
        $defaults['contract_id'] = (string) ($probe->resolveContractIdForPoste() ?? '');
        $defaults['shipping_option_code'] = (string) $request->session()->get(
            'sendcloud_test_shipping_option_code',
            $defaults['shipping_option_code'],
        );

        $input = array_merge($defaults, $request->only(array_keys($defaults)));
        $azione = (string) $request->input('azione', '');
        $preventivo = null;
        $etichetta = null;
        $cancellazione = null;
        $quoteRows = [];
        $pdfUrl = null;

        if ($request->isMethod('post') && SendcloudClient::isConfigured()) {
            if ($azione === 'preventivo') {
                $preventivo = $probe->runPreventivo($input);
                if ($preventivo['ok'] ?? false) {
                    $allRows = $shippingOptions->parseQuoteRows(
                        json_decode((string) ($preventivo['rawBody'] ?? ''), true)
                    );
                    $quoteRows = $probe->filtraPosteExpress($allRows);
                    if ($quoteRows === [] && $allRows !== []) {
                        $quoteRows = $allRows;
                    }
                    $selected = trim((string) ($input['shipping_option_code'] ?? ''));
                    if ($selected === '' && $quoteRows !== []) {
                        foreach ($quoteRows as $row) {
                            $code = (string) ($row['code'] ?? '');
                            if (str_contains(strtolower($code), 'express/kg=0-2')) {
                                $input['shipping_option_code'] = $code;
                                break;
                            }
                        }
                        if ($input['shipping_option_code'] === '') {
                            $input['shipping_option_code'] = (string) ($quoteRows[0]['code'] ?? '');
                        }
                    }
                }
            }

            if ($azione === 'etichetta') {
                $selectedCode = trim((string) ($input['shipping_option_code'] ?? ''));
                if ($selectedCode !== '') {
                    $request->session()->put('sendcloud_test_shipping_option_code', $selectedCode);
                }

                $etichetta = $probe->runAnnounce($input);
                if (! empty($etichetta['hints']['shipping_option_code'])) {
                    $input['shipping_option_code'] = (string) $etichetta['hints']['shipping_option_code'];
                    $request->session()->put('sendcloud_test_shipping_option_code', $input['shipping_option_code']);
                }
                $shipmentId = (string) (($etichetta['hints']['shipmentId'] ?? '') ?: '');
                if (($etichetta['ok'] ?? false) && $shipmentId !== '') {
                    $request->session()->put(self::SESSION_SHIPMENT_ID, $shipmentId);
                    $pdfPath = $probe->salvaPdfDaAnnounce($etichetta, $shipmentId);
                    if ($pdfPath !== null) {
                        $pdfUrl = route('test.sendcloud-etichetta.pdf', [
                            'shipmentId' => $shipmentId,
                        ]);
                    }
                }
            }

            if ($azione === 'cancella') {
                $shipmentId = trim((string) (
                    $request->input('shipment_id')
                    ?: $request->session()->get(self::SESSION_SHIPMENT_ID, '')
                ));
                $cancellazione = $probe->runCancel($shipmentId);
                if (($cancellazione['ok'] ?? false) && $shipmentId !== '') {
                    $probe->rimuoviPdfTest($shipmentId);
                    $request->session()->forget(self::SESSION_SHIPMENT_ID);
                }
            }
        }

        $client = app(SendcloudClient::class);

        return view('test.sendcloud-etichetta', [
            'input' => $input,
            'configured' => SendcloudClient::isConfigured(),
            'apiBase' => $client->baseUrl(),
            'azione' => $azione,
            'preventivo' => $preventivo,
            'etichetta' => $etichetta,
            'cancellazione' => $cancellazione,
            'quoteRows' => $quoteRows,
            'pdfUrl' => $pdfUrl,
            'sessionShipmentId' => $request->session()->get(self::SESSION_SHIPMENT_ID),
        ]);
    }

    public function downloadEtichetta(string $shipmentId): BinaryFileResponse
    {
        $safeId = preg_replace('/[^\w\-]/', '_', $shipmentId) ?: 'unknown';
        $path = storage_path('app/sendcloud_test/etichetta_'.$safeId.'.pdf');
        if (! is_file($path)) {
            abort(404, 'Etichetta non trovata. Crea prima la spedizione da /test/sendcloud-etichetta.');
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="etichetta-sendcloud-'.$safeId.'.pdf"',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function defaultInput(): array
    {
        return [
            'carrier_code' => 'poste_it_delivery',
            'shipping_option_code' => '',
            'contract_id' => '',
            'cap_origine' => '81100',
            'citta_origine' => 'Caserta',
            'mitt_cap' => '81100',
            'mitt_citta' => 'Caserta',
            'mitt_provincia' => 'CE',
            'mitt_via' => 'Via Roma',
            'mitt_civico' => '15',
            'mitt_nome' => 'Mario',
            'mitt_cognome' => 'Bianchi',
            'mitt_azienda' => 'Spedisciqui Test Mittente',
            'mitt_telefono' => '0823123456',
            'mitt_email' => 'mittente.test@spedisciqui.it',
            'cap_destino' => '80143',
            'citta_destino' => 'Napoli',
            'dest_cap' => '80143',
            'dest_citta' => 'Napoli',
            'dest_provincia' => 'NA',
            'dest_via' => 'Via Toledo',
            'dest_civico' => '88',
            'dest_nome' => 'Giuseppe',
            'dest_cognome' => 'Verdi',
            'dest_azienda' => '',
            'dest_telefono' => '0819876543',
            'dest_email' => 'destinatario.test@spedisciqui.it',
            'altezza' => '15',
            'larghezza' => '20',
            'spessore' => '30',
            'peso' => '2',
            'valore_assicurazione' => '0',
        ];
    }
}
