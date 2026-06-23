<?php

namespace App\Services\SpedisciOnline;

use App\Models\corriere;
use App\Models\spedizione;
use App\Support\SpedisciOnlineEamultiContratti;

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
            'mittente_nome' => trim((string) ($input['mittente_nome'] ?? '')),
            'mittente_azienda' => trim((string) ($input['mittente_azienda'] ?? '')),
            'mittente_indirizzo' => trim((string) ($input['mittente_indirizzo'] ?? '')),
            'mittente_telefono' => trim((string) ($input['mittente_telefono'] ?? '')),
            'mittente_email' => trim((string) ($input['mittente_email'] ?? '')),
            'destinatario_nome' => trim((string) ($input['destinatario_nome'] ?? '')),
            'destinatario_azienda' => trim((string) ($input['destinatario_azienda'] ?? '')),
            'destinatario_indirizzo' => trim((string) ($input['destinatario_indirizzo'] ?? '')),
            'destinatario_telefono' => trim((string) ($input['destinatario_telefono'] ?? '')),
            'destinatario_email' => trim((string) ($input['destinatario_email'] ?? '')),
            'note' => trim((string) ($input['note_spedizione'] ?? '')),
            'valore_assicurazione' => $input['valore_assicurazione'] ?? 0,
            'contrassegno' => $input['contrassegno'] ?? 0,
        ];
    }

    /**
     * Payload create da spedizione pagata (stessa struttura della pagina test).
     *
     * @return array<string, mixed>
     */
    public function buildCreatePayloadFromSpedizione(spedizione $spedizione, ?corriere $corriere = null): array
    {
        $corriere ??= $spedizione->corriereRecord;
        $input = $this->rates->buildInputFromSpedizione($spedizione);

        $createInput = array_merge($input, [
            'spessore' => $input['lunghezza'] ?? 30,
            'note_spedizione' => $input['note'] ?? '',
            'label_format' => 'PDF',
        ]);

        if ($corriere) {
            $createInput['create_carrier_code'] = $corriere->carrier_code;
        }

        $contractSnapshot = trim((string) ($spedizione->codice_servizio ?? ''));
        if ($contractSnapshot !== '') {
            $createInput['create_contract_code'] = $contractSnapshot;
        }

        return $this->buildCreatePayload($createInput, $corriere);
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
            ?? ($corriere ? SpedisciOnlineEamultiContratti::contractCodeForCorriere($corriere) : '')
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
