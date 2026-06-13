<?php

namespace App\Services\Sendcloud;

use App\Models\spedizione;
use App\Support\LdvStorage;
use App\Support\SendcloudIntegrazione;
use Illuminate\Support\Facades\Log;

/**
 * Scarica e salva PDF etichetta Sendcloud (link documento o label_file base64).
 */
final class SendcloudEtichettaPdfService
{
    public function __construct(
        private readonly SendcloudClient $client,
    ) {}

    /**
     * @param  array<string, mixed>  $announceData  Nodo data della risposta announce
     */
    public function salvaDaAnnounceResponse(spedizione $spedizione, array $announceData): ?string
    {
        $parcels = $announceData['parcels'] ?? [];
        if (! is_array($parcels) || $parcels === []) {
            return null;
        }
        $parcel = $parcels[0] ?? null;
        if (! is_array($parcel)) {
            return null;
        }

        $labelFile = $parcel['label_file'] ?? null;
        if (is_string($labelFile) && $labelFile !== '') {
            $binary = base64_decode($labelFile, true);
            if (is_string($binary) && $binary !== '') {
                return $this->salvaBinary($spedizione, $binary);
            }
        }

        $url = $this->estraiLabelUrl($parcel);
        if ($url === null) {
            $url = trim((string) (SendcloudIntegrazione::decode($spedizione)['label_url'] ?? ''));
            if ($url === '') {
                return null;
            }
        }

        return $this->scaricaESalva($spedizione, $url);
    }

    public function scaricaESalva(spedizione $spedizione, string $url): ?string
    {
        $response = $this->client->getDocument($url);
        if (! $response->successful()) {
            Log::warning('Sendcloud: download etichetta fallito', [
                'spedizione_id' => $spedizione->id,
                'http_status' => $response->status(),
                'url' => $url,
            ]);

            return null;
        }

        $binary = $response->body();
        if (! is_string($binary) || $binary === '') {
            return null;
        }

        return $this->salvaBinary($spedizione, $binary);
    }

    public function salvaBinary(spedizione $spedizione, string $binary): ?string
    {
        if ($binary === '' || ! str_starts_with($binary, '%PDF')) {
            Log::warning('Sendcloud: contenuto etichetta non sembra un PDF', [
                'spedizione_id' => $spedizione->id,
            ]);

            return null;
        }

        $this->rimuovi($spedizione);

        $relative = LdvStorage::salvaPdf($spedizione, $binary);
        if ($relative === null) {
            return null;
        }

        $spedizione->forceFill(['etiqueta_pdf_path' => $relative])->saveQuietly();

        return $relative;
    }

    public function rimuovi(spedizione $spedizione): void
    {
        LdvStorage::rimuoviFile($spedizione);
        $spedizione->forceFill(['etiqueta_pdf_path' => null])->saveQuietly();
    }

    /**
     * @param  array<string, mixed>  $parcel
     */
    private function estraiLabelUrl(array $parcel): ?string
    {
        $documents = $parcel['documents'] ?? [];
        if (! is_array($documents)) {
            return null;
        }
        foreach ($documents as $doc) {
            if (! is_array($doc)) {
                continue;
            }
            if (strtolower((string) ($doc['type'] ?? '')) !== 'label') {
                continue;
            }
            $link = trim((string) ($doc['link'] ?? ''));

            return $link !== '' ? $link : null;
        }

        return null;
    }
}
