<?php

namespace App\Http\Controllers;

use App\Models\metodo_pagamento_ordine;
use App\Models\ordine;
use App\Models\User;
use App\Services\Ordine\BackofficeOrdineEstadoService;
use App\Support\BackofficeOrdineListaFilter;
use App\Support\FiltriTabella;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BackofficeOrdiniController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = BackofficeOrdineListaFilter::fromRequest($request);

        $query = ordine::query()
            ->with(['user', 'metodoPagamentoOrdine', 'statoOrdine'])
            ->perIdRecente();

        $filtros->applyToQuery($query);

        $lista = $query->paginate($filtros->perPage)->withQueryString();

        $selectedUser = $filtros->userId > 0
            ? User::query()->find($filtros->userId)
            : null;

        return view('backoffice.ordini', [
            'lista' => $lista,
            'perPage' => $filtros->perPage,
            'filtros' => $filtros->toArray(),
            'pagamentoFiltroUi' => $filtros->pagamentoUi(),
            'hasActiveFilters' => $filtros->hasActiveFilters(),
            'customPeriodoSemDatas' => $filtros->customPeriodoSemDatas(),
            'selectedUser' => $selectedUser,
            'metodosPagamento' => metodo_pagamento_ordine::query()
                ->paraPagamentoCliente()
                ->orderBy('metodo_pagamento')
                ->get(['id', 'metodo_pagamento']),
            'queryParams' => FiltriTabella::parametriQuery($request),
        ]);
    }

    public function segnaPagato(Request $request, ordine $ordine, BackofficeOrdineEstadoService $svc)
    {
        if (! $ordine->isNonPagato()) {
            return redirect()
                ->route('backoffice.ordini.index', FiltriTabella::parametriRedirect($request, ['_token']))
                ->withErrors(['backoffice' => 'Solo gli ordini non pagati possono essere segnati come pagati.']);
        }

        $validated = $request->validate([
            'metodo_pagamento_id' => [
                'required',
                'integer',
                Rule::exists('metodo_pagamento_ordinis', 'id')->where(function ($q): void {
                    $q->where('abilitato', true)
                        ->where('metodo_pagamento', 'not like', '%BackOffice%')
                        ->where('metodo_pagamento', 'not like', '%Back-office%');
                }),
            ],
            'token_2' => ['nullable', 'string', 'max:500'],
            'data_pagamento' => ['required', 'date'],
        ]);

        $redirectParams = FiltriTabella::parametriRedirect($request, [
            '_token', 'metodo_pagamento_id', 'token_2', 'data_pagamento',
        ]);

        try {
            $svc->marcarPagoManual(
                $ordine,
                (int) $validated['metodo_pagamento_id'],
                isset($validated['token_2']) ? (string) $validated['token_2'] : null,
                (string) $validated['data_pagamento'],
            );
            $avvisi = $svc->processarEtichettePosPagamento($ordine);
        } catch (\DomainException $e) {
            return redirect()
                ->route('backoffice.ordini.index', $redirectParams)
                ->withErrors(['backoffice' => $e->getMessage()]);
        }

        $redirect = redirect()
            ->route('backoffice.ordini.index', $redirectParams)
            ->with('ok', 'Ordine '.$ordine->fresh()->codice.' segnato come pagato (conferma manuale back-office).');

        if ($avvisi !== []) {
            $redirect->with('warning', implode(' ', array_slice($avvisi, 0, 3)));
        }

        return $redirect;
    }

    public function anular(ordine $ordine, BackofficeOrdineEstadoService $svc, Request $request)
    {
        if (! $ordine->isNonPagato()) {
            return redirect()
                ->route('backoffice.ordini.index', FiltriTabella::parametriRedirect($request, ['_token']))
                ->withErrors(['backoffice' => 'Solo gli ordini non pagati possono essere annullati.']);
        }

        try {
            $svc->anularOrdineNonPagato($ordine);
        } catch (\DomainException $e) {
            return redirect()
                ->route('backoffice.ordini.index', FiltriTabella::parametriRedirect($request, ['_token']))
                ->withErrors(['backoffice' => $e->getMessage()]);
        }

        return redirect()
            ->route('backoffice.ordini.index', FiltriTabella::parametriRedirect($request, ['_token']))
            ->with('ok', 'Ordine '.$ordine->codice.' annullato.');
    }
}
