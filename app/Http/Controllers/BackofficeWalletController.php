<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\wallet_descrizione;
use App\Models\wallet_movimento;
use App\Models\wallet_ricarica_richiesta;
use App\Services\Wallet\WalletExtratoFilters;
use App\Services\Wallet\WalletExtratoListService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BackofficeWalletController extends Controller
{
    public function __construct(
        private readonly WalletExtratoListService $extratoList,
    ) {}
    public function ricariche(Request $request)
    {
        $filtros = \App\Support\WalletRicaricaListaFilter::fromRequest($request, backoffice: true);

        $query = wallet_ricarica_richiesta::query()
            ->with(['user.anagrafica', 'metodoPagamentoWalletRicarica'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $filtros->applyToQuery($query);

        $ricariche = $query->paginate($filtros->perPage)->withQueryString();

        $selectedUser = $filtros->userId > 0
            ? User::query()->find($filtros->userId)
            : null;

        return view('backoffice.ricariche', [
            'ricariche' => $ricariche,
            'perPage' => $filtros->perPage,
            'filtros' => array_merge($filtros->toArray(), [
                'metodo_pagamento_id' => $filtros->metodoPagamentoId > 0 ? (string) $filtros->metodoPagamentoId : '',
            ]),
            'hasActiveFilters' => $filtros->hasActiveFilters(),
            'customPeriodoSemDatas' => $filtros->customPeriodoSemDatas(),
            'selectedUser' => $selectedUser,
            'metodosWallet' => \App\Models\metodo_pagamento_wallet_ricarica::query()
                ->orderBy('metodo_pagamento')
                ->get(['id', 'metodo_pagamento']),
            'queryParams' => \App\Support\FiltriTabella::parametriQuery($request),
        ]);
    }

    public function walletCliente(Request $request): View
    {
        $filters = WalletExtratoFilters::fromRequest($request);

        $userId = $filters->userId;
        $busca = $filters->usuario;

        $selectedUser = null;
        $invalidUserId = false;
        /** @var Collection<int, User> $candidatos */
        $candidatos = collect();
        $buscaSemResultado = false;

        if ($userId > 0) {
            $selectedUser = User::query()->with(['walletSaldo', 'anagrafica'])->find($userId);
            if ($selectedUser === null) {
                $invalidUserId = true;
            }
        } elseif ($busca !== '') {
            $emailBusca = mb_strtolower($busca);
            $selectedUser = User::query()
                ->with(['walletSaldo', 'anagrafica'])
                ->whereRaw('LOWER(email) = ?', [$emailBusca])
                ->first();
            if ($selectedUser === null) {
                $like = '%'.addcslashes($emailBusca, '%_\\').'%';
                $candidatos = User::query()
                    ->with('anagrafica')
                    ->whereRaw('LOWER(email) LIKE ?', [$like])
                    ->orderBy('email')
                    ->limit(30)
                    ->get(['id', 'email']);
                $buscaSemResultado = $candidatos->isEmpty();
            }
        }

        $tiposMovimento = wallet_descrizione::query()
            ->orderBy('tipo')
            ->orderBy('descrizione')
            ->get(['id', 'descrizione']);

        if ($selectedUser !== null) {
            $linhas = $this->extratoList->paginateForUser($selectedUser, $filters, $request);
        } else {
            $linhas = $this->emptyPaginator($filters, $request);
        }

        return view('backoffice.wallet-cliente', [
            'selectedUser' => $selectedUser,
            'candidatos' => $candidatos,
            'busca' => $busca,
            'buscaSemResultado' => $buscaSemResultado,
            'invalidUserId' => $invalidUserId,
            'linhas' => $linhas,
            'tiposMovimento' => $tiposMovimento,
            'hasActiveFilters' => $filters->hasActiveFilters(),
            'customPeriodoSemDatas' => $filters->customPeriodoSemDatas(),
            'perPage' => $filters->perPage,
            'filtros' => [
                'periodo' => $filters->periodo,
                'data_de' => $filters->dataDe,
                'data_ate' => $filters->dataAte,
                'wallet_descrizione_id' => $filters->walletDescrizioneId > 0 ? $filters->walletDescrizioneId : '',
            ],
            'showUsuarioColumn' => true,
            'formAction' => route('backoffice.wallet.cliente'),
        ]);
    }

    private function emptyPaginator(WalletExtratoFilters $filters, Request $request): LengthAwarePaginator
    {
        return new Paginator(
            collect(),
            0,
            $filters->perPage,
            max(1, (int) $request->input('page', 1)),
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );
    }

    public function accreditaRicarica(Request $request, int $id)
    {
        $richiesta = wallet_ricarica_richiesta::query()->findOrFail($id);

        $filtriRedirect = \App\Support\FiltriTabella::parametriQuery($request, ['_token']);

        if ($richiesta->stato !== 'in_attesa') {
            return redirect()
                ->route('backoffice.ricariche.index', $filtriRedirect)
                ->withErrors(['backoffice' => 'Questa ricarica non è in attesa.']);
        }

        $desc = wallet_descrizione::query()->where('codice', 'ricarica')->firstOrFail();
        $importo = (float) $richiesta->importo;

        $mov = DB::transaction(function () use ($richiesta, $desc, $importo) {
            $row = wallet_ricarica_richiesta::query()
                ->whereKey($richiesta->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($row->stato !== 'in_attesa') {
                return null;
            }

            $movimento = wallet_movimento::query()->create([
                'user_id' => $row->user_id,
                'tipo' => 'credito',
                'wallet_descrizione_id' => $desc->id,
                'importo' => $importo,
                'data_movimento' => now(),
                'riferimento' => 'Ricarica (simulazione back-office #'.$row->id.')',
                'ordine_id' => null,
            ]);

            $row->forceFill([
                'stato' => 'accreditata',
                'wallet_movimento_id' => $movimento->id,
            ])->save();

            return $movimento;
        });

        if (! $mov) {
            return redirect()
                ->route('backoffice.ricariche.index', $filtriRedirect)
                ->withErrors(['backoffice' => 'Stato ricarica cambiato: operazione non eseguita.']);
        }

        $importoFmt = \App\Support\ImportoEuro::format($importo);

        return redirect()
            ->route('backoffice.ricariche.index', $filtriRedirect)
            ->with('ok', 'Ricarica '.$richiesta->numero_ordine_wallet.' confermata: '.$importoFmt.' accreditati sul wallet del cliente.');
    }
}
