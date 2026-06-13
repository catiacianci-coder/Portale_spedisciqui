<?php

namespace App\Services\Sendcloud;

use App\Models\corriere;
use App\Models\spedizione;
use App\Support\IndirizzoViaCivico;
use App\Support\SpedizioneCampiPersistenza;

/**
 * Mappa spedizione portale → payload POST /shipments/announce (Sendcloud API v3).
 */
final class SendcloudShipmentMapper
{
    public function __construct(
        private readonly SendcloudContractResolver $contracts,
    ) {}

    /**
     * @return array{payload: array<string, mixed>, error: string|null}
     */
    public function buildAnnouncePayload(spedizione $spedizione, corriere $corriere): array
    {
        $spedizione->loadMissing('serviziAggiuntiviRighe');

        $code = trim((string) ($spedizione->codice_servizio ?? $corriere->codice_servizio ?? ''));
        if ($code === '') {
            return ['payload' => [], 'error' => 'Codice servizio Sendcloud mancante sulla spedizione.'];
        }

        $mitt = SpedizioneCampiPersistenza::mittenteArray($spedizione);
        $dest = SpedizioneCampiPersistenza::destinatarioArray($spedizione);
        $pacco = SpedizioneCampiPersistenza::paccoArray($spedizione);
        $servizi = $this->estraiValoriServizi($spedizione);

        $from = $this->indirizzoSendcloud($mitt, 'Mittente');
        $to = $this->indirizzoSendcloud($dest, 'Destinatario');

        $errFrom = $this->validaIndirizzo($from, 'mittente');
        if ($errFrom !== null) {
            return ['payload' => [], 'error' => $errFrom];
        }
        $errTo = $this->validaIndirizzo($to, 'destinatario');
        if ($errTo !== null) {
            return ['payload' => [], 'error' => $errTo];
        }

        $peso = max(0.1, (float) ($pacco['peso_kg'] ?? $spedizione->peso ?? 1));
        $length = max(1, (int) ($pacco['spessore_cm'] ?? $spedizione->spessore ?? 30));
        $width = max(1, (int) ($pacco['larghezza_cm'] ?? $spedizione->larghezza ?? 20));
        $height = max(1, (int) ($pacco['altezza_cm'] ?? $spedizione->altezza ?? 15));

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

        $insured = SendcloudShippingOptionsService::additionalInsuredPriceForShipment(
            $servizi['assicurazione'] > 0 ? $servizi['assicurazione'] : null,
        );
        if ($insured !== null) {
            $collo['additional_insured_price'] = $insured;
        }

        $note = trim((string) ($dest['note'] ?? $mitt['note'] ?? ''));
        if ($note !== '') {
            $collo['label_notes'] = [mb_substr($note, 0, 255)];
        }

        $contractId = $this->contracts->resolveForCorriere($corriere);
        if ($contractId === null || $contractId < 1) {
            return ['payload' => [], 'error' => 'contract_id Sendcloud mancante per questo corriere.'];
        }

        $shipProps = [
            'shipping_option_code' => $code,
            'contract_id' => $contractId,
        ];

        $orderNumber = trim((string) ($spedizione->codice_interno ?? ''));
        if ($orderNumber === '') {
            $orderNumber = 'SPQ_'.$spedizione->id;
        }

        $payload = [
            'label_details' => [
                'mime_type' => 'application/pdf',
                'dpi' => 72,
            ],
            'from_address' => $from,
            'to_address' => $to,
            'ship_with' => [
                'type' => 'shipping_option_code',
                'properties' => $shipProps,
            ],
            'order_number' => $orderNumber,
            'parcels' => [$collo],
        ];

        $servicePointId = (int) ($spedizione->to_service_point ?? 0);
        if ($servicePointId > 0) {
            $payload['to_service_point'] = [
                'id' => (string) $servicePointId,
            ];
        }

        return ['payload' => $payload, 'error' => null];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function indirizzoSendcloud(array $row, string $fallbackName): array
    {
        [$via, $civico] = IndirizzoViaCivico::perSendcloud(
            isset($row['indirizzo']) ? (string) $row['indirizzo'] : null,
            isset($row['numero']) ? (string) $row['numero'] : null,
            isset($row['via']) ? (string) $row['via'] : null,
        );

        $nome = trim((string) (($row['nome'] ?? '').' '.($row['cognome'] ?? '')));
        if ($nome === '') {
            $nome = $fallbackName;
        }

        $company = trim((string) ($row['denominazione_impresa'] ?? $row['ragione_sociale'] ?? ''));
        $prov = strtoupper(trim((string) ($row['provincia'] ?? '')));

        $out = array_filter([
            'name' => $nome,
            'company_name' => $company !== '' ? $company : null,
            'address_line_1' => $via !== '' ? $via : null,
            'house_number' => $civico !== '' ? $civico : null,
            'postal_code' => trim((string) ($row['cap'] ?? '')),
            'city' => trim((string) ($row['comune'] ?? $row['citta'] ?? '')),
            'country_code' => 'IT',
            'phone_number' => $this->normalizzaTelefono((string) ($row['telefono'] ?? '')),
            'email' => trim((string) ($row['email'] ?? '')),
            'state_province_code' => $prov !== '' ? 'IT-'.$prov : null,
        ], static fn ($v) => $v !== null && $v !== '');

        return $out;
    }

    /**
     * @param  array<string, mixed>  $addr
     */
    private function validaIndirizzo(array $addr, string $ruolo): ?string
    {
        foreach (['name', 'address_line_1', 'postal_code', 'city', 'country_code'] as $key) {
            if (trim((string) ($addr[$key] ?? '')) === '') {
                return 'Indirizzo '.$ruolo.' incompleto (manca '.$key.').';
            }
        }

        return null;
    }

    /**
     * @return array{contrassegno: float, assicurazione: float}
     */
    private function estraiValoriServizi(spedizione $spedizione): array
    {
        $contrassegno = 0.0;
        $assicurazione = 0.0;

        foreach ($spedizione->serviziAggiuntiviRighe as $riga) {
            $testo = mb_strtolower(trim((string) ($riga->testo_servizio ?? '')));
            $valore = max(0.0, (float) ($riga->valore_merce ?? 0));
            if ($testo === 'contrassegno') {
                $contrassegno = $valore;
            } elseif ($testo === 'assicurazione') {
                $assicurazione = $valore;
            }
        }

        return [
            'contrassegno' => $contrassegno,
            'assicurazione' => $assicurazione,
        ];
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

}
