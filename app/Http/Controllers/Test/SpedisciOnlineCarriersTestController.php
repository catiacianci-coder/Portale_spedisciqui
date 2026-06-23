<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use App\Models\corriere;
use App\Services\SpedisciOnline\SpedisciOnlineClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpedisciOnlineCarriersTestController extends Controller
{
    public function show(Request $request): View
    {
        $tenant = (string) $request->input('tenant', 'eamulti');
        if (! in_array($tenant, ['eamulti', 'liccardi'], true)) {
            $tenant = 'eamulti';
        }

        $client = new SpedisciOnlineClient($tenant);
        $apiBase = $client->baseUrl();
        $configured = $client->isConfigured();

        $httpStatus = null;
        $rawBody = null;
        $errorMessage = null;
        $carriers = [];
        $contractRows = [];
        $executed = $request->isMethod('post') || $request->boolean('esegui');

        if ($executed) {
            if (! $configured) {
                $denom = $tenant === 'liccardi'
                    ? 'spedisci_online_liccardi_api_key'
                    : 'spedisci_online_eamulti_api_key';
                $errorMessage = "API key non configurata (parametro {$denom}).";
            } else {
                $response = $client->http()->get('carriers');
                $httpStatus = $response->status();
                $rawBody = $response->body();
                $body = $response->json();

                if (! $response->successful()) {
                    $errorMessage = trim((string) (is_array($body) ? ($body['message'] ?? $body['error'] ?? '') : ''));
                    if ($errorMessage === '') {
                        $errorMessage = 'Errore HTTP '.$httpStatus.' su GET /carriers';
                    }
                } elseif (is_array($body)) {
                    $carriers = $body;
                    $contractRows = $this->flattenContracts($carriers);
                }
            }
        }

        $corrieriDb = corriere::query()
            ->where('attivo', true)
            ->where(function ($q): void {
                $q->where('piattaforma', 'like', 'quick_%')
                    ->orWhere('piattaforma', 'like', 'eamultiexp%')
                    ->orWhere('piattaforma', 'like', 'liccardi_spediscionline%');
            })
            ->orderBy('nome_corriere')
            ->get(['id', 'nome_corriere', 'nome_servizio', 'piattaforma', 'carrier_code', 'contract_code']);

        return view('test.spedisci-online-carriers', [
            'tenant' => $tenant,
            'apiBase' => $apiBase,
            'configured' => $configured,
            'executed' => $executed,
            'httpStatus' => $httpStatus,
            'rawBody' => $rawBody,
            'errorMessage' => $errorMessage,
            'carriers' => $carriers,
            'contractRows' => $contractRows,
            'corrieriDb' => $corrieriDb,
        ]);
    }

    /**
     * @param  array<int, mixed>  $carriers
     * @return list<array{carrier_code: string, carrier_name: string, contract_code: string, contract_name: string}>
     */
    private function flattenContracts(array $carriers): array
    {
        $rows = [];

        foreach ($carriers as $carrier) {
            if (! is_array($carrier)) {
                continue;
            }

            $carrierCode = (string) ($carrier['carrierCode'] ?? '');
            $carrierName = (string) ($carrier['carrierName'] ?? $carrierCode);

            foreach ($carrier['contracts'] ?? [] as $contract) {
                if (! is_array($contract)) {
                    continue;
                }

                $contractCode = trim((string) ($contract['contractCode'] ?? ''));
                if ($contractCode === '') {
                    continue;
                }

                $rows[] = [
                    'carrier_code' => $carrierCode,
                    'carrier_name' => $carrierName,
                    'contract_code' => $contractCode,
                    'contract_name' => trim((string) ($contract['contractName'] ?? $contractCode)),
                ];
            }
        }

        usort($rows, fn (array $a, array $b): int => strcmp(
            $a['carrier_name'].' '.$a['contract_name'],
            $b['carrier_name'].' '.$b['contract_name'],
        ));

        return $rows;
    }
}
