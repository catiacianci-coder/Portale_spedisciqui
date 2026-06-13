<?php

namespace App\Services\SpedisciOnline;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;

class SpedisciOnlineCarriersService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function listCarriers(?string $piattaforma = null): array
    {
        $client = SpedisciOnlineClient::forPiattaforma($piattaforma);

        if (! $client->isConfigured()) {
            return [];
        }

        $cacheKey = 'spedisci_online.carriers.'.$client->tenant();

        return Cache::remember($cacheKey, now()->addHour(), function () use ($client) {
            $response = $client->http()->get('carriers');

            if (! $response->successful()) {
                return [];
            }

            $body = $response->json();

            return is_array($body) ? $body : [];
        });
    }

    /**
     * Opzioni per select contractCode: label = nome pannello, value = codice API.
     *
     * @return array<int, array{value: string, label: string, carrier_code: string, carrier_name: string}>
     */
    public function contractSelectOptions(?string $piattaforma = null): array
    {
        $options = [];

        foreach ($this->listCarriers($piattaforma) as $carrier) {
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

                $contractName = trim((string) ($contract['contractName'] ?? $contractCode));

                $options[] = [
                    'value' => $contractCode,
                    'label' => $carrierName.' — '.$contractName.' ('.$contractCode.')',
                    'carrier_code' => $carrierCode,
                    'carrier_name' => $carrierName,
                    'contract_name' => $contractName,
                ];
            }
        }

        usort($options, fn (array $a, array $b): int => strcmp($a['label'], $b['label']));

        return $options;
    }

    public function findContract(?string $piattaforma, string $contractCode): ?array
    {
        $contractCode = trim($contractCode);
        if ($contractCode === '') {
            return null;
        }

        foreach ($this->contractSelectOptions($piattaforma) as $option) {
            if ($option['value'] === $contractCode) {
                return $option;
            }
        }

        return null;
    }
}
