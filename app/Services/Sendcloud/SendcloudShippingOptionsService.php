<?php

namespace App\Services\Sendcloud;

use Illuminate\Http\Client\Response;

class SendcloudShippingOptionsService
{
    public function __construct(
        private readonly SendcloudClient $client,
    ) {}

    /**
     * Opzioni di spedizione con quotazioni (POST /shipping-options).
     *
     * @param  array<string, mixed>  $payload
     */
    public function listWithQuotes(array $payload): Response
    {
        $body = array_merge(['calculate_quotes' => true], $payload);

        return $this->client->post('/shipping-options', $body);
    }

    /**
     * Payload IT → IT per POST /shipping-options (solo spedizioni nazionali).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function buildNationalPayload(array $input): array
    {
        $peso = max(0.1, (float) ($input['peso'] ?? 1));
        $length = max(1, (int) ($input['spessore'] ?? 30));
        $width = max(1, (int) ($input['larghezza'] ?? 20));
        $height = max(1, (int) ($input['altezza'] ?? 15));
        $assicurazione = max(0.0, (float) ($input['valore_assicurazione'] ?? 0));

        $capOrigine = trim((string) ($input['cap_origine'] ?? ''));
        $capDestino = trim((string) ($input['cap_destino'] ?? ''));

        $collo = [
            'weight' => [
                'value' => number_format($peso, 3, '.', ''),
                'unit' => 'kg',
            ],
            'dimensions' => [
                'length' => (string) $length,
                'width' => (string) $width,
                'height' => (string) $height,
                'unit' => 'cm',
            ],
        ];

        $insured = self::additionalInsuredAmountForQuotes($assicurazione > 0 ? $assicurazione : null);
        if ($insured !== null) {
            $collo['additional_insured_price'] = $insured;
        }

        $payload = [
            'from_address' => [
                'country_code' => 'IT',
                'postal_code' => $capOrigine,
                'city' => trim((string) ($input['citta_origine'] ?? 'Roma')),
            ],
            'to_address' => [
                'country_code' => 'IT',
                'postal_code' => $capDestino,
                'city' => trim((string) ($input['citta_destino'] ?? 'Milano')),
            ],
            'parcels' => [$collo],
        ];

        $servicePointId = (int) ($input['to_service_point'] ?? 0);
        if ($servicePointId > 0) {
            $payload['to_service_point'] = [
                'id' => (string) $servicePointId,
            ];
        }

        return $payload;
    }

    /**
     * Parametri IT→IT da sessione preventivo (CAP destinatario / locker inclusi).
     *
     * @param  array<string, mixed>  $preventivo
     * @return array<string, mixed>
     */
    public function inputFromPreventivo(array $preventivo, float $valoreAssicurazione = 0.0): array
    {
        $input = is_array($preventivo['input'] ?? null) ? $preventivo['input'] : [];
        $ind = is_array($preventivo['indirizzi'] ?? null) ? $preventivo['indirizzi'] : [];
        $dest = is_array($ind['destinazione'] ?? null) ? $ind['destinazione'] : [];

        $data = [
            'cap_origine' => (string) ($input['cap_origine'] ?? ''),
            'cap_destino' => (string) ($dest['cap'] ?? $input['cap_destino'] ?? ''),
            'citta_origine' => (string) (($preventivo['origine']['comune'] ?? 'Roma')),
            'citta_destino' => (string) ($dest['comune'] ?? $preventivo['destino']['comune'] ?? 'Milano'),
            'peso' => (float) ($input['peso'] ?? 1),
            'spessore' => (float) ($input['spessore'] ?? 30),
            'larghezza' => (float) ($input['larghezza'] ?? 20),
            'altezza' => (float) ($input['altezza'] ?? 15),
        ];

        if ($valoreAssicurazione > 0) {
            $data['valore_assicurazione'] = $valoreAssicurazione;
        }

        $servicePointId = (int) ($dest['to_service_point'] ?? 0);
        if ($servicePointId > 0) {
            $data['to_service_point'] = $servicePointId;
        }

        return $data;
    }

