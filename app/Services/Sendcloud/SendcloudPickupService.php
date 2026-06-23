<?php

namespace App\Services\Sendcloud;

use App\Models\corriere;
use App\Models\spedizione;
use App\Support\IndirizzoViaCivico;
use App\Support\RitiroCheckoutDomicilio;
use App\Support\RitiroDateSelezionabili;
use App\Support\SendcloudIntegrazione;
use App\Support\SpedizioneCampiPersistenza;
use Illuminate\Support\Carbon;

/**
 * Prenotazione ritiro a domicilio Sendcloud (POST /pickups).
 *
 * @see https://sendcloud.dev/docs/shipments/pickups
 */
class SendcloudPickupService
{
    public function __construct(
        private readonly SendcloudClient $client,
        private readonly SendcloudContractResolver $contracts,
    ) {}

    public function createFromSpedizione(spedizione $spedizione): SendcloudPickupResult
    {
        $spedizione->loadMissing('corriereRecord');
        $corriere = $spedizione->corriereRecord;

        if ($corriere === null) {
            return new SendcloudPickupResult(false, 'Corriere non associato alla spedizione.');
        }

        if (! RitiroCheckoutDomicilio::spedizioneRichiedePickup($spedizione)) {
            return new SendcloudPickupResult(false, 'Ritiro a domicilio Sendcloud non richiesto per questa spedizione.');
        }

        $payload = $this->buildPayloadFromSpedizione($spedizione, $corriere);
        $carrierCode = trim((string) ($payload['carrier_code'] ?? ''));
        if ($carrierCode === '') {
            return new SendcloudPickupResult(
                false,
                'carrier_code Sendcloud mancante per il ritiro.',
                payload: $payload,
            );
        }

        $errAddr = $this->validaIndirizzoPickup($payload['address'] ?? []);
        if ($errAddr !== null) {
            return new SendcloudPickupResult(false, $errAddr, payload: $payload);
        }

        if (! SendcloudClient::isConfigured()) {
            return new SendcloudPickupResult(
                false,
                'API Sendcloud non configurata.',
                payload: $payload,
            );
        }

        $response = $this->client->post('/pickups', $payload);
        $httpStatus = $response->status();
        $bodyArr = is_array($response->json()) ? $response->json() : [];

        if (! $response->successful()) {
            $msg = trim((string) ($bodyArr['message'] ?? $bodyArr['error'] ?? ''));
            if ($msg === '' && is_array($bodyArr['errors'] ?? null)) {
                $msg = json_encode($bodyArr['errors'], JSON_UNESCAPED_UNICODE) ?: '';
            }
            if ($msg === '') {
                $msg = 'Errore HTTP '.$httpStatus.' su /pickups';
            }

            return new SendcloudPickupResult(false, $msg, $httpStatus, null, $payload, $bodyArr);
        }

        $pickupId = $this->estraiPickupId($bodyArr);

        $integrazione = array_merge(SendcloudIntegrazione::decode($spedizione), [
            'pickup' => [
                'created_at' => now()->toIso8601String(),
                'http_status' => $httpStatus,
                'pickup_id' => $pickupId,
                'payload' => $payload,
                'response' => $bodyArr,
            ],
        ]);
        SendcloudIntegrazione::encode($spedizione, $integrazione);

        $msg = $pickupId !== null
            ? 'Ritiro prenotato. pickup id: '.$pickupId
            : 'Ritiro prenotato (id non presente in risposta).';

        return new SendcloudPickupResult(true, $msg, $httpStatus, $pickupId, $payload, $bodyArr);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayloadFromSpedizione(spedizione $spedizione, ?corriere $corriere = null): array
    {
        $corriere ??= $spedizione->corriereRecord;
        $mitt = SpedizioneCampiPersistenza::mittenteArray($spedizione);
        $pacco = SpedizioneCampiPersistenza::paccoArray($spedizione);
        $peso = max(0.1, (float) ($pacco['peso_kg'] ?? $spedizione->peso ?? 1));

        $carrierCode = $corriere ? $this->contracts->carrierCode($corriere) : '';
        $dataRitiro = $spedizione->data_ritiro?->format('Y-m-d')
            ?? (RitiroDateSelezionabili::dateDa(now())[0] ?? now()->toDateString());

        $payload = [
            'carrier_code' => $carrierCode,
            'address' => $this->pickupAddress($mitt),
            'time_slots' => $this->timeSlotsForDate($dataRitiro, $carrierCode),
            'quantity' => 1,
            'total_weight' => [
                'value' => number_format($peso, 2, '.', ''),
                'unit' => 'kg',
            ],
            'reference' => $this->referenceSpedizione($spedizione),
            'items' => [[
                'quantity' => 1,
                'container_type' => 'parcel',
                'total_weight' => [
                    'value' => number_format($peso, 2, '.', ''),
                    'unit' => 'kg',
                ],
            ]],
        ];

        if ($corriere !== null) {
            $contractId = $this->contracts->resolveForCorriere($corriere);
            if ($contractId !== null && $contractId > 0) {
                $payload['contract_id'] = $contractId;
            }
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $mitt
     * @return array<string, mixed>
     */
    private function pickupAddress(array $mitt): array
    {
        [$via, $civico] = IndirizzoViaCivico::perSendcloud(
            isset($mitt['indirizzo']) ? (string) $mitt['indirizzo'] : null,
            isset($mitt['numero']) ? (string) $mitt['numero'] : null,
            isset($mitt['via']) ? (string) $mitt['via'] : null,
        );

        $nome = trim((string) (($mitt['nome'] ?? '').' '.($mitt['cognome'] ?? '')));
        if ($nome === '') {
            $nome = 'Mittente';
        }

        $company = trim((string) ($mitt['denominazione_impresa'] ?? $mitt['ragione_sociale'] ?? ''));
        $prov = strtoupper(trim((string) ($mitt['provincia'] ?? '')));

        return array_filter([
            'name' => $nome,
            'company_name' => $company !== '' ? $company : null,
            'address_line_1' => $via !== '' ? $via : null,
            'house_number' => $civico !== '' ? $civico : null,
            'address_line_2' => trim((string) ($mitt['indirizzo2'] ?? '')) ?: null,
            'postal_code' => trim((string) ($mitt['cap'] ?? '')),
            'city' => trim((string) ($mitt['comune'] ?? $mitt['citta'] ?? '')),
            'country_code' => 'IT',
            'state_province_code' => $prov !== '' ? 'IT-'.$prov : null,
            'phone_number' => $this->normalizzaTelefono((string) ($mitt['telefono'] ?? '')),
            'email' => trim((string) ($mitt['email'] ?? '')),
        ], static fn ($v) => $v !== null && $v !== '');
    }

    /**
     * @return list<array{start_at: string, end_at: string}>
     */
    private function timeSlotsForDate(string $date, string $carrierCode): array
    {
        $tz = 'Europe/Rome';
        $code = strtolower(trim($carrierCode));

        if (in_array($code, ['brt', 'gls_it'], true)) {
            return [
                [
                    'start_at' => Carbon::parse($date.' 09:00:00', $tz)->utc()->toIso8601String(),
                    'end_at' => Carbon::parse($date.' 12:00:00', $tz)->utc()->toIso8601String(),
                ],
                [
                    'start_at' => Carbon::parse($date.' 14:00:00', $tz)->utc()->toIso8601String(),
                    'end_at' => Carbon::parse($date.' 17:00:00', $tz)->utc()->toIso8601String(),
                ],
            ];
        }

        return [[
            'start_at' => Carbon::parse($date.' 09:00:00', $tz)->utc()->toIso8601String(),
            'end_at' => Carbon::parse($date.' 17:00:00', $tz)->utc()->toIso8601String(),
        ]];
    }

    private function referenceSpedizione(spedizione $spedizione): string
    {
        $ref = trim((string) ($spedizione->codice_interno ?? ''));

        return $ref !== '' ? $ref : 'SPQ_'.$spedizione->id;
    }

    private function normalizzaTelefono(string $tel): string
    {
        $tel = preg_replace('/[^\d+]/', '', $tel) ?? '';
        if ($tel === '') {
            return '';
        }
        if (str_starts_with($tel, '+')) {
            return $tel;
        }
        if (str_starts_with($tel, '00')) {
            return '+'.substr($tel, 2);
        }

        return '+39'.$tel;
    }

    /**
     * @param  array<string, mixed>  $address
     */
    private function validaIndirizzoPickup(array $address): ?string
    {
        foreach (['name', 'address_line_1', 'postal_code', 'city', 'country_code'] as $key) {
            if (trim((string) ($address[$key] ?? '')) === '') {
                return 'Indirizzo mittente incompleto per il ritiro Sendcloud (manca '.$key.').';
            }
        }

        $prov = trim((string) ($address['state_province_code'] ?? ''));
        if ($prov === '') {
            return 'Provincia mittente mancante per il ritiro Sendcloud (state_province_code).';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $bodyArr
     */
    private function estraiPickupId(array $bodyArr): ?string
    {
        $data = $bodyArr['data'] ?? null;
        if (is_array($data)) {
            $id = $data['id'] ?? null;
            if ($id !== null && $id !== '') {
                return (string) $id;
            }
        }

        $id = $bodyArr['id'] ?? null;

        return ($id !== null && $id !== '') ? (string) $id : null;
    }
}
