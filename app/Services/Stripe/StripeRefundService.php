<?php

namespace App\Services\Stripe;

use App\Models\metodo_pagamento_ordine;
use App\Models\ordine;
use App\Services\Liccardi\LiccardiTmsAcquistoService;
use App\Services\Rimborso\RimborsoStripeSpedizioniService;
use App\Services\Sendcloud\SendcloudAcquistoService;
use App\Services\SpedisciOnline\SpedisciOnlineAcquistoService;
use App\Support\OrdinePagamentoEffettivo;
use App\Support\StripeOrdineStripeIds;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Refund;
use Stripe\Stripe;

class StripeRefundService
{
    /**
     * @return array{ok: bool, motivo: string}
     */
    public function verificaElegibilita(ordine $ordine): array
    {
        if (! StripeConfig::isConfigured()) {
            return ['ok' => false, 'motivo' => 'Stripe non configurato.'];
        }

        if ($ordine->stripe_refund_id) {
            return ['ok' => false, 'motivo' => 'Questo ordine risulta già rimborsato su Stripe.'];
        }

        if (! $ordine->haStato(ordine::STATO_PAGATO)) {
            return ['ok' => false, 'motivo' => 'Solo gli ordini pagati possono essere rimborsati.'];
        }

        if (! $this->ordinePagatoConCarta($ordine)) {
            return ['ok' => false, 'motivo' => 'Il rimborso Stripe è disponibile solo per ordini pagati con carta.'];
        }

        if ($this->paymentIntentId($ordine) === null) {
            return ['ok' => false, 'motivo' => 'Manca il Payment Intent Stripe collegato all’ordine: impossibile rimborsare.'];
        }

        return ['ok' => true, 'motivo' => ''];
    }

