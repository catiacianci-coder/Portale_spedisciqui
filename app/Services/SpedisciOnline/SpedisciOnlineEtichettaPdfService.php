<?php

namespace App\Services\SpedisciOnline;

use App\Models\spedizione;
use App\Support\LdvStorage;
use App\Support\SpedisciOnlineIntegrazione;
use Illuminate\Support\Facades\Log;

/**
 * Decodifica labelData (PDF base64) dalla risposta Spedisci.online e lo salva su disco.
 */
class SpedisciOnlineEtichettaPdfService
{
    public function salvaDaRispostaCreate(spedizione $spedizione, array $body): ?string
    {
        $base64 = $this->estraiLabelBase64($body);
        if ($base64 === null) {
            return null;
        }

        return $this->scriviPdf($spedizione, $base64);
    }

    /**
     * Backfill da file integrazione (spedizioni già create prima del salvataggio PDF).
     */
    public function salvaDaIntegrazione(spedizione $spedizione): ?string
    {
        $data = SpedisciOnlineIntegrazione::decode($spedizione);
        $response = is_array($data['response'] ?? null) ? $data['response'] : [];

        return $this->salvaDaRispostaCreate($spedizione, $response);
    }

    public function rimuovi(spedizione $spedizione): void
    {
        LdvStorage::rimuoviFile($spedizione);

        $spedizione->forceFill(['etiqueta_pdf_path' => null])->saveQuietly();
    }

    public function percorsoAssoluto(spedizione $spedizione): ?string
    {
        if (SpedisciOnlineIntegrazione::etichettaCancellata($spedizione)) {
            return null;
        }

        if (trim((string) $spedizione->etiqueta_pdf_path) === '') {
            $this->salvaDaIntegrazione($spedizione);
        }

        return LdvStorage::percorsoAssoluto($spedizione);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public function estraiLabelBase64(array $body): ?string
    {
        foreach ($this->chiaviLabel() as $key) {
            $raw = $body[$key] ?? null;
            if (is_string($raw) && trim($raw) !== '') {
                return $this->normalizzaBase64($raw);
            }
        }

        foreach (['data', 'shipment', 'label', 'packages'] as $wrap) {
            if (! isset($body[$wrap]) || ! is_array($body[$wrap])) {
                continue;
            }
            $nested = $this->estraiLabelBase64($body[$wrap]);
            if ($nested !== null) {
                return $nested;
            }
            if ($wrap === 'packages' && array_is_list($body[$wrap])) {
                foreach ($body[$wrap] as $pkg) {
                    if (! is_array($pkg)) {
                        continue;
                    }
                    $nested = $this->estraiLabelBase64($pkg);
                    if ($nested !== null) {
                        return $nested;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function chiaviLabel(): array
    {
        return [
            'labelData',
            'label_data',
            'labelPdf',
            'label_pdf',
            'pdf',
            'pdfLabel',
            'pdf_label',
        ];
    }

    private function normalizzaBase64(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (str_contains($raw, 'base64,')) {
            $parts = explode('base64,', $raw, 2);
            $raw = $parts[1] ?? '';
        }

        $raw = preg_replace('/\s+/', '', $raw) ?? '';

        return $raw !== '' ? $raw : null;
    }

    private function scriviPdf(spedizione $spedizione, string $base64): ?string
    {
        $binary = base64_decode($base64, true);
        if ($binary === false || $binary === '') {
            Log::warning('Spedisci.online: labelData base64 non decodificabile', [
                'spedizione_id' => $spedizione->id,
            ]);

            return null;
        }

        if (! str_starts_with($binary, '%PDF')) {
            Log::warning('Spedisci.online: contenuto etichetta non sembra un PDF', [
                'spedizione_id' => $spedizione->id,
            ]);

            return null;
        }

        LdvStorage::rimuoviFile($spedizione);

        $relative = LdvStorage::salvaPdf($spedizione, $binary);
        if ($relative === null) {
            return null;
        }

        $spedizione->forceFill(['etiqueta_pdf_path' => $relative])->saveQuietly();

        return $relative;
    }
}
