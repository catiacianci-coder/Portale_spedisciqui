<?php

namespace App\Services\Liccardi;

/**
 * Costruzione payload da form pagina test (allineati alla Postman collection TMS).
 */
final class LiccardiTmsPayloadBuilder
{
    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function buildQuotePayload(array $in, string $companyId): array
    {
        return [
            'codiceCliente' => $companyId,
            'codiceServizio' => trim((string) ($in['codice_servizio'] ?? 'E')),
            'capConsegna' => trim((string) ($in['cap_destino'] ?? '')),
            'comuneConsegna' => trim((string) ($in['citta_destino'] ?? '')),
            'viaConsegna' => trim((string) ($in['via_destino'] ?? '')),
            'civicoConsegna' => trim((string) ($in['civico_destino'] ?? '')),
            'siglaProvinciaConsegna' => strtoupper(trim((string) ($in['pv_destino'] ?? ''))),
            'capRitiro' => trim((string) ($in['cap_origine'] ?? '')),
            'comuneRitiro' => trim((string) ($in['citta_origine'] ?? '')),
            'viaRitiro' => trim((string) ($in['via_origine'] ?? '')),
            'civicoRitiro' => trim((string) ($in['civico_origine'] ?? '')),
            'siglaProvinciaRitiro' => strtoupper(trim((string) ($in['pv_origine'] ?? ''))),
            'contrassegno' => (float) ($in['contrassegno'] ?? 0),
            'assicurazione' => (float) ($in['assicurazione'] ?? 0),
            'colli' => $this->buildColli($in),
        ];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function buildCreateFastPayload(array $in, string $companyId): array
    {
        return [
            'pickUpAddress' => $this->buildAddressBlock(
                trim((string) ($in['mittente_azienda'] ?? 'Test Company')),
                trim((string) ($in['citta_origine'] ?? '')),
                trim((string) ($in['pv_origine'] ?? '')),
                trim((string) ($in['via_origine'] ?? '')),
                trim((string) ($in['civico_origine'] ?? '')),
                trim((string) ($in['cap_origine'] ?? '')),
                null,
            ),
            'recipientAddress' => $this->buildAddressBlock(
                trim((string) ($in['destinatario_azienda'] ?? '')),
                trim((string) ($in['citta_destino'] ?? '')),
                trim((string) ($in['pv_destino'] ?? '')),
                trim((string) ($in['via_destino'] ?? '')),
                trim((string) ($in['civico_destino'] ?? '')),
                trim((string) ($in['cap_destino'] ?? '')),
                trim((string) ($in['destinatario_nome'] ?? 'Destinatario test')),
            ),
            'colli' => $this->buildColli($in),
            'cashOnDeliveryCurrency' => 'EUR',
            'cashOnDeliveryValue' => (float) ($in['contrassegno'] ?? 0),
            'cashOnDeliveryMode' => (int) ($in['contrassegno_mode'] ?? 0),
            'clientNotes' => trim((string) ($in['note_spedizione'] ?? '')),
            'clientReferenceNumber' => trim((string) ($in['riferimento_cliente'] ?? 'TEST_PORTALE_'.date('Ymd_His'))),
            'companyId' => $companyId,
            'serviceType' => trim((string) ($in['codice_servizio'] ?? 'E')),
            'autoClose' => ($in['auto_close'] ?? '1') === '1',
        ];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function buildCreateHeadPayload(array $in, string $companyId): array
    {
        return [
            'recipientAddress' => $this->buildAddressBlock(
                trim((string) ($in['destinatario_azienda'] ?? '')),
                trim((string) ($in['citta_destino'] ?? '')),
                trim((string) ($in['pv_destino'] ?? '')),
                trim((string) ($in['via_destino'] ?? '')),
                trim((string) ($in['civico_destino'] ?? '')),
                trim((string) ($in['cap_destino'] ?? '')),
                trim((string) ($in['destinatario_nome'] ?? 'Destinatario test')),
            ),
            'cashOnDeliveryCurrency' => 'EUR',
            'cashOnDeliveryValue' => (float) ($in['contrassegno'] ?? 0),
            'cashOnDeliveryMode' => (int) ($in['contrassegno_mode'] ?? 0),
            'clientNotes' => trim((string) ($in['note_spedizione'] ?? '')),
            'clientReferenceNumber' => trim((string) ($in['riferimento_cliente'] ?? 'TEST_HEAD_'.date('Ymd_His'))),
            'companyId' => $companyId,
            'serviceType' => trim((string) ($in['codice_servizio'] ?? 'E')),
        ];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array{colli: list<array<string, mixed>>}
     */
    public function buildAddParcelsPayload(array $in): array
    {
        return ['colli' => $this->buildColli($in)];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return list<array<string, mixed>>
     */
    private function buildColli(array $in): array
    {
        $n = max(1, min(10, (int) ($in['num_colli'] ?? 1)));
        $peso = round((float) ($in['peso'] ?? 1), 3);
        $altezza = (int) round((float) ($in['altezza'] ?? 20));
        $larghezza = (int) round((float) ($in['larghezza'] ?? 25));
        $profondita = (int) round((float) ($in['spessore'] ?? 30));
        $volume = trim((string) ($in['volume_collo'] ?? ''));

        $colli = [];
        for ($i = 0; $i < $n; $i++) {
            $collo = ['peso' => $peso];
            if ($volume !== '' && is_numeric($volume)) {
                $collo['volume'] = (float) $volume;
            } else {
                $collo['altezza'] = $altezza;
                $collo['larghezza'] = $larghezza;
                $collo['profondita'] = $profondita;
            }
            $colli[] = $collo;
        }

        return $colli;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAddressBlock(
        string $companyName,
        string $city,
        string $district,
        string $street,
        string $buildingNr,
        string $zipCode,
        ?string $referencePerson,
    ): array {
        $block = [
            'companyName' => $companyName,
            'city' => $city,
            'district' => strtoupper($district),
            'streetName' => $street,
            'buildingNr' => $buildingNr,
            'zipCode' => $zipCode,
            'country' => 'ITA',
        ];
        if ($referencePerson !== null && $referencePerson !== '') {
            $block['referencePerson'] = $referencePerson;
        }

        return $block;
    }
}
