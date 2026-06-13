<?php

namespace App\Services\SpedisciOnline;

/**
 * Payload per POST /pickup/create (doc: https://apidocs.spedisci.online/api/pickup).
 */
class SpedisciOnlinePickupService
{
    public function __construct(
        private readonly SpedisciOnlineRatesService $rates,
        private readonly SpedisciOnlineCarriersService $carriers,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function buildPayload(array $input): array
    {
        $ratesInput = [
            'cap_origine' => $input['cap_origine'] ?? '',
            'citta_origine' => $input['citta_origine'] ?? '',
            'mittente_nome' => $input['mittente_nome'] ?? 'Mittente test',
            'mittente_azienda' => $input['mittente_azienda'] ?? 'Spedisciqui',
            'mittente_indirizzo' => $input['mittente_indirizzo'] ?? 'Via test 1',
            'mittente_telefono' => $input['mittente_telefono'] ?? '0612345678',
            'mittente_email' => $input['mittente_email'] ?? 'mittente@test.local',
            'peso' => $input['peso'] ?? 1,
            'lunghezza' => $input['spessore'] ?? 30,
            'larghezza' => $input['larghezza'] ?? 20,
            'altezza' => $input['altezza'] ?? 15,
            'cap_destino' => $input['cap_destino'] ?? '20100',
        ];

        $shipFrom = $this->rates->buildPayload($ratesInput)['shipFrom'];

        $contractCode = trim((string) ($input['pickup_contract_code'] ?? $input['contract_code'] ?? ''));
        $carrierCode = trim((string) ($input['pickup_carrier_code'] ?? $input['carrier_code'] ?? ''));

        $contractMeta = $this->carriers->findContract(
            (string) ($input['piattaforma'] ?? ''),
            $contractCode,
        );
        if ($contractMeta !== null && $carrierCode === '') {
            $carrierCode = $contractMeta['carrier_code'];
        }

        $pickupTime = trim((string) ($input['ora_inizio'] ?? '09:00'));
        if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $pickupTime)) {
            $pickupTime = substr($pickupTime, 0, 5);
        }

        $colli = max(1, (int) ($input['colli'] ?? 1));
        $pesoTotale = (float) ($input['peso'] ?? 1);
        $pesoPerCollo = number_format($colli > 0 ? $pesoTotale / $colli : $pesoTotale, 1, '.', '');
        $length = (int) ($input['spessore'] ?? 30);
        $width = (int) ($input['larghezza'] ?? 20);
        $height = (int) ($input['altezza'] ?? 15);

        $packagesDetails = [];
        for ($i = 0; $i < $colli; $i++) {
            $packagesDetails[] = [
                'weight' => $pesoPerCollo,
                'length' => $length,
                'width' => $width,
                'height' => $height,
            ];
        }

        $payload = [
            'contractCode' => $contractCode,
            'pickupDate' => trim((string) ($input['data_ritiro'] ?? date('Y-m-d', strtotime('+1 weekday')))),
            'pickupTime' => $pickupTime,
            'shipFrom' => $shipFrom,
            'packagesDetails' => $packagesDetails,
        ];

        if ($carrierCode !== '') {
            $payload['carrierCode'] = $carrierCode;
        }

        $shipmentId = trim((string) ($input['tracking'] ?? ''));
        if ($shipmentId !== '') {
            $payload['shipmentId'] = $shipmentId;
        }

        $note = trim((string) ($input['note_ritiro'] ?? ''));
        if ($note !== '') {
            $payload['specialInstruction'] = $note;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function decodeCustomPayload(?string $json): ?array
    {
        $json = trim((string) $json);
        if ($json === '') {
            return null;
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }
}
