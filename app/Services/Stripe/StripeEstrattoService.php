<?php

namespace App\Services\Stripe;

use App\Models\ordine;
use Carbon\Carbon;
use Stripe\Balance;
use Stripe\BalanceTransaction;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

final class StripeEstrattoService
{
    private const TYPE_LABELS = [
        'charge' => 'Addebito',
        'payment' => 'Pagamento',
        'refund' => 'Rimborso',
        'payment_refund' => 'Rimborso pagamento',
        'payout' => 'Bonifico verso banca',
        'payout_cancel' => 'Bonifico annullato',
        'payout_failure' => 'Bonifico fallito',
        'adjustment' => 'Rettifica',
        'application_fee' => 'Commissione applicazione',
        'application_fee_refund' => 'Rimborso commissione',
        'stripe_fee' => 'Commissione Stripe',
        'transfer' => 'Trasferimento',
        'transfer_refund' => 'Rimborso trasferimento',
    ];

    public function isConfigured(): bool
    {
        return StripeConfig::isConfigured();
    }

    /**
     * @return array{ok: bool, message: ?string, balance: ?array{available: float, pending: float, currency: string}}
     */
    public function saldo(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'Stripe non configurato.', 'balance' => null];
        }

        Stripe::setApiKey(StripeConfig::secretKey());

        try {
            return ['ok' => true, 'message' => null, 'balance' => $this->saldoEuro()];
        } catch (ApiErrorException $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'balance' => null];
        }
    }

    /**
     * @return array{
     *     ok: bool,
     *     message: ?string,
     *     balance: ?array{available: float, pending: float, currency: string},
     *     righe: list<array<string, mixed>>,
     *     has_more: bool,
     *     first_id: ?string,
     *     last_id: ?string
     * }
     */
    public function elenco(
        Carbon $from,
        Carbon $to,
        int $limit = 50,
        ?string $startingAfter = null,
        ?string $endingBefore = null,
    ): array {
        if (! $this->isConfigured()) {
            return $this->fail('Stripe non configurato: imposta la secret key in Parametri globali.');
        }

        Stripe::setApiKey(StripeConfig::secretKey());

        try {
            $balance = $this->saldoEuro();
        } catch (ApiErrorException $e) {
            return $this->fail('Impossibile leggere il saldo Stripe: '.$e->getMessage());
        }

        $params = [
            'limit' => min(max($limit, 1), 100),
            'created' => [
                'gte' => $from->copy()->startOfDay()->timestamp,
                'lte' => $to->copy()->endOfDay()->timestamp,
            ],
            'expand' => ['data.source'],
        ];

        if ($startingAfter !== null && $startingAfter !== '') {
            $params['starting_after'] = $startingAfter;
        } elseif ($endingBefore !== null && $endingBefore !== '') {
            $params['ending_before'] = $endingBefore;
        }

        try {
            $list = BalanceTransaction::all($params);
        } catch (ApiErrorException $e) {
            return $this->fail('Errore API Stripe: '.$e->getMessage());
        }

        $righe = [];
        foreach ($list->data as $tx) {
            $righe[] = $this->normalizzaRiga($tx);
        }

        $this->collegaOrdini($righe);

        $firstId = isset($list->data[0]) ? (string) $list->data[0]->id : null;
        $lastIndex = count($list->data) - 1;
        $lastId = $lastIndex >= 0 ? (string) $list->data[$lastIndex]->id : null;

        return [
            'ok' => true,
            'message' => null,
            'balance' => $balance,
            'righe' => $righe,
            'has_more' => (bool) ($list->has_more ?? false),
            'first_id' => $firstId,
            'last_id' => $lastId,
        ];
    }

    /**
     * @return \Generator<int, array<string, mixed>>
     */
    public function elencoCompletoPeriodo(Carbon $from, Carbon $to, int $maxRighe = 500): \Generator
    {
        if (! $this->isConfigured()) {
            return;
        }

        Stripe::setApiKey(StripeConfig::secretKey());

        $startingAfter = null;
        $count = 0;

        do {
            $params = [
                'limit' => 100,
                'created' => [
                    'gte' => $from->copy()->startOfDay()->timestamp,
                    'lte' => $to->copy()->endOfDay()->timestamp,
                ],
                'expand' => ['data.source'],
            ];
            if ($startingAfter !== null) {
                $params['starting_after'] = $startingAfter;
            }

            $list = BalanceTransaction::all($params);
            foreach ($list->data as $tx) {
                $riga = $this->normalizzaRiga($tx);
                yield $riga;
                $count++;
                if ($count >= $maxRighe) {
                    return;
                }
            }

            $hasMore = (bool) ($list->has_more ?? false);
            $lastIndex = count($list->data) - 1;
            $startingAfter = $lastIndex >= 0 ? (string) $list->data[$lastIndex]->id : null;
        } while ($hasMore && $startingAfter !== null);
    }

    /**
     * @return array{available: float, pending: float, currency: string}
     */
    private function saldoEuro(): array
    {
        $balance = Balance::retrieve();
        $currency = strtolower(StripeConfig::currency());

        return [
            'available' => $this->importoDaBalanceArray($balance->available ?? [], $currency),
            'pending' => $this->importoDaBalanceArray($balance->pending ?? [], $currency),
            'currency' => strtoupper($currency),
        ];
    }

    /**
     * @param  array<int, object>|null  $entries
     */
    private function importoDaBalanceArray(?array $entries, string $currency): float
    {
        if (! is_array($entries)) {
            return 0.0;
        }

        foreach ($entries as $entry) {
            if (strtolower((string) ($entry->currency ?? '')) === $currency) {
                return round(((int) ($entry->amount ?? 0)) / 100, 2);
            }
        }

        return 0.0;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizzaRiga(object $tx): array
    {
        $type = (string) ($tx->type ?? '');
        $currency = strtolower((string) ($tx->currency ?? StripeConfig::currency()));
        $created = (int) ($tx->created ?? 0);

        return [
            'id' => (string) ($tx->id ?? ''),
            'created_at' => $created > 0 ? Carbon::createFromTimestamp($created) : null,
            'type' => $type,
            'type_label' => self::TYPE_LABELS[$type] ?? ucfirst(str_replace('_', ' ', $type)),
            'description' => trim((string) ($tx->description ?? '')),
            'amount' => round(((int) ($tx->amount ?? 0)) / 100, 2),
            'fee' => round(((int) ($tx->fee ?? 0)) / 100, 2),
            'net' => round(((int) ($tx->net ?? 0)) / 100, 2),
            'currency' => strtoupper($currency),
            'payment_intent_id' => $this->paymentIntentDaSource($tx->source ?? null),
            'source_id' => is_string($tx->source ?? null)
                ? (string) $tx->source
                : (is_object($tx->source) ? (string) ($tx->source->id ?? '') : ''),
            'ordine_id' => null,
            'ordine_codice' => null,
        ];
    }

    private function paymentIntentDaSource(mixed $source): ?string
    {
        if ($source === null) {
            return null;
        }

        if (is_string($source)) {
            if (str_starts_with($source, 'pi_')) {
                return $source;
            }

            return null;
        }

        if (! is_object($source)) {
            return null;
        }

        $object = (string) ($source->object ?? '');
        if ($object === 'payment_intent') {
            return (string) ($source->id ?? '') ?: null;
        }

        $pi = $source->payment_intent ?? null;
        if (is_string($pi) && $pi !== '') {
            return $pi;
        }
        if (is_object($pi)) {
            return (string) ($pi->id ?? '') ?: null;
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $righe
     */
    private function collegaOrdini(array &$righe): void
    {
        $pis = [];
        foreach ($righe as $riga) {
            $pi = trim((string) ($riga['payment_intent_id'] ?? ''));
            if ($pi !== '') {
                $pis[$pi] = true;
            }
        }

        if ($pis === []) {
            return;
        }

        $ordini = ordine::query()
            ->whereIn('stripe_payment_intent_id', array_keys($pis))
            ->get(['id', 'stripe_payment_intent_id']);

        $map = [];
        foreach ($ordini as $ordine) {
            $pi = trim((string) ($ordine->stripe_payment_intent_id ?? ''));
            if ($pi !== '') {
                $map[$pi] = (int) $ordine->id;
            }
        }

        foreach ($righe as &$riga) {
            $pi = trim((string) ($riga['payment_intent_id'] ?? ''));
            if ($pi !== '' && isset($map[$pi])) {
                $riga['ordine_id'] = $map[$pi];
                $riga['ordine_codice'] = (string) $map[$pi];
            }
        }
        unset($riga);
    }

    /**
     * @return array{ok: false, message: string, balance: null, righe: array, has_more: false, first_id: null, last_id: null}
     */
    private function fail(string $message): array
    {
        return [
            'ok' => false,
            'message' => $message,
            'balance' => null,
            'righe' => [],
            'has_more' => false,
            'first_id' => null,
            'last_id' => null,
        ];
    }
}
