<?php

namespace App\Http\Controllers;

use App\Models\metodo_pagamento_ordine;
use App\Models\ordine;
use App\Models\parametri_globali;
use App\Services\OrdineTotaleIvatoService;
use App\Services\Stripe\StripeConfig;
use App\Services\WalletSaldoService;
use App\Support\MetodoPagamentoIcon;
use App\Support\OrdineTotaliPagamento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class OrdinePagamentoCheckoutController extends Controller
{
    public function wallet(Request $request, ordine $ordine): View|RedirectResponse
    {
        $resolved = $this->resolveMetodo($request, $ordine, 'wallet');
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }

        return view('ordini.pagamento.wallet', $resolved);
    }

    public function carta(Request $request, ordine $ordine): View|RedirectResponse
    {
        $resolved = $this->resolveMetodo($request, $ordine, 'carta');
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }

        if (! ($resolved['stripeConfigured'] ?? false)) {
            return redirect()
                ->route('ordini.pagamento.show', $ordine)
                ->withErrors(['pagamento' => 'Pagamento con carta non disponibile: configura Stripe.']);
        }

        return view('ordini.pagamento.carta', $resolved);
    }

    public function bonifico(Request $request, ordine $ordine): View|RedirectResponse
    {
        $resolved = $this->resolveMetodo($request, $ordine, 'bonifico');
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }

        $resolved['ibanBonifico'] = parametri_globali::valoreTesto(parametri_globali::DENOM_IBAN_CC_R_B);

        return view('ordini.pagamento.bonifico', $resolved);
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    private function resolveMetodo(Request $request, ordine $ordine, string $tipo): array|RedirectResponse
    {
        $this->authorize('pay', $ordine);

        if ($ordine->stato !== ordine::STATO_NON_PAGATO) {
            return redirect()
                ->route('ordini.index', ['aba' => 'pagati'])
                ->withErrors(['pagamento' => 'Questo ordine non è in attesa di pagamento.']);
        }

        $metodoId = (int) $request->query('metodo_pagamento_id', 0);
        if ($metodoId <= 0) {
            abort(404);
        }

        $totaleSvc = app(OrdineTotaleIvatoService::class);
        $metodo = metodo_pagamento_ordine::query()
            ->where('abilitato', true)
            ->findOrFail($metodoId);

        $tipoOk = match ($tipo) {
            'wallet' => $totaleSvc->metodoIsWallet($metodoId),
            'carta' => $totaleSvc->metodoIsCarta($metodoId),
            'bonifico' => $totaleSvc->metodoIsBonifico($metodoId),
            default => false,
        };

        if (! $tipoOk) {
            abort(404);
        }

        $totali = OrdineTotaliPagamento::totaliPerMetodo($ordine, $metodoId);
        $metodoJson = [
            'id' => $metodo->id,
            'nome' => $metodo->metodo_pagamento,
            'icon_url' => MetodoPagamentoIcon::pubblico((int) $metodo->id),
            'pct' => (float) $metodo->commissioni,
            'abs' => 0.0,
            'imponibile' => $totali['imponibile'],
            'iva' => $totali['iva'],
            'totale' => $totali['totale'],
            'is_wallet' => $totaleSvc->metodoIsWallet($metodoId),
            'is_carta' => $totaleSvc->metodoIsCarta($metodoId),
            'is_bonifico' => $totaleSvc->metodoIsBonifico($metodoId),
        ];

        $walletSaldoOk = true;
        if ($totaleSvc->metodoIsWallet($metodoId)) {
            $totaleWallet = round((float) ($ordine->total_pagamento_wallet ?? 0), 2);
            $saldo = app(WalletSaldoService::class)->saldoUtente((int) $ordine->user_id);
            $walletSaldoOk = round($saldo, 2) + 1e-9 >= round($totaleWallet, 2);
        }

        return [
            'ordine' => $ordine,
            'metodoJson' => $metodoJson,
            'walletSaldoOk' => $walletSaldoOk,
            'stripeConfigured' => StripeConfig::isConfigured(),
            'stripePublicKey' => StripeConfig::publicKey(),
        ];
    }
}
