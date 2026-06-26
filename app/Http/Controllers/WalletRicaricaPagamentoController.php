<?php

namespace App\Http\Controllers;

use App\Http\Requests\WalletRicaricaPagamentoCartaRequest;
use App\Models\metodo_pagamento_wallet_ricarica;
use App\Models\parametri_globali;
use App\Models\wallet_ricarica_richiesta;
use App\Services\Stripe\StripeConfig;
use App\Services\Stripe\StripeRicaricaPaymentIntentService;
use App\Services\Wallet\WalletRicaricaPagamentoService;
use App\Support\MetodoPagamentoIcon;
use App\Support\WalletRicaricaTotaliPagamento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class WalletRicaricaPagamentoController extends Controller
{
    public function show(Request $request, wallet_ricarica_richiesta $ricarica): View|RedirectResponse
    {
        if ($redirect = $this->guardRicaricaPagabile($request, $ricarica)) {
            return $redirect;
        }

        $metodi = metodo_pagamento_wallet_ricarica::query()->abilitatiCliente()->get();
        if ($metodi->isEmpty()) {
            return redirect()
                ->route('wallet.ricariche')
                ->withErrors(['ricarica' => 'Nessun metodo di pagamento disponibile per le ricariche.']);
        }

        $payload = $this->payloadMetodi($ricarica, $metodi);

        return view('wallet.pagamento.show', [
            'ricarica' => $ricarica,
            'metodiJson' => $payload['metodiJson'],
            'stripeConfigured' => $payload['stripeConfigured'],
        ]);
    }

    public function carta(Request $request, wallet_ricarica_richiesta $ricarica): View|RedirectResponse
    {
        $resolved = $this->resolveMetodo($request, $ricarica, 'carta');
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }

        if (! ($resolved['stripeConfigured'] ?? false)) {
            return redirect()
                ->route('wallet.ricariche.pagamento.show', $ricarica)
                ->withErrors(['ricarica' => 'Pagamento con carta non disponibile: configura Stripe.']);
        }

        return view('wallet.pagamento.carta', $resolved);
    }

    public function bonifico(Request $request, wallet_ricarica_richiesta $ricarica): View|RedirectResponse
    {
        $resolved = $this->resolveMetodo($request, $ricarica, 'bonifico');
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }

        $resolved['ibanBonifico'] = parametri_globali::valoreTesto(parametri_globali::DENOM_IBAN_CC_R_B);
        $resolved['causaleBonifico'] = (string) (
            $ricarica->numero_ordine_wallet
            ?? wallet_ricarica_richiesta::PREFIX_NUMERO_ORDINE_WALLET.$ricarica->id
        );

        return view('wallet.pagamento.bonifico', $resolved);
    }

    public function cartaProcess(
        WalletRicaricaPagamentoCartaRequest $request,
        wallet_ricarica_richiesta $ricarica,
    ): JsonResponse {
        $validated = $request->validated();
        $metodoId = (int) $validated['metodo_pagamento_id'];
        metodo_pagamento_wallet_ricarica::query()->where('abilitato', true)->findOrFail($metodoId);

        $svc = app(StripeRicaricaPaymentIntentService::class);

        if (! empty($validated['payment_intent_id'])) {
            $result = $svc->finalizzaDaIntent(
                $ricarica,
                $metodoId,
                (string) $validated['payment_intent_id'],
            );
        } else {
            $result = $svc->addebitaRicarica(
                $ricarica,
                $metodoId,
                (string) ($validated['payment_method_id'] ?? ''),
            );
        }

        if (($result['ok'] ?? false) || ! empty($result['requires_action'])) {
            return response()->json($result);
        }

        return response()->json($result, 422);
    }

    public function bonificoStore(Request $request, wallet_ricarica_richiesta $ricarica): RedirectResponse
    {
        if ($redirect = $this->guardRicaricaPagabile($request, $ricarica)) {
            return $redirect;
        }

        $validated = $request->validate([
            'metodo_pagamento_id' => ['required', 'integer', 'exists:metodo_pagamento_wallet_ricariches,id'],
        ]);

        return app(WalletRicaricaPagamentoService::class)->registraBonifico(
            $ricarica,
            (int) $validated['metodo_pagamento_id'],
        );
    }

    private function guardRicaricaPagabile(Request $request, wallet_ricarica_richiesta $ricarica): ?RedirectResponse
    {
        if ((int) $ricarica->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ($ricarica->stato !== 'in_attesa') {
            return redirect()
                ->route('wallet.ricariche')
                ->withErrors(['ricarica' => 'Questa ricarica non è in attesa di pagamento.']);
        }

        return null;
    }

    private function assertRicaricaPagabile(Request $request, wallet_ricarica_richiesta $ricarica): void
    {
        if ($redirect = $this->guardRicaricaPagabile($request, $ricarica)) {
            abort($redirect);
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, metodo_pagamento_wallet_ricarica>  $metodi
     * @return array{metodiJson: list<array<string, mixed>>, stripeConfigured: bool}
     */
    private function payloadMetodi(wallet_ricarica_richiesta $ricarica, $metodi): array
    {
        $metodiJson = $metodi->map(function (metodo_pagamento_wallet_ricarica $m) use ($ricarica) {
            $t = WalletRicaricaTotaliPagamento::perMetodo($ricarica, (int) $m->id);

            return [
                'id' => $m->id,
                'nome' => $m->metodo_pagamento,
                'icon_url' => MetodoPagamentoIcon::pubblico((int) $m->id),
                'pct' => (float) $m->commissioni,
                'abs' => 0.0,
                'imponibile' => $t['imponibile'],
                'iva' => $t['iva'],
                'totale' => $t['totale'],
                'is_carta' => $m->isCarta(),
                'is_bonifico' => $m->isBonifico(),
            ];
        })->values()->all();

        return [
            'metodiJson' => $metodiJson,
            'stripeConfigured' => StripeConfig::isConfigured(),
        ];
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    private function resolveMetodo(Request $request, wallet_ricarica_richiesta $ricarica, string $tipo): array|RedirectResponse
    {
        $this->assertRicaricaPagabile($request, $ricarica);

        $metodoId = (int) $request->query('metodo_pagamento_id', 0);
        if ($metodoId <= 0) {
            abort(404);
        }

        $metodo = metodo_pagamento_wallet_ricarica::query()
            ->where('abilitato', true)
            ->findOrFail($metodoId);

        $tipoOk = match ($tipo) {
            'carta' => $metodo->isCarta(),
            'bonifico' => $metodo->isBonifico(),
            default => false,
        };

        if (! $tipoOk) {
            abort(404);
        }

        $totali = WalletRicaricaTotaliPagamento::perMetodo($ricarica, $metodoId);

        return [
            'ricarica' => $ricarica,
            'metodoJson' => [
                'id' => $metodo->id,
                'nome' => $metodo->metodo_pagamento,
                'icon_url' => MetodoPagamentoIcon::pubblico((int) $metodo->id),
                'pct' => (float) $metodo->commissioni,
                'abs' => 0.0,
                'imponibile' => $totali['imponibile'],
                'iva' => $totali['iva'],
                'totale' => $totali['totale'],
                'is_carta' => $metodo->isCarta(),
                'is_bonifico' => $metodo->isBonifico(),
            ],
            'stripeConfigured' => StripeConfig::isConfigured(),
            'stripePublicKey' => StripeConfig::publicKey(),
        ];
    }
}
