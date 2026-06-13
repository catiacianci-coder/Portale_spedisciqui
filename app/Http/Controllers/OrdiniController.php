<?php

namespace App\Http\Controllers;

use App\Http\Requests\PagamentoOrdineRequest;
use App\Models\metodo_pagamento_ordine;
use App\Models\ordine;
use App\Models\parametri_globali;
use App\Models\spedizione;
use App\Models\stato_spedizione;
use App\Services\Liccardi\LiccardiTmsAcquistoService;
use App\Services\Ordine\OrdinePrezziSincronizzazioneService;
use App\Services\Ordine\OrdinePagamentoService;
use App\Services\OrdineTotaleIvatoService;
use App\Services\Sendcloud\SendcloudAcquistoService;
use App\Services\SpedisciOnline\SpedisciOnlineAcquistoService;
use App\Services\SpedizioneStatoService;
use App\Services\Stripe\StripeConfig;
use App\Services\WalletSaldoService;
use App\Support\FiltriTabella;
use App\Support\MetodoPagamentoIcon;
use App\Support\OrdineDettaglioRighe;
use App\Support\OrdineDatiPagamento;
use App\Support\OrdineRiepilogo;
use App\Support\OrdineTotaliPagamento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdiniController extends Controller
{
    public function __construct(
        private readonly OrdinePagamentoService $pagamentoSvc,
    ) {}

    public function index(Request $request)
    {
        $perPage = FiltriTabella::perPage($request);
        $periodo = FiltriTabella::periodoDaRequest($request, '30');
        $numeroOrdine = trim((string) $request->input('numero_ordine', ''));

        $aba = (string) $request->input('aba', 'non_pagati');
        if (! in_array($aba, ['non_pagati', 'pagati', 'annullati'], true)) {
            $aba = 'non_pagati';
        }

        $statoPerAba = [
            'non_pagati' => ordine::STATO_NON_PAGATO,
            'pagati' => ordine::STATO_PAGATO,
            'annullati' => ordine::STATO_ANNULLATO,
        ];

        $baseQuery = fn () => $this->ordiniIndexBaseQuery($request, $periodo, $numeroOrdine);

        $contagens = [
            'non_pagati' => (clone $baseQuery())->where('stato_ordine_id', ordine::statoId(ordine::STATO_NON_PAGATO))->count(),
            'pagati' => (clone $baseQuery())->where('stato_ordine_id', ordine::statoId(ordine::STATO_PAGATO))->count(),
            'annullati' => (clone $baseQuery())->where('stato_ordine_id', ordine::statoId(ordine::STATO_ANNULLATO))->count(),
        ];

        $query = $baseQuery()
            ->where('stato_ordine_id', ordine::statoId($statoPerAba[$aba]))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $ordini = $query
            ->withCount([
                'spedizioni',
                'spedizioni as spedizioni_attive_count' => fn ($q) => $q->where(
                    'spedizione_stato_id',
                    '!=',
                    stato_spedizione::ANNULLATA,
                ),
            ])
            ->with([
                'spedizioni.tariffaSpedizione',
                'spedizioni.spedizioneStato',
                'spedizioni.corriereRecord',
                'spedizioni.ordine.metodoPagamentoOrdine',
                'metodoPagamentoOrdine',
            ])
            ->paginate($perPage)
            ->withQueryString();

        return view('ordini.index', [
            'ordini' => $ordini,
            'aba' => $aba,
            'contagens' => $contagens,
            'perPage' => $perPage,
            'filtroPeriod' => $periodo['period'],
            'filtroDataDa' => $periodo['data_da'],
            'filtroDataA' => $periodo['data_a'],
            'filtroNumeroOrdine' => $numeroOrdine,
            'filtroErrors' => $periodo['errors'],
            'queryParams' => FiltriTabella::parametriQuery($request),
        ]);
    }

    /** @return \Illuminate\Database\Eloquent\Builder<ordine> */
    private function ordiniIndexBaseQuery(Request $request, array $periodo, string $numeroOrdine)
    {
        $query = $request->user()->ordini();

        if ($periodo['errors'] === []) {
            FiltriTabella::applicaFiltroCreatedAt($query, $periodo['from'], $periodo['to']);
        }

        FiltriTabella::filtraNumeroOrdine($query, $numeroOrdine);

        return $query;
    }

    public function show(Request $request, ordine $ordine)
    {
        $this->authorize('view', $ordine);

        $ordine->loadMissing(['metodoPagamento']);

        $podeEditarSpedizioni = $ordine->stato === ordine::STATO_NON_PAGATO;

        return view('ordini.show', array_merge(
            ['ordine' => $ordine, 'podeEditarSpedizioni' => $podeEditarSpedizioni],
            $this->datiRiepilogoSpedizioniOrdine($ordine),
        ));
    }

    public function pagamentoShow(Request $request, ordine $ordine)
    {
        $this->authorize('pay', $ordine);

        if ($ordine->stato !== ordine::STATO_NON_PAGATO) {
            return redirect()
                ->route('ordini.show', $ordine)
                ->withErrors(['pagamento' => 'Questo ordine non è in attesa di pagamento.']);
        }

        return view('ordini.pagamento', array_merge(
            ['ordine' => $ordine],
            $this->datiRiepilogoSpedizioniOrdine($ordine),
            $this->datiVistaPagamentoOrdine($ordine),
        ));
    }

    /** @return array{servizioPerSpedizione: array<int, string>, totaleIvatoOrdine: float} */
    private function datiRiepilogoSpedizioniOrdine(ordine $ordine): array
    {
        $ordine->loadMissing([
            'spedizioni' => fn ($q) => $q->with([
                'corriereRecord',
                'tipoSpedizione',
                'tariffaSpedizione',
                'serviziAggiuntiviRighe.corriereServizioAggiuntivo:id,testo_servizio',
            ])->orderBy('id'),
        ]);

        $servizioPerSpedizione = [];
        foreach ($ordine->spedizioni as $sp) {
            $servizioPerSpedizione[(int) $sp->id] = trim((string) (
                $sp->service_description
                ?? $sp->corriere
                ?? $sp->corriereRecord?->nome_visualizzato
                ?? $sp->corriereRecord?->nome_corriere
                ?? ''
            ));
        }

        $totaleIvatoOrdine = 0.0;
        foreach ($ordine->spedizioni as $sp) {
            if ((int) $sp->spedizione_stato_id === stato_spedizione::ANNULLATA) {
                continue;
            }
            $totaleIvatoOrdine += (float) ($sp->prezzoClienteIvato() ?? 0);
        }

        return [
            'servizioPerSpedizione' => $servizioPerSpedizione,
            'totaleIvatoOrdine' => round($totaleIvatoOrdine, 2),
        ];
    }

    /** @return array<string, mixed> */
    private function datiVistaPagamentoOrdine(ordine $ordine): array
    {
        $righe = OrdineDettaglioRighe::righePerCards($ordine);

        $totaleTrasportoSolo = 0.0;
        $totaleExtraServizi = 0.0;
        foreach ($righe as $r) {
            if (! is_array($r)) {
                continue;
            }
            $totaleTrasportoSolo += (float) ($r['trasporto_iva_esc'] ?? 0);
            $totaleExtraServizi += (float) ($r['extra_servizi_iva_esc'] ?? 0);
        }
        $totaleTrasportoSolo = round($totaleTrasportoSolo, 2);
        $totaleExtraServizi = round($totaleExtraServizi, 2);

        $metodi = metodo_pagamento_ordine::query()
            ->where('abilitato', true)
            ->orderBy('id')
            ->get();

        $metodiJson = $metodi->map(function (metodo_pagamento_ordine $m) use ($ordine) {
            $t = OrdineTotaliPagamento::totaliPerMetodo($ordine, (int) $m->id);

            return [
                'id' => $m->id,
                'nome' => $m->metodo_pagamento,
                'icon_url' => MetodoPagamentoIcon::pubblico((int) $m->id),
                'pct' => (float) $m->commissioni,
                'abs' => 0.0,
                'imponibile' => $t['imponibile'],
                'iva' => $t['iva'],
                'totale' => $t['totale'],
                'is_wallet' => app(OrdineTotaleIvatoService::class)->metodoIsWallet((int) $m->id),
                'is_carta' => app(OrdineTotaleIvatoService::class)->metodoIsCarta((int) $m->id),
                'is_bonifico' => app(OrdineTotaleIvatoService::class)->metodoIsBonifico((int) $m->id),
            ];
        })->values()->all();

        $walletMetodoId = null;
        foreach ($metodiJson as $mj) {
            if (! empty($mj['is_wallet'])) {
                $walletMetodoId = (int) $mj['id'];
                break;
            }
        }

        $walletSaldoOk = true;
        if ($walletMetodoId !== null) {
            $totaleWallet = round((float) ($ordine->total_pagamento_wallet ?? 0), 2);
            if ($totaleWallet <= 0) {
                $totaleWallet = OrdineTotaliPagamento::totaliPerMetodo($ordine, $walletMetodoId)['totale'];
            }
            $saldo = app(WalletSaldoService::class)->saldoUtente((int) $ordine->user_id);
            $walletSaldoOk = round($saldo, 2) + 1e-9 >= round($totaleWallet, 2);
        }

        return [
            'metodiJson' => $metodiJson,
            'totaleTrasportoSolo' => $totaleTrasportoSolo,
            'totaleExtraServizi' => $totaleExtraServizi,
            'walletSaldoOk' => $walletSaldoOk,
            'stripeConfigured' => StripeConfig::isConfigured(),
        ];
    }

    private function aliquotaIvaCorrente(): float
    {
        $aliquotaIva = parametri_globali::query()
            ->where('denominazione', 'Aliquota IVA')
            ->attivoOggi()
            ->value('valore_percentuale');

        return $aliquotaIva !== null ? (float) $aliquotaIva : 22.0;
    }

    public function eliminaSpedizioniMarcate(Request $request, ordine $ordine): RedirectResponse
    {
        $this->authorize('update', $ordine);

        $ids = collect($request->input('spedizioni', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return redirect()
                ->route('ordini.show', $ordine)
                ->with('ok', 'Nessuna spedizione selezionata.');
        }

        $ordineAnnullato = false;

        DB::transaction(function () use ($ordine, $ids, &$ordineAnnullato): void {
            $now = now();

            spedizione::query()
                ->where('ordine_id', $ordine->id)
                ->whereIn('id', $ids)
                ->where('spedizione_stato_id', stato_spedizione::NON_PAGATA)
                ->update([
                    'spedizione_stato_id' => stato_spedizione::ANNULLATA,
                    'cancellata_il' => $now,
                ]);

            app(OrdinePrezziSincronizzazioneService::class)->dopoRimozioneSpedizioni($ordine);

            $restantiAttive = spedizione::query()
                ->where('ordine_id', $ordine->id)
                ->where('spedizione_stato_id', stato_spedizione::NON_PAGATA)
                ->count();

            if ($restantiAttive === 0) {
                $ordine->refresh();
                SpedizioneStatoService::segnaAnnullataPerOrdine($ordine);
                $ordine->update(OrdineDatiPagamento::attributiAnnullamento());
                $ordineAnnullato = true;
            }
        });

        $msg = $ordineAnnullato
            ? 'Tutte le spedizioni sono state eliminate. L\'ordine è stato annullato.'
            : 'Spedizioni selezionate eliminate dall\'ordine.';

        if ($ordineAnnullato) {
            return redirect()
                ->route('ordini.index', ['aba' => 'annullati'])
                ->with('ok', $msg);
        }

        return redirect()
            ->route('ordini.show', $ordine)
            ->with('ok', $msg);
    }

    public function pagamento(PagamentoOrdineRequest $request, ordine $ordine)
    {
        metodo_pagamento_ordine::query()->where('abilitato', true)->findOrFail((int) $request->validated('metodo_pagamento_id'));

        return $this->pagamentoSvc->esegui(
            $request,
            $ordine,
            (int) $request->validated('metodo_pagamento_id'),
        );
    }

    public function annulla(Request $request, ordine $ordine): RedirectResponse
    {
        $this->authorize('cancel', $ordine);

        if ($ordine->stato === ordine::STATO_ANNULLATO) {
            return redirect()
                ->route('ordini.index')
                ->with('ok', 'Ordine già annullato.');
        }

        if (! in_array($ordine->stato, [ordine::STATO_NON_PAGATO, ordine::STATO_PAGATO], true)) {
            return redirect()
                ->route('ordini.show', $ordine)
                ->withErrors(['ordine' => 'Questo ordine non può essere annullato.']);
        }

        $spedisciDelete = [];
        $liccardiDelete = [];
        $sendcloudDelete = [];
        if ($ordine->stato === ordine::STATO_PAGATO) {
            $spedisciDelete = app(SpedisciOnlineAcquistoService::class)->eliminaEtichettePerOrdine($ordine);
            $liccardiDelete = app(LiccardiTmsAcquistoService::class)->eliminaEtichettePerOrdine($ordine);
            $sendcloudDelete = app(SendcloudAcquistoService::class)->eliminaEtichettePerOrdine($ordine);
            $falliti = array_merge(
                array_filter($spedisciDelete, fn ($r) => ! ($r['ok'] ?? false)),
                array_filter($liccardiDelete, fn ($r) => ! ($r['ok'] ?? false)),
                array_filter($sendcloudDelete, fn ($r) => ! ($r['ok'] ?? false)),
            );
            if ($falliti !== []) {
                return redirect()
                    ->route('ordini.show', $ordine)
                    ->withErrors([
                        'ordine' => 'Eliminazione etichetta su fornitore non riuscita. L’ordine non è stato annullato.',
                    ]);
            }
        }

        if ($ordine->stato === ordine::STATO_NON_PAGATO) {
            SpedizioneStatoService::segnaAnnullataPerOrdine($ordine);
        }

        $ordine->update(OrdineDatiPagamento::attributiAnnullamento());

        $msg = 'Ordine '.$ordine->codice.' annullato.';
        if ($spedisciDelete !== [] || $liccardiDelete !== []) {
            $msg .= ' Etichette fornitore eliminate.';
        }

        return redirect()
            ->route('ordini.index')
            ->with('ok', $msg);
    }
}
