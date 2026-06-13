<?php

namespace App\Services\Sendcloud;

use Illuminate\Http\Client\Response;

class SendcloudServicePointsService
{
    private const DAY_LABELS = [
        'monday' => 'Lunedì',
        'tuesday' => 'Martedì',
        'wednesday' => 'Mercoledì',
        'thursday' => 'Giovedì',
        'friday' => 'Venerdì',
        'saturday' => 'Sabato',
        'sunday' => 'Domenica',
    ];

    public function __construct(
        private readonly SendcloudClient $client,
    ) {}

    /**
     * GET /service-points — ricerca punti (v3, beta).
     *
     * @param  array<string, mixed>  $input
     */
    public function search(array $input): Response
    {
        return $this->client->get('/service-points', $this->buildQuery($input, ''));
    }

    public function searchMittente(
        string $cap,
        string $city,
        ?string $generalShopType = null,
        ?string $carrierShopType = null,
        int $limit = 40,
        int $radiusM = 8000,
        ?string $carrierCode = null,
    ): Response {
        return $this->client->get('/service-points', $this->buildServicePointsQuery(
            $cap,
            $city,
            $generalShopType,
            $carrierShopType,
            $limit,
            $radiusM,
            $carrierCode,
        ));
    }

    public function searchDestinatario(
        string $cap,
        string $city,
        ?string $generalShopType = null,
        ?string $carrierShopType = null,
        int $limit = 50,
        int $radiusM = 5000,
        ?string $carrierCode = null,
    ): Response {
        return $this->searchMittente($cap, $city, $generalShopType, $carrierShopType, $limit, $radiusM, $carrierCode);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildServicePointsQuery(
        string $cap,
        string $city,
        ?string $generalShopType,
        ?string $carrierShopType,
        int $limit,
        int $radiusM,
        ?string $carrierCode,
    ): array {
        $query = [
            'country_code' => 'IT',
            'limit' => min(200, max(1, $limit)),
        ];

        $cap = trim($cap);
        $city = trim($city);
        if ($cap !== '') {
            $query['address_postal_code'] = $cap;
        }
        if ($city !== '') {
            $query['address_city'] = $city;
        }

        if ($radiusM >= 100 && $radiusM <= 50000) {
            $query['radius'] = $radiusM;
        }

        $carrierCode = trim((string) $carrierCode);
        if ($carrierCode !== '') {
            $query['carrier_code'] = $carrierCode;
        } else {
            $query['use_integration_carriers'] = 'true';
        }

        $generalShopType = trim((string) $generalShopType);
        if ($generalShopType !== '') {
            $query['general_shop_type'] = $generalShopType;
        }

        $carrierShopType = trim((string) $carrierShopType);
        if ($carrierShopType !== '') {
            $query['carrier_shop_type'] = $carrierShopType;
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function searchWithPrefix(array $input, string $prefix): Response
    {
        return $this->client->get('/service-points', $this->buildQuery($input, $prefix));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function buildQueryPreview(array $input, string $prefix = ''): array
    {
        return $this->buildQuery($input, $prefix);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function buildQuery(array $input, string $prefix): array
    {
        $p = $prefix;
        $limitKey = $p.'limit';
        $query = [
            'country_code' => 'IT',
            'limit' => min(200, max(1, (int) ($input[$limitKey] ?? $input['sp_limit'] ?? 50))),
        ];

        $useIntegration = filter_var(
            $input[$p.'use_integration_carriers'] ?? $input['sp_use_integration_carriers'] ?? false,
            FILTER_VALIDATE_BOOLEAN,
        );

        if ($useIntegration) {
            $query['use_integration_carriers'] = 'true';
        } else {
            $carrier = trim((string) ($input[$p.'carrier_code'] ?? $input['sp_carrier_code'] ?? 'poste_italiane'));
            if ($carrier !== '') {
                $query['carrier_code'] = $carrier;
            }
        }

        $cap = trim((string) ($input[$p.'cap'] ?? ''));
        $city = trim((string) ($input[$p.'citta'] ?? ''));
        if ($cap === '' && $p === 'mitt_') {
            $cap = trim((string) ($input['cap_origine'] ?? ''));
            $city = trim((string) ($input['citta_origine'] ?? ''));
        }
        if ($cap === '' && $p === 'dest_') {
            $cap = trim((string) ($input['cap_destino'] ?? ''));
            $city = trim((string) ($input['citta_destino'] ?? ''));
        }
        if ($cap === '' && $p === '') {
            $cap = trim((string) ($input['sp_cap'] ?? $input['cap_destino'] ?? ''));
            $city = trim((string) ($input['sp_citta'] ?? $input['citta_destino'] ?? ''));
        }

        if ($cap !== '') {
            $query['address_postal_code'] = $cap;
        }
        if ($city !== '') {
            $query['address_city'] = $city;
        }

        $radius = (int) ($input[$p.'radius'] ?? $input['sp_radius'] ?? 5000);
        if ($radius >= 100 && $radius <= 50000) {
            $query['radius'] = $radius;
        }

        $shopType = trim((string) ($input[$p.'shop_type'] ?? $input['sp_shop_type'] ?? ''));
        if ($shopType !== '') {
            $query['general_shop_type'] = $shopType;
        }

        return $query;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function parseRows(mixed $body): array
    {
        if (! is_array($body)) {
            return [];
        }

        $results = $body['data']['results'] ?? $body['results'] ?? [];
        if (! is_array($results)) {
            return [];
        }

        $rows = [];
        foreach ($results as $row) {
            if (! is_array($row)) {
                continue;
            }

            $address = is_array($row['address'] ?? null) ? $row['address'] : [];
            $position = is_array($row['position'] ?? null) ? $row['position'] : [];
            $carrier = is_array($row['carrier'] ?? null) ? $row['carrier'] : [];

            $street = trim((string) ($address['street'] ?? ''));
            $house = trim((string) ($address['house_number'] ?? ''));
            $line = trim($street.($house !== '' ? ' '.$house : ''));

            $lat = $position['latitude'] ?? $row['latitude'] ?? null;
            $lng = $position['longitude'] ?? $row['longitude'] ?? null;

            $rows[] = [
                'id' => $row['id'] ?? null,
                'name' => (string) ($row['name'] ?? '—'),
                'carrier_code' => (string) ($carrier['code'] ?? $row['carrier'] ?? '—'),
                'carrier_shop_type' => (string) ($row['carrier_shop_type'] ?? '—'),
                'general_shop_type' => (string) ($row['general_shop_type'] ?? '—'),
                'street' => $street !== '' ? $street : '—',
                'house_number' => $house !== '' ? $house : '—',
                'address_line' => $line !== '' ? $line : '—',
                'postal_code' => (string) ($address['postal_code'] ?? '—'),
                'city' => (string) ($address['city'] ?? '—'),
                'latitude' => $lat !== null && $lat !== '' ? (float) $lat : null,
                'longitude' => $lng !== null && $lng !== '' ? (float) $lng : null,
                'distance_m' => $row['distance'] ?? null,
                'is_expired' => (bool) ($row['is_expired'] ?? false),
                'opening_hours' => $this->formatOpeningTimes($row['opening_times'] ?? null),
                'is_open_tomorrow' => (bool) ($row['is_open_tomorrow'] ?? false),
                'next_open_at' => isset($row['next_open_at']) ? (string) $row['next_open_at'] : null,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{day: string, hours: string}>
     */
    public function formatOpeningTimes(mixed $openingTimes): array
    {
        if (! is_array($openingTimes)) {
            return [];
        }

        $lines = [];
        foreach (self::DAY_LABELS as $key => $label) {
            $slots = $openingTimes[$key] ?? null;
            if (! is_array($slots) || $slots === []) {
                $lines[] = ['day' => $label, 'hours' => 'Chiuso'];

                continue;
            }

            $parts = [];
            foreach ($slots as $slot) {
                if (! is_array($slot)) {
                    continue;
                }
                $start = trim((string) ($slot['start_time'] ?? ''));
                $end = trim((string) ($slot['end_time'] ?? ''));
                if ($start !== '' && $end !== '') {
                    $parts[] = $start.' – '.$end;
                }
            }

            $lines[] = [
                'day' => $label,
                'hours' => $parts !== [] ? implode(', ', $parts) : '—',
            ];
        }

        return $lines;
    }

    /**
     * @return array{status: string|null, precision: string|null}
     */
    public function parseGeocoding(mixed $body): array
    {
        if (! is_array($body)) {
            return ['status' => null, 'precision' => null];
        }

        $geo = $body['data']['geocoding'] ?? null;
        if (! is_array($geo)) {
            return ['status' => null, 'precision' => null];
        }

        return [
            'status' => isset($geo['status']) ? (string) $geo['status'] : null,
            'precision' => isset($geo['precision']) ? (string) $geo['precision'] : null,
        ];
    }
}