    /**
     * Costo assicurazione (€) dalla breakdown della risposta shipping-options.
     */
    public function extractInsurancePriceForCode(mixed $body, string $shippingOptionCode): ?float
    {
        if (! is_array($body) || $shippingOptionCode === '') {
            return null;
        }

        $options = $body['data'] ?? $body['shipping_options'] ?? (array_is_list($body) ? $body : []);
        if (! is_array($options)) {
            return null;
        }

        foreach ($options as $row) {
            if (! is_array($row)) {
                continue;
            }
            $code = (string) ($row['code'] ?? $row['shipping_option_code'] ?? '');
            if ($code !== $shippingOptionCode) {
                continue;
            }

            $quotes = $row['quotes'] ?? null;
            if (! is_array($quotes)) {
                return null;
            }
            $first = array_is_list($quotes) ? ($quotes[0] ?? null) : $quotes;
            if (! is_array($first)) {
                return null;
            }
            $breakdown = $first['price']['breakdown'] ?? null;
            if (! is_array($breakdown)) {
                return null;
            }

            foreach ($breakdown as $item) {
                if (! is_array($item)) {
                    continue;
                }
                if (($item['type'] ?? '') !== 'insurance_price') {
                    continue;
                }
                $price = $item['price'] ?? null;
                if (! is_array($price)) {
                    continue;
                }
                $value = $price['value'] ?? null;
                if ($value === null || $value === '') {
                    return null;
                }

                return round((float) $value, 2);
            }

            return null;
        }

        return null;
    }

