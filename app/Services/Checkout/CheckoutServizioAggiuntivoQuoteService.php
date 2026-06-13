<?php

namespace App\Services\Checkout;

use App\Models\corriere;
use App\Models\corrieri_servizi_aggiuntivi;
use App\Services\Liccardi\LiccardiTmsRatesService;
use App\Services\Sendcloud\SendcloudClient;
use App\Services\Sendcloud\SendcloudShippingOptionsService;
use App\Services\ServiziAggiuntiviPrezzoService;
use App\Support\PiattaformaCorriere;

/**
 * Quotazione servizi aggiuntivi (assicurazione / contrassegno) via API fornitore al checkout.
 */
final class CheckoutServizioAggiuntivoQuoteService
{
    public function __construct(
        private readonly LiccardiTmsRatesService $liccardiRates,
        private readonly SendcloudShippingOptionsService $sendcloudRates,
    ) {}

    public function corriereUsaQuoteApiServizi(corriere $corriere): bool
    {
        if ((bool) ($corriere->tariffa_interna ?? true)) {
            return false;
        }

        return PiattaformaCorriere::corriereUsaPreventivoSendcloud($corriere)
            || PiattaformaCorriere::corriereUsaPreventivoLiccardiTms($corriere);
    }

    /**
     * @param  array<string, mixed>  $preventivo
     * @return array{ok: bool, error?: string, costo_fornitore?: float, costo_cliente?: float, valore_merce?: float, testo_servizio?: string, fonte?: string}
     */
    public function quote(
        array $preventivo,
        corriere $corriere,
        corrieri_servizi_aggiuntivi $config,
        float $valoreMerce,
    ): array {
        $testo = mb_strtolower(trim((string) $config->testo_servizio));
        if (! in_array($testo, ['assicurazione', 'contrassegno'], true)) {
            return ['ok' => false, 'error' => 'Servizio non quotabile via API.'];
        }

        $errFascia = $this->validaFascia($config, $valoreMerce);
        if ($errFascia !== null) {
            return ['ok' => false, 'error' => $errFascia];
        }

        if (! $this->corriereUsaQuoteApiServizi($corriere)) {
            return $this->quoteDaListino(
                $config,
                $valoreMerce,
                $this->baseTrasportoListino($preventivo, (int) $corriere->id),
            );
        }

        if (PiattaformaCorriere::corriereUsaPreventivoLiccardiTms($corriere)) {
            return $this->quoteLiccardi($preventivo, $corriere, $config, $valoreMerce, $testo);
        }

        if (PiattaformaCorriere::corriereUsaPreventivoSendcloud($corriere)) {
            return $this->quoteSendcloud($preventivo, $corriere, $config, $valoreMerce, $testo);
        }

        return ['ok' => false, 'error' => 'Piattaforma non supportata per la quotazione servizi.'];
    }

    /**
     * @return array{ok: bool, error?: string, costo_fornitore?: float, costo_cliente?: float, valore_merce?: float, testo_servizio?: string, fonte?: string}
     */
    private function quoteLiccardi(
        array $preventivo,
        corriere $corriere,
        corrieri_servizi_aggiuntivi $config,
        float $valoreMerce,
        string $testo,
    ): array {
        $contrassegno = $testo === 'contrassegno' ? $valoreMerce : 0.0;
        $assicurazione = $testo === 'assicurazione' ? $valoreMerce : 0.0;
        $result = $this->liccardiRates->quoteImporto($preventivo, $corriere, $contrassegno, $assicurazione);
        $apiTrace = $this->wrapApiTrace('liccardi_tms', [
            $this->liccardiChiamataTrace(
                $result,
                'getImporto (contrassegno='.round($contrassegno, 2).', assicurazione='.round($assicurazione, 2).')',
            ),
        ]);

        if (! ($result['configured'] ?? false)) {
            return [
                'ok' => false,
                'error' => (string) ($result['error'] ?? 'API Liccardi non configurata.'),
                'api_trace' => $apiTrace,
            ];
        }

        $box = is_array($result['response_json']['importoSpedizione'] ?? null)
            ? $result['response_json']['importoSpedizione']
            : null;
        if ($box === null) {
            return [
                'ok' => false,
                'error' => 'Risposta Liccardi senza blocco importoSpedizione.',
                'api_trace' => $apiTrace,
            ];
        }

        if ($testo === 'contrassegno') {
            return [
                'ok' => false,
                'error' => 'Quotazione contrassegno non disponibile: commissioneContrassegno nella risposta Liccardi non è usabile come costo servizio (vedi JSON sotto).',
                'api_trace' => $apiTrace,
            ];
        }

        $costoFornitore = round((float) ($box['commissioneAssicurazione'] ?? 0), 2);
        if ($costoFornitore <= 0) {
            return [
                'ok' => false,
                'error' => 'commissioneAssicurazione assente o zero nella risposta Liccardi.',
                'api_trace' => $apiTrace,
            ];
        }

        return array_merge(
            $this->formatOk($config, $valoreMerce, $costoFornitore, 'liccardi_tms'),
            ['api_trace' => $apiTrace],
        );
    }

