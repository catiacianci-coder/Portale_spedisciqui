<?php

namespace App\Services\Sendcloud;

use App\Models\corriere;
use Illuminate\Support\Facades\Cache;

/**
 * Risolve contract_id Sendcloud per corriere (campo DB o elenco /contracts).
 */
final class SendcloudContractResolver
{
    public function __construct(
        private readonly SendcloudClient $client,
    ) {}

    public function resolveForCorriere(corriere $corriere): ?int
    {
        $explicit = trim((string) ($corriere->contract_code ?? ''));
        if ($explicit !== '' && ctype_digit($explicit)) {
            return (int) $explicit;
        }

        $carrier = $this->carrierCode($corriere);
        if ($carrier === '') {
            return null;
        }

        return $this->contractIdForCarrier($carrier);
    }

    public function carrierCode(corriere $corriere): string
    {
        $carrier = trim((string) ($corriere->carrier_code ?? ''));
        if ($carrier !== '') {
            return $carrier;
        }

        $service = trim((string) ($corriere->codice_servizio ?? ''));
        if ($service !== '' && str_contains($service, ':')) {
            return explode(':', $service, 2)[0];
        }

        return '';
    }

    private function contractIdForCarrier(string $carrierCode): ?int
    {
        $carrierCode = strtolower(trim($carrierCode));
        if ($carrierCode === '') {
            return null;
        }

        $contracts = $this->contractsIndex();
        foreach ($contracts as $row) {
            if (! is_array($row)) {
                continue;
            }
            $code = strtolower(trim((string) (
                $row['carrier']['code']
                ?? $row['carrier_code']
                ?? ''
            )));
            if ($code === $carrierCode) {
                $id = (int) ($row['id'] ?? 0);

                return $id > 0 ? $id : null;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function contractsIndex(): array
    {
        if (! SendcloudClient::isConfigured()) {
            return [];
        }

        return Cache::remember('sendcloud_contracts_index', 300, function (): array {
            $response = $this->client->get('/contracts');
            if (! $response->successful()) {
                return [];
            }

            $rows = $response->json('data') ?? $response->json('contracts') ?? $response->json() ?? [];

            return is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
        });
    }
}
