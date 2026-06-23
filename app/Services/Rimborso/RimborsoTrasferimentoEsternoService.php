<?php

namespace App\Services\Rimborso;

use App\Models\ordine;
use App\Models\rimborso;
use App\Models\wallet_descrizione;
use App\Models\wallet_movimento;
use App\Services\Revolut\RevolutPayoutService;
use App\Services\Stripe\StripeRefundService;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Trasferimento dal wallet al metodo di pagamento originale dell’ordine (carta / bonifico).
 */
final class RimborsoTrasferimentoEsternoService
{
    public function __construct(
        private readonly RimborsoStripeSpedizioneService $stripeRimborso,
        private readonly StripeRefundService $stripeRefund,
        private readonly RevolutPayoutService $revolutPayout,
    ) {}

    public function registraRichiestaCliente(rimborso $rimborso): void
    {
        $rimborso->refresh();

        if (! $rimborso->isAccreditatoSuWallet()) {
            throw new DomainException('Il rimborso non risulta accreditato sul wallet.');
        }

        if ($rimborso->isTrasferimentoEsternoCompletato()) {
            throw new DomainException('Trasferimento già completato.');
        }

        if ($rimborso->haRichiestaTrasferimentoEsterno()) {
            throw new DomainException('Richiesta di trasferimento già registrata.');
        }

        $rimborso->update(['data_richiesta_trasferimento_esterno' => now()]);
    }

    public function trasferisciSuCarta(rimborso $rimborso): void
    {
        $this->assertProntoPerTrasferimento($rimborso);

        $ordine = $this->ordineDelRimborso($rimborso);
        $metodo = $ordine->metodoPagamentoOrdine;
        if (! $metodo?->isCarta()) {
            throw new DomainException('L’ordine non risulta pagato con carta.');
        }

        if (! $this->stripeRefund->ordinePagatoConCarta($ordine)) {
            throw new DomainException('Stripe non risulta collegato a questo ordine.');
        }

        $paymentIntentId = $this->stripeRefund->paymentIntentId($ordine);
        if ($paymentIntentId === null) {
            throw new DomainException('Payment Intent Stripe non trovato per l’ordine.');
        }

        $refundId = trim((string) ($rimborso->stripe_refund_id ?? ''));

        if ($refundId === '') {
            if (trim((string) ($rimborso->stripe_payment_intent_id ?? '')) === '') {
                $rimborso->update([
                    'stripe_payment_intent_id' => $paymentIntentId,
                    'payment_id' => $paymentIntentId,
                ]);
            }

            $result = $this->stripeRimborso->rimborsa($rimborso, $ordine);
            if (! $result['ok']) {
                throw new DomainException($result['message']);
            }

            $refundId = trim((string) ($result['refund_id'] ?? ''));
            if ($refundId === '') {
                throw new DomainException('Stripe non ha restituito l’ID del rimborso.');
            }
        }

        DB::transaction(function () use ($rimborso, $refundId, $paymentIntentId): void {
            $locked = rimborso::query()->whereKey($rimborso->id)->lockForUpdate()->firstOrFail();
            if ($locked->isTrasferimentoEsternoCompletato()) {
                return;
            }

            $attrs = [
                'stripe_refund_id' => $refundId,
                'data_trasferimento_esterno' => now(),
            ];
            if (trim((string) ($locked->stripe_payment_intent_id ?? '')) === '') {
                $attrs['stripe_payment_intent_id'] = $paymentIntentId;
                $attrs['payment_id'] = $paymentIntentId;
            }
            if (! $locked->haRichiestaTrasferimentoEsterno()) {
                $attrs['data_richiesta_trasferimento_esterno'] = now();
            }

            $locked->update($attrs);
            $this->addebitaWalletPerUscita($locked);
        });
    }