    /**
     * @return array{ok: bool, error?: string, costo_fornitore?: float, costo_cliente?: float, valore_merce?: float, testo_servizio?: string, fonte?: string}
     */
    private function quoteSendcloud(
        array $preventivo,
        corriere $corriere,
        corrieri_servizi_aggiuntivi $config,
        float $valoreMerce,
        string $testo,
    ): array {
        if (! SendcloudClient::isConfigured()) {
            return ['ok' => false, 'error' => 'API Sendcloud non configurata.'];
        }

        $code = trim((string) ($corriere->codice_servizio ?? ''));
        if ($code === '') {
            return ['ok' => false, 'error' => 'Codice servizio Sendcloud mancante sul corriere.'];
        }

        if ($testo === 'contrassegno') {
            return ['ok' => false, 'error' => 'Quotazione contrassegno Sendcloud non disponibile via API: usa un corriere Liccardi TMS o listino interno.'];
        }

        $payload = $this->sendcloudRates->buildNationalPayload(
            $this->sendcloudRates->inputFromPreventivo($preventivo, $valoreMerce),
        );

        $response = $this->sendcloudRates->listWithQuotes($payload);
        $apiTrace = $this->wrapApiTrace('sendcloud', [
            [
                'etichetta' => 'POST /shipping-options (calculate_quotes=true)',
                'metodo' => 'POST',
                'path' => '/shipping-options',
                'request' => array_merge(['calculate_quotes' => true], $payload),
                'http_status' => $response->status(),
                'response' => $response->json(),
            ],
        ]);

        if (! $response->successful()) {
            return [
                'ok' => false,
                'error' => $this->erroreHttpSendcloud($response),
                'api_trace' => $apiTrace,
            ];
        }

        $insurance = $this->sendcloudRates->extractInsurancePriceForCode($response->json(), $code);
        if ($insurance === null || $insurance <= 0) {
            return [
                'ok' => false,
                'error' => 'Assicurazione non disponibile per questo servizio Sendcloud.',
                'api_trace' => $apiTrace,
            ];
        }

        return array_merge(
            $this->formatOk($config, $valoreMerce, $insurance, 'sendcloud'),
            ['api_trace' => $apiTrace],
        );
    }

    /**
     * @return array{ok: bool, error?: string, costo_fornitore?: float, costo_cliente?: float, valore_merce?: float, testo_servizio?: string, fonte?: string}
     */
    private function quoteDaListino(
        corrieri_servizi_aggiuntivi $config,
        float $valoreMerce,
        float $baseTrasportoListino,
    ): array {
        $netto = ServiziAggiuntiviPrezzoService::importoNettoListino($config, $valoreMerce, $baseTrasportoListino);
        if ($netto <= 0) {
            return ['ok' => false, 'error' => 'Impossibile calcolare il costo dal listino.'];
        }

        return $this->formatOk($config, $valoreMerce, round($netto, 2), 'listino');
    }

    /**
     * @return array{ok: bool, costo_fornitore: float, costo_cliente: float, valore_merce: float, testo_servizio: string, fonte: string}
     */
    private function formatOk(
        corrieri_servizi_aggiuntivi $config,
        float $valoreMerce,
        float $costoFornitore,
        string $fonte,
    ): array {
        $costoCliente = ServiziAggiuntiviPrezzoService::importoClienteIvaEsc($costoFornitore, $config, 0.0);

        return [
            'ok' => true,
            'costo_fornitore' => $costoFornitore,
            'costo_cliente' => $costoCliente,
            'valore_merce' => round($valoreMerce, 2),
            'testo_servizio' => (string) $config->testo_servizio,
            'fonte' => $fonte,
        ];
    }

    private function validaFascia(corrieri_servizi_aggiuntivi $config, float $valoreMerce): ?string
    {
        return ServiziAggiuntiviPrezzoService::messaggioFasciaNonValidaRiga($config, $valoreMerce);
    }

    /**
     * @param  array<string, mixed>  $preventivo
     */
    private function baseTrasportoListino(array $preventivo, int $corriereId): float
    {
        foreach ($preventivo['righe'] ?? [] as $riga) {
            if (! is_array($riga)) {
                continue;
            }
            if ((int) ($riga['corriere']['id'] ?? 0) !== $corriereId) {
                continue;
            }

            return (float) ($riga['prezzo_base'] ?? 0);
        }

        return 0.0;
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function liccardiChiamataTrace(array $result, string $etichetta): array
    {
        $box = is_array($result['response_json']['importoSpedizione'] ?? null)
            ? $result['response_json']['importoSpedizione']
            : [];

        return [
            'etichetta' => $etichetta,
            'metodo' => 'POST',
            'path' => (string) ($result['path'] ?? 'spedizioni/importi/getImporto'),
            'request' => $result['payload'] ?? null,
            'http_status' => $result['http_status'] ?? null,
            'response' => $result['response_json'] ?? null,
            'tot_risposta' => $box['tot'] ?? null,
            'commissione_contrassegno' => $box['commissioneContrassegno'] ?? null,
            'commissione_assicurazione' => $box['commissioneAssicurazione'] ?? null,
            'errore' => $result['error'] ?? null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $chiamate
     * @return array{piattaforma: string, chiamate: list<array<string, mixed>>}
     */
    private function wrapApiTrace(string $piattaforma, array $chiamate): array
    {
        return [
            'piattaforma' => $piattaforma,
            'chiamate' => $chiamate,
        ];
    }

    private function erroreHttpSendcloud(\Illuminate\Http\Client\Response $response): string
    {
        $status = $response->status();
        $body = $response->json();
        if (is_array($body)) {
            foreach ($body['errors'] ?? [] as $err) {
                if (! is_array($err)) {
                    continue;
                }
                $detail = trim((string) ($err['detail'] ?? $err['message'] ?? ''));
                if ($detail !== '') {
                    return 'Sendcloud ('.$status.'): '.$detail;
                }
            }
            $detail = trim((string) ($body['detail'] ?? ''));
            if ($detail !== '') {
                return 'Sendcloud ('.$status.'): '.$detail;
            }
        }

        return 'Errore HTTP '.$status.' da Sendcloud.';
    }
}
