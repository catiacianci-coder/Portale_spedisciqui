<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;

use App\Services\Liccardi\LiccardiTmsClient;
use App\Services\Liccardi\LiccardiTmsProbeRunner;
use App\Services\Liccardi\LiccardiTmsResponseFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\View\View;

class LiccardiTmsTestController extends Controller
{
    private const SESSION_SHIPMENT_ID = 'liccardi_test_spedizione_id';

    public function show(Request $request, LiccardiTmsProbeRunner $runner): View
    {
        $defaults = $this->defaultInput();
        $defaults['spedizione_id'] = (string) $request->session()->get(self::SESSION_SHIPMENT_ID, '');
        $input = array_merge($defaults, $request->only(array_keys($defaults)));

        $azione = (string) $request->input('azione', '');
        $preventivo = null;
        $etichetta = null;
        $elimina = null;
        $pdfSolo = null;

        if ($request->isMethod('post') && LiccardiTmsClient::isConfigured()) {
            if ($azione === 'preventivo') {
                $preventivo = $runner->run('quote', $input);
                $preventivo['prezzoEstratto'] = LiccardiTmsProbeRunner::estraiPrezzoPreventivo(
                    $this->decodedBody($preventivo)
                );
            }

            if ($azione === 'etichetta') {
                $input['generate_ritiro'] = '1';
                $input['auto_close'] = '1';

                $create = $runner->run('create_fast', $input);
                $etichetta = [
                    'create' => $create,
                    'pdf' => null,
                    'pdfUrl' => null,
                ];

                $shipmentId = (int) ($create['hints']['spedizioneId'] ?? 0);
                if ($shipmentId < 1 && is_array($decoded = $this->decodedBody($create))) {
                    $shipmentId = (int) ($decoded['spedizioneId'] ?? 0);
                }

                if ($shipmentId > 0 && ($create['ok'] ?? false)) {
                    $request->session()->put(self::SESSION_SHIPMENT_ID, $shipmentId);
                    $input['spedizione_id'] = (string) $shipmentId;
                    $pdf = $runner->run('labels_pdf', $input);
                    $etichetta['pdf'] = $pdf;
                    $etichetta['pdfUrl'] = $this->salvaPdfEtichetta($shipmentId, $pdf);
                }
            }

            if ($azione === 'pdf_solo') {
                $shipmentId = (int) ($input['spedizione_id'] ?? 0);
                $pdf = $runner->run('labels_pdf', $input);
                $pdfSolo = [
                    'spedizioneId' => $shipmentId,
                    'pdf' => $pdf,
                    'pdfUrl' => $shipmentId > 0 ? $this->salvaPdfEtichetta($shipmentId, $pdf) : null,
                ];
            }

            if ($azione === 'elimina') {
                $elimina = $runner->run('delete', $input);
                if ($elimina['ok'] ?? false) {
                    $request->session()->forget(self::SESSION_SHIPMENT_ID);
                    $input['spedizione_id'] = '';
                }
            }
        }

        $client = app(LiccardiTmsClient::class);

        return view('test.liccardi-tms', [
            'input' => $input,
            'configured' => LiccardiTmsClient::isConfigured(),
            'apiBase' => $client->baseUrl(),
            'companyId' => $client->companyId(),
            'azione' => $azione,
            'preventivo' => $preventivo,
            'etichetta' => $etichetta,
            'elimina' => $elimina,
            'pdfSolo' => $pdfSolo,
            'sessionShipmentId' => $request->session()->get(self::SESSION_SHIPMENT_ID),
        ]);
    }

    public function downloadEtichetta(int $spedizioneId): BinaryFileResponse
    {
        $path = $this->pdfPath($spedizioneId);
        if (! is_file($path)) {
            abort(404, 'Etichetta non trovata. Crea prima la spedizione da /test/liccardi-tms.');
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="etichetta-liccardi-'.$spedizioneId.'.pdf"',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function defaultInput(): array
    {
        return [
            'codice_servizio' => 'E',
            'cap_origine' => '00187',
            'citta_origine' => 'Roma',
            'pv_origine' => 'RM',
            'via_origine' => 'Via Condotti',
            'civico_origine' => '5',
            'cap_destino' => '20121',
            'citta_destino' => 'Milano',
            'pv_destino' => 'MI',
            'via_destino' => 'Corso Venezia',
            'civico_destino' => '1',
            'altezza' => '30',
            'larghezza' => '25',
            'spessore' => '40',
            'peso' => '6.1',
            'mittente_azienda' => 'K91 Demo Mittente',
            'destinatario_nome' => 'Mario Rossi',
        ];
    }

    /**
     * @param  array<string, mixed>  $probeResult
     */
    private function salvaPdfEtichetta(int $spedizioneId, array $probeResult): ?string
    {
        $body = $probeResult['rawBodyBinary'] ?? null;
        if ((! is_string($body) || ! str_starts_with($body, '%PDF'))
            && is_array($probeResult['responseJson'] ?? null)) {
            $body = LiccardiTmsResponseFormatter::estraiPdfDaJson($probeResult['responseJson']);
        }
        if (! is_string($body) || $body === '' || ! str_starts_with($body, '%PDF')) {
            return null;
        }

        $dir = storage_path('app/liccardi_test');
        if (! is_dir($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        file_put_contents($this->pdfPath($spedizioneId), $body);

        return route('test.liccardi-tms.pdf', ['spedizioneId' => $spedizioneId]);
    }

    private function pdfPath(int $spedizioneId): string
    {
        return storage_path('app/liccardi_test/etichetta_'.$spedizioneId.'.pdf');
    }

    /**
     * @param  array<string, mixed>|null  $probeResult
     * @return array<string, mixed>|null
     */
    private function decodedBody(?array $probeResult): ?array
    {
        if (! is_array($probeResult)) {
            return null;
        }
        $raw = $probeResult['rawBody'] ?? '';
        if (! is_string($raw) || $raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }
}
