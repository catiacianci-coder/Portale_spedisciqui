<?php

namespace App\Services\Checkout;

use App\Http\Controllers\CarrelloController;
use App\Models\metodo_pagamento_ordine;
use App\Models\ordine;
use App\Services\Ordine\OrdinePagamentoService;
use App\Support\PreventivoRigaSelezionabile;
use App\Support\PuntoConsegnaSessione;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CheckoutPagamentoService
{
    public function __construct(
        private readonly OrdinePagamentoService $pagamentoSvc,
    ) {}

    public function paga(Request $request, int $corriereId, int $metodoId): RedirectResponse
    {
        metodo_pagamento_ordine::query()->where('abilitato', true)->findOrFail($metodoId);

        $preventivo = $request->session()->get('preventivo');
        if (! is_array($preventivo)) {
            return redirect()
                ->route('preventivi')
                ->withErrors(['checkout' => 'Sessione preventivo non valida.']);
        }

        $riga = PreventivoRigaSelezionabile::trovaRiga($preventivo, $corriereId);
        if (! $riga) {
            abort(404);
        }

        $errPunto = PuntoConsegnaSessione::sincronizzaDaRichiesta($preventivo, $riga, $request);
        if ($errPunto !== null) {
            return redirect()
                ->route('checkout.show', ['corriere' => $corriereId])
                ->withErrors(['checkout' => $errPunto]);
        }
        $request->session()->put('preventivo', $preventivo);

        $ordineOrRedirect = app(CarrelloController::class)->creaOrdineSingoloDaCheckoutComeOrdine($request);
        if ($ordineOrRedirect instanceof RedirectResponse) {
            return $ordineOrRedirect;
        }

        $request->merge(['checkout_corriere_id' => $corriereId]);

        return $this->pagamentoSvc->esegui($request, $ordineOrRedirect, $metodoId);
    }
}
