<?php

namespace App\Http\Controllers;

use App\Models\ordine;
use App\Services\Stripe\StripeCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StripeOrdineController extends Controller
{
    public function success(Request $request, ordine $ordine, StripeCheckoutService $stripe): RedirectResponse
    {
        $this->authorize('view', $ordine);

        $sessionId = trim((string) $request->query('session_id', ''));
        if ($sessionId === '') {
            return redirect()
                ->route('ordini.pagamento.show', $ordine)
                ->withErrors(['pagamento' => 'Sessione Stripe mancante nel ritorno dal pagamento.']);
        }

        $outcome = $stripe->confermaDaSessionId($sessionId, $ordine);
        $ordine->refresh();

        $ordine->loadMissing('spedizioni');
        $spedizione = $ordine->spedizioni->first();
        $corriereId = (int) ($spedizione?->id_codice_servizio ?? 0);

        if (! $outcome['ok']) {
            return redirect()
                ->route('ordini.pagamento.show', $ordine)
                ->withErrors(['pagamento' => $outcome['message']]);
        }

        $flash = $outcome['message'];
        if ($ordine->spedizioni()->where('esiste_integrazione', true)->exists()) {
            $flash .= ' Acquisto etichetta avviato (Spedisci.online / Liccardi TMS / Sendcloud se applicabile).';
        }

        if ($corriereId > 0 && $request->session()->has('checkout_ritiro_api_risposta')) {
            return redirect()
                ->route('checkout.show', ['corriere' => $corriereId])
                ->with('ok', $flash);
        }

        return redirect()
            ->route('ordini.show', $ordine)
            ->with('ok', $flash);
    }

    public function cancel(Request $request, ordine $ordine): RedirectResponse
    {
        $this->authorize('view', $ordine);

        return redirect()
            ->route('ordini.pagamento.show', $ordine)
            ->withErrors(['pagamento' => 'Pagamento con carta annullato. Puoi riprovare quando vuoi.']);
    }
}
