<?php

namespace App\Services\SpedisciOnline;

use App\Models\corriere;
use App\Models\spedizione;
use App\Support\SpedisciOnlineEamultiContratti;
use App\Support\SpedisciOnlineIntegrazione;
use App\Support\RitiroDateSelezionabili;

/**
 * Payload per POST /pickup/create (doc: https://apidocs.spedisci.online/api/pickup).
 */
class SpedisciOnlinePickupService
{
    public function __construct(
        private readonly SpedisciOnlineRatesService $rates,
        private readonly SpedisciOnlineCarriersService $carriers,
    ) {}

    public function createFromSpedizione(spedizione $spedizione): SpedisciOnlinePickupResult
    {
        $spedizione->loadMissing('corriereRecord');
        $corriere = $spedizione->corriereRecord;

        if ($corriere === null) {
            return new SpedisciOnlinePickupResult(false, 'Corriere non associato alla spedizione.');
        }

        $payload = $this->buildPayloadFromSpedizione($spedizione, $corriere);
        $contractCode = trim((string) ($payload['contractCode'] ?? ''));
        if ($contractCode === '') {
            return new SpedisciOnlinePickupResult(
                false,
                'codice_servizio (contractCode) mancante per il ritiro SDA.',
                payload: $payload,
            );
        }

        $client = SpedisciOnlineClient::forPiattaforma($corriere->piattaforma);
        if (! $client->isConfigured()) {
            return new SpedisciOnlinePickupResult(
                false,
                'API Spedisci.online non configurata per '.$client->tenant().'.',
                payload: $payload,
            );
        }

        $response = $client->post('/pickup/create', $payload);
        $httpStatus = $response->status();
        $bodyArr = is_array($response->json()) ? $response->json() : [];

        if (! $response->successful()) {
            $msg = trim((string) ($bodyArr['message'] ?? $bodyArr['error'] ?? ''));
            if ($msg === '') {
                $msg = 'Errore HTTP '.$httpStatus.' su /pickup/create';
            }

            return new SpedisciOnlinePickupResult(false, $msg, $httpStatus, null, $payload, $bodyArr);
        }

        $pickupId = trim((string) ($bodyArr['pickupId'] ?? $bodyArr['pickup_id'] ?? ''));

        $integrazione = array_merge(SpedisciOnlineIntegrazione::decode($spedizione), [
            'pickup' => [
                'created_at' => now()->toIso8601String(),
                'http_status' => $httpStatus,
                'pickup_id' => $pickupId !== '' ? $pickupId : null,
                'payload' => $payload,
                'response' => $bodyArr,
            ],
        ]);
        SpedisciOnlineIntegrazione::encode($spedizione, $integrazione);

        $msg = $pickupId !== ''
            ? 'Ritiro prenotato. pickupId: '.$pickupId
            : 'Ritiro prenotato (pickupId non presente in risposta).';

        return new SpedisciOnlinePickupResult(true, $msg, $httpStatus, $pickupId ?: null, $payload, $bodyArr);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayloadFromSpedizione(spedizione $spedizione, ?corriere $corriere = null): array
    {
        $corriere ??= $spedizione->corriereRecord;
        $ratesInput = $this->rates->buildInputFromSpedizione($spedizione);

        $dataRitiro = $spedizione->data_ritiro?->format('Y-m-d')
            ?? (RitiroDateSelezionabili::dateDa(now())[0] ?? now()->toDateString());

        $input = array_merge($ratesInput, [
            'spessore' => $ratesInput['lunghezza'] ?? 30,
            'peso' => (float) ($ratesInput['peso'] ?? 1),
            'data_ritiro' => $dataRitiro,
            'tracking' => trim((string) ($spedizione->tracking ?? '')),
            'pickup_contract_code' => trim((string) (
                $spedizione->codice_servizio
                ?? ($corriere ? SpedisciOnlineEamultiContratti::contractCodeForCorriere($corriere) : '')
            )),
            'pickup_carrier_code' => trim((string) ($corriere?->carrier_code ?? '')),
            'piattaforma' => $corriere?->piattaforma,
            'colli' => 1,
        ]);

        return $this->buildPayload($input);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function buildPayload(array $input): array
    {
        $ratesInput = [
            'cap_origine' => $input['cap_origine'] ?? '',
            'citta_origine' => $input['citta_origine'] ?? '',
            'mittente_nome' => trim((string) ($input['mittente_nome'] ?? '')),
            'mittente_azienda' => trim((string) ($input['mittente_azienda'] ?? '')),
            'mittente_indirizzo' => trim((string) ($input['mittente_indirizzo'] ?? '')),
            'mittente_telefono' => trim((string) ($input['mittente_telefono'] ?? '')),
            'mittente_email' => trim((string) ($input['mittente_email'] ?? '')),
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
