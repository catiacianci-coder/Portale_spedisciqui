<?php

namespace App\Services\SpedisciOnline;

use App\Models\corriere;
use Illuminate\Http\Client\Response;

/**
 * Payload per POST /shipping/create (doc: https://apidocs.spedisci.online/api/shipping/create).
 */
class SpedisciOnlineCreateLabelService
{
    public function __construct(
        private readonly SpedisciOnlineRatesService $rates,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function buildRatesInput(array $input): array
    {
        return [
            'cap_origine' => $input['cap_origine'] ?? '',
            'cap_destino' => $input['cap_destino'] ?? '',
            'citta_origine' => $input['citta_origine'] ?? '',
            'citta_destino' => $input['citta_destino'] ?? '',
            'peso' => $input['peso'] ?? 1,
            'lunghezza' => $input['spessore'] ?? 30,
            'larghezza' => $input['larghezza'] ?? 20,
            'altezza' => $input['altezza'] ?? 15,
            'mittente_nome' => $input['mittente_nome'] ?? 'Mittente test',
            'mittente_azienda' => $input['mittente_azienda'] ?? '',
            'mittente_indirizzo' => $input['mittente_indirizzo'] ?? 'Via test 1',
            'mittente_telefono' => $input['mittente_telefono'] ?? '',
            'mittente_email' => $input['mittente_email'] ?? '',
            'destinatario_nome' => $input['destinatario_nome'] ?? 'Destinatario test',
            'destinatario_azienda' => $input['destinatario_azienda'] ?? '',
            'destinatario_indirizzo' => $input['destinatario_indirizzo'] ?? 'Via test 2',
            'destinatario_telefono' => $input['destinatario_telefono'] ?? '',
            'destinatario_email' => $input['destinatario_email'] ?? '',
            'note' => $input['note_spedizione'] ?? 'Etichetta test portale Spedisciqui',
            'valore_assicurazione' => $input['valore_assicurazione'] ?? 0,
            'contrassegno' => $input['contrassegno'] ?? 0,
        ];
    }

    /**
     * Body come da documentazione Spedisci.online (campi in radice, senza selectedRate).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function buildCreatePayload(array $input, ?corriere $corriere = null): array
    {
        $carrierCode = trim((string) (
            $input['create_carrier_code']
            ?? $corriere?->carrier_code
            ?? ''
        ));
        $contractCode = trim((string) (
            $input['create_contract_code']
            ?? $corriere?->contract_code
            ?? ''
        ));
        $labelFormat = trim((string) ($input['label_format'] ?? 'PDF'));
        if ($labelFormat === '') {
            $labelFormat = 'PDF';
        }

        $base = $this->rates->buildPayload($this->buildRatesInput($input));
        $package = is_array($base['packages'][0] ?? null) ? $base['packages'][0] : [];

        return [
            'carrierCode' => $carrierCode,
            'contractCode' => $contractCode,
            'label_format' => $labelFormat,
            'packages' => [[
                'length' => (int) round((float) ($package['length'] ?? 30)),
                'width' => (int) round((float) ($package['width'] ?? 20)),
                'height' => (int) round((float) ($package['height'] ?? 15)),
                'weight' => (float) ($package['weight'] ?? 1),
            ]],
            'shipFrom' => $this->normalizeAddress($base['shipFrom'] ?? []),
            'shipTo' => $this->normalizeAddress($base['shipTo'] ?? []),
            'notes' => (string) ($base['notes'] ?? ''),
            'insuranceValue' => (float) ($base['insuranceValue'] ?? 0),
            'codValue' => (float) ($base['codValue'] ?? 0),
            'accessoriServices' => is_array($base['accessoriServices'] ?? null)
                ? $base['accessoriServices']
                : [],
        ];
    }

    /**
     * Opzionale: anteprima POST /shipping/rates (stessa struttura senza label_format).
     *
     * @return array{ratesResponse: Response, ratesList: array<int, array<string, mixed>>}
     */
    public function previewRates(array $input, ?string $piattaforma): array
    {
        $payload = $this->buildCreatePayload($input);
        unset($payload['label_format']);

        $ratesResponse = $this->rates->fetchRates($payload, $piattaforma);

        return [
            'ratesResponse' => $ratesResponse,
            'ratesList' => $this->rates->parseRatesFromResponse($ratesResponse),
        ];
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

    /**
     * @param  array<string, mixed>  $address
     * @return array<string, mixed>
     */
    private function normalizeAddress(array $address): array
    {
        foreach (['phone', 'email'] as $key) {
            $value = trim((string) ($address[$key] ?? ''));
            $address[$key] = $value === '' ? null : $value;
        }

        return $address;
    }
}
