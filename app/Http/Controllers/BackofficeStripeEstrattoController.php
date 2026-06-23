<?php

namespace App\Http\Controllers;

use App\Services\Stripe\StripeEstrattoService;
use App\Support\StripeEstrattoFilters;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackofficeStripeEstrattoController extends Controller
{
    public function __construct(
        private readonly StripeEstrattoService $estratto,
    ) {}

    public function index(Request $request): View|StreamedResponse
    {
        $filtri = StripeEstrattoFilters::fromRequest($request);
        $haRicerca = (string) $request->input('cerca', '') === '1' || $request->boolean('cerca');

        $filtroErrors = [];
        $from = null;
        $to = null;
        $result = [
            'ok' => true,
            'message' => null,
            'balance' => null,
            'righe' => [],
            'has_more' => false,
            'first_id' => null,
            'last_id' => null,
        ];

        if (! $this->estratto->isConfigured()) {
            return view('backoffice.stripe-estratto', [
                'stripeConfigured' => false,
                'haRicerca' => $haRicerca,
                'filtri' => $filtri,
                'filtroErrors' => [],
                'result' => $result,
                'from' => null,
                'to' => null,
            ]);
        }

        $saldo = $this->estratto->saldo();
        if ($saldo['balance'] !== null) {
            $result['balance'] = $saldo['balance'];
        }

        if ($haRicerca) {
            [$from, $to, $filtroErrors] = $filtri->intervallo();

            if ($filtroErrors === [] && $from !== null && $to !== null) {
                if ($request->input('export') === 'csv') {
                    return $this->exportCsv($from, $to, $filtri);
                }

                $result = $this->estratto->elenco(
                    $from,
                    $to,
                    $filtri->limit,
                    $filtri->startingAfter,
                    $filtri->endingBefore,
                );
            }
        }

        return view('backoffice.stripe-estratto', [
            'stripeConfigured' => true,
            'haRicerca' => $haRicerca,
            'filtri' => $filtri,
            'filtroErrors' => $filtroErrors,
            'result' => $result,
            'from' => $from ?? null,
            'to' => $to ?? null,
        ]);
    }

    private function exportCsv(\Carbon\Carbon $from, \Carbon\Carbon $to, StripeEstrattoFilters $filtri): StreamedResponse
    {
        $filename = sprintf(
            'stripe-estratto_%s_%s.csv',
            $from->format('Y-m-d'),
            $to->format('Y-m-d'),
        );

        $ordiniMap = [];

        return response()->streamDownload(function () use ($from, $to, &$ordiniMap): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fprintf($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Data',
                'Tipo',
                'Descrizione',
                'Lordo EUR',
                'Commissioni Stripe EUR',
                'Netto EUR',
                'Payment Intent',
                'Ordine portale',
                'ID transazione Stripe',
            ], ';');

            $batch = [];
            foreach ($this->estratto->elencoCompletoPeriodo($from, $to) as $riga) {
                $batch[] = $riga;
                if (count($batch) >= 50) {
                    $this->flushCsvBatch($out, $batch, $ordiniMap);
                    $batch = [];
                }
            }
            if ($batch !== []) {
                $this->flushCsvBatch($out, $batch, $ordiniMap);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  resource  $out
     * @param  list<array<string, mixed>>  $batch
     * @param  array<string, int>  $ordiniMap
     */
    private function flushCsvBatch($out, array $batch, array &$ordiniMap): void
    {
        $pis = [];
        foreach ($batch as $riga) {
            $pi = trim((string) ($riga['payment_intent_id'] ?? ''));
            if ($pi !== '' && ! isset($ordiniMap[$pi])) {
                $pis[$pi] = true;
            }
        }

        if ($pis !== []) {
            foreach (\App\Models\ordine::query()
                ->whereIn('stripe_payment_intent_id', array_keys($pis))
                ->get(['id', 'stripe_payment_intent_id']) as $ordine) {
                $pi = trim((string) ($ordine->stripe_payment_intent_id ?? ''));
                if ($pi !== '') {
                    $ordiniMap[$pi] = (int) $ordine->id;
                }
            }
        }

        foreach ($batch as $riga) {
            $pi = trim((string) ($riga['payment_intent_id'] ?? ''));
            $ordineId = $pi !== '' ? ($ordiniMap[$pi] ?? '') : '';
            $created = $riga['created_at'] ?? null;

            fputcsv($out, [
                $created instanceof \Carbon\Carbon ? $created->format('d/m/Y H:i:s') : '',
                $riga['type_label'] ?? '',
                $riga['description'] ?? '',
                number_format((float) ($riga['amount'] ?? 0), 2, ',', ''),
                number_format((float) ($riga['fee'] ?? 0), 2, ',', ''),
                number_format((float) ($riga['net'] ?? 0), 2, ',', ''),
                $pi,
                $ordineId !== '' ? (string) $ordineId : '',
                $riga['id'] ?? '',
            ], ';');
        }
    }
}
