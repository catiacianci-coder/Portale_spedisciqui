<?php

namespace App\Services\Sendcloud;

use App\Models\spedizione;
use App\Support\SendcloudIntegrazione;

/**
 * Verifica via API Sendcloud che la spedizione risulti annullata sul corriere.
 */
final class SendcloudVerificaAnnullamentoService
{
    public function __construct(
        private readonly SendcloudClient $client,
    ) {}

    /**
     * @return array{ok: bool, message: string}
     */
    public function verificaAnnullata(spedizione $spedizione): array
    {
        if (! SendcloudClient::isConfigured()) {
            return [
                'ok' => false,
                'message' => 'API Sendcloud non configurata: impossibile verificare l’annullamento.',
            ];
        }

        $shipmentId = SendcloudIntegrazione::shipmentId($spedizione);
        if ($shipmentId === null) {
            return ['ok' => true, 'message' => 'Nessun shipment Sendcloud da verificare.'];
        }

        $response = $this->client->get('/shipments/'.rawurlencode($shipmentId));

        if ($response->status() === 404) {
            return ['ok' => true, 'message' => 'Shipment Sendcloud non trovato (annullato).'];
        }

        if (! $response->successful()) {
            $detail = trim((string) ($response->json('errors.0.detail') ?? $response->body()));

            return [
                'ok' => false,
                'message' => $detail !== ''
                    ? 'Verifica Sendcloud non riuscita: '.$detail
                    : 'Verifica Sendcloud non riuscita (HTTP '.$response->status().').',
            ];
        }

        $data = $response->json('data');
        if (! is_array($data)) {
            return [
                'ok' => false,
                'message' => 'Risposta Sendcloud non valida durante la verifica annullamento.',
            ];
        }

        if ($this->payloadIndicaAnnullata($data)) {
            return ['ok' => true, 'message' => 'Etichetta annullata su Sendcloud.'];
        }

        return [
            'ok' => false,
            'message' => (string) config(
                'rimborso.messaggio_etichetta_ancora_attiva_corriere',
                'L’etichetta risulta ancora attiva sul corriere Sendcloud: impossibile accreditare il rimborso.',
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function payloadIndicaAnnullata(array $data): bool
    {
        if ($this->testoIndicaAnnullata($this->statoDaNodo($data))) {
            return true;
        }

        $parcels = $data['parcels'] ?? null;
        if (is_array($parcels)) {
            foreach ($parcels as $parcel) {
                if (! is_array($parcel)) {
                    continue;
                }
                if ($this->testoIndicaAnnullata($this->statoDaNodo($parcel))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function statoDaNodo(array $node): string
    {
        $status = $node['status'] ?? null;
        if (is_array($status)) {
            foreach (['message', 'code', 'description'] as $key) {
                $value = trim((string) ($status[$key] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        foreach (['status_description', 'statusDescription', 'state', 'phase'] as $key) {
            $value = trim((string) ($node[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function testoIndicaAnnullata(string $stato): bool
    {
        $norm = mb_strtolower(trim($stato), 'UTF-8');
        if ($norm === '') {
            return false;
        }

        foreach (config('rimborso.stato_corriere_annullato_fragmenti', []) as $frag) {
            if (! is_string($frag)) {
                continue;
            }
            $f = mb_strtolower(trim($frag), 'UTF-8');
            if ($f !== '' && str_contains($norm, $f)) {
                return true;
            }
        }

        return false;
    }
}
