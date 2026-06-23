<?php

namespace App\Services\Revolut;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Bonifico in uscita verso IBAN cliente via Revolut Business API.
 */
final class RevolutPayoutService
{
    public function __construct(
        private readonly RevolutClient $client,
    ) {}

    /**
     * @return array{ok: bool, message: string, transaction_id: ?string}
     */
    public function bonificoVersoIban(
        string $iban,
        string $beneficiario,
        float $importo,
        string $reference,
        ?string $requestId = null,
    ): array {
        if (! RevolutConfig::isConfigured()) {
            return ['ok' => false, 'message' => 'Revolut non configurato (token e ID conto).', 'transaction_id' => null];
        }

        $iban = strtoupper(preg_replace('/\s+/', '', trim($iban)) ?? '');
        if (! preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/', $iban)) {
            return ['ok' => false, 'message' => 'IBAN non valido.', 'transaction_id' => null];
        }

        $importo = round($importo, 2);
        if ($importo < 0.01) {
            return ['ok' => false, 'message' => 'Importo bonifico non valido.', 'transaction_id' => null];
        }

        $beneficiario = trim($beneficiario);
        if ($beneficiario === '') {
            return ['ok' => false, 'message' => 'Nome beneficiario obbligatorio.', 'transaction_id' => null];
        }

        $reference = trim($reference);
        if ($reference === '') {
            $reference = 'Rimborso';
        }
        if (strlen($reference) > 140) {
            $reference = substr($reference, 0, 140);
        }

        $requestId = $requestId ?? (string) Str::uuid();

        $counterparty = $this->creaCounterparty($iban, $beneficiario);
        if (! $counterparty['ok']) {
            return ['ok' => false, 'message' => $counterparty['message'], 'transaction_id' => null];
        }

        $payload = [
            'request_id' => $requestId,
            'account_id' => RevolutConfig::accountId(),
            'receiver' => [
                'counterparty_id' => $counterparty['counterparty_id'],
                'account_id' => $counterparty['account_id'],
            ],
            'amount' => $importo,
            'currency' => 'EUR',
            'reference' => $reference,
            'charge_bearer' => 'debtor',
        ];

        try {
            $response = $this->client->post('/pay', $payload);
        } catch (\Throwable $e) {
            Log::error('Revolut pay fallito (rete)', [
                'request_id' => $requestId,
                'message' => $e->getMessage(),
            ]);

            return ['ok' => false, 'message' => 'Revolut non raggiungibile: '.$e->getMessage(), 'transaction_id' => null];
        }

        if (! $response->successful()) {
            $body = $response->json();
            $msg = is_array($body)
                ? (string) ($body['message'] ?? $body['error'] ?? json_encode($body))
                : $response->body();

            Log::warning('Revolut pay rifiutato', [
                'request_id' => $requestId,
                'status' => $response->status(),
                'body' => $body,
            ]);

            return ['ok' => false, 'message' => 'Revolut ha rifiutato il bonifico: '.$msg, 'transaction_id' => null];
        }

        $data = $response->json();
        $transactionId = is_array($data)
            ? trim((string) ($data['id'] ?? $data['transaction_id'] ?? ''))
            : '';

        if ($transactionId === '') {
            return ['ok' => false, 'message' => 'Bonifico inviato ma ID transazione Revolut assente nella risposta.', 'transaction_id' => null];
        }

        return [
            'ok' => true,
            'message' => 'Bonifico Revolut avviato (ID '.$transactionId.').',
            'transaction_id' => $transactionId,
        ];
    }

    /**
     * @return array{ok: bool, message: string, counterparty_id: ?string, account_id: ?string}
     */
    private function creaCounterparty(string $iban, string $beneficiario): array
    {
        $payload = [
            'profile_type' => 'personal',
            'name' => $beneficiario,
            'bank_country' => substr($iban, 0, 2),
            'currency' => 'EUR',
            'iban' => $iban,
        ];

        try {
            $response = $this->client->post('/counterparty', $payload);
        } catch (\Throwable $e) {
            Log::error('Revolut counterparty fallito (rete)', ['message' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'Revolut non raggiungibile: '.$e->getMessage(), 'counterparty_id' => null, 'account_id' => null];
        }

        if (! $response->successful()) {
            $body = $response->json();
            $msg = is_array($body)
                ? (string) ($body['message'] ?? $body['error'] ?? json_encode($body))
                : $response->body();

            return ['ok' => false, 'message' => 'Impossibile registrare il beneficiario su Revolut: '.$msg, 'counterparty_id' => null, 'account_id' => null];
        }

        $data = $response->json();
        if (! is_array($data)) {
            return ['ok' => false, 'message' => 'Risposta counterparty Revolut non valida.', 'counterparty_id' => null, 'account_id' => null];
        }

        $counterpartyId = trim((string) ($data['id'] ?? ''));
        $accountId = '';
        $accounts = $data['accounts'] ?? [];
        if (is_array($accounts)) {
            foreach ($accounts as $account) {
                if (! is_array($account)) {
                    continue;
                }
                $candidate = trim((string) ($account['id'] ?? ''));
                if ($candidate !== '') {
                    $accountId = $candidate;
                    break;
                }
            }
        }

        if ($counterpartyId === '' || $accountId === '') {
            return ['ok' => false, 'message' => 'Counterparty Revolut creato ma mancano gli ID per il bonifico.', 'counterparty_id' => null, 'account_id' => null];
        }

        return [
            'ok' => true,
            'message' => '',
            'counterparty_id' => $counterpartyId,
            'account_id' => $accountId,
        ];
    }
}