    /**
     * Estrae righe leggibili da risposta shipping-options.
     *
     * @return list<array{code: string, name: string, carrier: string, price: string, price_amount: float|null, currency: string, lead_time_hours: int|null, tracked: bool|null, tracking_label: string}>
     */
    public function parseQuoteRows(mixed $body): array
    {
        if (! is_array($body)) {
            return [];
        }

        $options = $body['data'] ?? $body['shipping_options'] ?? (array_is_list($body) ? $body : []);
        if (! is_array($options)) {
            return [];
        }

        $rows = [];
        foreach ($options as $row) {
            if (! is_array($row)) {
                continue;
            }
            $code = (string) ($row['code'] ?? $row['shipping_option_code'] ?? '');
            $name = (string) ($row['name'] ?? $row['title'] ?? '');
            $carrier = (string) ($row['carrier']['name'] ?? $row['carrier_code'] ?? $row['carrier'] ?? '');
            $quoteMeta = $this->extractQuoteMeta($row['quotes'] ?? null);
            if ($code === '' && $name === '') {
                continue;
            }
            $functionalities = is_array($row['functionalities'] ?? null) ? $row['functionalities'] : [];
            $codRaw = $functionalities['cash_on_delivery'] ?? null;
            $trackedRaw = array_key_exists('tracked', $functionalities)
                ? $functionalities['tracked']
                : null;
            $rows[] = [
                'code' => $code !== '' ? $code : '—',
                'name' => $name !== '' ? $name : '—',
                'carrier' => $carrier !== '' ? $carrier : '—',
                'price' => $quoteMeta['label'],
                'price_amount' => $quoteMeta['amount'],
                'currency' => $quoteMeta['currency'],
                'lead_time_hours' => $quoteMeta['lead_time_hours'],
                'cash_on_delivery' => $codRaw,
                'contrassegno_label' => self::labelContrassegno($codRaw),
                'tracked' => is_bool($trackedRaw) ? $trackedRaw : null,
                'tracking_label' => self::labelTracked($trackedRaw),
                'insurance_price' => null,
                'insurance_label' => '—',
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public function enrichRowsWithInsurancePrices(array $rows, mixed $bodyInsurance): array
    {
        foreach ($rows as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            $code = (string) ($row['code'] ?? '');
            if ($code === '' || $code === '—') {
                continue;
            }
            $insurance = $this->extractInsurancePriceForCode($bodyInsurance, $code);
            $rows[$i]['insurance_price'] = $insurance;
            $rows[$i]['insurance_label'] = ($insurance !== null && $insurance > 0)
                ? \App\Support\ImportoEuro::format($insurance)
                : '—';
        }

        return $rows;
    }

    /**
     * Importo assicurazione per POST /shipping-options (schema: number).
     */
    public static function additionalInsuredAmountForQuotes(?float $euro): ?float
    {
        return self::clampInsuredEuro($euro);
    }

    /**
     * Assicurazione per POST /shipments/announce (schema: optional-price).
     *
     * @return array{value: string, currency: string}|null
     */
    public static function additionalInsuredPriceForShipment(?float $euro): ?array
    {
        $amount = self::clampInsuredEuro($euro);
        if ($amount === null) {
            return null;
        }

        return [
            'value' => number_format($amount, 2, '.', ''),
            'currency' => 'EUR',
        ];
    }

    /**
     * @deprecated Usare additionalInsuredPriceForShipment
     *
     * @return array{value: string, currency: string}|null
     */
    public static function additionalInsuredPrice(?float $euro): ?array
    {
        return self::additionalInsuredPriceForShipment($euro);
    }

    private static function clampInsuredEuro(?float $euro): ?float
    {
        if ($euro === null || $euro <= 0) {
            return null;
        }

        return round(max(2.0, min(5000.0, $euro)), 2);
    }

    /**
     * @return list<string>
     */
    public function extractOptionCodes(mixed $body): array
    {
        $codes = [];
        foreach ($this->parseQuoteRows($body) as $row) {
            $code = trim((string) ($row['code'] ?? ''));
            if ($code !== '' && $code !== '—') {
                $codes[] = $code;
            }
        }

        return $codes;
    }

    private static function labelContrassegno(mixed $codRaw): string
    {
        if ($codRaw === null || $codRaw === '') {
            return 'No';
        }

        return 'Sì (max '.(string) $codRaw.')';
    }

    private static function labelTracked(mixed $tracked): string
    {
        if ($tracked === true) {
            return 'Sì';
        }
        if ($tracked === false) {
            return 'No';
        }

        return '—';
    }

    /**
     * @return array{label: string, amount: float|null, currency: string, lead_time_hours: int|null}
     */
    private function extractQuoteMeta(mixed $quotes): array
    {
        if (! is_array($quotes)) {
            return [
                'label' => '—',
                'amount' => null,
                'currency' => 'EUR',
                'lead_time_hours' => null,
            ];
        }

        $first = array_is_list($quotes) ? ($quotes[0] ?? null) : $quotes;
        if (! is_array($first)) {
            return [
                'label' => '—',
                'amount' => null,
                'currency' => 'EUR',
                'lead_time_hours' => null,
            ];
        }

        $price = $first['price'] ?? null;
        if (! is_array($price)) {
            return [
                'label' => '—',
                'amount' => null,
                'currency' => 'EUR',
                'lead_time_hours' => null,
            ];
        }

        $total = is_array($price['total'] ?? null) ? $price['total'] : null;
        $value = $total['value'] ?? $price['value'] ?? $price['amount'] ?? null;
        $currency = (string) ($total['currency'] ?? $price['currency'] ?? 'EUR');
        $leadTime = isset($first['lead_time']) ? (int) $first['lead_time'] : null;

        if ($value === null || $value === '') {
            return [
                'label' => '—',
                'amount' => null,
                'currency' => $currency,
                'lead_time_hours' => $leadTime,
            ];
        }

        return [
            'label' => trim((string) $value).' '.$currency,
            'amount' => (float) $value,
            'currency' => $currency,
            'lead_time_hours' => $leadTime,
        ];
    }
}
