<?php

namespace App\Services\Ordine;

use App\Models\metodo_pagamento_ordine;
use App\Models\ordine;
use App\Models\wallet_descrizione;
use App\Models\wallet_movimento;
use App\Models\wallet_saldo;
use App\Services\Liccardi\LiccardiTmsAcquistoService;
use App\Services\OrdineTotaleIvatoService;
use App\Services\Sendcloud\SendcloudAcquistoService;
use App\Services\SpedisciOnline\SpedisciOnlineAcquistoService;
use App\Services\SpedizioneStatoService;
use App\Services\Stripe\StripeCheckoutService;
use App\Services\Stripe\StripeConfig;
use App\Support\OrdineDatiPagamento;
use App\Support\OrdinePagamentoEffettivo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class OrdinePagamentoService
{
    public function __construct(
        private readonly OrdineTotaleIvatoService $totaleSvc,
    ) {}

    public function esegui(Request $request, ordine $ordine, int $metodoId): RedirectResponse
    {
        if ($ordine->stato !== ordine::STATO_NON_PAGATO) {
            return redirect()
                ->route('ordini.show', $ordine)
                ->withErrors(['pagamento' => 'Questo ordine non è in attesa di pagamento.']);
        }

        if ($this->totaleSvc->metodoIsWallet($metodoId)) {
            if (! $request->boolean('conferma_wallet')) {
                if ($request->filled('checkout_corriere_id')) {
                    return redirect()
                        ->route('checkout.show', ['corriere' => (int) $request->input('checkout_corriere_id')])
                        ->withErrors([
                            'checkout' => 'Per il Wallet: clicca «Paga», conferma nella finestra e poi «Conferma».',
                        ]);
                }

                return redirect()
                    ->route('ordini.pagamento.show', $ordine)
                    ->withErrors([
                        'pagamento' => 'Per pagare con Wallet usa il pulsante «Paga», conferma nella finestra e poi «Conferma».',
                    ]);
            }

            return $this->completaPagamentoWallet($ordine, $metodoId);
        }

        if ($this->totaleSvc->metodoIsCarta($metodoId)) {
            if (! StripeConfig::isConfigured()) {
                return $this->redirectPagamentoErrore($request, $ordine, 'Pagamento con carta non disponibile: configura le chiavi Stripe in .env.');
            }

            try {
                $url = app(StripeCheckoutService::class)->createCheckoutSessionUrl($ordine, $metodoId);

                return redirect()->away($url);
            } catch (\Throwable $e) {
                report($e);

                return $this->redirectPagamentoErrore($request, $ordine, 'Impossibile avviare il pagamento Stripe. Riprova tra poco.');
            }
        }

        $metodo = metodo_pagamento_ordine::query()->find($metodoId);
        $ordine->update([
            'metodo_pagamento_ordinis_id' => $metodoId,
            'metodo_pagamento' => $metodo?->metodo_pagamento,
        ]);

        $redirect = redirect()
            ->route('ordini.show', $ordine)
            ->with('ok', 'Metodo di pagamento registrato. Completa il bonifico secondo le istruzioni riportate.');

        if ($this->totaleSvc->metodoIsBonifico($metodoId)) {
            $redirect->with('mostra_popup_bonifico', true);
        }

        return $redirect;
    }

    private function redirectPagamentoErrore(Request $request, ordine $ordine, string $message): RedirectResponse
    {
        if ($request->filled('checkout_corriere_id')) {
            return redirect()
                ->route('checkout.show', ['corriere' => (int) $request->input('checkout_corriere_id')])
                ->withErrors(['checkout' => $message]);
        }

        return redirect()
            ->route('ordini.pagamento.show', $ordine)
            ->withErrors(['pagamento' => $message]);
    }

    private function completaPagamentoWallet(ordine $ordine, int $metodoId): RedirectResponse
    {
        if (! $this->totaleSvc->metodoIsWallet($metodoId)) {
            abort(403);
        }

        $descr = wallet_descrizione::query()->where('codice', 'pagamento_ordine')->first();
        if (! $descr || $descr->tipo !== 'debito') {
            return redirect()
                ->route('ordini.pagamento.show', $ordine)
                ->withErrors(['pagamento' => 'Configurazione Wallet non disponibile. Contatta l’assistenza.']);
        }

        $blockReason = null;

        try {
            DB::transaction(function () use ($ordine, $metodoId, $descr, &$blockReason): void {
                $locked = ordine::query()->whereKey($ordine->id)->lockForUpdate()->first();
                if (! $locked || ! $locked->haStato(ordine::STATO_NON_PAGATO)) {
                    $blockReason = 'stato';

                    return;
                }

                $totaleIvato = OrdinePagamentoEffettivo::importoOrdine($locked, $metodoId);
                $uid = (int) $locked->user_id;

                $saldoRow = wallet_saldo::query()->where('user_id', $uid)->lockForUpdate()->first();
                $saldo = $saldoRow ? (float) $saldoRow->saldo : 0.0;

                if (round($saldo, 2) + 1e-9 < round($totaleIvato, 2)) {
                    $blockReason = 'saldo';

                    return;
                }

                wallet_movimento::create([
                    'user_id' => $uid,
                    'tipo' => 'debito',
                    'wallet_descrizione_id' => $descr->id,
                    'importo' => $totaleIvato,
                    'data_movimento' => now(),
                    'riferimento' => (string) $locked->codice,
                    'ordine_id' => $locked->id,
                ]);

                $metodo = metodo_pagamento_ordine::query()->find($metodoId);
                $locked->update(OrdineDatiPagamento::attributiPagamentoCompletato($locked, $metodoId, $metodo));
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('ordini.pagamento.show', $ordine)
                ->withErrors(['pagamento' => 'Pagamento Wallet non riuscito. Riprova o contatta l’assistenza.']);
        }

        if ($blockReason === 'saldo') {
            return redirect()
                ->route('ordini.pagamento.show', $ordine)
                ->withErrors(['pagamento' => 'Saldo Wallet insufficiente al momento della conferma.']);
        }

        if ($blockReason === 'stato') {
            return redirect()
                ->route('ordini.pagamento.show', $ordine)
                ->withErrors(['pagamento' => 'Questo ordine non è più in attesa di pagamento.']);
        }

        $ordine->refresh();
        OrdinePagamentoEffettivo::registraSuTariffe($ordine, $metodoId);
        SpedizioneStatoService::segnaPagataPerOrdine($ordine);
        app(SpedisciOnlineAcquistoService::class)->elaboraOrdinePagato($ordine);
        app(LiccardiTmsAcquistoService::class)->elaboraOrdinePagato($ordine);
        app(SendcloudAcquistoService::class)->elaboraOrdinePagato($ordine);

        $flashOk = 'Pagamento completato con Wallet. L’ordine risulta pagato e l’importo è stato addebitato dal saldo.';
        if ($ordine->spedizioni()->where('esiste_integrazione', true)->exists()) {
            $flashOk .= ' Acquisto etichetta avviato (Spedisci.online / Liccardi TMS / Sendcloud se applicabile).';
        }

        return redirect()
            ->route('ordini.show', $ordine)
            ->with('ok', $flashOk);
    }
}
