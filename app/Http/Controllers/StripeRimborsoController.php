<?php

namespace App\Http\Controllers;

use App\Models\ordine;
use App\Services\Stripe\StripeRefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StripeRimborsoController extends Controller
{
    public function store(Request $request, ordine $ordine, StripeRefundService $refunds): RedirectResponse
    {
        $user = $request->user();
        $isBackoffice = $user && $user->canAccessBackoffice();

        if (! $isBackoffice) {
            abort_unless((int) $ordine->user_id === (int) $user?->id, 403);
        }

        $importo = null;
        if ($request->filled('importo')) {
            $importo = (float) str_replace(',', '.', (string) $request->input('importo'));
        }

        if (! $isBackoffice && $importo !== null) {
            return $this->redirectBack($request, $ordine)
                ->withErrors(['rimborso' => 'Il rimborso parziale è disponibile solo dal back-office.']);
        }

        $reason = (string) $request->input('reason', 'requested_by_customer');
        $result = $refunds->rimborsaOrdine($ordine, $importo, $reason);

        if (! $result['ok']) {
            return $this->redirectBack($request, $ordine)
                ->withErrors(['rimborso' => $result['message']]);
        }

        return $this->redirectBack($request, $ordine)
            ->with('ok', $result['message']);
    }

    private function redirectBack(Request $request, ordine $ordine): RedirectResponse
    {
        if ($request->user()?->canAccessBackoffice() && $request->boolean('backoffice')) {
            return redirect()->route('backoffice.ordini.index');
        }

        if ($request->headers->get('referer') && str_contains((string) $request->headers->get('referer'), '/ordini')) {
            return redirect()->route('ordini.index');
        }

        return redirect()->route('ordini.show', $ordine);
    }
}
