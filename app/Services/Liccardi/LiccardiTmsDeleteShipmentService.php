<?php

namespace App\Services\Liccardi;

use App\Models\spedizione;
use App\Support\LiccardiTmsIntegrazione;

/**
 * DELETE /spedizioni — annulla spedizione su TMS Liccardi (body: spedizioneId).
 */
final class LiccardiTmsDeleteShipmentService
{
    public function __construct(
        private readonly LiccardiTmsClient $client,
        private readonly LiccardiTmsResponseFormatter $formatter,
    ) {}

    /**
     * @return array<string, mixed> Stesso formato pagina test / LiccardiTmsResponseFormatter
     */
    public function deleteBySpedizioneId(int $spedizioneId): array
    {
        if ($spedizioneId < 1) {
            return $this->erroreValidazione('spedizioneId non valido.');
        }

        if (! $this->client->isConfigured()) {
            return $this->erroreValidazione('liccardi_tms_api_key o liccardi_tms_company_id mancanti in parametri globali.');
        }

        $payload = ['spedizioneId' => $spedizioneId];
        $path = 'spedizioni';
        $response = $this->client->deleteJson($path, $payload);

        return $this->formatter->fromHttp('DELETE', $path, [], $payload, $response);
    }

    /**
     * Elimina su TMS usando id salvato su spedizione / sidecar integrazione.
     *
     * @return array{ok: bool, message: string, probe?: array<string, mixed>}
     */
    public function deleteFromSpedizione(spedizione $spedizione): array
    {
        $id = LiccardiTmsIntegrazione::spedizioneId($spedizione);
        if ($id === null) {
            return [
                'ok' => false,
                'message' => 'Nessun spedizioneId Liccardi salvato per questa spedizione.',
            ];
        }

        $probe = $this->deleteBySpedizioneId($id);
        if ($probe['ok'] ?? false) {
            LiccardiTmsIntegrazione::segnaEliminata($spedizione, is_array($probe['responseJson'] ?? null) ? $probe['responseJson'] : null);
        }

        return [
            'ok' => (bool) ($probe['ok'] ?? false),
            'message' => ($probe['ok'] ?? false)
                ? 'Spedizione '.$id.' eliminata su TMS Liccardi.'
                : (string) ($probe['errorMessage'] ?? 'Eliminazione TMS non riuscita.'),
            'probe' => $probe,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function erroreValidazione(string $message): array
    {
        return [
            'searched' => true,
            'method' => 'DELETE',
            'path' => 'spedizioni',
            'query' => [],
            'url' => null,
            'requestHeaders' => $this->client->requestHeadersForDisplay(),
            'payload' => null,
            'httpStatus' => null,
            'contentType' => null,
            'rawBody' => null,
            'bodyNote' => null,
            'errorMessage' => $message,
            'hints' => [],
            'ok' => false,
            'responseJson' => null,
        ];
    }
}
