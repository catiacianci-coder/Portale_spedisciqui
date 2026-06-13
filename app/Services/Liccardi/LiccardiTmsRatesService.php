<?php

namespace App\Services\Liccardi;

use App\Models\corriere;
use Illuminate\Http\Client\Response;

/**
 * Preventivo TMS Liccardi (POST spedizioni/importi/getImporto) per pagina /preventivi.
 */
final class LiccardiTmsRatesService
{
    public function __construct(
        private readonly LiccardiTmsClient $client,
        private readonly LiccardiTmsPayloadBuilder $payloads,
    ) {}

    /**
     * @param  array<string, mixed>  $preventivo
     * @return array<string, mixed>
     */
    public function quoteForPreventivo(array $preventivo, corriere $corriere): array
    {
        return $this->quoteImporto($preventivo, $corriere, 0.0, 0.0);
    }

    /**
     * @return array<string, mixed>
     */
    public function quoteImporto(
        array $preventivo,
        corriere $corriere,
        float $contrassegno = 0.0,
        float $assicurazione = 0.0,
    ): array {
        if (! $this->client->isConfigured()) {
            return [
                'configured' => false,
                'error' => 'API Liccardi TMS non configurata (parametri globali).',
            ];
        }

        $input = $this->buildInputFromPreventivo($preventivo, $corriere, $contrassegno, $assicurazione);
        $companyId = $this->client->companyId();
        $payload = $this->payloads->buildQuotePayload($input, $companyId);
        $path = 'spedizioni/importi/getImporto';
        $response = $this->client->postJson($path, $payload);

        return $this->formatQuoteResult($path, $payload, $response, $corriere);
    }

    /**
     * @param  array<string, mixed>  $preventivo
     * @return array<string, mixed>
     */
    public function buildInputFromPreventivo(
        array $preventivo,
        corriere $corriere,
        float $contrassegno = 0.0,
        float $assicurazione = 0.0,
    ): array {
        $input = is_array($preventivo['input'] ?? null) ? $preventivo['input'] : [];
        $origine = is_array($preventivo['origine'] ?? null) ? $preventivo['origine'] : [];
        $destino = is_array($preventivo['destino'] ?? null) ? $preventivo['destino'] : [];

        $pvOrigine = strtoupper(trim((string) ($origine['provincia'] ?? '')));
        $pvDestino = strtoupper(trim((string) ($destino['provincia'] ?? '')));

        $codiceServizio = trim((string) ($corriere->codice_servizio ?? ''));
        if ($codiceServizio === '') {
            $codiceServizio = trim((string) ($corriere->istat ?? ''));
        }
        if ($codiceServizio === '') {
            $codiceServizio = 'E';
        }

        return [
            'codice_servizio' => $codiceServizio,
            'cap_origine' => (string) ($input['cap_origine'] ?? ''),
            'citta_origine' => (string) ($origine['comune'] ?? ''),
            'pv_origine' => $pvOrigine,
            'via_origine' => 'Via preventivo',
            'civico_origine' => '1',
            'cap_destino' => (string) ($input['cap_destino'] ?? ''),
            'citta_destino' => (string) ($destino['comune'] ?? ''),
            'pv_destino' => $pvDestino,
            'via_destino' => 'Via preventivo',
            'civico_destino' => '1',
            'peso' => (float) ($input['peso'] ?? 1),
            'altezza' => (float) ($input['altezza'] ?? 15),
            'larghezza' => (float) ($input['larghezza'] ?? 20),
            'spessore' => (float) ($input['spessore'] ?? 30),
            'contrassegno' => max(0.0, $contrassegno),
            'assicurazione' => max(0.0, $assicurazione),
            'num_colli' => 1,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function formatQuoteResult(string $path, array $payload, Response $response, corriere $corriere): array
    {
        $decoded = $response->json();
        $decoded = is_array($decoded) ? $decoded : null;
        $importo = LiccardiTmsProbeRunner::estraiImportoNumerico($decoded);

        $error = null;
        if (! $response->successful()) {
            $error = 'Errore HTTP '.$response->status().' da Liccardi TMS.';
        } elseif ($decoded !== null && isset($decoded['status']['code']) && (int) $decoded['status']['code'] !== 200) {
            $error = trim((string) ($decoded['status']['message'] ?? 'Risposta TMS non OK.'));
        } elseif ($importo === null || $importo <= 0) {
            $error = 'Importo non trovato nella risposta getImporto.';
        }

        $quote = null;
        if ($importo !== null && $importo > 0) {
            $quote = [
                'price_amount' => round($importo, 2),
                'formatted' => LiccardiTmsProbeRunner::estraiPrezzoPreventivo($decoded),
                'codice_servizio' => trim((string) ($corriere->codice_servizio ?? 'E')),
            ];
        }

        return [
            'configured' => true,
            'api_base' => $this->client->baseUrl(),
            'http_status' => $response->status(),
            'path' => $path,
            'payload' => $payload,
            'quote' => $quote,
            'response_json' => $decoded,
            'error' => $error,
        ];
    }
}
