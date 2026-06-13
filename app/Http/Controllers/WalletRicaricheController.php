<?php

namespace App\Http\Controllers;

use App\Models\wallet_ricarica_richiesta;
use App\Support\FiltriTabella;
use App\Support\WalletRicaricaListaFilter;
use Illuminate\Http\Request;

class WalletRicaricheController extends Controller
{
    public function destroy(Request $request, wallet_ricarica_richiesta $ricarica)
    {
        if ((int) $ricarica->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ($ricarica->stato !== 'in_attesa') {
            return redirect()
                ->route('wallet.ricariche')
                ->withErrors(['ricarica' => 'Solo le ricariche non pagate possono essere annullate.']);
        }

        $ricarica->update(['stato' => 'annullata']);

        return redirect()->route('wallet.ricariche')->with('ok', 'Ricarica annullata.');
    }

    public function index(Request $request)
    {
        $uid = (int) $request->user()->id;
        $filtros = WalletRicaricaListaFilter::fromRequest($request);

        $query = wallet_ricarica_richiesta::query()
            ->where('user_id', $uid)
            ->with(['metodoPagamentoWalletRicarica'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $filtros->applyToQuery($query);

        $ricariche = $query->paginate($filtros->perPage)->withQueryString();

        return view('wallet.ricariche', [
            'ricariche' => $ricariche,
            'perPage' => $filtros->perPage,
            'filtros' => [
                'numero_ordine' => $filtros->numeroOrdine,
                'periodo' => $filtros->periodo,
                'data_de' => $filtros->dataDe,
                'data_a' => $filtros->dataA,
                'importo' => $filtros->importo,
                'stato' => $filtros->stato,
            ],
            'hasActiveFilters' => $filtros->hasActiveFilters(),
            'customPeriodoSemDatas' => $filtros->customPeriodoSemDatas(),
            'queryParams' => FiltriTabella::parametriQuery($request),
        ]);
    }
}