    public function trasferisciSuBonifico(rimborso $rimborso, string $iban, string $beneficiario): void
    {
        $this->assertProntoPerTrasferimento($rimborso);

        $ordine = $this->ordineDelRimborso($rimborso);
        $metodo = $ordine->metodoPagamentoOrdine;
        if (! $metodo?->isBonifico()) {
            throw new DomainException('L’ordine non risulta pagato con bonifico.');
        }

        $importo = round((float) $rimborso->valore, 2);
        $reference = trim((string) ($rimborso->codice_interno ?? ''));
        if ($reference === '') {
            $reference = 'Rimborso '.(string) (int) $ordine->id;
        }

        $requestId = 'rimborso-'.$rimborso->id.'-'.now()->format('YmdHis');

        $result = $this->revolutPayout->bonificoVersoIban(
            $iban,
            $beneficiario,
            $importo,
            $reference,
            $requestId,
        );

        if (! $result['ok']) {
            throw new DomainException($result['message']);
        }

        DB::transaction(function () use ($rimborso, $result): void {
            $locked = rimborso::query()->whereKey($rimborso->id)->lockForUpdate()->firstOrFail();
            if ($locked->isTrasferimentoEsternoCompletato()) {
                throw new DomainException('Trasferimento già completato.');
            }

            $attrs = [
                'revolut_transaction_id' => $result['transaction_id'],
                'data_trasferimento_esterno' => now(),
            ];
            if (! $locked->haRichiestaTrasferimentoEsterno()) {
                $attrs['data_richiesta_trasferimento_esterno'] = now();
            }

            $locked->update($attrs);
            $this->addebitaWalletPerUscita($locked);
        });
    }

    public function segnaCompletatoManuale(rimborso $rimborso): void
    {
        $this->assertProntoPerTrasferimento($rimborso);

        DB::transaction(function () use ($rimborso): void {
            $locked = rimborso::query()->whereKey($rimborso->id)->lockForUpdate()->firstOrFail();
            if ($locked->isTrasferimentoEsternoCompletato()) {
                throw new DomainException('Trasferimento già completato.');
            }

            $attrs = ['data_trasferimento_esterno' => now()];
            if (! $locked->haRichiestaTrasferimentoEsterno()) {
                $attrs['data_richiesta_trasferimento_esterno'] = now();
            }

            $locked->update($attrs);
            $this->addebitaWalletPerUscita($locked);
        });
    }

    public function nomeBeneficiarioDefault(rimborso $rimborso): string
    {
        return $rimborso->nomeBeneficiarioBonifico();
    }

    private function assertProntoPerTrasferimento(rimborso $rimborso): void
    {
        $rimborso->refresh();

        if (! $rimborso->canTrasferimentoEsterno()) {
            throw new DomainException('Rimborso non in coda per trasferimento esterno.');
        }
    }

    private function ordineDelRimborso(rimborso $rimborso): ordine
    {
        $rimborso->loadMissing(['ordine.metodoPagamentoOrdine', 'spedizione.ordine.metodoPagamentoOrdine']);
        $ordine = $rimborso->ordine ?? $rimborso->spedizione?->ordine;
        if (! $ordine) {
            throw new DomainException('Ordine non trovato per il rimborso.');
        }

        return $ordine;
    }

    private function addebitaWalletPerUscita(rimborso $rimborso): void
    {
        $user = $rimborso->spedizione?->user;
        if (! $user) {
            throw new DomainException('Utente non trovato per l’addebito wallet.');
        }

        $descr = wallet_descrizione::query()
            ->where('codice', 'trasferimento_uscita')
            ->where('tipo', 'debito')
            ->first();

        if (! $descr) {
            throw new DomainException('Descrizione wallet «trasferimento_uscita» non configurata.');
        }

        $importo = round((float) $rimborso->valore, 2);
        $riferimento = (string) ($rimborso->codice_interno ?? $rimborso->token ?? '');

        $giaAddebitato = wallet_movimento::query()
            ->where('user_id', $user->id)
            ->where('wallet_descrizione_id', $descr->id)
            ->where('ordine_id', $rimborso->ordine_id)
            ->where('riferimento', $riferimento)
            ->where('importo', $importo)
            ->exists();

        if ($giaAddebitato) {
            return;
        }

        wallet_movimento::create([
            'user_id' => $user->id,
            'tipo' => 'debito',
            'wallet_descrizione_id' => $descr->id,
            'importo' => $importo,
            'data_movimento' => now(),
            'riferimento' => $riferimento,
            'ordine_id' => $rimborso->ordine_id,
        ]);
    }
}