    /**
     * Rimborso su Stripe: ordine resta pagato, spedizioni → rimborsata.
     *
     * @param  float|null  $importoEuro  Importo in euro; null = rimborso totale pagato
     * @return array{ok: bool, message: string, refund_id: ?string, amount_euro: ?float}
     */
    public function rimborsaOrdine(
        ordine $ordine,
        ?float $importoEuro = null,
        string $reason = 'requested_by_customer',
    ): array {
        $check = $this->verificaElegibilita($ordine);
        if (! $check['ok']) {
            return ['ok' => false, 'message' => $check['motivo'], 'refund_id' => null, 'amount_euro' => null];
        }

        $paymentIntentId = $this->paymentIntentId($ordine);
        if ($paymentIntentId === null) {
            return ['ok' => false, 'message' => 'Payment Intent Stripe non trovato.', 'refund_id' => null, 'amount_euro' => null];
        }

        $totalePagato = (float) ($ordine->pag_effettivo_or ?? 0);
        if ($totalePagato <= 0) {
            $totalePagato = (float) ($ordine->total_pagamento ?? 0);
        }
        if ($totalePagato <= 0 && $ordine->metodo_pagamento_ordinis_id) {
            $totalePagato = OrdinePagamentoEffettivo::importoOrdine(
                $ordine,
                (int) $ordine->metodo_pagamento_ordinis_id,
            );
        }

        $amountCents = null;
        if ($importoEuro !== null) {
            $importoEuro = round($importoEuro, 2);
            if ($importoEuro <= 0) {
                return ['ok' => false, 'message' => 'Importo rimborso non valido.', 'refund_id' => null, 'amount_euro' => null];
            }
            if ($totalePagato > 0 && $importoEuro - $totalePagato > 0.01) {
                return ['ok' => false, 'message' => 'L’importo supera il totale pagato ('.\App\Support\ImportoEuro::format($totalePagato).').', 'refund_id' => null, 'amount_euro' => null];
            }
            $amountCents = (int) round($importoEuro * 100);
            if ($amountCents < 50) {
                return ['ok' => false, 'message' => 'Importo minimo rimborso '.\App\Support\ImportoEuro::format(0.5).'.', 'refund_id' => null, 'amount_euro' => null];
            }
        }

        if ($ordine->stripe_payment_intent_id !== $paymentIntentId) {
            $ordine->update(['stripe_payment_intent_id' => $paymentIntentId]);
            StripeOrdineStripeIds::propagaPaymentIntentSuSpedizioni($ordine->fresh(), $paymentIntentId);
        }

        Stripe::setApiKey(StripeConfig::secretKey());

        $payload = [
            'payment_intent' => $paymentIntentId,
            'metadata' => [
                'ordine_id' => (string) $ordine->id,
                'ordine_codice' => (string) $ordine->codice,
            ],
        ];

        if (in_array($reason, ['duplicate', 'fraudulent', 'requested_by_customer'], true)) {
            $payload['reason'] = $reason;
        }

        if ($amountCents !== null) {
            $payload['amount'] = $amountCents;
        }

        $spedisciDelete = app(SpedisciOnlineAcquistoService::class)->eliminaEtichettePerOrdine($ordine);
        $liccardiDelete = app(LiccardiTmsAcquistoService::class)->eliminaEtichettePerOrdine($ordine);
        $sendcloudDelete = app(SendcloudAcquistoService::class)->eliminaEtichettePerOrdine($ordine);
        $falliti = array_merge(
            array_filter($spedisciDelete, fn ($r) => ! ($r['ok'] ?? false)),
            array_filter($liccardiDelete, fn ($r) => ! ($r['ok'] ?? false)),
            array_filter($sendcloudDelete, fn ($r) => ! ($r['ok'] ?? false)),
        );
        if ($falliti !== []) {
            return [
                'ok' => false,
                'message' => 'Eliminazione etichetta su fornitore non riuscita. Il rimborso non è stato avviato.',
                'refund_id' => null,
                'amount_euro' => null,
            ];
        }

        try {
            $refund = Refund::create($payload);
        } catch (\Throwable $e) {
            Log::error('Stripe refund fallito', [
                'ordine_id' => $ordine->id,
                'payment_intent' => $paymentIntentId,
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'Stripe ha rifiutato il rimborso: '.$e->getMessage(),
                'refund_id' => null,
                'amount_euro' => null,
            ];
        }

        $refundedEuro = $refund->amount !== null
            ? round((int) $refund->amount / 100, 2)
            : ($importoEuro ?? $totalePagato);

        $motivoRimborso = match ($reason) {
            'duplicate' => 'Rimborso Stripe — duplicato',
            'fraudulent' => 'Rimborso Stripe — fraudolento',
            default => 'Rimborso Stripe — richiesto dal cliente',
        };

        $rimborsoIntero = $importoEuro === null;

        DB::transaction(function () use ($ordine, $refund, $refundedEuro, $paymentIntentId, $motivoRimborso, $rimborsoIntero): void {
            $locked = ordine::query()->whereKey($ordine->id)->lockForUpdate()->firstOrFail();
            $locked->update([
                'stripe_refund_id' => $refund->id,
                'stripe_refund_amount' => $refundedEuro,
                'stripe_refunded_at' => now(),
            ]);

            app(RimborsoStripeSpedizioniService::class)->applicaRimborsoStripe(
                $locked->fresh(['spedizioni.tariffaSpedizione', 'spedizioni.rimborso']),
                $refund->id,
                $paymentIntentId,
                $refundedEuro,
                $motivoRimborso,
                $rimborsoIntero,
            );
        });

        $msg = 'Rimborso Stripe di '.\App\Support\ImportoEuro::format($refundedEuro).' completato (ID '.$refund->id.'). L\'ordine resta pagato.';
        if ($spedisciDelete !== []) {
            $msg .= ' Etichette Spedisci.online eliminate.';
        }

        return [
            'ok' => true,
            'message' => $msg,
            'refund_id' => $refund->id,
            'amount_euro' => $refundedEuro,
        ];
    }

    public function ordinePagatoConCarta(ordine $ordine): bool
    {
        $ordine->loadMissing('metodoPagamento');
        $metodo = $ordine->metodoPagamento;
        if ($metodo instanceof metodo_pagamento_ordine) {
            return $metodo->isCarta();
        }

        if ($ordine->metodo_pagamento_ordinis_id) {
            $m = metodo_pagamento_ordine::query()->find($ordine->metodo_pagamento_ordinis_id);

            return $m !== null && $m->isCarta();
        }

        return $ordine->stripe_payment_intent_id !== null
            || $ordine->stripe_checkout_session_id !== null;
    }

    public function paymentIntentId(ordine $ordine): ?string
    {
        $pi = trim((string) ($ordine->stripe_payment_intent_id ?? ''));
        if ($pi !== '' && str_starts_with($pi, 'pi_')) {
            return $pi;
        }

        $sessionId = trim((string) ($ordine->stripe_checkout_session_id ?? ''));
        if ($sessionId === '' || ! StripeConfig::isConfigured()) {
            return null;
        }

        try {
            Stripe::setApiKey(StripeConfig::secretKey());
            $session = Session::retrieve($sessionId, ['expand' => ['payment_intent']]);
            $resolved = is_string($session->payment_intent)
                ? $session->payment_intent
                : ($session->payment_intent->id ?? null);

            if ($resolved && str_starts_with($resolved, 'pi_')) {
                $ordine->update(['stripe_payment_intent_id' => $resolved]);

                return $resolved;
            }
        } catch (\Throwable $e) {
            Log::warning('Impossibile recuperare Payment Intent da sessione Stripe', [
                'ordine_id' => $ordine->id,
                'session_id' => $sessionId,
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
